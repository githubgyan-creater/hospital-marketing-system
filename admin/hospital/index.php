<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('admin');

$page_title = 'Hospital Master';

$user = current_user();

$message = '';
$message_type = '';

/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $registration_number = trim(
        $_POST['registration_number'] ?? ''
    );
    $phone = trim($_POST['phone'] ?? '');
    $emergency_phone = trim(
        $_POST['emergency_phone'] ?? ''
    );
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $status = $_POST['status'] ?? 'ACTIVE';

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

    } else {

        try {

            /*
            |--------------------------------------------------------------------------
            | Check whether hospital master record already exists
            |--------------------------------------------------------------------------
            */

            $stmt = $pdo->query("
                SELECT id
                FROM hospitals
                ORDER BY id ASC
                LIMIT 1
            ");

            $existing_hospital = $stmt->fetch();

            if ($existing_hospital) {

                /*
                |--------------------------------------------------------------------------
                | Update Existing Hospital
                |--------------------------------------------------------------------------
                */

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
                    $registration_number ?: null,
                    $phone ?: null,
                    $emergency_phone ?: null,
                    $email ?: null,
                    $website ?: null,
                    $address ?: null,
                    $city ?: null,
                    $state ?: null,
                    $pincode ?: null,
                    $status,
                    $existing_hospital['id']
                ]);

                $message =
                    'Hospital information updated successfully.';

            } else {

                /*
                |--------------------------------------------------------------------------
                | Create First Hospital Record
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
                    $registration_number ?: null,
                    $phone ?: null,
                    $emergency_phone ?: null,
                    $email ?: null,
                    $website ?: null,
                    $address ?: null,
                    $city ?: null,
                    $state ?: null,
                    $pincode ?: null,
                    $status
                ]);

                $message =
                    'Hospital information saved successfully.';
            }

            $message_type = 'success';

        } catch (PDOException $e) {

            $message =
                'Unable to save hospital information: ' .
                $e->getMessage();

            $message_type = 'danger';
        }
    }
}

/*
|--------------------------------------------------------------------------
| Fetch Current Hospital
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT *
    FROM hospitals
    ORDER BY id ASC
    LIMIT 1
");

$hospital = $stmt->fetch();

/*
|--------------------------------------------------------------------------
| Default Values
|--------------------------------------------------------------------------
*/

$hospital = $hospital ?: [
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

require_once __DIR__ . '/../../includes/header.php';

?>

<main class="container py-5">

    <!-- PAGE HEADER -->

    <div class="mb-4">

        <span class="badge text-bg-light">
            ADMINISTRATOR
        </span>

        <h1 class="hm-page-title mt-2">
            Hospital Master
        </h1>

        <p class="hm-muted">
            Maintain the hospital's primary information used across
            the marketing management system.
        </p>

    </div>

    <?php if ($message !== ''): ?>

        <div class="alert alert-<?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <div class="hm-card p-4">

        <form method="POST">

            <div class="row g-4">

                <!-- HOSPITAL NAME -->

                <div class="col-md-8">

                    <label class="form-label fw-semibold">
                        Hospital Name
                    </label>

                    <input
                        type="text"
                        name="hospital_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['hospital_name']
                        ); ?>"
                        required
                    >

                </div>

                <!-- STATUS -->

                <div class="col-md-4">

                    <label class="form-label fw-semibold">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option
                            value="ACTIVE"
                            <?php
                            echo $hospital['status'] === 'ACTIVE'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="INACTIVE"
                            <?php
                            echo $hospital['status'] === 'INACTIVE'
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

                    <label class="form-label fw-semibold">
                        Registration Number
                    </label>

                    <input
                        type="text"
                        name="registration_number"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['registration_number'] ?? ''
                        ); ?>"
                    >

                </div>

                <!-- PHONE -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['phone'] ?? ''
                        ); ?>"
                    >

                </div>

                <!-- EMERGENCY PHONE -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Emergency Phone
                    </label>

                    <input
                        type="text"
                        name="emergency_phone"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['emergency_phone'] ?? ''
                        ); ?>"
                    >

                </div>

                <!-- EMAIL -->

                <div class="col-md-6">

                    <label class="form-label fw-semibold">
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['email'] ?? ''
                        ); ?>"
                    >

                </div>

                <!-- WEBSITE -->

                <div class="col-md-12">

                    <label class="form-label fw-semibold">
                        Website
                    </label>

                    <input
                        type="url"
                        name="website"
                        class="form-control"
                        placeholder="https://example.com"
                        value="<?php echo htmlspecialchars(
                            $hospital['website'] ?? ''
                        ); ?>"
                    >

                </div>

                <!-- ADDRESS -->

                <div class="col-md-12">

                    <label class="form-label fw-semibold">
                        Address
                    </label>

                    <textarea
                        name="address"
                        class="form-control"
                        rows="3"
                    ><?php
                    echo htmlspecialchars(
                        $hospital['address'] ?? ''
                    );
                    ?></textarea>

                </div>

                <!-- CITY -->

                <div class="col-md-4">

                    <label class="form-label fw-semibold">
                        City
                    </label>

                    <input
                        type="text"
                        name="city"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['city'] ?? ''
                        ); ?>"
                    >

                </div>

                <!-- STATE -->

                <div class="col-md-4">

                    <label class="form-label fw-semibold">
                        State
                    </label>

                    <input
                        type="text"
                        name="state"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['state'] ?? ''
                        ); ?>"
                    >

                </div>

                <!-- PINCODE -->

                <div class="col-md-4">

                    <label class="form-label fw-semibold">
                        PIN Code
                    </label>

                    <input
                        type="text"
                        name="pincode"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $hospital['pincode'] ?? ''
                        ); ?>"
                    >

                </div>

            </div>

            <hr class="my-4">

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