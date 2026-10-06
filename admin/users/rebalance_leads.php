 
<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/lead_workflow.php';

require_role('admin');

$page_title = 'Rebalance Active Leads';

$message = '';
$error = '';

$before_workloads = [];
$after_workloads = [];
$rebalance_result = null;

/*
|--------------------------------------------------------------------------
| Get Current Admin
|--------------------------------------------------------------------------
*/

$current_user = current_user();
$current_admin_id = (int) $current_user['id'];

/*
|--------------------------------------------------------------------------
| Get Current Workload
|--------------------------------------------------------------------------
*/

try {

    $before_workloads = get_telecaller_active_workloads($pdo);

} catch (Throwable $e) {

    $error = 'Unable to load current Telecaller workload: ' .
        $e->getMessage();
}

/*
|--------------------------------------------------------------------------
| Handle Rebalance
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['rebalance'])
) {

    try {

        /*
        |--------------------------------------------------------------------------
        | Start transaction
        |--------------------------------------------------------------------------
        |
        | The existing rebalance function also handles transactions.
        | Therefore we DO NOT start another transaction here.
        |
        */

        $rebalance_result = rebalance_all_active_leads(
            $pdo,
            $current_admin_id
        );

        if (
            empty($rebalance_result['success'])
        ) {

            throw new RuntimeException(
                $rebalance_result['message']
                    ?? 'Lead redistribution failed.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Get Final Workload
        |--------------------------------------------------------------------------
        */

        $after_workloads =
            get_telecaller_active_workloads($pdo);

        $message =
            'Active leads have been redistributed successfully.';

    } catch (Throwable $e) {

        $error =
            'Unable to rebalance active leads: ' .
            $e->getMessage();

        /*
        |--------------------------------------------------------------------------
        | Reload workload after error
        |--------------------------------------------------------------------------
        */

        try {

            $after_workloads =
                get_telecaller_active_workloads($pdo);

        } catch (Throwable $ignored) {
            $after_workloads = [];
        }
    }
}

/*
|--------------------------------------------------------------------------
| Get Telecaller Names
|--------------------------------------------------------------------------
*/

$telecallers = [];

try {

    $telecaller_stmt = $pdo->query("
        SELECT
            u.id,
            u.name,
            u.email,
            u.status
        FROM users u
        INNER JOIN roles r
            ON r.id = u.role_id
        WHERE u.status = 'active'
          AND LOWER(r.name) = 'telecaller'
        ORDER BY u.id ASC
    ");

    $telecallers = $telecaller_stmt->fetchAll();

} catch (Throwable $e) {

    if ($error === '') {
        $error =
            'Unable to load Telecallers: ' .
            $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| Count Active / Unassigned Leads
|--------------------------------------------------------------------------
*/

$total_active_leads = 0;
$unassigned_active_leads = 0;

try {

    $lead_count_stmt = $pdo->query("
        SELECT
            COUNT(*) AS total_active_leads,
            SUM(
                CASE
                    WHEN assigned_to IS NULL THEN 1
                    ELSE 0
                END
            ) AS unassigned_active_leads
        FROM leads
        WHERE status NOT IN (
            'Converted',
            'Lost'
        )
    ");

    $lead_counts = $lead_count_stmt->fetch();

    if ($lead_counts) {

        $total_active_leads =
            (int) $lead_counts['total_active_leads'];

        $unassigned_active_leads =
            (int) $lead_counts['unassigned_active_leads'];
    }

} catch (Throwable $e) {

    if ($error === '') {

        $error =
            'Unable to count active leads: ' .
            $e->getMessage();
    }
}

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <!--
    |--------------------------------------------------------------------------
    | Page Header
    |--------------------------------------------------------------------------
    -->

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/admin/users/index.php"
            class="text-decoration-none"
        >
            ← Back to User Management
        </a>

        <h1 class="hm-page-title mt-3 mb-1">
            Rebalance Active Leads
        </h1>

        <p class="hm-muted">
            Redistribute all active leads equally among active Telecallers.
        </p>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | Success Message
    |--------------------------------------------------------------------------
    -->

    <?php if ($message !== ''): ?>

        <div class="alert alert-success">

            <strong>
                Success
            </strong>

            <div class="mt-2">
                <?php echo htmlspecialchars($message); ?>
            </div>

        </div>

    <?php endif; ?>


    <!--
    |--------------------------------------------------------------------------
    | Error Message
    |--------------------------------------------------------------------------
    -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <strong>
                Error
            </strong>

            <div class="mt-2">
                <?php echo htmlspecialchars($error); ?>
            </div>

        </div>

    <?php endif; ?>


    <!--
    |--------------------------------------------------------------------------
    | Current System Summary
    |--------------------------------------------------------------------------
    -->

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="hm-card p-4">

                <div class="text-muted small">
                    Active Telecallers
                </div>

                <div class="fs-3 fw-bold mt-1">
                    <?php echo count($telecallers); ?>
                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4">

                <div class="text-muted small">
                    Active Leads
                </div>

                <div class="fs-3 fw-bold mt-1">
                    <?php echo $total_active_leads; ?>
                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="hm-card p-4">

                <div class="text-muted small">
                    Unassigned Active Leads
                </div>

                <div class="fs-3 fw-bold mt-1">
                    <?php echo $unassigned_active_leads; ?>
                </div>

            </div>

        </div>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | Telecaller Workload
    |--------------------------------------------------------------------------
    -->

    <div class="hm-card p-4 mb-4">

        <h5 class="mb-3">
            Current Telecaller Workload
        </h5>

        <?php if (!empty($telecallers)): ?>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>

                        <tr>

                            <th>
                                Telecaller
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Active Leads
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach (
                            $telecallers as $telecaller
                        ): ?>

                            <?php

                            $telecaller_id =
                                (int) $telecaller['id'];

                            $current_count =
                                $before_workloads[
                                    $telecaller_id
                                ] ?? 0;

                            ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $telecaller['name']
                                        );
                                        ?>
                                    </strong>

                                    <div class="small text-muted">

                                        ID:
                                        <?php
                                        echo $telecaller_id;
                                        ?>

                                    </div>

                                </td>

                                <td>

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo $current_count;
                                        ?>
                                    </strong>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="alert alert-warning mb-0">

                No active Telecallers found.

            </div>

        <?php endif; ?>

    </div>


    <!--
    |--------------------------------------------------------------------------
    | Rebalance Result
    |--------------------------------------------------------------------------
    -->

    <?php if ($rebalance_result !== null): ?>

        <div class="hm-card p-4 mb-4">

            <h5 class="mb-3">
                Redistribution Result
            </h5>

            <div class="row g-3">

                <div class="col-md-4">

                    <div class="border rounded p-3">

                        <div class="text-muted small">
                            Total Active Leads
                        </div>

                        <div class="fs-4 fw-bold">
                            <?php
                            echo (int) (
                                $rebalance_result[
                                    'total_leads'
                                ] ?? 0
                            );
                            ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="border rounded p-3">

                        <div class="text-muted small">
                            Leads Reassigned
                        </div>

                        <div class="fs-4 fw-bold">
                            <?php
                            echo (int) (
                                $rebalance_result[
                                    'changed_leads'
                                ] ?? 0
                            );
                            ?>
                        </div>

                    </div>

                </div>


                <div class="col-md-4">

                    <div class="border rounded p-3">

                        <div class="text-muted small">
                            Active Telecallers
                        </div>

                        <div class="fs-4 fw-bold">
                            <?php
                            echo (int) (
                                $rebalance_result[
                                    'telecallers'
                                ] ?? 0
                            );
                            ?>
                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!--
        |--------------------------------------------------------------------------
        | Final Workload
        |--------------------------------------------------------------------------
        -->

        <div class="hm-card p-4 mb-4">

            <h5 class="mb-3">
                Final Telecaller Workload
            </h5>

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead>

                        <tr>

                            <th>
                                Telecaller
                            </th>

                            <th>
                                Leads Before
                            </th>

                            <th>
                                Leads After
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach (
                            $telecallers as $telecaller
                        ): ?>

                            <?php

                            $telecaller_id =
                                (int) $telecaller['id'];

                            $before =
                                $before_workloads[
                                    $telecaller_id
                                ] ?? 0;

                            $after =
                                $after_workloads[
                                    $telecaller_id
                                ] ?? 0;

                            ?>

                            <tr>

                                <td>

                                    <strong>
                                        <?php
                                        echo htmlspecialchars(
                                            $telecaller['name']
                                        );
                                        ?>
                                    </strong>

                                </td>

                                <td>
                                    <?php
                                    echo $before;
                                    ?>
                                </td>

                                <td>

                                    <strong>
                                        <?php
                                        echo $after;
                                        ?>
                                    </strong>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>


    <!--
    |--------------------------------------------------------------------------
    | Action Card
    |--------------------------------------------------------------------------
    -->

    <div class="hm-card p-4">

        <h5>
            Redistribute Active Leads
        </h5>

        <p class="text-muted">

            This will redistribute all leads whose status is not
            <strong>Converted</strong> or <strong>Lost</strong>
            among the currently active Telecallers.

        </p>

        <div class="alert alert-warning">

            <strong>Please note:</strong>

            This action will change the Telecaller assignment
            of existing active leads.

            Converted and Lost leads will not be changed.

        </div>


        <form
            method="POST"
            onsubmit="return confirm(
                'Are you sure you want to redistribute all active leads among active Telecallers?'
            );"
        >

            <button
                type="submit"
                name="rebalance"
                value="1"
                class="btn btn-hm-primary"
            >
                Rebalance Active Leads
            </button>

            <a
                href="<?php
                echo BASE_URL;
                ?>/admin/users/index.php"
                class="btn btn-outline-secondary ms-2"
            >
                Cancel
            </a>

        </form>

    </div>

</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>
```
