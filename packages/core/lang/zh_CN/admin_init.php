<?php

return [
    'title' => '=== AdminFlow 超级管理员账号初始化 ===',
    'already_exists' => '系统中已存在超级管理员账号 [:email]。如需继续创建，请加上 --force 参数。',
    'prompt_name' => '请输入管理员姓名/昵称',
    'default_name' => '超级管理员',
    'prompt_email' => '请输入管理员邮箱地址',
    'prompt_password' => '请输入管理员密码（至少 8 位，包含字母与数字）',
    'prompt_password_confirm' => '请再次输入密码以确认',
    'err_name_required' => '姓名不能为空。',
    'err_email_invalid' => '请输入合法的邮箱地址。',
    'err_email_exists' => '该邮箱已被注册，请更换其他邮箱。',
    'err_password_too_short' => '密码长度至少为 8 位。',
    'err_password_weak' => '密码过于简单，必须同时包含字母和数字，且不能为常见弱密码。',
    'err_password_mismatch' => '两次输入的密码不一致，请重新操作。',
    'success' => '超级管理员账号 [:email] 创建成功！',
    'security_hint' => '安全提示：请妥善保管管理员密码，生产环境请勿使用默认密码。',
];
