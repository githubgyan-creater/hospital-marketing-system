<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('telecaller');

$user = current_user();

/*
|--------------------------------------------------------------------------
| Overdue Follow-ups
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        l.id,
        l.name,
        l.phone,
        l.service_interest,
        l.status,
        l.priority,
        l.next_action_type,
        l.next_action_at
    FROM leads l
    WHERE l.assigned_to = ?
      AND l.next_action_at IS NOT NULL
      AND l.next_action_at < NOW()
    ORDER BY l.next_action_at ASC
");

$stmt->execute([
    $user['id']
]);

$overdue_followups = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Today's Follow-ups
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        l.id,
        l.name,
        l.phone,
        l.service_interest,
        l.status,
        l.priority,
        l.next_action_type,
        l.next_action_at
    FROM leads l
    WHERE l.assigned_to = ?
      AND l.next_action_at IS NOT NULL
      AND DATE(l.next_action_at) = CURDATE()
    ORDER BY l.next_action_at ASC
");

$stmt->execute([
    $user['id']
]);

$today_followups = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Upcoming Follow-ups
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        l.id,
        l.name,
        l.phone,
        l.service_interest,
        l.status,
        l.priority,
        l.next_action_type,
        l.next_action_at
    FROM leads l
    WHERE l.assigned_to = ?
      AND l.next_action_at IS NOT NULL
      AND l.next_action_at > NOW()
      AND DATE(l.next_action_at) > CURDATE()
    ORDER BY l.next_action_at ASC
");

$stmt->execute([
    $user['id']
]);

$upcoming_followups = $stmt->fetchAll();


$page_title = 'Follow-ups';

require_once __DIR__ . '/../../includes/header.php';

?>


<div class="container py-4">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Follow-ups
            </h2>

            <p class="hm-muted mb-0">
                Manage your overdue, today's and upcoming follow-ups.
            </p>

        </div>

        <a
            href="<?php echo BASE_URL; ?>/telecaller/dashboard.php"
            class="btn btn-outline-secondary"
        >
            My Day
        </a>

    </div>


    <!-- Summary Cards -->

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="hm-card p-4">

                <div class="hm-muted">
                    Overdue
                </div>

                <h2 class="mb-0 text-danger">
                    <?php echo count($overdue_followups); ?>
                </h2>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4">

                <div class="hm-muted">
                    Today
                </div>

                <h2 class="mb-0 hm-gold">
                    <?php echo count($today_followups); ?>
                </h2>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4">

                <div class="hm-muted">
                    Upcoming
                </div>

                <h2 class="mb-0 text-success">
                    <?php echo count($upcoming_followups); ?>
                </h2>

            </div>

        </div>

    </div>


    <!-- Overdue -->

    <div class="hm-card p-4 mb-4">

        <h5 class="mb-3 text-danger">
            Overdue Follow-ups
        </h5>

        <?php if (empty($overdue_followups)): ?>

            <div class="alert alert-success mb-0">
                No overdue follow-ups.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Service</th>
                            <th>Next Action</th>
                            <th>Due</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($overdue_followups as $lead): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $lead['name']
                                    );
                                    ?>
                                </strong>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead['phone']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead['service_interest']
                                    ?: '-'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead['next_action_type']
                                    ?: '-'
                                );
                                ?>
                            </td>

                            <td class="text-danger">
                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $lead['next_action_at']
                                    )
                                );
                                ?>
                            </td>

                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/telecaller/calls/add.php?lead_id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-hm-primary"
                                >
                                    Record Call
                                </a>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-outline-secondary"
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


    <!-- Today's Follow-ups -->

    <div class="hm-card p-4 mb-4">

        <h5 class="mb-3">
            Today's Follow-ups
        </h5>

        <?php if (empty($today_followups)): ?>

            <div class="alert alert-light mb-0">
                No follow-ups scheduled for today.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Next Action</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($today_followups as $lead): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $lead['name']
                                    );
                                    ?>
                                </strong>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead['phone']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead['next_action_type']
                                    ?: '-'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo date(
                                    'h:i A',
                                    strtotime(
                                        $lead['next_action_at']
                                    )
                                );
                                ?>
                            </td>

                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/telecaller/calls/add.php?lead_id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-hm-primary"
                                >
                                    Record Call
                                </a>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-outline-secondary"
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


    <!-- Upcoming -->

    <div class="hm-card p-4">

        <h5 class="mb-3">
            Upcoming Follow-ups
        </h5>

        <?php if (empty($upcoming_followups)): ?>

            <div class="alert alert-light mb-0">
                No upcoming follow-ups.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>
                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Next Action</th>
                            <th>Date & Time</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php foreach ($upcoming_followups as $lead): ?>

                        <tr>

                            <td>
                                <strong>
                                    <?php
                                    echo htmlspecialchars(
                                        $lead['name']
                                    );
                                    ?>
                                </strong>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead['phone']
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo htmlspecialchars(
                                    $lead['next_action_type']
                                    ?: '-'
                                );
                                ?>
                            </td>

                            <td>
                                <?php
                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $lead['next_action_at']
                                    )
                                );
                                ?>
                            </td>

                            <td>

                                <a
                                    href="<?php echo BASE_URL; ?>/telecaller/calls/add.php?lead_id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-hm-primary"
                                >
                                    Record Call
                                </a>

                                <a
                                    href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $lead['id']; ?>"
                                    class="btn btn-sm btn-outline-secondary"
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

</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>