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

| FORM VALUES

*/

$plan_title = '';
$objective = '';
$focus_area = '';
$target_description = '';
$start_date = '';
$end_date = '';
$status = 'Draft';
$remarks = '';

$error = '';


/*

| HANDLE FORM SUBMISSION

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $plan_title = trim($_POST['plan_title'] ?? '');
    $objective = trim($_POST['objective'] ?? '');
    $focus_area = trim($_POST['focus_area'] ?? '');
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
    
    | VALIDATION
    
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
    
    | INSERT PLAN
    
    */

    if ($error === '') {

        $stmt = $pdo->prepare("
            INSERT INTO marketing_plans
            (
                plan_title,
                objective,
                focus_area,
                target_description,
                start_date,
                end_date,
                status,
                remarks,
                created_by
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        $stmt->execute([

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

            $user['id']
        ]);


        header(
            'Location: ' .
            BASE_URL .
            '/marketing/marketing-plans/index.php?saved=1'
        );

        exit;
    }
}

?>

<?php require_once __DIR__ . '/../../includes/header.php'; ?>


<div class="container py-4">

    <!-- HEADER -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="mb-1">
                Add Marketing Plan
            </h2>

            <p class="text-muted mb-0">
                Create the next marketing plan for hospital growth.
            </p>

        </div>


        <a
            href="<?php echo BASE_URL; ?>/marketing/marketing-plans/index.php"
            class="btn btn-outline-secondary"
        >
            Back to Plans
        </a>

    </div>


    <!-- ERROR -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error); ?>
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
                        placeholder="Example: October Doctor Referral Campaign"
                        value="<?php
                        echo htmlspecialchars($plan_title);
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
                        placeholder="What is the main objective of this marketing plan?"
                    ><?php
                    echo htmlspecialchars($objective);
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
                        placeholder="Example: Doctor Relationship"
                        value="<?php
                        echo htmlspecialchars($focus_area);
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
                        placeholder="Example: Visit 20 doctors and generate 10 referral enquiries."
                    ><?php
                    echo htmlspecialchars(
                        $target_description
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
                            echo htmlspecialchars($start_date);
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
                            echo htmlspecialchars($end_date);
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
                        placeholder="Additional notes about this plan."
                    ><?php
                    echo htmlspecialchars($remarks);
                    ?></textarea>

                </div>


                <!-- BUTTONS -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Save Marketing Plan
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/marketing/marketing-plans/index.php"
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