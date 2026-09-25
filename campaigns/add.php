 <?php

require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', 'manager');

$page_title = 'Add Campaign';

$user = current_user();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $campaign_type = $_POST['campaign_type'] ?? 'Other';
    $objective = trim($_POST['objective'] ?? '');
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    $budget = $_POST['budget'] ?? 0;
    $status = $_POST['status'] ?? 'Draft';
    $description = trim($_POST['description'] ?? '');

    /* Validation */

    if ($name === '') {
        $errors[] = 'Campaign name is required.';
    }

    if ($start_date && $end_date && $end_date < $start_date) {
        $errors[] = 'End date cannot be before start date.';
    }

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

    if (!in_array($campaign_type, $allowed_types, true)) {
        $errors[] = 'Invalid campaign type.';
    }

    $allowed_statuses = [
        'Draft',
        'Planned',
        'Active',
        'Completed',
        'Cancelled'
    ];

    if (!in_array($status, $allowed_statuses, true)) {
        $errors[] = 'Invalid campaign status.';
    }

    if ($budget === '') {
        $budget = 0;
    }

    if (!is_numeric($budget) || $budget < 0) {
        $errors[] = 'Budget must be a valid positive number.';
    }

    /* Insert Campaign */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO campaigns
            (
                name,
                campaign_type,
                objective,
                start_date,
                end_date,
                budget,
                status,
                description,
                created_by
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $name,
            $campaign_type,
            $objective !== '' ? $objective : null,
            $start_date !== '' ? $start_date : null,
            $end_date !== '' ? $end_date : null,
            $budget,
            $status,
            $description !== '' ? $description : null,
            $user['id']
        ]);

        header(
            'Location: ' .
            BASE_URL .
            '/campaigns/index.php?success=created'
        );

        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h2 class="hm-page-title mb-1">
                Add Campaign
            </h2>

            <p class="hm-muted mb-0">
                Create a new hospital marketing campaign.
            </p>
        </div>

        <a
            href="<?php echo BASE_URL; ?>/campaigns/index.php"
            class="btn btn-outline-secondary"
        >
            Back to Campaigns
        </a>

    </div>


    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <strong>Please fix the following:</strong>

            <ul class="mb-0 mt-2">

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?php echo htmlspecialchars($error); ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <div class="hm-card p-4">

        <form method="POST">

            <div class="row g-3">

                <!-- Campaign Name -->

                <div class="col-md-6">

                    <label class="form-label">
                        Campaign Name
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        placeholder="Example: World Heart Day Campaign"
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

                        <?php

                        $types = [
                            'Digital',
                            'Health Camp',
                            'Corporate Outreach',
                            'Referral',
                            'Service Promotions',
                            'Community Outrich',
                            'Event',
                            'Other'
                        ];

                        $selected_type =
                            $_POST['campaign_type'] ?? 'Other';

                        foreach ($types as $type):

                        ?>

                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php
                                echo $selected_type === $type
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
                        value="<?php echo htmlspecialchars($_POST['objective'] ?? ''); ?>"
                        placeholder="Example: Increase cardiac consultation enquiries"
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
                        value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>"
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
                        value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>"
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
                        value="<?php echo htmlspecialchars($_POST['budget'] ?? '0'); ?>"
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

                        <?php

                        $statuses = [
                            'Draft',
                            'Planned',
                            'Active',
                            'Completed',
                            'Cancelled'
                        ];

                        $selected_status =
                            $_POST['status'] ?? 'Draft';

                        foreach ($statuses as $status):

                        ?>

                            <option
                                value="<?php echo htmlspecialchars($status); ?>"
                                <?php
                                echo $selected_status === $status
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php echo htmlspecialchars($status); ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Created By -->

                <div class="col-md-6">

                    <label class="form-label">
                        Created By
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?php echo htmlspecialchars($user['name']); ?>"
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
                    ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>

                </div>


                <!-- Buttons -->

                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-hm-primary px-4"
                    >
                        Save Campaign
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/campaigns/index.php"
                        class="btn btn-outline-secondary ms-2"
                    >
                        Cancel
                    </a>

                </div>

            </div>

        </form>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>