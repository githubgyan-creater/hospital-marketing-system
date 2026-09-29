 <?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('marketing');

$user = current_user();

$user_id = (int) $user['id'];

$page_title = 'Marketing Dashboard';


/*
|--------------------------------------------------------------------------
| Today's Tasks
|--------------------------------------------------------------------------
*/

$task_stmt = $pdo->prepare("
    SELECT
        t.id,
        t.title,
        t.task_type,
        t.due_date,
        t.priority,
        t.status
    FROM tasks t
    WHERE t.assigned_to = ?
      AND t.status IN (
          'Pending',
          'In Progress'
      )
      AND DATE(t.due_date) <= ?
    ORDER BY t.due_date ASC
    LIMIT 10
");

$task_stmt->execute([
    $user_id,
    date('Y-m-d')
]);

$my_tasks = $task_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Assigned Events
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        ea.id AS assignment_id,
        e.id AS event_id,
        e.event_name,
        e.event_type,
        e.event_date,
        e.location,
        e.description,
        e.status
    FROM event_assignments ea
    INNER JOIN events e
        ON ea.event_id = e.id
    WHERE ea.user_id = ?
      AND e.status <> 'Cancelled'
    ORDER BY
        e.event_date ASC,
        e.id ASC
    LIMIT 10
");

$stmt->execute([
    $user_id
]);

$assigned_events = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Common Header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">


    <!-- ========================================================= -->
    <!-- HEADER -->
    <!-- ========================================================= -->

    <div class="mb-4">

        <!-- <span class="badge bg-light text-dark">
            MARKETING EXECUTIVE
        </span> -->

        <h1 class="hm-page-title mt-2 mb-1">
        Marketing Executive Dashboard
        </h1>

        <p class="hm-muted mb-0">

            Welcome,

            <?php
            echo htmlspecialchars(
                $user['name']
            );
            ?>.

        </p>

    </div>


    <!-- ========================================================= -->
    <!-- TASK QUICK ACCESS -->
    <!-- ========================================================= -->

    <div class="row g-4 mb-4">

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <h5 class="fw-bold">
                    Tasks
                </h5>

                <p class="hm-muted">
                    View and complete your assigned marketing tasks.
                </p>

                <a
                    href="<?php echo BASE_URL; ?>/marketing/tasks/index.php"
                    class="btn btn-outline-primary"
                >
                    View My Tasks
                </a>

            </div>

        </div>

    </div>


    <!-- ========================================================= -->
    <!-- ASSIGNED EVENTS -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div
            class="d-flex flex-wrap justify-content-between align-items-center mb-3"
        >

            <div>

                <h5 class="mb-1">
                    Assigned Events
                </h5>

                <span class="hm-muted">
                    Events assigned to you by the manager.
                </span>

            </div>

            <span class="badge bg-primary">

                <?php
                echo count(
                    $assigned_events
                );
                ?>

            </span>

        </div>


        <?php if (empty($assigned_events)): ?>

            <div class="alert alert-light border mb-0">

                No events are currently assigned to you.

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead>

                        <tr>

                            <th>
                                Event
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Date
                            </th>

                            <th>
                                Location
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach (
                        $assigned_events
                        as $event
                    ): ?>

                        <tr>

                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $event['event_name']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $event['event_type']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    'd M Y',
                                    strtotime(
                                        $event['event_date']
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $event['location']
                                    ?: '-'
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                $event_status =
                                    $event['status'];

                                $event_badge =
                                    'bg-primary';

                                if (
                                    $event_status === 'Ongoing'
                                ) {

                                    $event_badge =
                                        'bg-warning text-dark';

                                } elseif (
                                    $event_status === 'Completed'
                                ) {

                                    $event_badge =
                                        'bg-success';

                                }

                                ?>

                                <span
                                    class="badge <?php echo $event_badge; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $event_status
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/events/view.php?id=<?php echo (int) $event['event_id']; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    View
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>


    <!-- ========================================================= -->
    <!-- TODAY'S TASKS -->
    <!-- ========================================================= -->

    <div class="hm-card p-4 mb-4">

        <div
            class="d-flex flex-wrap justify-content-between align-items-center mb-3"
        >

            <div>

                <h5 class="mb-1">
                    Today's Tasks
                </h5>

                <span class="hm-muted">
                    Pending and in-progress tasks requiring your attention.
                </span>

            </div>


            <a
                href="<?php echo BASE_URL; ?>/marketing/tasks/index.php"
                class="btn btn-outline-primary btn-sm"
            >
                View All Tasks
            </a>

        </div>


        <?php if (empty($my_tasks)): ?>

            <div class="alert alert-light border mb-0">

                No pending tasks for today.

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle mb-0">

                    <thead>

                        <tr>

                            <th>
                                Task
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Due
                            </th>

                            <th>
                                Priority
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php foreach (
                        $my_tasks
                        as $task
                    ): ?>

                        <tr>


                            <td>

                                <strong>

                                    <?php
                                    echo htmlspecialchars(
                                        $task['title']
                                    );
                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo htmlspecialchars(
                                    $task['task_type']
                                );
                                ?>

                            </td>


                            <td>

                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $task['due_date']
                                    )
                                );
                                ?>

                            </td>


                            <td>

                                <?php

                                if (
                                    $task['priority']
                                    === 'High'
                                ) {

                                    $priority_class =
                                        'bg-danger';

                                } elseif (
                                    $task['priority']
                                    === 'Medium'
                                ) {

                                    $priority_class =
                                        'bg-warning text-dark';

                                } else {

                                    $priority_class =
                                        'bg-secondary';
                                }

                                ?>

                                <span
                                    class="badge <?php echo $priority_class; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $task['priority']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <?php

                                if (
                                    $task['status']
                                    === 'In Progress'
                                ) {

                                    $status_class =
                                        'bg-primary';

                                } else {

                                    $status_class =
                                        'bg-warning text-dark';
                                }

                                ?>

                                <span
                                    class="badge <?php echo $status_class; ?>"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $task['status']
                                    );
                                    ?>

                                </span>

                            </td>


                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/marketing/tasks/view.php?id=<?php echo (int) $task['id']; ?>"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    Open
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>


</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>