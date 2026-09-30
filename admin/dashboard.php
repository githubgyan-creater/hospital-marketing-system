 <?php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin');

$page_title = 'Admin Dashboard';

$user = current_user();

require_once __DIR__ . '/../includes/header.php';


/*
|--------------------------------------------------------------------------
| SAFE COUNT HELPER
|--------------------------------------------------------------------------
*/

function admin_dashboard_count(PDO $pdo, string $sql): int
{
    try {

        $stmt = $pdo->query($sql);

        return (int) $stmt->fetchColumn();

    } catch (Throwable $e) {

        return 0;
    }
}


/*
|--------------------------------------------------------------------------
| DASHBOARD COUNTS
|--------------------------------------------------------------------------
*/

$total_users = admin_dashboard_count(
    $pdo,
    "SELECT COUNT(*) FROM users"
);

$active_users = admin_dashboard_count(
    $pdo,
    "SELECT COUNT(*) FROM users WHERE status = 'active'"
);


$total_leads = admin_dashboard_count(
    $pdo,
    "SELECT COUNT(*) FROM leads"
);

$new_leads = admin_dashboard_count(
    $pdo,
    "SELECT COUNT(*) FROM leads WHERE status = 'New'"
);


$active_events = admin_dashboard_count(
    $pdo,
    "SELECT COUNT(*) FROM events WHERE status IN ('Planned', 'Ongoing')"
);


$total_appointments = admin_dashboard_count(
    $pdo,
    "SELECT COUNT(*) FROM appointments"
);


$converted_leads = admin_dashboard_count(
    $pdo,
    "SELECT COUNT(*) FROM leads WHERE status = 'Converted'"
);


$total_activities = admin_dashboard_count(
    $pdo,
    "SELECT COUNT(*) FROM lead_activities"
);

?>

<style>

    .admin-hero {
        background: linear-gradient(
            135deg,
            #17324d 0%,
            #214c6d 100%
        );

        border-radius: 18px;

        padding: 32px;

        color: #ffffff;

        box-shadow:
            0 8px 24px rgba(23, 50, 77, 0.12);
    }


    .admin-hero .hero-muted {
        color: rgba(255, 255, 255, 0.78);
    }


    .admin-stat-card {
        background: #ffffff;

        border: 1px solid #e9e6df;

        border-radius: 14px;

        padding: 22px;

        height: 100%;

        box-shadow:
            0 4px 16px rgba(23, 50, 77, 0.05);
    }


    .admin-stat-label {
        color: #71808c;

        font-size: 0.88rem;

        margin-bottom: 4px;
    }


    .admin-stat-value {
        color: #17324d;

        font-size: 1.9rem;

        font-weight: 700;
    }


    .admin-module-card {
        background: #ffffff;

        border: 1px solid #e7e3da;

        border-radius: 16px;

        padding: 28px;

        min-height: 300px;

        display: flex;

        flex-direction: column;

        box-shadow:
            0 5px 20px rgba(23, 50, 77, 0.05);

        transition:
            transform 0.2s ease,
            box-shadow 0.2s ease;
    }


    .admin-module-card:hover {

        transform: translateY(-3px);

        box-shadow:
            0 10px 26px rgba(23, 50, 77, 0.09);
    }


    .admin-module-icon {
        font-size: 2.2rem;

        margin-bottom: 18px;
    }


    .admin-module-title {
        color: #17324d;

        font-weight: 700;

        margin-bottom: 10px;
    }


    .admin-module-description {
        color: #71808c;

        line-height: 1.6;

        min-height: 76px;
    }


    .admin-module-info {
        border-top: 1px solid #eeeeee;

        padding-top: 16px;

        margin-top: 8px;
    }


    .admin-module-button {
        margin-top: auto;

        padding-top: 20px;
    }


    .admin-section-title {
        color: #17324d;

        font-weight: 700;
    }


    .admin-section-subtitle {
        color: #71808c;

        font-size: 0.92rem;
    }

</style>


<main class="container py-4">


    <!-- =========================================================
         HERO / FIRST IMPRESSION
    ========================================================== -->

    <div class="admin-hero mb-4">

        <div class="row align-items-center">

            <div class="col-lg-8">

                <!-- <div class="small text-uppercase mb-2 hero-muted">
                    Hospital Marketing System
                </div> -->


                <h1 class="mb-2">
                    Admin Dashboard
                </h1>


                <p class="mb-0 hero-muted">

                    Welcome,
                    <?php
                    echo htmlspecialchars(
                        $user['name']
                    );
                    ?>.

                    <!-- Manage your hospital marketing operations
                    from one central workspace. -->

                </p>

            </div>


            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">

                <span class="badge bg-light text-dark px-3 py-2">

                    Admin Control Panel

                </span>

            </div>

        </div>

    </div>


    <!-- =========================================================
         SYSTEM SNAPSHOT
    ========================================================== -->

    <div class="row g-3 mb-5">


        <!-- USERS -->

        <div class="col-6 col-xl-3">

            <div class="admin-stat-card">

                <div class="admin-stat-label">
                    Total Users
                </div>

                <div class="admin-stat-value">
                    <?php echo $total_users; ?>
                </div>

                <div class="small text-success mt-1">

                    Active:
                    <?php echo $active_users; ?>

                </div>

            </div>

        </div>


        <!-- LEADS -->

        <div class="col-6 col-xl-3">

            <div class="admin-stat-card">

                <div class="admin-stat-label">
                    Total Leads
                </div>

                <div class="admin-stat-value">
                    <?php echo $total_leads; ?>
                </div>

                <div class="small text-primary mt-1">

                    New:
                    <?php echo $new_leads; ?>

                </div>

            </div>

        </div>


        <!-- EVENTS -->

        <div class="col-6 col-xl-3">

            <div class="admin-stat-card">

                <div class="admin-stat-label">
                    Active Events
                </div>

                <div class="admin-stat-value">
                    <?php echo $active_events; ?>
                </div>

                <div class="small hm-muted mt-1">
                    Planned or ongoing
                </div>

            </div>

        </div>


        <!-- APPOINTMENTS -->

        <div class="col-6 col-xl-3">

            <div class="admin-stat-card">

                <div class="admin-stat-label">
                    Appointments
                </div>

                <div class="admin-stat-value">
                    <?php echo $total_appointments; ?>
                </div>

                <div class="small hm-muted mt-1">
                    Total appointments
                </div>

            </div>

        </div>

    </div>


    <!-- =========================================================
         CORE MANAGEMENT
    ========================================================== -->

    <div class="text-center mb-4">

        <h3 class="admin-section-title mb-1">
            Core Management
        </h3>

        <div class="admin-section-subtitle">
            Access the three main areas of the administration system.
        </div>

    </div>


    <div class="row g-4 justify-content-center">


        <!-- =====================================================
             USER MANAGEMENT
        ====================================================== -->

        <div class="col-md-6 col-xl-4">

            <div class="admin-module-card">


                <div class="admin-module-icon">
                    👥
                </div>


                <h4 class="admin-module-title">
                    User Management
                </h4>


                <p class="admin-module-description">

                    Create and manage Manager, Telecaller,
                    Marketing Executive and other staff accounts.

                </p>


                <div class="admin-module-info">

                    <div class="d-flex justify-content-between">

                        <span class="hm-muted">
                            Total Users
                        </span>

                        <strong>
                            <?php echo $total_users; ?>
                        </strong>

                    </div>


                    <div class="d-flex justify-content-between mt-1">

                        <span class="hm-muted">
                            Active Users
                        </span>

                        <strong class="text-success">
                            <?php echo $active_users; ?>
                        </strong>

                    </div>

                </div>


                <div class="admin-module-button">

                    <a
                        href="<?php echo BASE_URL; ?>/admin/users/index.php"
                        class="btn btn-hm-primary w-100"
                    >
                        Manage Users
                    </a>

                </div>

            </div>

        </div>


        <!-- =====================================================
             LEAD MANAGEMENT
        ====================================================== -->

        <div class="col-md-6 col-xl-4">

            <div class="admin-module-card">


                <div class="admin-module-icon">
                    📋
                </div>


                <h4 class="admin-module-title">
                    Lead Management
                </h4>


                <p class="admin-module-description">

                    Monitor leads, assignments, priorities,
                    follow-ups, statuses and conversion activity.

                </p>


                <div class="admin-module-info">

                    <div class="d-flex justify-content-between">

                        <span class="hm-muted">
                            Total Leads
                        </span>

                        <strong>
                            <?php echo $total_leads; ?>
                        </strong>

                    </div>


                    <div class="d-flex justify-content-between mt-1">

                        <span class="hm-muted">
                            New Leads
                        </span>

                        <strong class="text-primary">
                            <?php echo $new_leads; ?>
                        </strong>

                    </div>

                </div>


                <div class="admin-module-button">

                    <a
                        href="<?php echo BASE_URL; ?>/leads/index.php"
                        class="btn btn-hm-primary w-100"
                    >
                        Manage Leads
                    </a>

                </div>

            </div>

        </div>


        <!-- =====================================================
             REPORTS
        ====================================================== -->

        <div class="col-md-6 col-xl-4">

            <div class="admin-module-card">


                <div class="admin-module-icon">
                    📊
                </div>


                <h4 class="admin-module-title">
                    Reports
                </h4>


                <p class="admin-module-description">

                    Review staff performance, lead conversion,
                    team activities and system-wide reports.

                </p>


                <div class="admin-module-info">

                    <div class="d-flex justify-content-between">

                        <span class="hm-muted">
                            Converted Leads
                        </span>

                        <strong class="text-success">
                            <?php echo $converted_leads; ?>
                        </strong>

                    </div>


                    <div class="d-flex justify-content-between mt-1">

                        <span class="hm-muted">
                            Activities
                        </span>

                        <strong>
                            <?php echo $total_activities; ?>
                        </strong>

                    </div>

                </div>


                <div class="admin-module-button">

                    <a
                        href="<?php echo BASE_URL; ?>/admin/reports/index.php"
                        class="btn btn-hm-primary w-100"
                    >
                        View Reports
                    </a>

                </div>

            </div>

        </div>


    </div>


</main>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>