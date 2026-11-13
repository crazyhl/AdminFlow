<?php

namespace AdminFlow\Core\Services;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class UserService
{
    /**
     * 常见弱密码黑名单
     *
     * @var list<string>
     */
    protected array $weakPasswords = [
        '12345678',
        'admin123',
        'password',
        'password123',
        '123456789',
        'admin888',
        'root1234',
    ];

    /**
     * 分页查询用户列表
     *
     * @param array{
     *     search?: string|null,
     *     role?: string|null,
     *     is_active?: bool|int|string|null
     * } $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query();

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(static function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%");
            });
        }

        $role = (string) ($filters['role'] ?? '');
        if ($role !== '') {
            $query->where('role', $role);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * 创建后台新用户
     *
     * @param array{
     *     name: string,
     *     email: string,
     *     password: string,
     *     role?: Role|string|null,
     *     is_active?: bool
     * } $data
     */
    public function create(array $data, ?User $actor = null): User
    {
        $email = trim($data['email'] ?? '');
        if (User::query()->where('email', $email)->exists()) {
            throw new InvalidArgumentException("邮箱 [{$email}] 已被占用");
        }

        $password = $data['password'] ?? '';
        $this->assertPasswordStrength($password);

        $role = $data['role'] ?? Role::VIEWER;
        if (is_string($role)) {
            $role = Role::from($role);
        }

        $isAdmin = $role === Role::SUPER_ADMIN;
        $isActive = $data['is_active'] ?? true;

        /** @var User $user */
        $user = User::query()->create([
            'name' => trim($data['name'] ?? ''),
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'is_admin' => $isAdmin,
            'is_active' => (bool) $isActive,
        ]);

        AuditLogger::record(
            action: 'user.create',
            target: $user,
            details: [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'is_active' => $user->is_active,
            ],
            userId: $actor?->id,
        );

        return $user;
    }

    /**
     * 更新用户基本信息与角色
     *
     * @param array{
     *     name?: string,
     *     email?: string,
     *     role?: Role|string
     * } $data
     */
    public function update(User $target, array $data, ?User $actor = null): User
    {
        $oldState = [
            'name' => $target->name,
            'email' => $target->email,
            'role' => $target->role->value,
        ];

        if (isset($data['email']) && $data['email'] !== $target->email) {
            $newEmail = trim($data['email']);
            if (User::query()->where('email', $newEmail)->where('id', '!=', $target->id)->exists()) {
                throw new InvalidArgumentException("邮箱 [{$newEmail}] 已被占用");
            }
            $target->email = $newEmail;
        }

        if (isset($data['name'])) {
            $target->name = trim($data['name']);
        }

        if (isset($data['role'])) {
            $newRole = is_string($data['role']) ? Role::from($data['role']) : $data['role'];
            $target->role = $newRole;
            $target->is_admin = $newRole === Role::SUPER_ADMIN;
        }

        $target->save();

        AuditLogger::record(
            action: 'user.update',
            target: $target,
            details: [
                'before' => $oldState,
                'after' => [
                    'name' => $target->name,
                    'email' => $target->email,
                    'role' => $target->role->value,
                ],
            ],
            userId: $actor?->id,
        );

        return $target;
    }

    /**
     * 切换账号启用/停用状态
     */
    public function toggleActive(User $target, ?User $actor = null): User
    {
        if ($actor && $actor->id === $target->id) {
            throw new InvalidArgumentException('禁止停用当前登录账号');
        }

        if ($target->isSuperAdmin()) {
            throw new InvalidArgumentException('禁止停用超级管理员账号');
        }

        $originalStatus = $target->is_active;
        $target->is_active = !$originalStatus;
        $target->save();

        AuditLogger::record(
            action: 'user.toggle_status',
            target: $target,
            details: [
                'from' => $originalStatus,
                'to' => $target->is_active,
            ],
            userId: $actor?->id,
        );

        return $target;
    }

    /**
     * 重置密码
     */
    public function resetPassword(User $target, #[\SensitiveParameter] string $newPassword, ?User $actor = null): void
    {
        $this->assertPasswordStrength($newPassword);

        $target->password = Hash::make($newPassword);
        $target->save();

        AuditLogger::record(
            action: 'user.reset_password',
            target: $target,
            details: [
                'target_email' => $target->email,
            ],
            userId: $actor?->id,
        );
    }

    /**
     * 校验密码强度（采用 Laravel 官方 Password 校验器）
     */
    protected function assertPasswordStrength(#[\SensitiveParameter] string $password): void
    {
        $validator = Validator::make(['password' => $password], [
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->numbers(),
                Rule::notIn($this->weakPasswords),
            ],
        ]);

        if ($validator->fails()) {
            if (strlen($password) < 8) {
                throw new InvalidArgumentException('密码长度至少为 8 位');
            }

            if (in_array(strtolower($password), $this->weakPasswords, true)) {
                throw new InvalidArgumentException('此密码为常见弱密码，请选用更安全的组合');
            }

            throw new InvalidArgumentException('密码必须同时包含英文字母与数字');
        }
    }
}
