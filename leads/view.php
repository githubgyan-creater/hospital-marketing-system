 <?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role(
    'admin',
    'manager',
    'telecaller',
    'marketing'
);

$user = current_user();
$role = $user['role'] ?? '';

$lead_id = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (
    $lead_id === false ||
    $lead_id === null ||
    $lead_id <= 0
) {
    exit('Invalid lead ID.');
}

/*
|--------------------------------------------------------------------------
| Get Lead Details
|--------------------------------------------------------------------------
*/
$stmt = $pdo->prepare("
    SELECT
        l.*,
        ms.name AS source_name,
        assigned.name AS assigned_name,
        creator.name AS creator_name,
        ref.name AS referral_name,
        ref.referral_type AS referral_type,
        ref.organization AS referral_organization,
        ref.phone AS referral_phone,
        ref.email AS referral_email
    FROM leads l
    LEFT JOIN marketing_sources ms
        ON l.source_id = ms.id
    LEFT JOIN users assigned
        ON l.assigned_to = assigned.id
    LEFT JOIN users creator
        ON l.created_by = creator.id
    LEFT JOIN referrals ref
        ON l.referral_id = ref.id
    WHERE l.id = :id
    LIMIT 1
");

$stmt->execute([
    'id' => $lead_id
]);

$lead = $stmt->fetch();

if (!$lead) {
    http_response_code(404);
    exit('Lead not found.');
}

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->
    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/leads/index.php"
            class="text-decoration-none"
        >
            ← Back to Leads
        </a>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

            <div>

                <h1 class="hm-page-title mt-3 mb-1">
                    Lead Details
                </h1>

                <p class="hm-muted">
                    View complete information about this enquiry.
                </p>

            </div>

            <div class="d-flex gap-2 flex-wrap">

                <!-- Activity Timeline -->
                <a
                    href="<?php echo BASE_URL; ?>/leads/timeline.php?id=<?php echo $lead_id; ?>"
                    class="btn btn-outline-secondary"
                >
                    Activity Timeline
                </a>

                <?php
                /*
                |--------------------------------------------------------------------------
                | Create Appointment
                |--------------------------------------------------------------------------
                | Allowed roles:
                | - Manager
                | - Telecaller
                | - Marketing Executive
                |
                | Admin does not see this button.
                |--------------------------------------------------------------------------
                */
                ?>

                <?php if (
                    in_array(
                        $role,
                        [
                            'manager',
                            'telecaller',
                            'marketing'
                        ],
                        true
                    )
                ): ?>

                    <a
                        href="<?php echo BASE_URL; ?>/telecaller/appointments/add.php?lead_id=<?php echo $lead_id; ?>"
                        class="btn btn-hm-primary"
                    >
                        Create Appointment
                    </a>

                <?php endif; ?>

                <!-- Edit Lead -->
                <a
                    href="<?php echo BASE_URL; ?>/leads/edit.php?id=<?php echo $lead_id; ?>"
                    class="btn btn-hm-primary"
                >
                    Edit Lead
                </a>

            </div>

        </div>

    </div>


    <!-- Lead Information -->
    <div class="hm-card p-4">

        <div class="row g-4">

            <!-- Name -->
            <div class="col-md-6">

                <small class="text-muted">
                    Name
                </small>

                <h5>
                    <?php
                    echo htmlspecialchars(
                        $lead['name']
                    );
                    ?>
                </h5>

            </div>


            <!-- Phone -->
            <div class="col-md-6">

                <small class="text-muted">
                    Phone
                </small>

                <h5>
                    <?php
                    echo htmlspecialchars(
                        $lead['phone']
                    );
                    ?>
                </h5>

            </div>


            <!-- Email -->
            <div class="col-md-6">

                <small class="text-muted">
                    Email
                </small>

                <h5>
                    <?php
                    echo htmlspecialchars(
                        $lead['email'] ?? '-'
                    );
                    ?>
                </h5>

            </div>


            <!-- Service Interest -->
            <div class="col-md-6">

                <small class="text-muted">
                    Service Interest
                </small>

                <h5>
                    <?php
                    echo htmlspecialchars(
                        $lead['service_interest'] ?? '-'
                    );
                    ?>
                </h5>

            </div>


            <!-- Source -->
            <div class="col-md-6">

                <small class="text-muted">
                    Source
                </small>

                <h5>
                    <?php
                    echo htmlspecialchars(
                        $lead['source_name'] ?? '-'
                    );
                    ?>
                </h5>

            </div>


            <!-- Referral Partner -->
            <div class="col-md-6">

                <small class="text-muted">
                    Referral Partner
                </small>

                <?php if (!empty($lead['referral_name'])): ?>

                    <h5 class="mb-1">

                        <?php
                        echo htmlspecialchars(
                            $lead['referral_name']
                        );
                        ?>

                    </h5>

                    <?php if (!empty($lead['referral_type'])): ?>

                        <div class="hm-muted">

                            <?php
                            echo htmlspecialchars(
                                $lead['referral_type']
                            );
                            ?>

                        </div>

                    <?php endif; ?>


                    <?php if (!empty($lead['referral_organization'])): ?>

                        <div class="hm-muted">

                            <?php
                            echo htmlspecialchars(
                                $lead['referral_organization']
                            );
                            ?>

                        </div>

                    <?php endif; ?>

                <?php else: ?>

                    <h5 class="hm-muted">
                        -
                    </h5>

                <?php endif; ?>

            </div>


            <!-- Referral Phone -->
            <div class="col-md-6">

                <small class="text-muted">
                    Referral Phone
                </small>

                <h5>

                    <?php
                    echo htmlspecialchars(
                        $lead['referral_phone'] ?? '-'
                    );
                    ?>

                </h5>

            </div>


            <!-- Referral Email -->
            <div class="col-md-6">

                <small class="text-muted">
                    Referral Email
                </small>

                <h5>

                    <?php
                    echo htmlspecialchars(
                        $lead['referral_email'] ?? '-'
                    );
                    ?>

                </h5>

            </div>


            <!-- Status -->
            <div class="col-md-6">

                <small class="text-muted">
                    Status
                </small>

                <h5>

                    <?php
                    echo htmlspecialchars(
                        $lead['status']
                    );
                    ?>

                </h5>

            </div>


            <!-- Priority -->
            <div class="col-md-6">

                <small class="text-muted">
                    Priority
                </small>

                <h5>

                    <?php
                    echo htmlspecialchars(
                        $lead['priority']
                    );
                    ?>

                </h5>

            </div>


            <!-- Assigned To -->
            <div class="col-md-6">

                <small class="text-muted">
                    Assigned To
                </small>

                <h5>

                    <?php
                    echo htmlspecialchars(
                        $lead['assigned_name']
                        ?? 'Not Assigned'
                    );
                    ?>

                </h5>

            </div>


            <!-- Next Action -->
            <div class="col-md-6">

                <small class="text-muted">
                    Next Action
                </small>

                <h5>

                    <?php
                    echo htmlspecialchars(
                        $lead['next_action_type']
                        ?? 'Not Scheduled'
                    );
                    ?>

                </h5>

            </div>


            <!-- Next Action Date & Time -->
            <div class="col-md-6">

                <small class="text-muted">
                    Next Action Date & Time
                </small>

                <h5>

                    <?php

                    if (!empty($lead['next_action_at'])) {

                        echo date(
                            'd M Y, h:i A',
                            strtotime(
                                $lead['next_action_at']
                            )
                        );

                    } else {

                        echo 'Not Scheduled';

                    }

                    ?>

                </h5>

            </div>


            <!-- Created By -->
            <div class="col-md-6">

                <small class="text-muted">
                    Created By
                </small>

                <h5>

                    <?php
                    echo htmlspecialchars(
                        $lead['creator_name']
                    );
                    ?>

                </h5>

            </div>


            <!-- Created On -->
            <div class="col-md-6">

                <small class="text-muted">
                    Created On
                </small>

                <p>

                    <?php
                    echo date(
                        'd M Y, h:i A',
                        strtotime(
                            $lead['created_at']
                        )
                    );
                    ?>

                </p>

            </div>


            <!-- Notes -->
            <div class="col-12">

                <hr>

                <small class="text-muted">
                    Notes
                </small>

                <p class="mt-2">

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $lead['notes']
                            ?? 'No notes added.'
                        )
                    );
                    ?>

                </p>

            </div>


            <!-- Last Updated -->
            <div class="col-md-6">

                <small class="text-muted">
                    Last Updated
                </small>

                <p>

                    <?php
                    echo date(
                        'd M Y, h:i A',
                        strtotime(
                            $lead['updated_at']
                        )
                    );
                    ?>

                </p>

            </div>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>