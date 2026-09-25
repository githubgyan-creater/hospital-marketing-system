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

$page_title = 'Add Lead';

$errors = [];
$duplicate_lead = null;


/*
|--------------------------------------------------------------------------
| Get Marketing Sources
|--------------------------------------------------------------------------
*/

$source_stmt = $pdo->query("
    SELECT id, name
    FROM marketing_sources
    WHERE status = 'active'
    ORDER BY name ASC
");

$sources = $source_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Get Marketing Staff
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Add Lead
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Get Form Data
    |--------------------------------------------------------------------------
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

    $source_id = !empty(
        $_POST['source_id']
    )
        ? (int) $_POST['source_id']
        : null;

    $priority = $_POST['priority'] ?? 'Medium';

    $assigned_to = !empty(
        $_POST['assigned_to']
    )
        ? (int) $_POST['assigned_to']
        : null;

    $notes = trim(
        $_POST['notes'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $errors[] =
            'Name is required.';
    }


    if ($phone === '') {

        $errors[] =
            'Phone number is required.';
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
    |--------------------------------------------------------------------------
    | Duplicate Lead Check
    |--------------------------------------------------------------------------
    */

    if (
        $phone !== '' &&
        empty($errors)
    ) {

        $duplicate_stmt = $pdo->prepare("
            SELECT
                l.id,
                l.name,
                l.phone,
                l.status,
                l.priority,
                u.name AS assigned_name

            FROM leads l

            LEFT JOIN users u
                ON l.assigned_to = u.id

            WHERE l.phone = :phone

            ORDER BY l.id DESC

            LIMIT 1
        ");

        $duplicate_stmt->execute([
            'phone' => $phone
        ]);

        $duplicate_lead =
            $duplicate_stmt->fetch();


        if ($duplicate_lead) {

            $errors[] =
                'A lead with this phone number already exists.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Insert Lead
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        !$duplicate_lead
    ) {

        $insert_stmt = $pdo->prepare("
            INSERT INTO leads (
                name,
                phone,
                email,
                service_interest,
                source_id,
                status,
                priority,
                assigned_to,
                notes,
                created_by
            )
            VALUES (
                :name,
                :phone,
                :email,
                :service_interest,
                :source_id,
                'New',
                :priority,
                :assigned_to,
                :notes,
                :created_by
            )
        ");


        $insert_stmt->execute([

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

            'priority' =>
                $priority,

            'assigned_to' =>
                $assigned_to,

            'notes' =>
                $notes !== ''
                    ? $notes
                    : null,

            'created_by' =>
                $user['id']
        ]);


        /*
        |--------------------------------------------------------------------------
        | Get New Lead ID
        |--------------------------------------------------------------------------
        */

        $lead_id =
            $pdo->lastInsertId();


        /*
        |--------------------------------------------------------------------------
        | Automatic Lead Created Activity
        |--------------------------------------------------------------------------
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
                'Lead Created',
                'Lead was created.',
                NOW()
            )
        ");


        $activity_stmt->execute([

            'lead_id' =>
                $lead_id,

            'user_id' =>
                $user['id']
        ]);


        /*
        |--------------------------------------------------------------------------
        | Automatic Lead Assigned Activity
        |--------------------------------------------------------------------------
        */

        if ($assigned_to !== null) {

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

                $assignment_activity_stmt =
                    $pdo->prepare("
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

                $assignment_activity_stmt->execute([

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


        /*
        |--------------------------------------------------------------------------
        | Redirect
        |--------------------------------------------------------------------------
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
            href="<?php
                echo BASE_URL;
            ?>/leads/index.php"
            class="text-decoration-none"
        >
            ← Back to Leads
        </a>


        <h1 class="hm-page-title mt-3 mb-1">
            Add Lead
        </h1>


        <p class="hm-muted">
            Create a new hospital marketing lead.
        </p>

    </div>



    <!-- Duplicate Warning -->

    <?php if ($duplicate_lead): ?>

        <div class="alert alert-warning">

            <h5 class="alert-heading">
                ⚠️ Duplicate Lead Found
            </h5>


            <p class="mb-2">

                A lead with this phone number
                already exists.

            </p>


            <div class="mb-2">

                <strong>
                    Name:
                </strong>

                <?php

                echo htmlspecialchars(
                    $duplicate_lead['name']
                );

                ?>

            </div>


            <div class="mb-2">

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


            <div class="mb-3">

                <strong>
                    Assigned To:
                </strong>

                <?php

                echo htmlspecialchars(
                    $duplicate_lead[
                        'assigned_name'
                    ]
                    ?? 'Not Assigned'
                );

                ?>

            </div>


            <a
                href="<?php
                    echo BASE_URL;
                ?>/leads/view.php?id=<?php
                    echo $duplicate_lead['id'];
                ?>"
                class="btn btn-sm btn-hm-primary"
            >
                View Existing Lead
            </a>

        </div>

    <?php endif; ?>



    <!-- Error Messages -->

    <?php if (
        !empty($errors) &&
        !$duplicate_lead
    ): ?>

        <div class="alert alert-danger">

            <ul class="mb-0">

                <?php foreach (
                    $errors
                    as $error
                ): ?>

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



    <!-- Lead Form -->

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
                                ?? ''
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
                                ?? ''
                            );

                        ?>"
                        required
                    >

                    <small class="text-muted">
                        Used to check for duplicate leads.
                    </small>

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
                                ?? ''
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
                                $_POST[
                                    'service_interest'
                                ]
                                ?? ''
                            );

                        ?>"
                    >

                </div>



                <!-- Source -->

                <div class="col-md-6">

                    <label class="form-label">
                        Source
                    </label>

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
                                    (
                                        $_POST[
                                            'source_id'
                                        ]
                                        ?? ''
                                    )
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



                <!-- Priority -->

                <div class="col-md-6">

                    <label class="form-label">
                        Priority
                    </label>

                    <select
                        name="priority"
                        class="form-select"
                    >

                        <option
                            value="Low"
                            <?php

                            echo (
                                (
                                    $_POST[
                                        'priority'
                                    ]
                                    ?? 'Medium'
                                )
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
                                (
                                    $_POST[
                                        'priority'
                                    ]
                                    ?? 'Medium'
                                )
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
                                (
                                    $_POST[
                                        'priority'
                                    ]
                                    ?? 'Medium'
                                )
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
                                    (
                                        $_POST[
                                            'assigned_to'
                                        ]
                                        ?? ''
                                    )
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
                            ?? ''
                        );

                    ?></textarea>

                </div>



                <!-- Buttons -->

                <div class="col-12">

                    <hr>


                    <a
                        href="<?php
                            echo BASE_URL;
                        ?>/leads/index.php"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>


                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Save Lead
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>