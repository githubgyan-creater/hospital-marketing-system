<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../config/database.php';

require_login();

$user = current_user();

if (
    !$user ||
    !in_array($user['role'], ['admin', 'manager'], true)
) {
    http_response_code(403);
    exit('Access denied.');
}


/*
|--------------------------------------------------------------------------
| GET PLAN ID
|--------------------------------------------------------------------------
*/

$plan_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;

if ($plan_id <= 0) {
    exit('Invalid marketing plan ID.');
}


/*
|--------------------------------------------------------------------------
| LOAD PLAN
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        plan_title,
        objective,
        focus_area,
        target_description,
        start_date,
        end_date,
        status,
        remarks
    FROM marketing_plans
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $plan_id
]);

$plan = $stmt->fetch();

if (!$plan) {
    exit('Marketing plan not found.');
}


/*
|--------------------------------------------------------------------------
| FORM VALUES
|--------------------------------------------------------------------------
*/

$plan_title = $plan['plan_title'];
$objective = $plan['objective'];
$focus_area = $plan['focus_area'];
$target_description = $plan['target_description'];
$start_date = $plan['start_date'];
$end_date = $plan['end_date'];
$status = $plan['status'];
$remarks = $plan['remarks'];

$error = '';


/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $plan_title = trim(
        $_POST['plan_title'] ?? ''
    );

    $objective = trim(
        $_POST['objective'] ?? ''
    );

    $focus_area = trim(
        $_POST['focus_area'] ?? ''
    );

    $target_description = trim(
        $_POST['target_description'] ?? ''
    );

    $start_date = trim(
        $_POST['start_date'] ?? ''
    );

    $end_date = trim(
        $_POST['end_date'] ?? ''
    );

    $status = trim(
        $_POST['status'] ?? 'Draft'
    );

    $remarks = trim(
        $_POST['remarks'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($plan_title === '') {

        $error = 'Please enter the plan title.';

    } elseif ($start_date === '') {

        $error = 'Please select a start date.';

    } elseif ($end_date === '') {

        $error = 'Please select an end date.';

    } elseif ($end_date < $start_date) {

        $error = 'End date cannot be before start date.';

    } elseif (
        !in_array(
            $status,
            [
                'Draft',
                'Active',
                'Completed',
                'Cancelled'
            ],
            true
        )
    ) {

        $error = 'Invalid plan status.';
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PLAN
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $update_stmt = $pdo->prepare("
            UPDATE marketing_plans
            SET
                plan_title = ?,
                objective = ?,
                focus_area = ?,
                target_description = ?,
                start_date = ?,
                end_date = ?,
                status = ?,
                remarks = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");

        $update_stmt->execute([

            $plan_title,

            $objective !== ''
                ? $objective
                : null,

            $focus_area !== ''
                ? $focus_area
                : null,

            $target_description !== ''
                ? $target_description
                : null,

            $start_date,

            $end_date,

            $status,

            $remarks !== ''
                ? $remarks
                : null,

            $plan_id
        ]);


        header(
            'Location: ' .
            BASE_URL .
            '/marketing/marketing-plans/view.php?id=' .
            $plan_id .
            '&updated=1'
        );

        exit;
    }
}

?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<div class="container py-4">


    <!-- PAGE HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Edit Marketing Plan
            </h2>

            <p class="text-muted mb-0">
                Update the marketing plan details.
            </p>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/marketing/marketing-plans/view.php?id=<?php echo $plan_id; ?>"
            class="btn btn-outline-secondary"
        >
            Back to Plan
        </a>

    </div>


    <!-- ERROR -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?php
            echo htmlspecialchars($error);
            ?>
        </div>

    <?php endif; ?>


    <!-- FORM -->

    <div class="card shadow-sm border-0">

        <div class="card-header bg-white">

            <h5 class="mb-0">
                Plan Details
            </h5>

        </div>


        <div class="card-body">

            <form method="POST">


                <!-- PLAN TITLE -->

                <div class="mb-3">

                    <label class="form-label">
                        Plan Title
                    </label>

                    <input
                        type="text"
                        name="plan_title"
                        class="form-control"
                        value="<?php
                        echo htmlspecialchars(
                            $plan_title
                        );
                        ?>"
                        required
                    >

                </div>


                <!-- OBJECTIVE -->

                <div class="mb-3">

                    <label class="form-label">
                        Objective
                    </label>

                    <textarea
                        name="objective"
                        class="form-control"
                        rows="4"
                    ><?php
                    echo htmlspecialchars(
                        $objective ?? ''
                    );
                    ?></textarea>

                </div>


                <!-- FOCUS AREA -->

                <div class="mb-3">

                    <label class="form-label">
                        Focus Area
                    </label>

                    <input
                        type="text"
                        name="focus_area"
                        class="form-control"
                        value="<?php
                        echo htmlspecialchars(
                            $focus_area ?? ''
                        );
                        ?>"
                    >

                </div>


                <!-- TARGET -->

                <div class="mb-3">

                    <label class="form-label">
                        Target / Expected Outcome
                    </label>

                    <textarea
                        name="target_description"
                        class="form-control"
                        rows="4"
                    ><?php
                    echo htmlspecialchars(
                        $target_description ?? ''
                    );
                    ?></textarea>

                </div>


                <!-- DATES -->

                <div class="row g-3">


                    <div class="col-md-6">

                        <label class="form-label">
                            Start Date
                        </label>

                        <input
                            type="date"
                            name="start_date"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $start_date
                            );
                            ?>"
                            required
                        >

                    </div>


                    <div class="col-md-6">

                        <label class="form-label">
                            End Date
                        </label>

                        <input
                            type="date"
                            name="end_date"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $end_date
                            );
                            ?>"
                            required
                        >

                    </div>


                </div>


                <!-- STATUS -->

                <div class="mb-3 mt-3">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option
                            value="Draft"
                            <?php
                            echo $status === 'Draft'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Draft
                        </option>

                        <option
                            value="Active"
                            <?php
                            echo $status === 'Active'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="Completed"
                            <?php
                            echo $status === 'Completed'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Completed
                        </option>

                        <option
                            value="Cancelled"
                            <?php
                            echo $status === 'Cancelled'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Cancelled
                        </option>

                    </select>

                </div>


                <!-- REMARKS -->

                <div class="mb-4">

                    <label class="form-label">
                        Remarks
                    </label>

                    <textarea
                        name="remarks"
                        class="form-control"
                        rows="4"
                    ><?php
                    echo htmlspecialchars(
                        $remarks ?? ''
                    );
                    ?></textarea>

                </div>


                <!-- BUTTONS -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Update Marketing Plan
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/marketing/marketing-plans/view.php?id=<?php echo $plan_id; ?>"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>

                </div>


            </form>

        </div>

    </div>

</div>


<?php require_once __DIR__ . '/../../includes/footer.php'; ?>