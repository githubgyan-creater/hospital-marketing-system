<?php

require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../config/database.php';

require_role('admin', 'manager');

$page_title = 'Campaign Details';

$campaign_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($campaign_id <= 0) {
    header(
        'Location: ' .
        BASE_URL .
        '/campaigns/index.php'
    );
    exit;
}

/*

 Get Campaign

*/

$stmt = $pdo->prepare("
    SELECT
        c.id,
        c.name,
        c.campaign_type,
        c.objective,
        c.start_date,
        c.end_date,
        c.budget,
        c.status,
        c.description,
        c.created_by,
        c.created_at,
        c.update_at,
        u.name AS created_by_name
    FROM campaigns c
    LEFT JOIN users u
        ON c.created_by = u.id
    WHERE c.id = ?
    LIMIT 1
");

$stmt->execute([$campaign_id]);

$campaign = $stmt->fetch();

if (!$campaign) {

    http_response_code(404);

    exit('Campaign not found.');
}


/*

 Helper Function

*/

function campaign_status_class(string $status): string
{
    return match ($status) {

        'Draft'      => 'bg-secondary',
        'Planned'    => 'bg-info text-dark',
        'Active'     => 'bg-success',
        'Completed'  => 'bg-primary',
        'Cancelled' => 'bg-danger',

        default => 'bg-secondary'
    };
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>


<div class="container py-4">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Campaign Details
            </h2>

            <p class="hm-muted mb-0">
                View complete information about this campaign.
            </p>

        </div>


        <div class="d-flex gap-2">

            <a
                href="<?php echo BASE_URL; ?>/campaigns/index.php"
                class="btn btn-outline-secondary"
            >
                Back to Campaigns
            </a>

            <a
                href="<?php echo BASE_URL; ?>/campaigns/edit.php?id=<?php echo $campaign['id']; ?>"
                class="btn btn-hm-primary"
            >
                Edit Campaign
            </a>

        </div>

    </div>


    <!-- Campaign Header Card -->

    <div class="hm-card p-4 mb-4">

        <div class="row align-items-center">

            <div class="col-md-8">

                <div class="d-flex align-items-center gap-2 mb-2">

                    <h3 class="mb-0">
                        <?php echo htmlspecialchars($campaign['name']); ?>
                    </h3>

                    <span
                        class="badge <?php echo campaign_status_class($campaign['status']); ?>"
                    >
                        <?php echo htmlspecialchars($campaign['status']); ?>
                    </span>

                </div>


                <p class="hm-muted mb-0">

                    Campaign ID:
                    <strong>
                        #<?php echo (int) $campaign['id']; ?>
                    </strong>

                </p>

            </div>


            <div class="col-md-4 text-md-end mt-3 mt-md-0">

                <div class="hm-muted small">
                    Campaign Type
                </div>

                <div class="fw-semibold">
                    <?php echo htmlspecialchars($campaign['campaign_type']); ?>
                </div>

            </div>

        </div>

    </div>


    <!-- Main Information -->

    <div class="row g-4">


        <!-- Campaign Information -->

        <div class="col-lg-8">

            <div class="hm-card p-4 h-100">

                <h5 class="mb-4">
                    Campaign Information
                </h5>


                <div class="row g-4">


                    <!-- Objective -->

                    <div class="col-md-6">

                        <div class="hm-muted small mb-1">
                            Objective
                        </div>

                        <div class="fw-semibold">

                            <?php if (!empty($campaign['objective'])): ?>

                                <?php echo htmlspecialchars($campaign['objective']); ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    Not specified
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>


                    <!-- Campaign Type -->

                    <div class="col-md-6">

                        <div class="hm-muted small mb-1">
                            Campaign Type
                        </div>

                        <div class="fw-semibold">
                            <?php echo htmlspecialchars($campaign['campaign_type']); ?>
                        </div>

                    </div>


                    <!-- Start Date -->

                    <div class="col-md-6">

                        <div class="hm-muted small mb-1">
                            Start Date
                        </div>

                        <div class="fw-semibold">

                            <?php

                            echo !empty($campaign['start_date'])
                                ? date(
                                    'd M Y',
                                    strtotime($campaign['start_date'])
                                )
                                : 'Not specified';

                            ?>

                        </div>

                    </div>


                    <!-- End Date -->

                    <div class="col-md-6">

                        <div class="hm-muted small mb-1">
                            End Date
                        </div>

                        <div class="fw-semibold">

                            <?php

                            echo !empty($campaign['end_date'])
                                ? date(
                                    'd M Y',
                                    strtotime($campaign['end_date'])
                                )
                                : 'Not specified';

                            ?>

                        </div>

                    </div>


                    <!-- Budget -->

                    <div class="col-md-6">

                        <div class="hm-muted small mb-1">
                            Campaign Budget
                        </div>

                        <div class="fw-semibold">

                            ₹<?php echo number_format(
                                (float) $campaign['budget'],
                                2
                            ); ?>

                        </div>

                    </div>


                    <!-- Created By -->

                    <div class="col-md-6">

                        <div class="hm-muted small mb-1">
                            Created By
                        </div>

                        <div class="fw-semibold">

                            <?php echo htmlspecialchars(
                                $campaign['created_by_name'] ?? 'Unknown'
                            ); ?>

                        </div>

                    </div>


                    <!-- Created At -->

                    <div class="col-md-6">

                        <div class="hm-muted small mb-1">
                            Created On
                        </div>

                        <div class="fw-semibold">

                            <?php echo date(
                                'd M Y, h:i A',
                                strtotime($campaign['created_at'])
                            ); ?>

                        </div>

                    </div>


                    <!-- Updated At -->

                    <div class="col-md-6">

                        <div class="hm-muted small mb-1">
                            Last Updated
                        </div>

                        <div class="fw-semibold">

                            <?php echo date(
                                'd M Y, h:i A',
                                strtotime($campaign['update_at'])
                            ); ?>

                        </div>

                    </div>


                    <!-- Description -->

                    <div class="col-12">

                        <div class="hm-muted small mb-1">
                            Description
                        </div>

                        <div>

                            <?php if (!empty($campaign['description'])): ?>

                                <?php echo nl2br(
                                    htmlspecialchars($campaign['description'])
                                ); ?>

                            <?php else: ?>

                                <span class="text-muted">
                                    No description added.
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- Campaign Summary -->

        <div class="col-lg-4">

            <div class="hm-card p-4 h-100">

                <h5 class="mb-4">
                    Campaign Summary
                </h5>


                <!-- Status -->

                <div class="mb-4">

                    <div class="hm-muted small mb-1">
                        Current Status
                    </div>

                    <span
                        class="badge <?php echo campaign_status_class($campaign['status']); ?>"
                    >
                        <?php echo htmlspecialchars($campaign['status']); ?>
                    </span>

                </div>


                <!-- Type -->

                <div class="mb-4">

                    <div class="hm-muted small mb-1">
                        Campaign Type
                    </div>

                    <div class="fw-semibold">

                        <?php echo htmlspecialchars(
                            $campaign['campaign_type']
                        ); ?>

                    </div>

                </div>


                <!-- Budget -->

                <div class="mb-4">

                    <div class="hm-muted small mb-1">
                        Budget
                    </div>

                    <div class="fs-4 fw-bold hm-gold">

                        ₹<?php echo number_format(
                            (float) $campaign['budget'],
                            2
                        ); ?>

                    </div>

                </div>


                <!-- Duration -->

                <div class="mb-4">

                    <div class="hm-muted small mb-1">
                        Campaign Duration
                    </div>

                    <div class="fw-semibold">

                        <?php if (
                            !empty($campaign['start_date']) &&
                            !empty($campaign['end_date'])
                        ): ?>

                            <?php

                            $start = new DateTime(
                                $campaign['start_date']
                            );

                            $end = new DateTime(
                                $campaign['end_date']
                            );

                            $duration =
                                $start->diff($end)->days + 1;

                            echo $duration . ' day';

                            if ($duration != 1) {
                                echo 's';
                            }

                            ?>

                        <?php else: ?>

                            <span class="text-muted">
                                Not specified
                            </span>

                        <?php endif; ?>

                    </div>

                </div>


                <!-- Created By -->

                <div>

                    <div class="hm-muted small mb-1">
                        Created By
                    </div>

                    <div class="fw-semibold">

                        <?php echo htmlspecialchars(
                            $campaign['created_by_name'] ?? 'Unknown'
                        ); ?>

                    </div>

                </div>

            </div>

        </div>

    </div>


</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>