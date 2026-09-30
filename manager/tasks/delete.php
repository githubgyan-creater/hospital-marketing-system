 <?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/permission_check.php';

require_role('manager');
require_permission('tasks.delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/manager/tasks/');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if ($id <= 0) {
    header('Location: ' . BASE_URL . '/manager/tasks/');
    exit;
}

/*
|--------------------------------------------------------------------------
| Check whether task exists
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT id
    FROM tasks
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$task = $stmt->fetch();

if (!$task) {
    header('Location: ' . BASE_URL . '/manager/tasks/');
    exit;
}

/*
|--------------------------------------------------------------------------
| Delete Task
|--------------------------------------------------------------------------
*/

try {

    $stmt = $pdo->prepare("
        DELETE FROM tasks
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    header(
        'Location: ' .
        BASE_URL .
        '/manager/tasks/?deleted=1'
    );

    exit;

} catch (PDOException $e) {

    /*
    |--------------------------------------------------------------------------
    | If the task cannot be deleted because of related records
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        '/manager/tasks/?error=delete'
    );

    exit;
}