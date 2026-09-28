<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');

$user = current_user();

$page_title = 'Add Visit';

$errors = [];


/*
|--------------------------------------------------------------------------
| Allowed Values
|--------------------------------------------------------------------------
*/

$visit_types = [
    'Doctor Visit',
    'Clinic Visit',
    'Corporate Visit',
    'Field Visit'
];


/*
|--------------------------------------------------------------------------
| Marketing Staff
|--------------------------------------------------------------------------
*/

$staff_stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        u.email,
        r.name AS role_name,
        r.display_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.status = 'active'

      AND (
          LOWER(r.name) = 'marketing'
          OR LOWER(r.name) = 'marketing executive'
      )

    ORDER BY u.name ASC
");

$staff = $staff_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Tasks
|--------------------------------------------------------------------------
*/

$task_stmt = $pdo->query("
    SELECT
        id,
        title,
        assigned_to,
        status,
        due_date

    FROM tasks

    WHERE status IN (
        'Pending',
        'In Progress'
    )

    ORDER BY due_date ASC

    LIMIT 500
");

$tasks = $task_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Leads
|--------------------------------------------------------------------------
*/

$lead_stmt = $pdo->query("
    SELECT
        id,
        name,
        phone,
        service_interest,
        status

    FROM leads

    WHERE status NOT IN (
        'Converted',
        'Lost'
    )

    ORDER BY created_at DESC

    LIMIT 500
");

$leads = $lead_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Form Values
|--------------------------------------------------------------------------
*/

$visit_type = '';

$title = '';

$person_name = '';

$organization_name = '';

$phone = '';

$email = '';

$location = '';

$purpose = '';

$visit_date = '';

$assigned_to = '';

$related_task_id = '';

$related_lead_id = '';


/*
|--------------------------------------------------------------------------
| Form Submit
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $visit_type = trim(
        $_POST['visit_type'] ?? ''
    );

    $title = trim(
        $_POST['title'] ?? ''
    );

    $person_name = trim(
        $_POST['person_name'] ?? ''
    );

    $organization_name = trim(
        $_POST['organization_name'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $location = trim(
        $_POST['location'] ?? ''
    );

    $purpose = trim(
        $_POST['purpose'] ?? ''
    );

    $visit_date = trim(
        $_POST['visit_date'] ?? ''
    );

    $assigned_to = !empty(
        $_POST['assigned_to']
    )
        ? (int) $_POST['assigned_to']
        : 0;

    $related_task_id = !empty(
        $_POST['related_task_id']
    )
        ? (int) $_POST['related_task_id']
        : null;

    $related_lead_id = !empty(
        $_POST['related_lead_id']
    )
        ? (int) $_POST['related_lead_id']
        : null;


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        !in_array(
            $visit_type,
            $visit_types,
            true
        )
    ) {

        $errors[] =
            'Please select a valid visit type.';
    }


    if ($title === '') {

        $errors[] =
            'Visit title is required.';
    }


    if ($assigned_to <= 0) {

        $errors[] =
            'Please select a Marketing Executive.';
    }


    if ($visit_date === '') {

        $errors[] =
            'Visit date and time are required.';
    }


    if (
        $email !== ''
        && !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errors[] =
            'Please enter a valid email address.';
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Staff
    |--------------------------------------------------------------------------
    */

    if ($assigned_to > 0) {

        $staff_check = $pdo->prepare("
            SELECT
                u.id

            FROM users u

            INNER JOIN roles r
                ON u.role_id = r.id

            WHERE u.id = ?

              AND u.status = 'active'

              AND (
                  LOWER(r.name) = 'marketing'
                  OR LOWER(r.name) = 'marketing executive'
              )

            LIMIT 1
        ");

        $staff_check->execute([
            $assigned_to
        ]);

        if (!$staff_check->fetch()) {

            $errors[] =
                'Selected Marketing Executive is not available.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Visit Date
    |--------------------------------------------------------------------------
    */

    $visit_date_mysql = null;

    if ($visit_date !== '') {

        $timestamp = strtotime(
            str_replace(
                'T',
                ' ',
                $visit_date
            )
        );

        if ($timestamp === false) {

            $errors[] =
                'Invalid visit date and time.';

        } else {

            $visit_date_mysql =
                date(
                    'Y-m-d H:i:s',
                    $timestamp
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Task
    |--------------------------------------------------------------------------
    */

    if (
        $related_task_id !== null
        && $related_task_id > 0
    ) {

        $task_check = $pdo->prepare("
            SELECT
                id
            FROM tasks
            WHERE id = ?
            LIMIT 1
        ");

        $task_check->execute([
            $related_task_id
        ]);

        if (!$task_check->fetch()) {

            $errors[] =
                'Selected task was not found.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Lead
    |--------------------------------------------------------------------------
    */

    if (
        $related_lead_id !== null
        && $related_lead_id > 0
    ) {

        $lead_check = $pdo->prepare("
            SELECT
                id
            FROM leads
            WHERE id = ?
            LIMIT 1
        ");

        $lead_check->execute([
            $related_lead_id
        ]);

        if (!$lead_check->fetch()) {

            $errors[] =
                'Selected lead was not found.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Insert Visit
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $stmt = $pdo->prepare("
            INSERT INTO marketing_visits
            (
                visit_type,
                title,
                person_name,
                organization_name,
                phone,
                email,
                location,
                purpose,
                visit_date,
                assigned_to,
                related_task_id,
                related_lead_id,
                status,
                created_by
            )

            VALUES
            (
                :visit_type,
                :title,
                :person_name,
                :organization_name,
                :phone,
                :email,
                :location,
                :purpose,
                :visit_date,
                :assigned_to,
                :related_task_id,
                :related_lead_id,
                'Planned',
                :created_by
            )
        ");


        $stmt->execute([

            'visit_type' =>
                $visit_type,

            'title' =>
                $title,

            'person_name' =>
                $person_name !== ''
                    ? $person_name
                    : null,

            'organization_name' =>
                $organization_name !== ''
                    ? $organization_name
                    : null,

            'phone' =>
                $phone !== ''
                    ? $phone
                    : null,

            'email' =>
                $email !== ''
                    ? $email
                    : null,

            'location' =>
                $location !== ''
                    ? $location
                    : null,

            'purpose' =>
                $purpose !== ''
                    ? $purpose
                    : null,

            'visit_date' =>
                $visit_date_mysql,

            'assigned_to' =>
                $assigned_to,

            'related_task_id' =>
                $related_task_id,

            'related_lead_id' =>
                $related_lead_id,

            'created_by' =>
                $user['id']
        ]);


        /*
        |--------------------------------------------------------------------------
        | Create Activity
        |--------------------------------------------------------------------------
        */

        if ($related_lead_id !== null) {

            $activity_stmt = $pdo->prepare("
                INSERT INTO lead_activities
                (
                    lead_id,
                    user_id,
                    activity_type,
                    description,
                    activity_at
                )

                VALUES
                (
                    ?,
                    ?,
                    'Marketing Visit Planned',
                    ?,
                    NOW()
                )
            ");


            $activity_description =
                'Marketing visit planned: ' .
                $title .
                ' (' .
                $visit_type .
                ').';


            $activity_stmt->execute([

                $related_lead_id,

                $user['id'],

                $activity_description
            ]);
        }


        header(
            'Location: ' .
            BASE_URL .
            '/manager/visits/index.php'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Header
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">


    <!-- ========================================================= -->
    <!-- HEADER -->
    <!-- ========================================================= -->

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/manager/visits/index.php"
            class="text-decoration-none"
        >
            ← Back to Visits
        </a>

        <h1 class="hm-page-title mt-2 mb-1">
            Add Visit
        </h1>

        <p class="hm-muted mb-0">
            Plan and assign a field marketing visit.
        </p>

    </div>


    <!-- ========================================================= -->
    <!-- ERRORS -->
    <!-- ========================================================= -->

    <?php if (!empty($errors)): ?>

        <div class="alert alert-danger">

            <div class="fw-semibold mb-2">
                Please correct the following:
            </div>

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


    <!-- ========================================================= -->
    <!-- FORM -->
    <!-- ========================================================= -->

    <div class="hm-card p-4">

        <form method="POST">


            <!-- Visit Type -->

            <div class="mb-3">

                <label class="form-label">
                    Visit Type
                    <span class="text-danger">*</span>
                </label>

                <select
                    name="visit_type"
                    class="form-select"
                    required
                >

                    <option value="">
                        -- Select Visit Type --
                    </option>

                    <?php foreach ($visit_types as $type): ?>

                        <option
                            value="<?php echo htmlspecialchars($type); ?>"
                            <?php
                            echo $visit_type === $type
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


            <!-- Title -->

            <div class="mb-3">

                <label class="form-label">
                    Visit Title
                    <span class="text-danger">*</span>
                </label>

                <input
                    type="text"
                    name="title"
                    class="form-control"
                    value="<?php echo htmlspecialchars($title); ?>"
                    placeholder="Example: Meet Apollo Clinic Coordinator"
                    required
                >

            </div>


            <!-- Person / Organization -->

            <div class="row g-3">


                <div class="col-md-6">

                    <label class="form-label">
                        Person / Contact Name
                    </label>

                    <input
                        type="text"
                        name="person_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($person_name); ?>"
                        placeholder="Doctor / Contact Person"
                    >

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Organization
                    </label>

                    <input
                        type="text"
                        name="organization_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($organization_name); ?>"
                        placeholder="Clinic / Corporate / Hospital"
                    >

                </div>

            </div>


            <!-- Contact -->

            <div class="row g-3 mt-1">


                <div class="col-md-6">

                    <label class="form-label">
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?php echo htmlspecialchars($phone); ?>"
                        placeholder="Contact phone"
                    >

                </div>


                <div class="col-md-6">

                    <label class="form-label">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?php echo htmlspecialchars($email); ?>"
                        placeholder="Contact email"
                    >

                </div>

            </div>


            <!-- Location -->

            <div class="mt-3">

                <label class="form-label">
                    Location
                </label>

                <input
                    type="text"
                    name="location"
                    class="form-control"
                    value="<?php echo htmlspecialchars($location); ?>"
                    placeholder="Visit location"
                >

            </div>


            <!-- Assignment -->

            <div class="row g-3 mt-1">


                <div class="col-md-4">

                    <label class="form-label">
                        Assign To
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="assigned_to"
                        class="form-select"
                        required
                    >

                        <option value="">
                            -- Select Staff --
                        </option>

                        <?php foreach ($staff as $member): ?>

                            <option
                                value="<?php echo (int) $member['id']; ?>"
                                <?php
                                echo $assigned_to ===
                                    (int) $member['id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $member['name']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        Related Task
                    </label>

                    <select
                        name="related_task_id"
                        class="form-select"
                    >

                        <option value="">
                            -- No Related Task --
                        </option>

                        <?php foreach ($tasks as $task): ?>

                            <option
                                value="<?php echo (int) $task['id']; ?>"
                                <?php
                                echo $related_task_id ===
                                    (int) $task['id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $task['title']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        Related Lead
                    </label>

                    <select
                        name="related_lead_id"
                        class="form-select"
                    >

                        <option value="">
                            -- No Related Lead --
                        </option>

                        <?php foreach ($leads as $lead): ?>

                            <option
                                value="<?php echo (int) $lead['id']; ?>"
                                <?php
                                echo $related_lead_id ===
                                    (int) $lead['id']
                                    ? 'selected'
                                    : '';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $lead['name']
                                );
                                ?>

                                -
                                <?php
                                echo htmlspecialchars(
                                    $lead['phone']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- Date -->

            <div class="mt-3">

                <label class="form-label">
                    Visit Date & Time
                    <span class="text-danger">*</span>
                </label>

                <input
                    type="datetime-local"
                    name="visit_date"
                    class="form-control"
                    value="<?php echo htmlspecialchars($visit_date); ?>"
                    required
                >

            </div>


            <!-- Purpose -->

            <div class="mt-3">

                <label class="form-label">
                    Purpose
                </label>

                <textarea
                    name="purpose"
                    class="form-control"
                    rows="4"
                    placeholder="Why is this visit being conducted?"
                ><?php
                echo htmlspecialchars($purpose);
                ?></textarea>

            </div>


            <!-- Buttons -->

            <div class="d-flex gap-2 mt-4">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Create Visit
                </button>


                <a
                    href="<?php echo BASE_URL; ?>/manager/visits/index.php"
                    class="btn btn-outline-secondary"
                >
                    Cancel
                </a>

            </div>

        </form>

    </div>


</div>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>