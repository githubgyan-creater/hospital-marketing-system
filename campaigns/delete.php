<?php

require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', 'manager');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: ' .
        BASE_URL .
        '/campaigns/index.php'
    );

    exit;
}

$campaign_id = isset($_POST['id'])
    ? (int) $_POST['id']
    : 0;

if ($campaign_id <= 0) {

    header(
        'Location: ' .
        BASE_URL .
        '/campaigns/index.php?error=invalid'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Check Campaign Exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM campaigns
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $campaign_id
]);

$campaign = $stmt->fetch();

if (!$campaign) {

    header(
        'Location: ' .
        BASE_URL .
        '/campaigns/index.php?error=notfound'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Delete Campaign
|--------------------------------------------------------------------------
*/

$delete = $pdo->prepare("
    DELETE FROM campaigns
    WHERE id = ?
");

$delete->execute([
    $campaign_id
]);


/*
|--------------------------------------------------------------------------
| Redirect
|--------------------------------------------------------------------------
*/

header(
    'Location: ' .
    BASE_URL .
    '/campaigns/index.php?success=deleted'
);

exit;