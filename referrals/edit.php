<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin', 'manager');

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header(
        'Location: ' .
        BASE_URL .
        '/referrals/'
    );
    exit;
}

$referral_types = [
    'Doctor',
    'Clinic',
    'Hospital',
    'Corporate',
    'Health Professional',
    'Other'
];

$error = '';

/*

| Fetch Referral

*/

$stmt = $pdo->prepare("
    SELECT *
    FROM referrals
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$referral = $stmt->fetch();

if (!$referral) {

    http_response_code(404);

    exit('Referral not found.');
}

/*

| Update Referral

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');

    $referral_type = trim(
        $_POST['referral_type'] ?? 'Other'
    );

    $organization = trim(
        $_POST['organization'] ?? ''
    );

    $phone = trim(
        $_POST['phone'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $address = trim(
        $_POST['address'] ?? ''
    );

    $specialty = trim(
        $_POST['specialty'] ?? ''
    );

    $status = trim(
        $_POST['status'] ?? 'Active'
    );

    $notes = trim(
        $_POST['notes'] ?? ''
    );

    /*
    
    | Validation
    
    */

    if ($name === '') {

        $error = 'Referral name is required.';

    } elseif (
        !in_array(
            $referral_type,
            $referral_types,
            true
        )
    ) {

        $error = 'Please select a valid referral type.';

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
            $status,
            ['Active', 'Inactive'],
            true
        )
    ) {

        $error = 'Please select a valid status.';

    } else {

        /*
        
        | Update Database
        
        */

        $stmt = $pdo->prepare("
            UPDATE referrals
            SET
                name = ?,
                referral_type = ?,
                organization = ?,
                phone = ?,
                email = ?,
                address = ?,
                specialty = ?,
                status = ?,
                notes = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $referral_type,
            $organization !== ''
                ? $organization
                : null,
            $phone !== ''
                ? $phone
                : null,
            $email !== ''
                ? $email
                : null,
            $address !== ''
                ? $address
                : null,
            $specialty !== ''
                ? $specialty
                : null,
            $status,
            $notes !== ''
                ? $notes
                : null,
            $id
        ]);

        header(
            'Location: ' .
            BASE_URL .
            '/referrals/view.php?id=' .
            $id
        );

        exit;
    }

    /*
    
    | Keep Submitted Values
    
    */

    $referral['name'] = $name;
    $referral['referral_type'] = $referral_type;
    $referral['organization'] = $organization;
    $referral['phone'] = $phone;
    $referral['email'] = $email;
    $referral['address'] = $address;
    $referral['specialty'] = $specialty;
    $referral['status'] = $status;
    $referral['notes'] = $notes;
}

$page_title = 'Edit Referral';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="mb-4">

        <h2 class="hm-page-title mb-1">
            Edit Referral
        </h2>

        <p class="hm-muted mb-0">
            Update referral partner information.
        </p>

    </div>

    <!-- Error -->

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>

    <!-- Form -->

    <div class="hm-card p-4">

        <form method="POST">

            <div class="row g-3">

                <!-- Name -->

                <div class="col-md-6">

                    <label
                        for="name"
                        class="form-label"
                    >
                        Referral Name
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control"
                        required
                        value="<?php echo htmlspecialchars(
                            $referral['name']
                        ); ?>"
                    >

                </div>

                <!-- Referral Type -->

                <div class="col-md-6">

                    <label
                        for="referral_type"
                        class="form-label"
                    >
                        Referral Type
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="referral_type"
                        id="referral_type"
                        class="form-select"
                        required
                    >

                        <?php foreach ($referral_types as $type): ?>

                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php
                                echo $referral['referral_type'] === $type
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

                <!-- Organization -->

                <div class="col-md-6">

                    <label
                        for="organization"
                        class="form-label"
                    >
                        Organization
                    </label>

                    <input
                        type="text"
                        name="organization"
                        id="organization"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $referral['organization'] ?? ''
                        ); ?>"
                    >

                </div>

                <!-- Specialty -->

                <div class="col-md-6">

                    <label
                        for="specialty"
                        class="form-label"
                    >
                        Specialty
                    </label>

                    <input
                        type="text"
                        name="specialty"
                        id="specialty"
                        class="form-control"
                        value="<?php echo htmlspecialchars(
                            $referral['specialty'] ?? ''
                        ); ?>"
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
                        value="<?php echo htmlspecialchars(
                            $referral['phone'] ?? ''
                        ); ?>"
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
                            $referral['email'] ?? ''
                        ); ?>"
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
                    >

                        <option
                            value="Active"
                            <?php
                            echo $referral['status'] === 'Active'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="Inactive"
                            <?php
                            echo $referral['status'] === 'Inactive'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>

                <!-- Address -->

                <div class="col-12">

                    <label
                        for="address"
                        class="form-label"
                    >
                        Address
                    </label>

                    <textarea
                        name="address"
                        id="address"
                        rows="3"
                        class="form-control"
                    ><?php
                    echo htmlspecialchars(
                        $referral['address'] ?? ''
                    );
                    ?></textarea>

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
                    ><?php
                    echo htmlspecialchars(
                        $referral['notes'] ?? ''
                    );
                    ?></textarea>

                </div>

                <!-- Buttons -->

                <div class="col-12 mt-4">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Update Referral
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/referrals/view.php?id=<?php echo $id; ?>"
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