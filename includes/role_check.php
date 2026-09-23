<?php

require_once __DIR__ . '/auth.php';


/*

 Allow only selected roles

*/

function require_role(string ...$allowed_roles): void
{
    require_login();

    $user = current_user();

    $current_role = $user['role'] ?? '';

    if (!in_array($current_role, $allowed_roles, true)) {

        http_response_code(403);

        exit('Access denied.');
    }
}