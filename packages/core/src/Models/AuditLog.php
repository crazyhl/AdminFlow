<?php

namespace AdminFlow\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 操作审计日志模型
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $target_type
 * @property string|null $target_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $details
 * @property Carbon|null $created_at
 * @property-read User|null $user
 * @property-read string $module_key
 * @property-read string $module_label
 * @property-read string $action_label
 * @property-read string $action_description
 * @property-read string $badge_class
 */
class AuditLog extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'adminflow_audit_logs';

    /**
     * 审计日志为不可变记录，禁用 updated_at
     */
    public const UPDATED_AT = null;

    /**
     * 可批量赋值属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'target_type',
        'target_id',
        'ip_address',
        'user_agent',
        'details',
        'created_at',
    ];

    /**
     * 属性类型转换
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * 模块中文字典映射
     *
     * @return array<string, string>
     */
    public static function moduleDictionary(): array
    {
        return [
            'auth' => '身份认证',
            'user' => '成员管理',
            'admin' => '系统底座',
            'cms' => '创作工坊',
        ];
    }

    /**
     * 操作动作中文字典映射
     *
     * @return array<string, array{module: string, label: string, desc: string}>
     */
    public static function actionDictionary(): array
    {
        return [
            // 系统底座
            'admin.init' => [
                'module' => '系统底座',
                'label' => '系统初始化',
                'desc' => '执行管理员初始建号与安全校验',
            ],

            // 身份认证
            'auth.login_success' => [
                'module' => '身份认证',
                'label' => '登录成功',
                'desc' => '管理员成功通过身份验证并建立会话',
            ],
            'auth.login_failed' => [
                'module' => '身份认证',
                'label' => '登录失败',
                'desc' => '凭证校验未通过拒绝访问',
            ],
            'auth.login_locked' => [
                'module' => '身份认证',
                'label' => '频繁重试锁定',
                'desc' => '触发防爆破限流，锁定账号登录',
            ],
            'auth.logout' => [
                'module' => '身份认证',
                'label' => '注销登录',
                'desc' => '管理员主动退出登录，会话已销毁',
            ],

            // 成员管理
            'user.create' => [
                'module' => '成员管理',
                'label' => '创建新成员',
                'desc' => '新建后台协同账号并分配角色',
            ],
            'user.update' => [
                'module' => '成员管理',
                'label' => '编辑资料角色',
                'desc' => '修改成员姓名、邮箱或变更分配角色',
            ],
            'user.toggle_status' => [
                'module' => '成员管理',
                'label' => '变更账号状态',
                'desc' => '切换成员停用或重新启用访问权限',
            ],
            'user.reset_password' => [
                'module' => '成员管理',
                'label' => '重置登录密码',
                'desc' => '重置成员密码，强制口令脱敏',
            ],
            'user.delete' => [
                'module' => '成员管理',
                'label' => '删除成员账号',
                'desc' => '永久清除成员账号与访问权限',
            ],

            // 创作工坊 (CMS 业务)
            'cms.post.create' => [
                'module' => '创作工坊',
                'label' => '创建文章草稿',
                'desc' => '新增 Markdown 格式文章',
            ],
            'cms.post.update' => [
                'module' => '创作工坊',
                'label' => '更新文章正文',
                'desc' => '修改文章正文与元数据配置',
            ],
            'cms.post.publish' => [
                'module' => '创作工坊',
                'label' => '公开发布文章',
                'desc' => '将文章发布至前台供访客阅读',
            ],
            'cms.post.unpublish' => [
                'module' => '创作工坊',
                'label' => '下架撤回草稿',
                'desc' => '撤回已发布文章至草稿箱',
            ],
            'cms.post.delete' => [
                'module' => '创作工坊',
                'label' => '删除文章内容',
                'desc' => '永久移除文章及其关联评论',
            ],
            'cms.media.upload' => [
                'module' => '创作工坊',
                'label' => '上传图床媒体',
                'desc' => '上传并持久化图片至内置图床',
            ],
            'cms.media.delete' => [
                'module' => '创作工坊',
                'label' => '删除图床媒体',
                'desc' => '移除图床中的静态图片资源',
            ],
            'cms.comment.moderate' => [
                'module' => '创作工坊',
                'label' => '审核访客评论',
                'desc' => '批准通过、标记垃圾或删除评论',
            ],
            'cms.import.batch' => [
                'module' => '创作工坊',
                'label' => 'Hugo博客迁移',
                'desc' => '执行 Hugo 外部内容批量解析导入',
            ],
        ];
    }

    /**
     * 获取所属模块 Key (如 auth, user, admin, cms)
     */
    public function getModuleKeyAttribute(): string
    {
        $parts = explode('.', $this->action);

        return $parts[0] ?? 'system';
    }

    /**
     * 获取模块中文显示名称
     */
    public function getModuleLabelAttribute(): string
    {
        $dict = self::actionDictionary();
        if (isset($dict[$this->action]['module'])) {
            return $dict[$this->action]['module'];
        }

        $moduleDict = self::moduleDictionary();
        $key = $this->module_key;

        return $moduleDict[$key] ?? '综合底座';
    }

    /**
     * 获取操作中文显示名称
     */
    public function getActionLabelAttribute(): string
    {
        $dict = self::actionDictionary();
        if (isset($dict[$this->action]['label'])) {
            return $dict[$this->action]['label'];
        }

        $parts = explode('.', $this->action);
        array_shift($parts);

        $fallback = implode(' ', $parts);

        return $fallback !== '' ? $fallback : $this->action;
    }

    /**
     * 获取操作说明描述
     */
    public function getActionDescriptionAttribute(): string
    {
        $dict = self::actionDictionary();

        return $dict[$this->action]['desc'] ?? '系统常规业务操作记录';
    }

    /**
     * 获取模块徽章样式类名 (符合 Nordic Minimal 规范)
     */
    public function getBadgeClassAttribute(): string
    {
        return match ($this->module_key) {
            'auth'
                => 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60',
            'user'
                => 'bg-amber-50 dark:bg-amber-950/50 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60',
            'cms'
                => 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/60',
            'admin' => 'bg-stone-900 text-stone-100 dark:bg-stone-100 dark:text-stone-900',
            default => 'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300',
        };
    }

    /**
     * 关联的操作人
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
