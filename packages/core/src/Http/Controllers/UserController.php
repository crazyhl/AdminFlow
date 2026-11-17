<?php

namespace AdminFlow\Core\Http\Controllers;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
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
        $stats = $this->userService->getStatistics();

        return view('adminflow-core::users.index', compact('users', 'roles', 'filters', 'stats'));
    }

    /**
     * 新增用户提交
     */
    public function store(Request $request): Response|RedirectResponse|JsonResponse
    {
        Gate::authorize('create', User::class);

        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:50'],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('adminflow_users', 'email')],
                'password' => ['required', 'string', 'min:8'],
                'role' => ['required', 'string', Rule::enum(Role::class)],
                'is_active' => ['nullable', 'boolean'],
            ], [
                'name.required' => '请输入成员姓名',
                'name.max' => '成员姓名长度不得超过 50 个字符',
                'email.required' => '请输入登录邮箱',
                'email.email' => '邮箱格式不符合规范',
                'email.unique' => '该邮箱已被其他成员占用',
                'password.required' => '请输入初始密码',
                'password.min' => '新密码长度至少需要 8 位',
                'role.required' => '请分配预设角色',
            ]);

            $data['is_active'] = $request->boolean('is_active', true);

            /** @var User|null $actor */
            $actor = Auth::guard('admin')->user();

            $user = $this->userService->create($data, $actor);

            if ($request->hasHeader('HX-Request')) {
                session()->flash('success', "成员「{$user->name}」已成功创建");

                return response('')
                    ->header('HX-Refresh', 'true')
                    ->header(
                        'HX-Trigger',
                        (string) json_encode([
                            'showToast' => [
                                'message' => "成员「{$user->name}」已成功创建",
                                'type' => 'success',
                            ],
                        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    );
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "成员「{$user->name}」已成功创建",
                ]);
            }

            return redirect()->route('admin.users.index')->with('success', "成员「{$user->name}」已成功创建");
        } catch (ValidationException $e) {
            if ($request->hasHeader('HX-Request')) {
                $errorBag = new ViewErrorBag()->put('default', $e->validator->errors());

                return response()->view('adminflow-core::users.partials.create-form', [
                    'roles' => Role::cases(),
                    'errors' => $errorBag,
                ]);
            }

            throw $e;
        } catch (InvalidArgumentException $e) {
            if ($request->hasHeader('HX-Request')) {
                $errorBag = new ViewErrorBag()->put('default', new MessageBag(['password' => [$e->getMessage()]]));

                return response()->view('adminflow-core::users.partials.create-form', [
                    'roles' => Role::cases(),
                    'errors' => $errorBag,
                ]);
            }

            throw ValidationException::withMessages([
                'password' => [$e->getMessage()],
            ]);
        }
    }

    /**
     * 更新用户基本信息与角色
     */
    public function update(Request $request, User $user): Response|RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $user);

        try {
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
            ], [
                'name.required' => '请输入成员姓名',
                'name.max' => '成员姓名长度不得超过 50 个字符',
                'email.required' => '请输入登录邮箱',
                'email.email' => '邮箱格式不符合规范',
                'email.unique' => '该邮箱已被其他成员占用',
                'role.required' => '请分配预设角色',
            ]);

            /** @var User|null $actor */
            $actor = Auth::guard('admin')->user();

            $this->userService->update($user, $data, $actor);

            if ($request->hasHeader('HX-Request')) {
                session()->flash('success', "成员「{$user->name}」资料已更新");

                return response('')
                    ->header('HX-Refresh', 'true')
                    ->header(
                        'HX-Trigger',
                        (string) json_encode([
                            'showToast' => [
                                'message' => "成员「{$user->name}」资料已更新",
                                'type' => 'success',
                            ],
                        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    );
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "成员「{$user->name}」资料已更新",
                ]);
            }

            return redirect()->route('admin.users.index')->with('success', "成员「{$user->name}」资料已更新");
        } catch (ValidationException $e) {
            if ($request->hasHeader('HX-Request')) {
                $errorBag = new ViewErrorBag()->put('default', $e->validator->errors());

                return response()->view('adminflow-core::users.partials.edit-form', [
                    'user' => $user,
                    'roles' => Role::cases(),
                    'errors' => $errorBag,
                ]);
            }

            throw $e;
        } catch (InvalidArgumentException $e) {
            if ($request->hasHeader('HX-Request')) {
                $errorBag = new ViewErrorBag()->put('default', new MessageBag(['email' => [$e->getMessage()]]));

                return response()->view('adminflow-core::users.partials.edit-form', [
                    'user' => $user,
                    'roles' => Role::cases(),
                    'errors' => $errorBag,
                ]);
            }

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

            return redirect()->back()->with('success', "成员「{$user->name}」状态{$statusText}");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 重置成员密码
     */
    public function resetPassword(Request $request, User $user): Response|RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $user);

        try {
            $data = $request->validate([
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ], [
                'password.required' => '请输入新密码',
                'password.min' => '新密码长度至少需要 8 位，须包含英文字母与数字',
                'password.confirmed' => '两次输入的新密码不一致，请重新核对',
            ]);

            /** @var User|null $actor */
            $actor = Auth::guard('admin')->user();

            $this->userService->resetPassword($user, (string) $data['password'], $actor);

            if ($request->hasHeader('HX-Request')) {
                session()->flash('success', "成员「{$user->name}」的密码已重置");

                return response('')
                    ->header('HX-Refresh', 'true')
                    ->header(
                        'HX-Trigger',
                        (string) json_encode([
                            'showToast' => [
                                'message' => "成员「{$user->name}」的密码已重置",
                                'type' => 'success',
                            ],
                        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    );
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "成员「{$user->name}」的密码已重置",
                ]);
            }

            return redirect()->back()->with('success', "成员「{$user->name}」的密码已重置");
        } catch (ValidationException $e) {
            if ($request->hasHeader('HX-Request')) {
                $errorBag = new ViewErrorBag()->put('default', $e->validator->errors());

                return response()->view('adminflow-core::users.partials.reset-password-form', [
                    'user' => $user,
                    'errors' => $errorBag,
                ]);
            }

            throw $e;
        } catch (InvalidArgumentException $e) {
            if ($request->hasHeader('HX-Request')) {
                $errorBag = new ViewErrorBag()->put('default', new MessageBag(['password' => [$e->getMessage()]]));

                return response()->view('adminflow-core::users.partials.reset-password-form', [
                    'user' => $user,
                    'errors' => $errorBag,
                ]);
            }

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

            return redirect()->route('admin.users.index')->with('success', "成员「{$name}」账号已删除");
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
