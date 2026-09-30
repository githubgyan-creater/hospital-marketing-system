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

$user_id = (int) $user['id'];
$user_role = $user['role'];

$page_title = 'Capture Lead from Event';

$error = '';
$success = '';

/*

| Get Event ID

*/

$event_id = filter_input(
    INPUT_GET,
    'event_id',
    FILTER_VALIDATE_INT
);

if (
    $event_id === false ||
    $event_id === null ||
    $event_id <= 0
) {
    exit('Invalid event ID.');
}


/*

| Get Event

*/

$stmt = $pdo->prepare("
    SELECT
        id,
        event_name,
        event_type,
        event_date,
        location,
        status
    FROM events
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([
    $event_id
]);

$event = $stmt->fetch();

if (!$event) {
    exit('Event not found.');
}


/*

| Check Staff Event Access

|
| Admin and Manager can capture leads for any event.
|
| Telecaller and Marketing Executive can capture
| leads only for events assigned to them.

*/

if (
    $user_role === 'telecaller' ||
    $user_role === 'marketing'
) {

    $stmt = $pdo->prepare("
        SELECT id
        FROM event_assignments
        WHERE event_id = ?
          AND user_id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $event_id,
        $user_id
    ]);

    $event_assignment = $stmt->fetch();

    if (!$event_assignment) {

        http_response_code(403);

        exit(
            'You are not assigned to this event.'
        );
    }
}


/*

| Form Values

*/

$name = '';
$phone = '';
$email = '';
$service_interest = '';
$priority = 'Medium';
$notes = '';

$duplicate_lead = null;


/*

| Process Form

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

    $priority = trim(
        $_POST['priority'] ?? 'Medium'
    );

    $notes = trim(
        $_POST['notes'] ?? ''
    );


    /*
    
    | Validation
    
    */

    if ($name === '') {

        $error =
            'Name is required.';

    } elseif ($phone === '') {

        $error =
            'Phone number is required.';

    } elseif (
        $email !== '' &&
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $error =
            'Please enter a valid email address.';

    } elseif (
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

        $error =
            'Invalid priority.';
    }


    /*
    
    | Duplicate Phone Check
    
    */

    if (
        $error === '' &&
        $phone !== ''
    ) {

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                phone,
                status,
                assigned_to
            FROM leads
            WHERE phone = ?
            LIMIT 1
        ");

        $stmt->execute([
            $phone
        ]);

        $duplicate_lead =
            $stmt->fetch();

    }


    /*
    
    | Save Lead / Link Existing Lead
    
    */

    if ($error === '') {

        try {

            $pdo->beginTransaction();


            /*
            
            | Existing Lead
            
            */

            if ($duplicate_lead) {

                $lead_id =
                    (int) $duplicate_lead['id'];


                /*
                
                | Check Existing Event Link
                
                */

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM event_leads
                    WHERE event_id = ?
                      AND lead_id = ?
                    LIMIT 1
                ");

                $stmt->execute([
                    $event_id,
                    $lead_id
                ]);

                $existing_event_lead =
                    $stmt->fetch();


                if ($existing_event_lead) {

                    $pdo->rollBack();

                    $error =
                        'This lead is already linked to this event.';

                } else {

                    /*
                    
                    | Link Existing Lead to Event
                    
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO event_leads (
                            event_id,
                            lead_id,
                            captured_by
                        )
                        VALUES (
                            ?,
                            ?,
                            ?
                        )
                    ");

                    $stmt->execute([
                        $event_id,
                        $lead_id,
                        $user_id
                    ]);


                    /*
                    
                    | Activity
                    
                    */

                    $stmt = $pdo->prepare("
                        INSERT INTO lead_activities (
                            lead_id,
                            user_id,
                            activity_type,
                            description,
                            activity_at
                        )
                        VALUES (
                            ?,
                            ?,
                            'Event Lead Linked',
                            ?,
                            NOW()
                        )
                    ");

                    $stmt->execute([
                        $lead_id,
                        $user_id,
                        'Existing lead linked to event: ' .
                        $event['event_name'] .
                        '.'
                    ]);


                    $pdo->commit();

                    $success =
                        'Existing lead linked to this event successfully.';
                }


            /*
            
            | New Lead
            
            */

            } else {

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

                        NULL,
                        NULL,
                        NULL,

                        'New',
                        ?,
                        ?,

                        NULL,
                        NULL,

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

                    $priority,

                    $user_id,

                    $notes !== ''
                        ? $notes
                        : null,

                    $user_id

                ]);


                /*
                
                | Get New Lead ID
                
                */

                $lead_id =
                    (int) $pdo->lastInsertId();


                /*
                
                | Link Lead to Event
                
                */

                $stmt = $pdo->prepare("
                    INSERT INTO event_leads (
                        event_id,
                        lead_id,
                        captured_by
                    )
                    VALUES (
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $event_id,
                    $lead_id,
                    $user_id
                ]);


                /*
                
                | Lead Created Activity
                
                */

                $stmt = $pdo->prepare("
                    INSERT INTO lead_activities (
                        lead_id,
                        user_id,
                        activity_type,
                        description,
                        activity_at
                    )
                    VALUES (
                        ?,
                        ?,
                        'Lead Created',
                        ?,
                        NOW()
                    )
                ");

                $stmt->execute([
                    $lead_id,
                    $user_id,
                    'Lead captured from event: ' .
                    $event['event_name'] .
                    '.'
                ]);


                /*
                
                | Lead Assigned Activity
                
                */

                $stmt = $pdo->prepare("
                    INSERT INTO lead_activities (
                        lead_id,
                        user_id,
                        activity_type,
                        description,
                        activity_at
                    )
                    VALUES (
                        ?,
                        ?,
                        'Lead Assigned',
                        ?,
                        NOW()
                    )
                ");

                $stmt->execute([
                    $lead_id,
                    $user_id,
                    'Lead automatically assigned to ' .
                    $user['name'] .
                    ' after event capture.'
                ]);


                $pdo->commit();

                $success =
                    'New lead captured and linked to the event successfully.';


                /*
                
                | Clear Form
                
                */

                $name = '';
                $phone = '';
                $email = '';
                $service_interest = '';
                $priority = 'Medium';
                $notes = '';
            }

        } catch (PDOException $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error =
                'Unable to save the event lead.';
        }
    }
}


require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/events/view.php?id=<?php echo (int) $event_id; ?>"
            class="text-decoration-none"
        >
            ← Back to Event
        </a>

        <h1 class="hm-page-title mt-3 mb-1">
            Capture Lead from Event
        </h1>

        <p class="hm-muted mb-0">
            Record a patient or enquiry generated during this event.
        </p>

    </div>


    <!-- Event Information -->

    <div class="hm-card p-4 mb-4">

        <div class="row g-3">

            <div class="col-md-6">

                <small class="text-muted">
                    Event
                </small>

                <h5 class="mb-0">

                    <?php

                    echo htmlspecialchars(
                        $event['event_name']
                    );

                    ?>

                </h5>

            </div>


            <div class="col-md-3">

                <small class="text-muted">
                    Event Type
                </small>

                <div>

                    <?php

                    echo htmlspecialchars(
                        $event['event_type']
                    );

                    ?>

                </div>

            </div>


            <div class="col-md-3">

                <small class="text-muted">
                    Event Date
                </small>

                <div>

                    <?php

                    echo date(
                        'd M Y',
                        strtotime(
                            $event['event_date']
                        )
                    );

                    ?>

                </div>

            </div>

        </div>

    </div>


    <!-- Messages -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?php

            echo htmlspecialchars(
                $success
            );

            ?>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php

            echo htmlspecialchars(
                $error
            );

            ?>

        </div>

    <?php endif; ?>


    <!-- Duplicate Information -->

    <?php if ($duplicate_lead): ?>

        <div class="alert alert-warning">

            <h5 class="alert-heading">
                Existing Lead Found
            </h5>

            <p class="mb-2">

                A lead with this phone number already exists.

            </p>

            <div class="mb-1">

                <strong>
                    Name:
                </strong>

                <?php

                echo htmlspecialchars(
                    $duplicate_lead['name']
                );

                ?>

            </div>

            <div class="mb-1">

                <strong>
                    Phone:
                </strong>

                <?php

                echo htmlspecialchars(
                    $duplicate_lead['phone']
                );

                ?>

            </div>

            <div class="mb-2">

                <strong>
                    Status:
                </strong>

                <?php

                echo htmlspecialchars(
                    $duplicate_lead['status']
                );

                ?>

            </div>

            <small class="hm-muted">

                Submitting the form will link this existing lead
                to the selected event instead of creating a duplicate.

            </small>

        </div>

    <?php endif; ?>


    <!-- Lead Form -->

    <div class="hm-card p-4">

        <form
            method="POST"
            action="<?php echo BASE_URL; ?>/events/capture-lead.php?event_id=<?php echo (int) $event_id; ?>"
        >

            <div class="row g-4">

                <!-- Name -->

                <div class="col-md-6">

                    <label
                        for="name"
                        class="form-label"
                    >
                        Name
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($name); ?>"
                        required
                    >

                </div>


                <!-- Phone -->

                <div class="col-md-6">

                    <label
                        for="phone"
                        class="form-label"
                    >
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        id="phone"
                        class="form-control"
                        value="<?php echo htmlspecialchars($phone); ?>"
                        required
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
                        value="<?php echo htmlspecialchars($email); ?>"
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
                        placeholder="Example: Cardiology"
                        value="<?php echo htmlspecialchars($service_interest); ?>"
                    >

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

                        <?php foreach (
                            [
                                'Low',
                                'Medium',
                                'High'
                            ] as $priority_option
                        ): ?>

                            <option
                                value="<?php echo htmlspecialchars($priority_option); ?>"
                                <?php

                                echo $priority === $priority_option
                                    ? 'selected'
                                    : '';

                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $priority_option
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Captured By -->

                <div class="col-md-6">

                    <label class="form-label">
                        Captured By
                    </label>

                    <input
                        type="text"
                        class="form-control"
                        value="<?php echo htmlspecialchars($user['name']); ?>"
                        readonly
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
                        class="form-control"
                        rows="5"
                        placeholder="Enter enquiry details or remarks..."
                    ><?php echo htmlspecialchars($notes); ?></textarea>

                </div>


                <!-- Buttons -->

                <div class="col-12">

                    <hr>

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Capture Lead
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/events/view.php?id=<?php echo (int) $event_id; ?>"
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