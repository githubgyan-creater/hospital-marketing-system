 <?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('admin');

$page_title = 'Hospital Master';

$user = current_user();

$message = '';
$message_type = '';

/*
|--------------------------------------------------------------------------
| HANDLE FORM SUBMISSION
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hospital_name = trim(
        $_POST['hospital_name'] ?? ''
    );

    $registration_number = trim(
        $_POST['registration_number'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );

    $emergency_phone = trim(
        $_POST['emergency_phone'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $website = trim(
        $_POST['website'] ?? ''
    );

    $address = trim(
        $_POST['address'] ?? ''
    );

    $city = trim(
        $_POST['city'] ?? ''
    );

    $state = trim(
        $_POST['state'] ?? ''
    );

    $pincode = trim(
        $_POST['pincode'] ?? ''
    );

    $status = $_POST['status'] ?? 'ACTIVE';

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($hospital_name === '') {

        $message = 'Hospital name is required.';
        $message_type = 'danger';

    } elseif (
        !in_array(
            $status,
            ['ACTIVE', 'INACTIVE'],
            true
        )
    ) {

        $message = 'Invalid hospital status.';
        $message_type = 'danger';

    } elseif (
        $email !== '' &&
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $message = 'Please enter a valid email address.';
        $message_type = 'danger';

    } elseif (
        $website !== '' &&
        !filter_var($website, FILTER_VALIDATE_URL)
    ) {

        $message = 'Please enter a valid website URL.';
        $message_type = 'danger';

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | CHECK EXISTING HOSPITAL
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->query("
                SELECT
                    id
                FROM hospitals
                ORDER BY id ASC
                LIMIT 1
            ");

            $existing_hospital = $stmt->fetch();

            /*
            |--------------------------------------------------------------------------
            | UPDATE EXISTING HOSPITAL
            |--------------------------------------------------------------------------
            */

            if ($existing_hospital) {

                $stmt = $pdo->prepare("
                    UPDATE hospitals
                    SET
                        hospital_name = ?,
                        registration_number = ?,
                        phone = ?,
                        emergency_phone = ?,
                        email = ?,
                        website = ?,
                        address = ?,
                        city = ?,
                        state = ?,
                        pincode = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $hospital_name,
                    $registration_number !== ''
                        ? $registration_number
                        : null,
                    $phone !== ''
                        ? $phone
                        : null,
                    $emergency_phone !== ''
                        ? $emergency_phone
                        : null,
                    $email !== ''
                        ? $email
                        : null,
                    $website !== ''
                        ? $website
                        : null,
                    $address !== ''
                        ? $address
                        : null,
                    $city !== ''
                        ? $city
                        : null,
                    $state !== ''
                        ? $state
                        : null,
                    $pincode !== ''
                        ? $pincode
                        : null,
                    $status,
                    (int) $existing_hospital['id']
                ]);

                $message =
                    'Hospital information updated successfully.';

                $message_type = 'success';

            } else {

                /*
                |--------------------------------------------------------------------------
                | CREATE FIRST HOSPITAL
                |--------------------------------------------------------------------------
                */

                $stmt = $pdo->prepare("
                    INSERT INTO hospitals (
                        hospital_name,
                        registration_number,
                        phone,
                        emergency_phone,
                        email,
                        website,
                        address,
                        city,
                        state,
                        pincode,
                        status
                    )
                    VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                    )
                ");

                $stmt->execute([
                    $hospital_name,
                    $registration_number !== ''
                        ? $registration_number
                        : null,
                    $phone !== ''
                        ? $phone
                        : null,
                    $emergency_phone !== ''
                        ? $emergency_phone
                        : null,
                    $email !== ''
                        ? $email
                        : null,
                    $website !== ''
                        ? $website
                        : null,
                    $address !== ''
                        ? $address
                        : null,
                    $city !== ''
                        ? $city
                        : null,
                    $state !== ''
                        ? $state
                        : null,
                    $pincode !== ''
                        ? $pincode
                        : null,
                    $status
                ]);

                $message =
                    'Hospital information saved successfully.';

                $message_type = 'success';
            }

        } catch (PDOException $e) {

            $message =
                'Unable to save hospital information.';

            $message_type = 'danger';
        }
    }
}

/*
|--------------------------------------------------------------------------
| FETCH CURRENT HOSPITAL
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        *
    FROM hospitals
    ORDER BY id ASC
    LIMIT 1
");

$hospital = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| DEFAULT VALUES
|--------------------------------------------------------------------------
*/

if (!$hospital) {

    $hospital = [
        'hospital_name' => '',
        'registration_number' => '',
        'phone' => '',
        'emergency_phone' => '',
        'email' => '',
        'website' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'pincode' => '',
        'status' => 'ACTIVE'
    ];
}

/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';

?>

<main class="container py-4">

    <!-- PAGE HEADER -->

    <div class="mb-4">

        <h1 class="hm-page-title mt-1">
            Hospital Master
        </h1>

        <p class="hm-muted">
            Maintain the hospital's primary information used
            across the marketing management system.
        </p>

    </div>


    <!-- MESSAGE -->

    <?php if ($message !== ''): ?>

        <div
            class="alert alert-<?php echo htmlspecialchars($message_type); ?>"
            role="alert"
        >
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <!-- HOSPITAL FORM -->

    <div class="hm-card p-4">

        <form method="POST">

            <div class="row g-4">

                <!-- HOSPITAL NAME -->

                <div class="col-md-8">

                    <label
                        class="form-label fw-semibold"
                        for="hospital_name"
                    >
                        Hospital Name
                    </label>

                    <input
                        type="text"
                        id="hospital_name"
                        name="hospital_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['hospital_name'] ?? ''
                        ); ?>"
                        maxlength="255"
                        required
                    >

                </div>


                <!-- STATUS -->

                <div class="col-md-4">

                    <label
                        class="form-label fw-semibold"
                        for="status"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="form-select"
                    >

                        <option
                            value="ACTIVE"
                            <?php
                            echo (
                                ($hospital['status'] ?? 'ACTIVE')
                                === 'ACTIVE'
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="INACTIVE"
                            <?php
                            echo (
                                ($hospital['status'] ?? '')
                                === 'INACTIVE'
                            )
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>


                <!-- REGISTRATION NUMBER -->

                <div class="col-md-6">

                    <label
                        class="form-label fw-semibold"
                        for="registration_number"
                    >
                        Registration Number
                    </label>

                    <input
                        type="text"
                        id="registration_number"
                        name="registration_number"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['registration_number'] ?? ''
                        ); ?>"
                        maxlength="100"
                    >

                </div>


                <!-- PHONE -->

                <div class="col-md-6">

                    <label
                        class="form-label fw-semibold"
                        for="phone"
                    >
                        Phone
                    </label>

                    <input
                        type="text"
                        id="phone"
                        name="phone"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['phone'] ?? ''
                        ); ?>"
                        maxlength="30
                    "
                    >

                </div>


                <!-- EMERGENCY PHONE -->

                <div class="col-md-6">

                    <label
                        class="form-label fw-semibold"
                        for="emergency_phone"
                    >
                        Emergency Phone
                    </label>

                    <input
                        type="text"
                        id="emergency_phone"
                        name="emergency_phone"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['emergency_phone'] ?? ''
                        ); ?>"
                        maxlength="30"
                    >

                </div>


                <!-- EMAIL -->

                <div class="col-md-6">

                    <label
                        class="form-label fw-semibold"
                        for="email"
                    >
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['email'] ?? ''
                        ); ?>"
                        maxlength="255"
                    >

                </div>


                <!-- WEBSITE -->

                <div class="col-md-12">

                    <label
                        class="form-label fw-semibold"
                        for="website"
                    >
                        Website
                    </label>

                    <input
                        type="url"
                        id="website"
                        name="website"
                        class="form-control"
                        placeholder="https://example.com"
                        value="<?php echo htmlspecialchars(
                            $hospital['website'] ?? ''
                        ); ?>"
                        maxlength="255"
                    >

                </div>


                <!-- ADDRESS -->

                <div class="col-md-12">

                    <label
                        class="form-label fw-semibold"
                        for="address"
                    >
                        Address
                    </label>

                    <textarea
                        id="address"
                        name="address"
                        class="form-control"
                        rows="3"
                    ><?php echo htmlspecialchars(
                        $hospital['address'] ?? ''
                    ); ?></textarea>

                </div>


                <!-- CITY -->

                <div class="col-md-4">

                    <label
                        class="form-label fw-semibold"
                        for="city"
                    >
                        City
                    </label>

                    <input
                        type="text"
                        id="city"
                        name="city"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['city'] ?? ''
                        ); ?>"
                        maxlength="100"
                    >

                </div>


                <!-- STATE -->

                <div class="col-md-4">

                    <label
                        class="form-label fw-semibold"
                        for="state"
                    >
                        State
                    </label>

                    <input
                        type="text"
                        id="state"
                        name="state"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['state'] ?? ''
                        ); ?>"
                        maxlength="100"
                    >

                </div>


                <!-- PINCODE -->

                <div class="col-md-4">

                    <label
                        class="form-label fw-semibold"
                        for="pincode"
                    >
                        PIN Code
                    </label>

                    <input
                        type="text"
                        id="pincode"
                        name="pincode"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['pincode'] ?? ''
                        ); ?>"
                        maxlength="20"
                    >

                </div>

            </div>


            <hr class="my-4">


            <!-- SAVE BUTTON -->

            <div class="d-flex justify-content-end">

                <button
                    type="submit"
                    class="btn btn-hm-primary"
                >
                    Save Hospital Information
                </button>

            </div>

        </form>

    </div>

</main>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>