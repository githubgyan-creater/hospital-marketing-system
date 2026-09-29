<?php

require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('telecaller');

$user = current_user();
$user_id = (int) $user['id'];

/*
|--------------------------------------------------------------------------
| Total Assigned Leads
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM leads
    WHERE assigned_to = ?
");
$stmt->execute([$user_id]);
$total_leads = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Total Calls
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM lead_calls
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$total_calls = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Today's Calls
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM lead_calls
    WHERE user_id = ?
      AND DATE(call_at) = CURDATE()
");
$stmt->execute([$user_id]);
$today_calls = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Connected Calls
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM lead_calls
    WHERE user_id = ?
      AND call_outcome = 'Connected'
");
$stmt->execute([$user_id]);
$connected_calls = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Appointment Requests
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM lead_calls
    WHERE user_id = ?
      AND call_outcome = 'Appointment Requested'
");
$stmt->execute([$user_id]);
$appointment_requests = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Follow-ups Due
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM leads
    WHERE assigned_to = ?
      AND next_action_at IS NOT NULL
      AND next_action_at <= NOW()
");
$stmt->execute([$user_id]);
$followups_due = (int) $stmt->fetchColumn();

/*
|--------------------------------------------------------------------------
| Recent Calls
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        lc.call_at,
        lc.call_outcome,
        lc.Call_notes,
        l.name AS lead_name,
        l.phone
    FROM lead_calls lc
    INNER JOIN leads l
        ON l.id = lc.lead_id
    WHERE lc.user_id = ?
    ORDER BY lc.call_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);

$recent_calls = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Connection Rate
|--------------------------------------------------------------------------
*/
$connection_rate = 0;

if ($total_calls > 0) {
    $connection_rate = round(
        ($connected_calls / $total_calls) * 100,
        1
    );
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>My Performance</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>

        body {
            background: #f7f5ef;
            color: #243746;
        }

        .navbar-custom {
            background: #17324d;
        }

        .navbar-brand,
        .nav-link {
            color: #ffffff !important;
        }

        .page-title {
            color: #17324d;
            font-weight: 700;
        }

        .stat-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(23, 50, 77, 0.08);
            height: 100%;
        }

        .stat-label {
            color: #71808c;
            font-size: 14px;
        }

        .stat-value {
            color: #17324d;
            font-size: 30px;
            font-weight: 700;
        }

        .section-card {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(23, 50, 77, 0.08);
        }

        .table thead th {
            background: #17324d;
            color: #ffffff;
            white-space: nowrap;
        }

        .badge-connected {
            background: #168a87;
        }

        .badge-appointment {
            background: #b89a5a;
        }

        .progress {
            height: 10px;
        }

        .footer-note {
            color: #71808c;
            font-size: 13px;
        }

    </style>

</head>

<body>

<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container-fluid">

        <a
            class="navbar-brand fw-bold"
            href="../../telecaller/dashboard.php"
        >
            Hospital Marketing System
        </a>

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#telecallerNav"
        >
            <span class="navbar-toggler-icon"></span>
        </button>

        <div
            class="collapse navbar-collapse"
            id="telecallerNav"
        >

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="../dashboard.php"
                    >
                        My Day
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="../leads/index.php"
                    >
                        Leads
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="../calls/index.php"
                    >
                        Calls
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link"
                        href="../followups/index.php"
                    >
                        Follow-ups
                    </a>
                </li>

                <li class="nav-item">
                    <a
                        class="nav-link active"
                        href="index.php"
                    >
                        My Performance
                    </a>
                </li>

            </ul>

            <span class="navbar-text text-white me-3">
                <?= htmlspecialchars($user['name']) ?>
            </span>

            <a
                href="../../logout.php"
                class="btn btn-outline-light btn-sm"
            >
                Logout
            </a>

        </div>

    </div>
</nav>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="page-title mb-1">
                My Performance
            </h2>

            <p class="text-muted mb-0">
                Track your telecalling activity and follow-up performance.
            </p>
        </div>

    </div>

    <!-- Statistics -->

    <div class="row g-4 mb-4">

        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="stat-label">
                    Assigned Leads
                </div>

                <div class="stat-value">
                    <?= $total_leads ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="stat-label">
                    Total Calls
                </div>

                <div class="stat-value">
                    <?= $total_calls ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="stat-label">
                    Calls Today
                </div>

                <div class="stat-value">
                    <?= $today_calls ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="stat-label">
                    Connected
                </div>

                <div class="stat-value">
                    <?= $connected_calls ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="stat-label">
                    Appointment Requests
                </div>

                <div class="stat-value">
                    <?= $appointment_requests ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 col-xl-2">
            <div class="card stat-card p-3">
                <div class="stat-label">
                    Follow-ups Due
                </div>

                <div class="stat-value">
                    <?= $followups_due ?>
                </div>
            </div>
        </div>

    </div>

    <!-- Connection Rate -->

    <div class="card section-card mb-4">

        <div class="card-body">

            <h5 class="fw-bold mb-3">
                Connection Rate
            </h5>

            <div class="d-flex justify-content-between mb-2">

                <span>
                    Connected Calls
                </span>

                <strong>
                    <?= $connection_rate ?>%
                </strong>

            </div>

            <div class="progress">

                <div
                    class="progress-bar bg-success"
                    role="progressbar"
                    style="width: <?= $connection_rate ?>%;"
                >
                </div>

            </div>

        </div>

    </div>

    <!-- Recent Calls -->

    <div class="card section-card">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center mb-3">

                <h5 class="fw-bold mb-0">
                    Recent Calls
                </h5>

                <a
                    href="../calls/index.php"
                    class="btn btn-sm btn-outline-primary"
                >
                    View All Calls
                </a>

            </div>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>

                        <tr>
                            <th>Date & Time</th>
                            <th>Lead</th>
                            <th>Phone</th>
                            <th>Outcome</th>
                            <th>Notes</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if (!empty($recent_calls)): ?>

                        <?php foreach ($recent_calls as $call): ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(
                                        date(
                                            'd M Y, h:i A',
                                            strtotime($call['call_at'])
                                        )
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $call['lead_name']
                                    ) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $call['phone']
                                    ) ?>
                                </td>

                                <td>

                                    <?php
                                    $badge_class = 'bg-secondary';

                                    if (
                                        $call['call_outcome']
                                        === 'Connected'
                                    ) {
                                        $badge_class =
                                            'badge-connected';
                                    }

                                    if (
                                        $call['call_outcome']
                                        === 'Appointment Requested'
                                    ) {
                                        $badge_class =
                                            'badge-appointment';
                                    }
                                    ?>

                                    <span
                                        class="badge <?= $badge_class ?>"
                                    >
                                        <?= htmlspecialchars(
                                            $call['call_outcome']
                                        ) ?>
                                    </span>

                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $call['Call_notes'] ?? ''
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td
                                colspan="5"
                                class="text-center text-muted py-4"
                            >
                                No call activity found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="footer-note mt-3">
                Performance figures are calculated from your assigned
                leads and recorded call activity.
            </div>

        </div>

    </div>

</div>

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
></script>

</body>
</html>