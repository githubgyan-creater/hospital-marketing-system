<?php

require_once __DIR__ . '/../config/config.php';


/*

 Check if user is logged in

*/

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}


/*

 Store user information in session

*/

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'    => (int) $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role_name']
    ];
}


/*

 Get currently logged-in user

*/

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}


/*

 Protect a page from unauthenticated users

*/

function require_login(): void
{
    if (!is_logged_in()) {

        header(
            'Location: ' .
            BASE_URL .
            '/login.php'
        );

        exit;
    }
}


/*

 Logout

*/

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}