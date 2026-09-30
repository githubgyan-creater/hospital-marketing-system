<?php

require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', 'manager');

$page_title = 'Edit Campaign';

$campaign_id = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/*

| Validate Campaign ID

*/

if ($campaign_id <= 0) {

    header(
        'Location: ' .
        BASE_URL .
        '/campaigns/index.php'
    );

    exit;
}


/*

| Get Existing Campaign

*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        campaign_type,
        objective,
        start_date,
        end_date,
        budget,
        status,
        description,
        created_by,
        created_at,
        update_at
    FROM campaigns
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $campaign_id
]);

$campaign = $stmt->fetch();


/*

| Campaign Not Found

*/

if (!$campaign) {

    http_response_code(404);

    exit('Campaign not found.');
}


/*

| Variables

*/

$errors = [];

$name = $campaign['name'];
$campaign_type = $campaign['campaign_type'];
$objective = $campaign['objective'];
$start_date = $campaign['start_date'];
$end_date = $campaign['end_date'];
$budget = $campaign['budget'];
$status = $campaign['status'];
$description = $campaign['description'];


/*

| Allowed Campaign Types

|
| These values match your current database ENUM.
|
*/

$allowed_types = [
    'Digital',
    'Health Camp',
    'Corporate Outreach',
    'Referral',
    'Service Promotions',
    'Community Outrich',
    'Event',
    'Other'
];


/*

| Allowed Campaign Statuses

*/

$allowed_statuses = [
    'Draft',
    'Planned',
    'Active',
    'Completed',
    'Cancelled'
];


/*

| Update Campaign

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim(
        $_POST['name'] ?? ''
    );

    $campaign_type = $_POST['campaign_type']
        ?? 'Other';

    $objective = trim(
        $_POST['objective'] ?? ''
    );

    $start_date = $_POST['start_date']
        ?? '';

    $end_date = $_POST['end_date']
        ?? '';

    $budget = $_POST['budget']
        ?? '0';

    $status = $_POST['status']
        ?? 'Draft';

    $description = trim(
        $_POST['description'] ?? ''
    );


    /*
    
    | Validation
    
    */

    if ($name === '') {

        $errors[] =
            'Campaign name is required.';
    }


    if (!in_array(
        $campaign_type,
        $allowed_types,
        true
    )) {

        $errors[] =
            'Invalid campaign type.';
    }


    if (!in_array(
        $status,
        $allowed_statuses,
        true
    )) {

        $errors[] =
            'Invalid campaign status.';
    }


    if (
        $start_date !== '' &&
        $end_date !== '' &&
        $end_date < $start_date
    ) {

        $errors[] =
            'End date cannot be before start date.';
    }


    if ($budget === '') {

        $budget = 0;
    }


    if (
        !is_numeric($budget) ||
        $budget < 0
    ) {

        $errors[] =
            'Budget must be a valid positive number.';
    }


    /*
    
    | Update Database
    
    */

    if (empty($errors)) {

        $update = $pdo->prepare("
            UPDATE campaigns
            SET
                name = ?,
                campaign_type = ?,
                objective = ?,
                start_date = ?,
                end_date = ?,
                budget = ?,
                status = ?,
                description = ?
            WHERE id = ?
        ");

        $update->execute([

            $name,

            $campaign_type,

            $objective !== ''
                ? $objective
                : null,

            $start_date !== ''
                ? $start_date
                : null,

            $end_date !== ''
                ? $end_date
                : null,

            $budget,

            $status,

            $description !== ''
                ? $description
                : null,

            $campaign_id
        ]);


        /*
        
        | Redirect After Successful Update
        
        */

        header(
            'Location: ' .
            BASE_URL .
            '/campaigns/view.php?id=' .
            $campaign_id .
            '&success=updated'
        );

        exit;
    }
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<div class="container py-4">


    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Edit Campaign
            </h2>

            <p class="hm-muted mb-0">
                Update campaign information and status.
            </p>

        </div>


        <div class="d-flex gap-2">

            <a
                href="<?php echo BASE_URL; ?>/campaigns/view.php?id=<?php echo $campaign_id; ?>"
                class="btn btn-outline-secondary"
            >
                View Campaign
            </a>

            <a
                href="<?php echo BASE_URL; ?>/campaigns/index.php"
                class="btn btn-outline-secondary"
            >
                Back to Campaigns
            </a>

        </div>

    </div>


    <!-- Validation Errors -->

    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <strong>
                Please fix the following:
            </strong>

            <ul class="mb-0 mt-2">

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?php echo htmlspecialchars($error); ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <!-- Edit Form -->

    <div class="hm-card p-4">

        <form
            method="POST"
            action=""
        >

            <div class="row g-3">


                <!-- Campaign Name -->

                <div class="col-md-6">

                    <label class="form-label">

                        Campaign Name

                        <span class="text-danger">
                            *
                        </span>

                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($name); ?>"
                        placeholder="Enter campaign name"
                        required
                    >

                </div>


                <!-- Campaign Type -->

                <div class="col-md-6">

                    <label class="form-label">
                        Campaign Type
                    </label>

                    <select
                        name="campaign_type"
                        class="form-select"
                    >

                        <?php foreach (
                            $allowed_types
                            as $type
                        ): ?>

                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php
                                echo $campaign_type === $type
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php echo htmlspecialchars($type); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Objective -->

                <div class="col-12">

                    <label class="form-label">
                        Campaign Objective
                    </label>

                    <input
                        type="text"
                        name="objective"
                        class="form-control"
                        value="<?php echo htmlspecialchars($objective ?? ''); ?>"
                        placeholder="Enter campaign objective"
                    >

                </div>


                <!-- Start Date -->

                <div class="col-md-4">

                    <label class="form-label">
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        class="form-control"
                        value="<?php echo htmlspecialchars($start_date ?? ''); ?>"
                    >

                </div>


                <!-- End Date -->

                <div class="col-md-4">

                    <label class="form-label">
                        End Date
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        class="form-control"
                        value="<?php echo htmlspecialchars($end_date ?? ''); ?>"
                    >

                </div>


                <!-- Budget -->

                <div class="col-md-4">

                    <label class="form-label">
                        Budget
                    </label>

                    <input
                        type="number"
                        name="budget"
                        class="form-control"
                        value="<?php echo htmlspecialchars($budget); ?>"
                        min="0"
                        step="0.01"
                        placeholder="0.00"
                    >

                </div>


                <!-- Status -->

                <div class="col-md-6">

                    <label class="form-label">
                        Campaign Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <?php foreach (
                            $allowed_statuses
                            as $campaign_status
                        ): ?>

                            <option
                                value="<?php echo htmlspecialchars($campaign_status); ?>"
                                <?php
                                echo $status === $campaign_status
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $campaign_status
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Campaign ID -->

                <div class="col-md-6">

                    <label class="form-label">
                        Campaign ID
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="#<?php echo (int) $campaign_id; ?>"
                        readonly
                    >

                </div>


                <!-- Description -->

                <div class="col-12">

                    <label class="form-label">
                        Campaign Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="5"
                        placeholder="Enter campaign details..."
                    ><?php echo htmlspecialchars($description ?? ''); ?></textarea>

                </div>


                <!-- Existing Information -->

                <div class="col-12">

                    <div class="alert alert-light border mb-0">

                        <div class="row">

                            <div class="col-md-6">

                                <small class="text-muted">
                                    Created On
                                </small>

                                <div class="fw-semibold">

                                    <?php echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $campaign['created_at']
                                        )
                                    ); ?>

                                </div>

                            </div>


                            <div class="col-md-6 mt-3 mt-md-0">

                                <small class="text-muted">
                                    Last Updated
                                </small>

                                <div class="fw-semibold">

                                    <?php echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $campaign['update_at']
                                        )
                                    ); ?>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- Buttons -->

                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-hm-primary px-4"
                    >
                        Update Campaign
                    </button>


                    <a
                        href="<?php echo BASE_URL; ?>/campaigns/view.php?id=<?php echo $campaign_id; ?>"
                        class="btn btn-outline-secondary ms-2"
                    >
                        Cancel
                    </a>

                </div>

            </div>

        </form>

    </div>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>