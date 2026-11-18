<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * 在测试环境中调用 actingAs 时，同步写入 password_hash_admin 会话标记
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $this->withSession([
            'password_hash_admin' => $user->getAuthPassword(),
        ]);

        return parent::actingAs($user, $guard);
    }
}
