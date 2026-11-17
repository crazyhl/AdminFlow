<?php

namespace AdminFlow\Core\Http\Controllers;

use AdminFlow\Core\Models\AuditLog;
use AdminFlow\Core\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class AuditLogController extends Controller
{
    /**
     * 审计日志列表页 (支持模块、动作、操作人、IP及时间范围筛选)
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()->with('user');

        // 模块筛选
        $module = trim((string) $request->query('module', ''));
        if ($module !== '') {
            $query->where('action', 'like', "{$module}.%");
        }

        // 具体操作筛选
        $action = trim((string) $request->query('action', ''));
        if ($action !== '') {
            $query->where('action', $action);
        }

        // 操作人筛选
        $userId = $request->query('user_id');
        if (!empty($userId)) {
            $query->where('user_id', (int) $userId);
        }

        // 关键词搜索 (IP, User-Agent)
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q
                    ->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('user_agent', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%");
            });
        }

        // 时间范围筛选
        $dateRange = (string) $request->query('date_range', '');
        match ($dateRange) {
            'today' => $query->whereDate('created_at', Carbon::today()),
            'week' => $query->where('created_at', '>=', Carbon::now()->subDays(7)),
            'month' => $query->where('created_at', '>=', Carbon::now()->subDays(30)),
            default => null,
        };

        $perPage = (int) $request->query('per_page', 15);
        if (!in_array($perPage, [5, 10, 15, 20, 50], true)) {
            $perPage = 15;
        }

        $logs = $query->latest('id')->paginate($perPage)->withQueryString();

        // 概览指标统计（单次聚合查询，避免 4 次全表扫描）
        $todayStart = Carbon::today()->startOfDay()->toDateTimeString();
        $todayEnd = Carbon::today()->endOfDay()->toDateTimeString();
        $rawStats = AuditLog::query()
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN created_at >= ? AND created_at <= ? THEN 1 ELSE 0 END) as today_count', [
                $todayStart,
                $todayEnd,
            ])
            ->selectRaw("SUM(CASE WHEN action LIKE 'auth.%' THEN 1 ELSE 0 END) as auth_count")
            ->selectRaw(
                "SUM(CASE WHEN action IN ('user.reset_password', 'user.delete', 'user.toggle_status', 'auth.login_locked') THEN 1 ELSE 0 END) as sensitive_count",
            )
            ->first();

        $stats = [
            'total' => (int) ($rawStats->total_count ?? 0),
            'today' => (int) ($rawStats->today_count ?? 0),
            'auth' => (int) ($rawStats->auth_count ?? 0),
            'sensitive' => (int) ($rawStats->sensitive_count ?? 0),
        ];

        $modules = AuditLog::moduleDictionary();
        $actions = AuditLog::actionDictionary();
        $users = User::query()->select(['id', 'name', 'email'])->get();
        $filters = $request->only(['module', 'action', 'user_id', 'search', 'date_range']);

        return view('adminflow-core::audit_logs.index', compact(
            'logs',
            'stats',
            'modules',
            'actions',
            'users',
            'filters',
        ));
    }
}
