<?php

namespace AdminFlow\Core\Http\Controllers;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * 用户管理列表页（支持关键字搜索与筛选）
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $filters = [
            'search' => $request->query('search'),
            'role' => $request->query('role'),
            'is_active' => $request->query('is_active'),
        ];

        $users = $this->userService->paginate($filters, perPage: 15);
        $roles = Role::cases();

        return view('adminflow-core::users.index', compact('users', 'roles', 'filters'));
    }

    /**
     * 新增用户提交
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('adminflow_users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::enum(Role::class)],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        /** @var User|null $actor */
        $actor = Auth::guard('admin')->user();

        try {
            $user = $this->userService->create($data, $actor);

            return redirect()->route('admin.users.index')
                ->with('success', "成员「{$user->name}」已成功创建");
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'password' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * 更新用户基本信息与角色
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('adminflow_users', 'email')->ignore($user->id),
            ],
            'role' => ['required', 'string', Rule::enum(Role::class)],
        ]);

        /** @var User|null $actor */
        $actor = Auth::guard('admin')->user();

        try {
            $this->userService->update($user, $data, $actor);

            return redirect()->route('admin.users.index')
                ->with('success', "成员「{$user->name}」资料已更新");
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'email' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * 切换成员启用/停用状态
     */
    public function toggleActive(User $user): RedirectResponse
    {
        Gate::authorize('toggleStatus', $user);

        /** @var User|null $actor */
        $actor = Auth::guard('admin')->user();

        try {
            $updated = $this->userService->toggleActive($user, $actor);
            $statusText = $updated->is_active ? '已启用' : '已停用';

            return redirect()->back()
                ->with('success', "成员「{$user->name}」状态{$statusText}");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 重置成员密码
     */
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        /** @var User|null $actor */
        $actor = Auth::guard('admin')->user();

        try {
            $this->userService->resetPassword($user, (string) $request->input('password'), $actor);

            return redirect()->back()
                ->with('success', "成员「{$user->name}」的密码已重置");
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'password' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * 删除成员账号
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        /** @var User|null $actor */
        $actor = Auth::guard('admin')->user();

        try {
            $name = $user->name;
            $this->userService->delete($user, $actor);

            return redirect()->route('admin.users.index')
                ->with('success', "成员「{$name}」账号已删除");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
