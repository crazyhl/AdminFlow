<?php

namespace AdminFlow\Core\Commands;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Services\AuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminInitCommand extends Command
{
    /**
     * 命令签名：支持 --locale 参数切换中英文，支持 --force 强制新建
     */
    protected $signature = 'adminflow:admin-init 
                            {--locale= : Preferred language (zh_CN or en)} 
                            {--force : Force creation even if super admin exists}';

    /**
     * 命令描述
     */
    protected $description = 'Initialize the super administrator account with bilingual support';

    /**
     * 常见弱密码黑名单（防止弱口令陷阱）
     *
     * @var array<int, string>
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
     * 执行命令
     */
    public function handle(): int
    {
        // 处理中英文切换
        $locale = $this->option('locale');
        if ($locale && in_array($locale, ['zh_CN', 'en'], true)) {
            App::setLocale($locale);
        }

        $this->info($this->t('title'));

        // 检查是否已存在超级管理员
        $existingAdmin = User::query()->where('is_admin', true)->orWhere('role', Role::SUPER_ADMIN->value)->first();

        if ($existingAdmin && !$this->option('force')) {
            $this->warn($this->t('already_exists', ['email' => $existingAdmin->email]));

            return self::FAILURE;
        }

        // 1. 输入姓名
        $name = $this->ask($this->t('prompt_name'), $this->t('default_name'));
        if (trim((string) $name) === '') {
            $this->error($this->t('err_name_required'));

            return self::FAILURE;
        }

        // 2. 输入邮箱并校验
        $email = $this->ask($this->t('prompt_email'));
        $validator = Validator::make(['email' => $email], [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            $this->error($this->t('err_email_invalid'));

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error($this->t('err_email_exists'));

            return self::FAILURE;
        }

        // 3. 输入密码（隐式输入）
        $password = $this->secret($this->t('prompt_password'));

        if (!is_string($password) || $password === '') {
            $this->error($this->t('err_password_too_short'));

            return self::FAILURE;
        }

        // 使用 Laravel 官方 Password 验证规则
        $passwordValidator = Validator::make(['password' => $password], [
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->numbers(),
                Rule::notIn($this->weakPasswords),
            ],
        ]);

        if ($passwordValidator->fails()) {
            if (strlen($password) < 8) {
                $this->error($this->t('err_password_too_short'));

                return self::FAILURE;
            }

            $this->error($this->t('err_password_weak'));

            return self::FAILURE;
        }

        // 4. 确认密码
        $confirmPassword = $this->secret($this->t('prompt_password_confirm'));
        if (!hash_equals($password, (string) $confirmPassword)) {
            $this->error($this->t('err_password_mismatch'));

            return self::FAILURE;
        }

        // 5. 创建超级管理员
        /** @var User $admin */
        $admin = User::query()->create([
            'name' => trim((string) $name),
            'email' => trim((string) $email),
            'password' => Hash::make($password),
            'is_admin' => true,
            'is_active' => true,
            'role' => Role::SUPER_ADMIN,
        ]);

        // 记录操作审计日志
        AuditLogger::record(
            action: 'auth.admin_init',
            target: $admin,
            details: [
                'name' => $admin->name,
                'email' => $admin->email,
                'locale' => $locale ?? config('app.locale'),
            ],
            userId: $admin->id,
        );

        $this->newLine();
        $this->info($this->t('success', ['email' => $admin->email]));
        $this->comment($this->t('security_hint'));

        return self::SUCCESS;
    }

    /**
     * 辅助方法：获取翻译文本
     */
    protected function t(string $key, array $replace = []): string
    {
        return trans("adminflow-core::admin_init.{$key}", $replace);
    }
}
