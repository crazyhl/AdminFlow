<?php

return [
    'title' => '=== AdminFlow Super Administrator Initialization ===',
    'already_exists' => 'A super administrator [:email] already exists. Use the --force option to create another.',
    'prompt_name' => 'Please enter the administrator name',
    'default_name' => 'Administrator',
    'prompt_email' => 'Please enter the administrator email address',
    'prompt_password' => 'Please enter the password (min 8 characters, letters & numbers required)',
    'prompt_password_confirm' => 'Please confirm the password',
    'err_name_required' => 'Name cannot be empty.',
    'err_email_invalid' => 'Please enter a valid email address.',
    'err_email_exists' => 'This email is already registered. Please use another one.',
    'err_password_too_short' => 'Password must be at least 8 characters long.',
    'err_password_weak' => 'Password is too weak. It must contain both letters and numbers, and not be a common weak password.',
    'err_password_mismatch' => 'The passwords do not match. Please try again.',
    'success' => 'Super administrator [:email] created successfully!',
    'security_hint' => 'Security Notice: Please safeguard your admin password and never use default credentials in production.',
];
