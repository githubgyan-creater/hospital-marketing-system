<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role(
    'admin',
    'manager',
    'telecaller',
    'marketing'
);

$page_title = 'Add Activity';

$lead_id = (int) ($_GET['id'] ?? 0);

if ($lead_id <= 0) {
    exit('Invalid lead ID.');
}


/*
|--------------------------------------------------------------------------
| Get Lead
|--------------------------------------------------------------------------
*/

$lead_stmt = $pdo->prepare("
    SELECT
        id,
        name,
        phone
    FROM leads
    WHERE id = :id
    LIMIT 1
");

$lead_stmt->execute([
    'id' => $lead_id
]);

$lead = $lead_stmt->fetch();

if (!$lead) {
    exit('Lead not found.');
}


$errors = [];


/*
|--------------------------------------------------------------------------
| Save Activity
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $activity_type = trim(
        $_POST['activity_type'] ?? ''
    );

    $description = trim(
        $_POST['description'] ?? ''
    );


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    $allowed_types = [
        'Call',
        'WhatsApp',
        'Follow-up',
        'Appointment',
        'Visit',
        'Note'
    ];

    if (!in_array(
        $activity_type,
        $allowed_types,
        true
    )) {
        $errors[] = 'Please select a valid activity type.';
    }

    if ($description === '') {
        $errors[] = 'Description is required.';
    }


    /*
    |--------------------------------------------------------------------------
    | Insert Activity
    |--------------------------------------------------------------------------
    */

    if (empty($errors)) {

        $user = current_user();

        $user_id = (int) $user['id'];

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
                :activity_type,
                :description,
                NOW()
            )
        ");

        $activity_stmt->execute([
            'lead_id' => $lead_id,
            'user_id' => $user_id,
            'activity_type' => $activity_type,
            'description' => $description
        ]);


        /*
        |--------------------------------------------------------------------------
        | Redirect to Timeline
        |--------------------------------------------------------------------------
        */

        header(
            'Location: ' .
            BASE_URL .
            '/leads/timeline.php?id=' .
            $lead_id
        );

        exit;
    }
}


require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Header -->

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/leads/timeline.php?id=<?php echo $lead_id; ?>"
            class="text-decoration-none"
        >
            ← Back to Activity Timeline
        </a>

        <h1 class="hm-page-title mt-3 mb-1">
            Add Activity
        </h1>

        <p class="hm-muted mb-0">

            Lead:
            <strong>
                <?php
                echo htmlspecialchars(
                    $lead['name']
                );
                ?>
            </strong>

            |

            <?php
            echo htmlspecialchars(
                $lead['phone']
            );
            ?>

        </p>

    </div>


    <!-- Errors -->

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


    <!-- Form -->

    <div class="hm-card p-4">

        <form method="POST">

            <div class="row g-4">

                <!-- Activity Type -->

                <div class="col-md-6">

                    <label class="form-label">
                        Activity Type
                    </label>

                    <select
                        name="activity_type"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Activity
                        </option>

                        <?php

                        $activity_types = [
                            'Call',
                            'WhatsApp',
                            'Follow-up',
                            'Appointment',
                            'Visit',
                            'Note'
                        ];

                        ?>

                        <?php foreach ($activity_types as $type): ?>

                            <option
                                value="<?php echo $type; ?>"
                                <?php
                                echo (
                                    ($_POST['activity_type'] ?? '')
                                    === $type
                                )
                                    ? 'selected'
                                    : '';
                                ?>
                            >
                                <?php echo $type; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- Description -->

                <div class="col-12">

                    <label class="form-label">
                        Description
                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="5"
                        placeholder="Enter activity details..."
                        required
                    ><?php
                        echo htmlspecialchars(
                            $_POST['description'] ?? ''
                        );
                    ?></textarea>

                </div>


                <!-- Buttons -->

                <div class="col-12">

                    <hr>

                    <a
                        href="<?php echo BASE_URL; ?>/leads/timeline.php?id=<?php echo $lead_id; ?>"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Save Activity
                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>