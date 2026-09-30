 <?php

require_once __DIR__ . '/../config/config.php';

/*

| Normalize Role Name

|
| Database role names:
| Administrator
| Marketing Manager
| Telecaller
| Marketing Executive
|
| Application uses:
| admin
| manager
| telecaller
| marketing

*/

function normalize_role_name(string $role_name): string
{
    $role_name = strtolower(trim($role_name));

    switch ($role_name) {

        case 'administrator':
        case 'admin':
            return 'admin';

        case 'marketing manager':
        case 'manager':
            return 'manager';

        case 'telecaller':
            return 'telecaller';

        case 'marketing executive':
        case 'marketing':
            return 'marketing';

        default:
            return $role_name;
    }
}

/*

| Check Login

*/

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

/*

| Login User

*/

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'    => (int) $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => normalize_role_name($user['role_name'])
    ];
}

/*

| Current User

*/

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/*

| Require Login

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

| Logout User

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