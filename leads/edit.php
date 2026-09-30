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

$page_title = 'Edit Lead';

$lead_id = (int) ($_GET['id'] ?? 0);

if ($lead_id <= 0) {
    exit('Invalid lead ID.');
}


/*

| Get Lead

*/

$stmt = $pdo->prepare("
    SELECT *
    FROM leads
    WHERE id = :id
    LIMIT 1
");

$stmt->execute([
    'id' => $lead_id
]);

$lead = $stmt->fetch();

if (!$lead) {
    exit('Lead not found.');
}


/*

| Store Old Values

*/

$old_assigned_to = $lead['assigned_to'];
$old_status = $lead['status'];


/*

| Get Marketing Sources

*/

$source_stmt = $pdo->query("
    SELECT id, name
    FROM marketing_sources
    WHERE status = 'active'
    ORDER BY name ASC
");

$sources = $source_stmt->fetchAll();


/*

| Get Staff

*/

$staff_stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        r.name AS role_name
    FROM users u
    INNER JOIN roles r
        ON u.role_id = r.id
    WHERE
        u.status = 'active'
        AND r.name IN (
            'manager',
            'telecaller',
            'marketing'
        )
    ORDER BY u.name ASC
");

$staff = $staff_stmt->fetchAll();


/*

| Update Lead

*/

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    
    | Get Form Data
    
    */

    $name = trim($_POST['name'] ?? '');

    $phone = trim($_POST['phone'] ?? '');

    $email = trim($_POST['email'] ?? '');

    $service_interest = trim(
        $_POST['service_interest'] ?? ''
    );

    $source_id = !empty($_POST['source_id'])
        ? (int) $_POST['source_id']
        : null;

    $status = trim(
        $_POST['status'] ?? 'New'
    );

    $priority = $_POST['priority'] ?? 'Medium';

    $assigned_to = !empty($_POST['assigned_to'])
        ? (int) $_POST['assigned_to']
        : null;

    $next_action_type = trim(
        $_POST['next_action_type'] ?? ''
    );

    $next_action_at = trim(
        $_POST['next_action_at'] ?? ''
    );

    $notes = trim(
        $_POST['notes'] ?? ''
    );


    /*
    
    | Validation
    
    */

    if ($name === '') {

        $errors[] = 'Name is required.';
    }


    if ($phone === '') {

        $errors[] = 'Phone number is required.';
    }


    if (
        $email !== '' &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            'Please enter a valid email address.';
    }


    if (
        !in_array(
            $priority,
            [
                'Low',
                'Medium',
                'High'
            ],
            true
        )
    ) {

        $errors[] =
            'Invalid priority.';
    }


    /*
    
    | Validate Status
    
    */

    $allowed_statuses = [
        'New',
        'Contacted',
        'Interested',
        'Follow-up',
        'Not Interested',
        'Appointment',
        'Visited',
        'Converted',
        'Lost'
    ];

    if (
        !in_array(
            $status,
            $allowed_statuses,
            true
        )
    ) {

        $errors[] =
            'Invalid status.';
    }


    /*
    
    | Validate Next Action
    
    */

    $allowed_actions = [
        '',
        'Call Lead',
        'WhatsApp',
        'Follow-up',
        'Schedule Appointment',
        'Visit'
    ];

    if (
        !in_array(
            $next_action_type,
            $allowed_actions,
            true
        )
    ) {

        $errors[] =
            'Invalid next action.';
    }


    /*
    
    | Prepare Next Action Date
    
    */

    $formatted_next_action_at = null;

    if ($next_action_at !== '') {

        $date_object = DateTime::createFromFormat(
            'Y-m-d\TH:i',
            $next_action_at
        );

        if (
            !$date_object ||
            $date_object->format('Y-m-d\TH:i')
            !== $next_action_at
        ) {

            $errors[] =
                'Invalid next action date and time.';
        } else {

            $formatted_next_action_at =
                $date_object->format(
                    'Y-m-d H:i:s'
                );
        }
    }


    /*
    
    | Save Changes
    
    */

    if (empty($errors)) {

        $update_stmt = $pdo->prepare("
            UPDATE leads
            SET
                name = :name,
                phone = :phone,
                email = :email,
                service_interest = :service_interest,
                source_id = :source_id,
                status = :status,
                priority = :priority,
                assigned_to = :assigned_to,
                next_action_type = :next_action_type,
                next_action_at = :next_action_at,
                notes = :notes
            WHERE id = :id
        ");


        $update_stmt->execute([

            'name' =>
                $name,

            'phone' =>
                $phone,

            'email' =>
                $email !== ''
                    ? $email
                    : null,

            'service_interest' =>
                $service_interest !== ''
                    ? $service_interest
                    : null,

            'source_id' =>
                $source_id,

            'status' =>
                $status,

            'priority' =>
                $priority,

            'assigned_to' =>
                $assigned_to,

            'next_action_type' =>
                $next_action_type !== ''
                    ? $next_action_type
                    : null,

            'next_action_at' =>
                $formatted_next_action_at,

            'notes' =>
                $notes !== ''
                    ? $notes
                    : null,

            'id' =>
                $lead_id
        ]);


        /*
        
        | Automatic Lead Assigned Activity
        
        */

        if ($old_assigned_to != $assigned_to) {

            if ($assigned_to !== null) {

                /*
                
                | Get Assigned Staff Name
                
                */

                $assigned_stmt = $pdo->prepare("
                    SELECT name
                    FROM users
                    WHERE id = :id
                    LIMIT 1
                ");

                $assigned_stmt->execute([
                    'id' => $assigned_to
                ]);

                $assigned_user =
                    $assigned_stmt->fetch();


                if ($assigned_user) {

                    /*
                    
                    | Insert Lead Assigned Activity
                    
                    */

                    $activity_stmt = $pdo->prepare("
                        INSERT INTO lead_activities (
                            lead_id,
                            user_id,
                            activity_type,
                            description,
                            activity_at
                        )
                        VALUES (
                            :lead_id,
                            :user_id,
                            'Lead Assigned',
                            :description,
                            NOW()
                        )
                    ");

                    $activity_stmt->execute([

                        'lead_id' =>
                            $lead_id,

                        'user_id' =>
                            $user['id'],

                        'description' =>
                            'Lead assigned to ' .
                            $assigned_user['name'] .
                            '.'
                    ]);
                }
            }
        }


        /*
        
        | Automatic Status Changed Activity
        
        */

        if ($old_status !== $status) {

            $activity_stmt = $pdo->prepare("
                INSERT INTO lead_activities (
                    lead_id,
                    user_id,
                    activity_type,
                    description,
                    activity_at
                )
                VALUES (
                    :lead_id,
                    :user_id,
                    'Status Changed',
                    :description,
                    NOW()
                )
            ");

            $activity_stmt->execute([

                'lead_id' =>
                    $lead_id,

                'user_id' =>
                    $user['id'],

                'description' =>
                    'Lead status changed from ' .
                    $old_status .
                    ' to ' .
                    $status .
                    '.'
            ]);
        }


        /*
        
        | Redirect To Lead View
        
        */

        header(
            'Location: ' .
            BASE_URL .
            '/leads/view.php?id=' .
            $lead_id
        );

        exit;
    }
}


require_once __DIR__ . '/../includes/header.php';

?>


<div class="container py-4">


    <!-- Page Header -->

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo $lead_id; ?>"
            class="text-decoration-none"
        >
            ← Back to Lead Details
        </a>


        <h1 class="hm-page-title mt-3 mb-1">
            Edit Lead
        </h1>


        <p class="hm-muted">
            Update the information of this lead.
        </p>

    </div>



    <!-- Error Messages -->

    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <ul class="mb-0">

                <?php foreach ($errors as $error): ?>

                    <li>
                        <?php
                        echo htmlspecialchars(
                            $error
                        );
                        ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>



    <!-- Edit Form -->

    <div class="hm-card p-4">

        <form method="POST">

            <div class="row g-4">


                <!-- Name -->

                <div class="col-md-6">

                    <label class="form-label">
                        Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['name']
                                ?? $lead['name']
                            );
                        ?>"
                        required
                    >

                </div>



                <!-- Phone -->

                <div class="col-md-6">

                    <label class="form-label">
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['phone']
                                ?? $lead['phone']
                            );
                        ?>"
                        required
                    >

                </div>



                <!-- Email -->

                <div class="col-md-6">

                    <label class="form-label">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['email']
                                ?? ($lead['email'] ?? '')
                            );
                        ?>"
                    >

                </div>



                <!-- Service Interest -->

                <div class="col-md-6">

                    <label class="form-label">
                        Service Interest
                    </label>

                    <input
                        type="text"
                        name="service_interest"
                        class="form-control"
                        value="<?php
                            echo htmlspecialchars(
                                $_POST['service_interest']
                                ?? (
                                    $lead['service_interest']
                                    ?? ''
                                )
                            );
                        ?>"
                    >

                </div>



                <!-- Source -->

                <div class="col-md-6">

                    <label class="form-label">
                        Source
                    </label>

                    <?php

                    $selected_source =
                        $_POST['source_id']
                        ?? $lead['source_id'];

                    ?>

                    <select
                        name="source_id"
                        class="form-select"
                    >

                        <option value="">
                            Select Source
                        </option>


                        <?php foreach (
                            $sources
                            as $source
                        ): ?>

                            <option
                                value="<?php
                                    echo $source['id'];
                                ?>"
                                <?php

                                echo (
                                    $selected_source
                                    == $source['id']
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



                <!-- Status -->

                <div class="col-md-6">

                    <label class="form-label">
                        Status
                    </label>


                    <?php

                    $current_status =
                        $_POST['status']
                        ?? $lead['status'];

                    $statuses = [

                        'New',

                        'Contacted',

                        'Interested',

                        'Follow-up',

                        'Not Interested',

                        'Appointment',

                        'Visited',

                        'Converted',

                        'Lost'

                    ];

                    ?>


                    <select
                        name="status"
                        class="form-select"
                    >

                        <?php foreach (
                            $statuses
                            as $status_option
                        ): ?>

                            <option
                                value="<?php
                                    echo htmlspecialchars(
                                        $status_option
                                    );
                                ?>"
                                <?php

                                echo (
                                    $current_status
                                    === $status_option
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

                    <label class="form-label">
                        Priority
                    </label>


                    <?php

                    $current_priority =
                        $_POST['priority']
                        ?? $lead['priority'];

                    ?>


                    <select
                        name="priority"
                        class="form-select"
                    >

                        <option
                            value="Low"
                            <?php

                            echo (
                                $current_priority
                                === 'Low'
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
                                $current_priority
                                === 'Medium'
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
                                $current_priority
                                === 'High'
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

                    <label class="form-label">
                        Assigned To
                    </label>


                    <?php

                    $current_assigned =
                        $_POST['assigned_to']
                        ?? $lead['assigned_to'];

                    ?>


                    <select
                        name="assigned_to"
                        class="form-select"
                    >

                        <option value="">
                            Not Assigned
                        </option>


                        <?php foreach (
                            $staff
                            as $member
                        ): ?>

                            <option
                                value="<?php
                                    echo $member['id'];
                                ?>"
                                <?php

                                echo (
                                    $current_assigned
                                    == $member['id']
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
                                    $member['role_name']
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- Next Action -->

                <div class="col-md-6">

                    <label class="form-label">
                        Next Action
                    </label>


                    <?php

                    $current_action =
                        $_POST['next_action_type']
                        ?? (
                            $lead['next_action_type']
                            ?? ''
                        );

                    ?>


                    <select
                        name="next_action_type"
                        class="form-select"
                    >

                        <option value="">
                            Select Next Action
                        </option>


                        <option
                            value="Call Lead"
                            <?php

                            echo (
                                $current_action
                                === 'Call Lead'
                            )
                                ? 'selected'
                                : '';

                            ?>
                        >
                            Call Lead
                        </option>


                        <option
                            value="WhatsApp"
                            <?php

                            echo (
                                $current_action
                                === 'WhatsApp'
                            )
                                ? 'selected'
                                : '';

                            ?>
                        >
                            WhatsApp
                        </option>


                        <option
                            value="Follow-up"
                            <?php

                            echo (
                                $current_action
                                === 'Follow-up'
                            )
                                ? 'selected'
                                : '';

                            ?>
                        >
                            Follow-up
                        </option>


                        <option
                            value="Schedule Appointment"
                            <?php

                            echo (
                                $current_action
                                === 'Schedule Appointment'
                            )
                                ? 'selected'
                                : '';

                            ?>
                        >
                            Schedule Appointment
                        </option>


                        <option
                            value="Visit"
                            <?php

                            echo (
                                $current_action
                                === 'Visit'
                            )
                                ? 'selected'
                                : '';

                            ?>
                        >
                            Visit
                        </option>

                    </select>

                </div>



                <!-- Next Action Date & Time -->

                <div class="col-md-6">

                    <label class="form-label">
                        Next Action Date & Time
                    </label>


                    <?php

                    $current_action_at =
                        $_POST['next_action_at']
                        ?? (
                            $lead['next_action_at']
                            ?? ''
                        );


                    if (
                        $current_action_at !== ''
                    ) {

                        $current_action_at =
                            date(
                                'Y-m-d\TH:i',
                                strtotime(
                                    $current_action_at
                                )
                            );
                    }

                    ?>


                    <input
                        type="datetime-local"
                        name="next_action_at"
                        class="form-control"
                        value="<?php

                            echo htmlspecialchars(
                                $current_action_at
                            );

                        ?>"
                    >

                </div>



                <!-- Notes -->

                <div class="col-12">

                    <label class="form-label">
                        Notes
                    </label>


                    <textarea
                        name="notes"
                        class="form-control"
                        rows="4"
                    ><?php

                        echo htmlspecialchars(
                            $_POST['notes']
                            ?? (
                                $lead['notes']
                                ?? ''
                            )
                        );

                    ?></textarea>

                </div>



                <!-- Buttons -->

                <div class="col-12">

                    <hr>


                    <a
                        href="<?php
                            echo BASE_URL;
                        ?>/leads/view.php?id=<?php
                            echo $lead_id;
                        ?>"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Update Lead
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>



<?php

require_once __DIR__ . '/../includes/footer.php';

?>