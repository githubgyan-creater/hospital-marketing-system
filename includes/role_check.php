 <?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

/*
|--------------------------------------------------------------------------
| Require Specific Role
|--------------------------------------------------------------------------
*/

function require_role(string ...$allowed_roles): void
{
    require_login();

    $user = current_user();

    if (!$user || empty($user['id'])) {

        http_response_code(403);

        exit('Access denied.');
    }

    global $pdo;

    /*
    |--------------------------------------------------------------------------
    | Get Current User Role
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            r.name AS role_name
        FROM users u
        INNER JOIN roles r
            ON u.role_id = r.id
        WHERE u.id = ?
          AND u.status = 'active'
        LIMIT 1
    ");

    $stmt->execute([
        $user['id']
    ]);

    $role = $stmt->fetch();

    if (!$role) {

        http_response_code(403);

        exit('Access denied.');
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Database Role
    |--------------------------------------------------------------------------
    */

    $current_role = normalize_role_name(
        $role['role_name']
    );

    /*
    |--------------------------------------------------------------------------
    | Normalize Allowed Roles
    |--------------------------------------------------------------------------
    */

    $normalized_allowed_roles = [];

    foreach ($allowed_roles as $allowed_role) {

        $normalized_allowed_roles[] =
            normalize_role_name($allowed_role);
    }

    /*
    |--------------------------------------------------------------------------
    | Check Permission
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $current_role,
            $normalized_allowed_roles,
            true
        )
    ) {

        http_response_code(403);

        exit('Access denied.');
    }

    /*
    |--------------------------------------------------------------------------
    | Keep Session Role Consistent
    |--------------------------------------------------------------------------
    */

    $_SESSION['user']['role'] = $current_role;
}