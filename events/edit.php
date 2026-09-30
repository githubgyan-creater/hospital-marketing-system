<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role(
    'admin',
    'manager'
);

$page_title = 'Edit Event';

/*

| Get Event ID

*/

$event_id = filter_input(
    INPUT_GET,
    'id',
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

| Get Existing Event

*/

$stmt = $pdo->prepare("
    SELECT
        *
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

| Form Values

*/

$event_name = $event['event_name'];
$event_type = $event['event_type'];
$event_date = $event['event_date'];
$location = $event['location'] ?? '';
$description = $event['description'] ?? '';
$status = $event['status'];

$error = '';
$success = '';

/*

| Process Form

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $event_name = trim(
        $_POST['event_name'] ?? ''
    );

    $event_type = trim(
        $_POST['event_type'] ?? ''
    );

    $event_date = trim(
        $_POST['event_date'] ?? ''
    );

    $location = trim(
        $_POST['location'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );

    $status = trim(
        $_POST['status'] ?? ''
    );

    /*
    
    | Allowed Values
    
    */

    $allowed_event_types = [
        'Health Camp',
        'Outreach',
        'Seminar',
        'Workshop',
        'Doctor Meet',
        'Corporate Event',
        'Other'
    ];

    $allowed_statuses = [
        'Planned',
        'Ongoing',
        'Completed',
        'Cancelled'
    ];

    /*
    
    | Validation
    
    */

    if ($event_name === '') {

        $error = 'Please enter the event name.';

    } elseif (
        !in_array(
            $event_type,
            $allowed_event_types,
            true
        )
    ) {

        $error = 'Please select a valid event type.';

    } elseif ($event_date === '') {

        $error = 'Please select the event date.';

    } elseif (
        !in_array(
            $status,
            $allowed_statuses,
            true
        )
    ) {

        $error = 'Please select a valid event status.';

    } else {

        /*
        
        | Update Event
        
        */

        try {

            $stmt = $pdo->prepare("
                UPDATE events
                SET
                    event_name = ?,
                    event_type = ?,
                    event_date = ?,
                    location = ?,
                    description = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $event_name,
                $event_type,
                $event_date,
                $location !== ''
                    ? $location
                    : null,
                $description !== ''
                    ? $description
                    : null,
                $status,
                $event_id
            ]);

            $success =
                'Event updated successfully.';

            /*
            
            | Refresh Event Data
            
            */

            $stmt = $pdo->prepare("
                SELECT
                    *
                FROM events
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->execute([
                $event_id
            ]);

            $event = $stmt->fetch();

            if ($event) {

                $event_name =
                    $event['event_name'];

                $event_type =
                    $event['event_type'];

                $event_date =
                    $event['event_date'];

                $location =
                    $event['location'] ?? '';

                $description =
                    $event['description'] ?? '';

                $status =
                    $event['status'];
            }

        } catch (PDOException $e) {

            $error =
                'Unable to update the event.';
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
            Edit Event
        </h1>

        <p class="hm-muted mb-0">
            Update the event information.
        </p>

    </div>


    <!-- Messages -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?php
            echo htmlspecialchars($success);
            ?>

            <a
                href="<?php echo BASE_URL; ?>/events/view.php?id=<?php echo (int) $event_id; ?>"
                class="alert-link ms-2"
            >
                View Event
            </a>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <!-- Edit Form -->

    <div class="hm-card p-4">

        <form
            method="POST"
            action="<?php echo BASE_URL; ?>/events/edit.php?id=<?php echo (int) $event_id; ?>"
        >

            <div class="row g-4">

                <!-- Event Name -->

                <div class="col-md-8">

                    <label
                        for="event_name"
                        class="form-label"
                    >
                        Event Name
                    </label>

                    <input
                        type="text"
                        name="event_name"
                        id="event_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($event_name); ?>"
                        required
                    >

                </div>


                <!-- Event Type -->

                <div class="col-md-4">

                    <label
                        for="event_type"
                        class="form-label"
                    >
                        Event Type
                    </label>

                    <select
                        name="event_type"
                        id="event_type"
                        class="form-select"
                        required
                    >

                        <?php foreach (
                            [
                                'Health Camp',
                                'Outreach',
                                'Seminar',
                                'Workshop',
                                'Doctor Meet',
                                'Corporate Event',
                                'Other'
                            ] as $type
                        ): ?>

                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php
                                echo $event_type === $type
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                <?php
                                echo htmlspecialchars($type);
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Event Date -->

                <div class="col-md-6">

                    <label
                        for="event_date"
                        class="form-label"
                    >
                        Event Date
                    </label>

                    <input
                        type="date"
                        name="event_date"
                        id="event_date"
                        class="form-control"
                        value="<?php echo htmlspecialchars($event_date); ?>"
                        required
                    >

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
                        required
                    >

                        <?php foreach (
                            [
                                'Planned',
                                'Ongoing',
                                'Completed',
                                'Cancelled'
                            ] as $status_option
                        ): ?>

                            <option
                                value="<?php echo htmlspecialchars($status_option); ?>"
                                <?php
                                echo $status === $status_option
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                <?php
                                echo htmlspecialchars($status_option);
                                ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Location -->

                <div class="col-12">

                    <label
                        for="location"
                        class="form-label"
                    >
                        Location
                    </label>

                    <input
                        type="text"
                        name="location"
                        id="location"
                        class="form-control"
                        value="<?php echo htmlspecialchars($location); ?>"
                        placeholder="Example: Bhopal Community Hall"
                    >

                </div>


                <!-- Description -->

                <div class="col-12">

                    <label
                        for="description"
                        class="form-label"
                    >
                        Description
                    </label>

                    <textarea
                        name="description"
                        id="description"
                        class="form-control"
                        rows="5"
                        placeholder="Enter event details, purpose, target audience, services, etc."
                    ><?php echo htmlspecialchars($description); ?></textarea>

                </div>


                <!-- Buttons -->

                <div class="col-12">

                    <hr>

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-hm-primary"
                        >
                            Update Event
                        </button>

                        <a
                            href="<?php echo BASE_URL; ?>/events/view.php?id=<?php echo (int) $event_id; ?>"
                            class="btn btn-outline-secondary"
                        >
                            Cancel
                        </a>

                    </div>

                </div>

            </div>

        </form>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>