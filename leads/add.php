 <?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role(
    'admin',
    'manager',
    'marketing'
);

$user = current_user();

$error = '';

/*

| Get Marketing Sources

*/

$source_stmt = $pdo->query("
    SELECT
        id,
        name
    FROM marketing_sources
    WHERE status = 'active'
    ORDER BY name ASC
");

$sources = $source_stmt->fetchAll();

/*

| Get Active / Planned Campaigns

*/

$campaign_stmt = $pdo->query("
    SELECT
        id,
        name,
        campaign_type,
        status
    FROM campaigns
    WHERE status IN ('Planned', 'Active')
    ORDER BY name ASC
");

$campaigns = $campaign_stmt->fetchAll();

/*

| Get Active Referral Partners

*/

$referral_stmt = $pdo->query("
    SELECT
        id,
        name,
        referral_type,
        organization
    FROM referrals
    WHERE status = 'Active'
    ORDER BY name ASC
");

$referrals = $referral_stmt->fetchAll();

/*

| Get Staff for Assignment

*/

$staff_stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        r.name AS role_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.status = 'active'

      AND r.name IN (
          'manager',
          'telecaller',
          'marketing'
      )

    ORDER BY u.name ASC
");

$staff = $staff_stmt->fetchAll();

/*

| Handle Form Submission

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    
    | Basic Lead Information
    
    */

    $name = trim(
        $_POST['name'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $service_interest = trim(
        $_POST['service_interest'] ?? ''
    );

    /*
    
    | Source / Campaign / Referral
    
    */

    $source_id = !empty($_POST['source_id'])
        ? (int) $_POST['source_id']
        : null;

    $campaign_id = !empty($_POST['campaign_id'])
        ? (int) $_POST['campaign_id']
        : null;

    $referral_id = !empty($_POST['referral_id'])
        ? (int) $_POST['referral_id']
        : null;

    /*
    
    | Lead Status / Priority
    
    */

    $status = trim(
        $_POST['status'] ?? 'New'
    );

    $priority = trim(
        $_POST['priority'] ?? 'Medium'
    );

    /*
    
    | Assignment
    
    */

    $assigned_to = !empty($_POST['assigned_to'])
        ? (int) $_POST['assigned_to']
        : null;

    /*
    
    | Next Action
    
    */

    $next_action_type = trim(
        $_POST['next_action_type'] ?? ''
    );

    $next_action_at = trim(
        $_POST['next_action_at'] ?? ''
    );

    /*
    
    | Notes
    
    */

    $notes = trim(
        $_POST['notes'] ?? ''
    );

    /*
    
    | Validation
    
    */

    if ($name === '') {

        $error = 'Lead name is required.';

    } elseif ($phone === '') {

        $error = 'Phone number is required.';

    } elseif (
        $email !== '' &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error = 'Please enter a valid email address.';

    } elseif (
        !in_array(
            $priority,
            ['Low', 'Medium', 'High'],
            true
        )
    ) {

        $error = 'Please select a valid priority.';

    } elseif (
        $status === ''
    ) {

        $error = 'Lead status is required.';

    }

    /*
    
    | Duplicate Phone Check
    
    */

    if ($error === '') {

        $duplicate_stmt = $pdo->prepare("
            SELECT id, name
            FROM leads
            WHERE phone = ?
            LIMIT 1
        ");

        $duplicate_stmt->execute([
            $phone
        ]);

        $duplicate = $duplicate_stmt->fetch();

        if ($duplicate) {

            $error =
                'A lead with this phone number already exists: ' .
                $duplicate['name'] .
                ' (Lead #' .
                $duplicate['id'] .
                ').';

        }
    }

    /*
    
    | Validate Source
    
    */

    if (
        $error === '' &&
        $source_id !== null
    ) {

        $source_check = $pdo->prepare("
            SELECT id
            FROM marketing_sources
            WHERE id = ?
              AND status = 'active'
            LIMIT 1
        ");

        $source_check->execute([
            $source_id
        ]);

        if (!$source_check->fetch()) {

            $error =
                'Please select a valid marketing source.';

        }
    }

    /*
    
    | Validate Campaign
    
    */

    if (
        $error === '' &&
        $campaign_id !== null
    ) {

        $campaign_check = $pdo->prepare("
            SELECT id
            FROM campaigns
            WHERE id = ?
              AND status IN ('Planned', 'Active')
            LIMIT 1
        ");

        $campaign_check->execute([
            $campaign_id
        ]);

        if (!$campaign_check->fetch()) {

            $error =
                'Please select a valid active or planned campaign.';

        }
    }

    /*
    
    | Validate Referral
    
    */

    if (
        $error === '' &&
        $referral_id !== null
    ) {

        $referral_check = $pdo->prepare("
            SELECT id
            FROM referrals
            WHERE id = ?
              AND status = 'Active'
            LIMIT 1
        ");

        $referral_check->execute([
            $referral_id
        ]);

        if (!$referral_check->fetch()) {

            $error =
                'Please select a valid active referral partner.';

        }
    }

    /*
    
    | Validate Assigned User
    
    */

    if (
        $error === '' &&
        $assigned_to !== null
    ) {

        $assigned_check = $pdo->prepare("
            SELECT
                u.id,
                r.name AS role_name

            FROM users u

            INNER JOIN roles r
                ON u.role_id = r.id

            WHERE u.id = ?
              AND u.status = 'active'

              AND r.name IN (
                  'manager',
                  'telecaller',
                  'marketing'
              )

            LIMIT 1
        ");

        $assigned_check->execute([
            $assigned_to
        ]);

        if (!$assigned_check->fetch()) {

            $error =
                'Please select a valid active staff member.';

        }
    }

    /*
    
    | Convert Next Action Date
    
    */

    $next_action_database = null;

    if (
        $error === '' &&
        $next_action_at !== ''
    ) {

        $next_action_database =
            str_replace(
                'T',
                ' ',
                $next_action_at
            );

        $date_check = DateTime::createFromFormat(
            'Y-m-d H:i',
            $next_action_database
        );

        if (!$date_check) {

            $error =
                'Please enter a valid next action date and time.';

        }
    }

    /*
    
    | Insert Lead
    
    */

    if ($error === '') {

        try {

            $pdo->beginTransaction();

            /*
            
            | Create Lead
            
            */

            $stmt = $pdo->prepare("
                INSERT INTO leads (
                    name,
                    phone,
                    email,
                    service_interest,
                    source_id,
                    campaign_id,
                    referral_id,
                    status,
                    priority,
                    assigned_to,
                    next_action_type,
                    next_action_at,
                    notes,
                    created_by
                )

                VALUES (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
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
                $name,

                $phone,

                $email !== ''
                    ? $email
                    : null,

                $service_interest !== ''
                    ? $service_interest
                    : null,

                $source_id,

                $campaign_id,

                $referral_id,

                $status,

                $priority,

                $assigned_to,

                $next_action_type !== ''
                    ? $next_action_type
                    : null,

                $next_action_database,

                $notes !== ''
                    ? $notes
                    : null,

                $user['id']
            ]);

            $lead_id = (int) $pdo->lastInsertId();

            /*
            
            | Lead Created Activity
            
            */

            $activity_stmt = $pdo->prepare("
                INSERT INTO lead_activities (
                    lead_id,
                    user_id,
                    activity_type,
                    description
                )

                VALUES (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

            $activity_stmt->execute([
                $lead_id,
                $user['id'],
                'Lead Created',
                'Lead created by ' . $user['name'] . '.'
            ]);

            /*
            
            | Assignment Activity
            
            */

            if ($assigned_to !== null) {

                $assigned_name_stmt = $pdo->prepare("
                    SELECT name
                    FROM users
                    WHERE id = ?
                    LIMIT 1
                ");

                $assigned_name_stmt->execute([
                    $assigned_to
                ]);

                $assigned_user =
                    $assigned_name_stmt->fetch();

                if ($assigned_user) {

                    $activity_stmt->execute([
                        $lead_id,
                        $user['id'],
                        'Lead Assigned',
                        'Lead assigned to ' .
                        $assigned_user['name'] .
                        '.'
                    ]);
                }
            }

            /*
            
            | Campaign Activity
            
            */

            if ($campaign_id !== null) {

                $campaign_name_stmt = $pdo->prepare("
                    SELECT name
                    FROM campaigns
                    WHERE id = ?
                    LIMIT 1
                ");

                $campaign_name_stmt->execute([
                    $campaign_id
                ]);

                $campaign = $campaign_name_stmt->fetch();

                if ($campaign) {

                    $activity_stmt->execute([
                        $lead_id,
                        $user['id'],
                        'Campaign Assigned',
                        'Lead linked to campaign: ' .
                        $campaign['name'] .
                        '.'
                    ]);
                }
            }

            /*
            
            | Referral Activity
            
            */

            if ($referral_id !== null) {

                $referral_name_stmt = $pdo->prepare("
                    SELECT
                        name,
                        organization
                    FROM referrals
                    WHERE id = ?
                    LIMIT 1
                ");

                $referral_name_stmt->execute([
                    $referral_id
                ]);

                $referral = $referral_name_stmt->fetch();

                if ($referral) {

                    $referral_display =
                        $referral['name'];

                    if (
                        !empty(
                            $referral['organization']
                        )
                    ) {

                        $referral_display .=
                            ' - ' .
                            $referral['organization'];
                    }

                    $activity_stmt->execute([
                        $lead_id,
                        $user['id'],
                        'Referral Assigned',
                        'Lead linked to referral partner: ' .
                        $referral_display .
                        '.'
                    ]);
                }
            }

            $pdo->commit();

            /*
            
            | Redirect to Lead View
            
            */

            header(
                'Location: ' .
                BASE_URL .
                '/leads/view.php?id=' .
                $lead_id
            );

            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'Unable to create lead: ' .
                $e->getMessage();
        }
    }
}

$page_title = 'Add Lead';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Add Lead
            </h2>

            <p class="hm-muted mb-0">
                Create a new marketing lead or enquiry.
            </p>

        </div>

        <a
            href="<?php echo BASE_URL; ?>/leads/"
            class="btn btn-outline-secondary"
        >
            Back to Leads
        </a>

    </div>

    <!-- Error -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>

    <!-- Lead Form -->

    <div class="hm-card p-4">

        <form method="POST">

            <div class="row g-3">

                <!-- Name -->

                <div class="col-md-6">

                    <label
                        for="name"
                        class="form-label"
                    >
                        Lead Name
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control"
                        required
                        value="<?php echo htmlspecialchars(
                            $_POST['name'] ?? ''
                        ); ?>"
                        placeholder="Enter lead name"
                    >

                </div>

                <!-- Phone -->

                <div class="col-md-6">

                    <label
                        for="phone"
                        class="form-label"
                    >
                        Phone
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="phone"
                        id="phone"
                        class="form-control"
                        required
                        value="<?php echo htmlspecialchars(
                            $_POST['phone'] ?? ''
                        ); ?>"
                        placeholder="Enter phone number"
                    >

                </div>

                <!-- Email -->

                <div class="col-md-6">

                    <label
                        for="email"
                        class="form-label"
                    >
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $_POST['email'] ?? ''
                        ); ?>"
                        placeholder="Enter email address"
                    >

                </div>

                <!-- Service Interest -->

                <div class="col-md-6">

                    <label
                        for="service_interest"
                        class="form-label"
                    >
                        Service Interest
                    </label>

                    <input
                        type="text"
                        name="service_interest"
                        id="service_interest"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $_POST['service_interest'] ?? ''
                        ); ?>"
                        placeholder="Example: Cardiology"
                    >

                </div>

                <!-- Source -->

                <div class="col-md-6">

                    <label
                        for="source_id"
                        class="form-label"
                    >
                        Marketing Source
                    </label>

                    <select
                        name="source_id"
                        id="source_id"
                        class="form-select"
                    >

                        <option value="">
                            -- Select Source --
                        </option>

                        <?php foreach ($sources as $source): ?>

                            <option
                                value="<?php echo (int) $source['id']; ?>"
                                <?php
                                echo (
                                    (int) (
                                        $_POST['source_id']
                                        ?? 0
                                    )
                                    ===
                                    (int) $source['id']
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $source['name']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- Campaign -->

                <div class="col-md-6">

                    <label
                        for="campaign_id"
                        class="form-label"
                    >
                        Campaign
                    </label>

                    <select
                        name="campaign_id"
                        id="campaign_id"
                        class="form-select"
                    >

                        <option value="">
                            -- No Campaign --
                        </option>

                        <?php foreach ($campaigns as $campaign): ?>

                            <option
                                value="<?php echo (int) $campaign['id']; ?>"
                                <?php
                                echo (
                                    (int) (
                                        $_POST['campaign_id']
                                        ?? 0
                                    )
                                    ===
                                    (int) $campaign['id']
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $campaign['name']
                                );
                                ?>

                                -
                                <?php
                                echo htmlspecialchars(
                                    $campaign['campaign_type']
                                );
                                ?>

                                [
                                <?php
                                echo htmlspecialchars(
                                    $campaign['status']
                                );
                                ?>
                                ]

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- Referral Partner -->

                <div class="col-md-6">

                    <label
                        for="referral_id"
                        class="form-label"
                    >
                        Referral Partner
                    </label>

                    <select
                        name="referral_id"
                        id="referral_id"
                        class="form-select"
                    >

                        <option value="">
                            -- No Referral / Direct Lead --
                        </option>

                        <?php foreach ($referrals as $referral): ?>

                            <option
                                value="<?php echo (int) $referral['id']; ?>"
                                <?php
                                echo (
                                    (int) (
                                        $_POST['referral_id']
                                        ?? 0
                                    )
                                    ===
                                    (int) $referral['id']
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $referral['name']
                                );
                                ?>

                                <?php if (
                                    !empty(
                                        $referral['organization']
                                    )
                                ): ?>

                                    -
                                    <?php
                                    echo htmlspecialchars(
                                        $referral['organization']
                                    );
                                    ?>

                                <?php endif; ?>

                                (
                                <?php
                                echo htmlspecialchars(
                                    $referral['referral_type']
                                );
                                ?>
                                )

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <div class="form-text">
                        Select the referral partner who generated this lead.
                    </div>

                </div>

                <!-- Status -->

                <div class="col-md-6">

                    <label
                        for="status"
                        class="form-label"
                    >
                        Status
                    </label>

                    <select
                        name="status"
                        id="status"
                        class="form-select"
                    >

                        <?php
                        $statuses = [
                            'New',
                            'Contacted',
                            'Interested',
                            'Not Interested',
                            'Appointment Requested',
                            'Converted',
                            'Lost'
                        ];
                        ?>

                        <?php foreach ($statuses as $status_option): ?>

                            <option
                                value="<?php echo htmlspecialchars($status_option); ?>"
                                <?php
                                echo (
                                    (
                                        $_POST['status']
                                        ?? 'New'
                                    )
                                    ===
                                    $status_option
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $status_option
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- Priority -->

                <div class="col-md-6">

                    <label
                        for="priority"
                        class="form-label"
                    >
                        Priority
                    </label>

                    <select
                        name="priority"
                        id="priority"
                        class="form-select"
                    >

                        <option
                            value="Low"
                            <?php
                            echo (
                                (
                                    $_POST['priority']
                                    ?? 'Medium'
                                ) === 'Low'
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Low
                        </option>

                        <option
                            value="Medium"
                            <?php
                            echo (
                                (
                                    $_POST['priority']
                                    ?? 'Medium'
                                ) === 'Medium'
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Medium
                        </option>

                        <option
                            value="High"
                            <?php
                            echo (
                                (
                                    $_POST['priority']
                                    ?? ''
                                ) === 'High'
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >
                            High
                        </option>

                    </select>

                </div>

                <!-- Assigned To -->

                <div class="col-md-6">

                    <label
                        for="assigned_to"
                        class="form-label"
                    >
                        Assigned To
                    </label>

                    <select
                        name="assigned_to"
                        id="assigned_to"
                        class="form-select"
                    >

                        <option value="">
                            -- Unassigned --
                        </option>

                        <?php foreach ($staff as $member): ?>

                            <option
                                value="<?php echo (int) $member['id']; ?>"
                                <?php
                                echo (
                                    (int) (
                                        $_POST['assigned_to']
                                        ?? 0
                                    )
                                    ===
                                    (int) $member['id']
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $member['name']
                                );
                                ?>

                                -
                                <?php
                                echo htmlspecialchars(
                                    ucfirst(
                                        $member['role_name']
                                    )
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <!-- Next Action Type -->

                <div class="col-md-6">

                    <label
                        for="next_action_type"
                        class="form-label"
                    >
                        Next Action
                    </label>

                    <input
                        type="text"
                        name="next_action_type"
                        id="next_action_type"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $_POST['next_action_type'] ?? ''
                        ); ?>"
                        placeholder="Example: Call, Follow-up, Visit"
                    >

                </div>

                <!-- Next Action Date -->

                <div class="col-md-6">

                    <label
                        for="next_action_at"
                        class="form-label"
                    >
                        Next Action Date & Time
                    </label>

                    <input
                        type="datetime-local"
                        name="next_action_at"
                        id="next_action_at"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $_POST['next_action_at'] ?? ''
                        ); ?>"
                    >

                </div>

                <!-- Notes -->

                <div class="col-12">

                    <label
                        for="notes"
                        class="form-label"
                    >
                        Notes
                    </label>

                    <textarea
                        name="notes"
                        id="notes"
                        rows="4"
                        class="form-control"
                        placeholder="Enter lead notes"
                    ><?php
                    echo htmlspecialchars(
                        $_POST['notes'] ?? ''
                    );
                    ?></textarea>

                </div>

                <!-- Buttons -->

                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Save Lead
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/leads/"
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