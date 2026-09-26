<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role(
    'admin',
    'manager'
);

$page_title = 'Add Event';

$user = current_user();

$error = '';
$success = '';

/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/

$event_name = '';
$event_type = 'Other';
$event_date = '';
$location = '';
$description = '';
$status = 'Planned';

/*
|--------------------------------------------------------------------------
| Process Form
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $event_name = trim($_POST['event_name'] ?? '');
    $event_type = trim($_POST['event_type'] ?? 'Other');
    $event_date = trim($_POST['event_date'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'Planned');

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
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
        |--------------------------------------------------------------------------
        | Save Event
        |--------------------------------------------------------------------------
        */

        try {

            $stmt = $pdo->prepare("
                INSERT INTO events (
                    event_name,
                    event_type,
                    event_date,
                    location,
                    description,
                    status,
                    created_by
                )
                VALUES (
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
                $user['id']
            ]);

            $success = 'Event created successfully.';

            /*
            |--------------------------------------------------------------------------
            | Clear Form
            |--------------------------------------------------------------------------
            */

            $event_name = '';
            $event_type = 'Other';
            $event_date = '';
            $location = '';
            $description = '';
            $status = 'Planned';

        } catch (PDOException $e) {

            $error = 'Unable to create the event.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/events/"
            class="text-decoration-none"
        >
            ← Back to Events
        </a>

        <h1 class="hm-page-title mt-3 mb-1">
            Add Event
        </h1>

        <p class="hm-muted mb-0">
            Create a hospital event, health camp or outreach activity.
        </p>

    </div>


    <!-- Messages -->

    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?php echo htmlspecialchars($success); ?>

            <a
                href="<?php echo BASE_URL; ?>/events/"
                class="alert-link ms-2"
            >
                View Events
            </a>

        </div>

    <?php endif; ?>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <!-- Event Form -->

    <div class="hm-card p-4">

        <form
            method="POST"
            action=""
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
                        placeholder="Example: Free Cardiology Health Camp"
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
                                <?php echo htmlspecialchars($type); ?>
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
                                <?php echo htmlspecialchars($status_option); ?>
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
                        placeholder="Example: Bhopal Community Hall"
                        value="<?php echo htmlspecialchars($location); ?>"
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
                            Save Event
                        </button>

                        <a
                            href="<?php echo BASE_URL; ?>/events/"
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