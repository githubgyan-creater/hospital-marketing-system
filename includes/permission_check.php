<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

/*

| Check Whether Current User Has a Permission

*/

function has_permission(string $permission_name): bool
{
    require_login();

    $user = current_user();

    if (!$user || empty($user['id'])) {
        return false;
    }

    global $pdo;

    $stmt = $pdo->prepare("
        SELECT
            p.id
        FROM users u
        INNER JOIN role_permissions rp
            ON rp.role_id = u.role_id
        INNER JOIN permissions p
            ON p.id = rp.permission_id
        WHERE u.id = ?
          AND u.status = 'active'
          AND p.name = ?
        LIMIT 1
    ");

    $stmt->execute([
        $user['id'],
        $permission_name
    ]);

    return (bool) $stmt->fetch();
}


/*

| Require a Permission

*/

function require_permission(string $permission_name): void
{
    require_login();

    if (!has_permission($permission_name)) {
        http_response_code(403);

        exit(
            'Access denied. You do not have permission to access this page.'
        );
    }
}