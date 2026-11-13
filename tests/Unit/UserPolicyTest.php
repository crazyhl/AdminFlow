<?php

namespace Tests\Unit;

use AdminFlow\Core\Enums\Role;
use AdminFlow\Core\Models\User;
use AdminFlow\Core\Policies\UserPolicy;
use PHPUnit\Framework\TestCase;

class UserPolicyTest extends TestCase
{
    private UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
    }

    /**
     * 测试超级管理员与普通用户的权限边界及自毁防护
     */
    public function test_user_policy_guards(): void
    {
        $superAdmin = new User([
            'name' => 'Root Admin',
            'email' => 'root@example.com',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);
        $superAdmin->id = 1;

        $anotherAdmin = new User([
            'name' => 'Second Admin',
            'email' => 'second@example.com',
            'role' => Role::SUPER_ADMIN,
            'is_admin' => true,
            'is_active' => true,
        ]);
        $anotherAdmin->id = 2;

        $editor = new User([
            'name' => 'Editor User',
            'email' => 'editor@example.com',
            'role' => Role::EDITOR,
            'is_admin' => false,
            'is_active' => true,
        ]);
        $editor->id = 3;

        // 1. 防御：超管禁止停用/删除自己
        $this->assertFalse($this->policy->toggleStatus($superAdmin, $superAdmin));
        $this->assertFalse($this->policy->delete($superAdmin, $superAdmin));

        // 2. 防御：超管禁止停用/删除其他超管
        $this->assertFalse($this->policy->toggleStatus($superAdmin, $anotherAdmin));
        $this->assertFalse($this->policy->delete($superAdmin, $anotherAdmin));

        // 3. 超管允许管理普通编辑员
        $this->assertTrue($this->policy->toggleStatus($superAdmin, $editor));
        $this->assertTrue($this->policy->delete($superAdmin, $editor));

        // 4. 普通编辑员禁止创建、停用、删除用户
        $this->assertFalse($this->policy->create($editor));
        $this->assertFalse($this->policy->toggleStatus($editor, $superAdmin));
        $this->assertFalse($this->policy->toggleStatus($editor, $editor));
        $this->assertFalse($this->policy->delete($editor, $superAdmin));
    }
}
