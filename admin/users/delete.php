 ```php
<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/permission_check.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/lead_workflow.php';

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
| Check User Exists + Get Role/Status
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.name,
        u.status,
        r.name AS role_name
    FROM users u
    LEFT JOIN roles r
        ON r.id = u.role_id
    WHERE u.id = ?
    LIMIT 1
");

$stmt->execute([
    $user_id
]);

$user_to_delete = $stmt->fetch();

if (!$user_to_delete) {

    exit('Staff account not found.');

}


/*
|--------------------------------------------------------------------------
| Identify Telecaller
|--------------------------------------------------------------------------
*/

$is_active_telecaller =
    strtolower((string) $user_to_delete['role_name']) === 'telecaller'
    && strtolower((string) $user_to_delete['status']) === 'active';


/*
|--------------------------------------------------------------------------
| Current Admin
|--------------------------------------------------------------------------
*/

$current_admin_id = $current_user
    ? (int) $current_user['id']
    : null;


/*
|--------------------------------------------------------------------------
| Delete User
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | Start Transaction
    |--------------------------------------------------------------------------
    |
    | The user deletion and lead redistribution must succeed together.
    |
    */

    $pdo->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | Delete Staff Account
    |--------------------------------------------------------------------------
    */

    $delete = $pdo->prepare("
        DELETE FROM users
        WHERE id = ?
    ");

    $delete->execute([
        $user_id
    ]);


    /*
    |--------------------------------------------------------------------------
    | Automatic Lead Redistribution
    |--------------------------------------------------------------------------
    |
    | If the deleted account was an ACTIVE Telecaller, redistribute
    | all active leads among the remaining active Telecallers.
    |
    | Converted and Lost leads are automatically excluded by the
    | existing rebalance_all_active_leads() function.
    |
    */

    if ($is_active_telecaller) {

        $rebalance_result =
            rebalance_all_active_leads(
                $pdo,
                $current_admin_id
            );


        /*
        |--------------------------------------------------------------------------
        | Verify Redistribution
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $rebalance_result['success']
            )
        ) {

            throw new RuntimeException(
                'Unable to redistribute active leads after deleting the Telecaller.'
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Commit
    |--------------------------------------------------------------------------
    */

    $pdo->commit();


    /*
    |--------------------------------------------------------------------------
    | Success Redirect
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/admin/users/index.php'
    );

    exit;


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Rollback
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    /*
    |--------------------------------------------------------------------------
    | Foreign Key / Database Error
    |--------------------------------------------------------------------------
    */

    if ($e instanceof PDOException) {

        http_response_code(409);

        exit(
            'This staff account cannot be deleted because it is already '
            . 'linked to existing records. Set the account to Inactive instead.'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Other Error
    |--------------------------------------------------------------------------
    */

    http_response_code(500);

    exit(
        'Unable to delete staff account and redistribute leads. '
        . htmlspecialchars(
            $e->getMessage(),
            ENT_QUOTES,
            'UTF-8'
        )
    );

}
 
