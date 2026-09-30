<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permission_check.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('admin');
require_permission('users.delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method.');
}

$user_id = filter_input(
    INPUT_POST,
    'id',
    FILTER_VALIDATE_INT
);

if (!$user_id) {
    exit('Invalid user ID.');
}

$current_user = current_user();

if (
    $current_user &&
    (int) $current_user['id'] === (int) $user_id
) {
    exit('You cannot delete your own account.');
}


/*
|--------------------------------------------------------------------------
| Check User Exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

if (!$stmt->fetch()) {
    exit('Staff account not found.');
}


/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

try {

    $delete = $pdo->prepare("
        DELETE FROM users
        WHERE id = ?
    ");

    $delete->execute([
        $user_id
    ]);

    header(
        'Location: ' .
        BASE_URL .
        '/admin/users/index.php'
    );

    exit;

} catch (PDOException $e) {

    http_response_code(409);

    exit(
        'This staff account cannot be deleted because it is already '
        . 'linked to existing records. Set the account to Inactive instead.'
    );
}