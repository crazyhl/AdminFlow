<?php

namespace Tests\Unit;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoreUserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 测试预设角色枚举属性与权限识别
     */
    public function test_role_enum_permissions_and_labels(): void
    {
        $superAdmin = Role::SUPER_ADMIN;
        $editor = Role::EDITOR;
        $viewer = Role::VIEWER;

        $this->assertSame('超级管理员', $superAdmin->label());
        $this->assertTrue($superAdmin->hasPermission('any.arbitrary.permission'));

        $this->assertSame('内容编辑员', $editor->label());
        $this->assertTrue($editor->hasPermission('cms.posts.publish'));
        $this->assertFalse($editor->hasPermission('core.users.delete'));

        $this->assertSame('只读访客', $viewer->label());
        $this->assertTrue($viewer->hasPermission('core.dashboard.view'));
        $this->assertFalse($viewer->hasPermission('cms.posts.publish'));
    }

    /**
     * 测试核心用户模型的创建、角色转换与超管权限继承
     */
    public function test_user_creation_and_permission_inheritance(): void
    {
        $user = User::create([
            'name' => '张三',
            'email' => 'zhangsan@example.com',
            'password' => 'secret123456',
            'is_admin' => false,
            'is_active' => true,
            'role' => Role::EDITOR,
        ]);

        $this->assertDatabaseHas('adminflow_users', [
            'email' => 'zhangsan@example.com',
            'role' => 'editor',
        ]);

        $this->assertInstanceOf(Role::class, $user->role);
        $this->assertFalse($user->isSuperAdmin());
        $this->assertTrue($user->hasPermission('cms.posts.publish'));
        $this->assertFalse($user->hasPermission('core.users.manage'));

        // 提升为超管
        $user->is_admin = true;
        $this->assertTrue($user->isSuperAdmin());
        $this->assertTrue($user->hasPermission('core.users.manage'));

        // 停用账号后无权限
        $user->is_active = false;
        $this->assertFalse($user->hasPermission('core.users.manage'));
    }
}
