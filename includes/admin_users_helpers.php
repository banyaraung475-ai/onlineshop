<?php

function addCustomerFromAdmin($db, $input)
{
    $full_name = sanitizeInput(trim($input['full_name'] ?? ''));
    $username = sanitizeInput(trim($input['username'] ?? ''));
    $email = sanitizeInput(trim($input['email'] ?? ''));
    $password = trim($input['password'] ?? '');
    $role = 'customer';

    if ($username === '' || $email === '' || $password === '') {
        throw new Exception("Username, email, and password are required.");
    }
    if (!validateEmail($email)) {
        throw new Exception("Please enter a valid email address.");
    }
    if (strlen($password) < 8) {
        throw new Exception("Password must be at least 8 characters long.");
    }

    $existingUser = $db->fetchOne(
        "SELECT user_id FROM users WHERE username = ? OR email = ? LIMIT 1",
        [$username, $email]
    );
    if ($existingUser) {
        throw new Exception("Username or email already exists.");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $db->insert('users', [
        'username' => $username,
        'email' => $email,
        'password_hash' => $password_hash,
        'full_name' => $full_name,
        'role' => $role,
        'is_active' => 1,
        'email_verified' => 0
    ]);
}

