<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('admin');

$page_title = 'Doctors';

$user = current_user();

$message = '';
$message_type = '';

/*

| Handle Add / Edit / Delete

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    /*
    
    | ADD DOCTOR
    
    */

    if ($action === 'add') {

        $doctor_name = trim(
            $_POST['doctor_name'] ?? ''
        );

        $department_id = filter_input(
            INPUT_POST,
            'department_id',
            FILTER_VALIDATE_INT
        );

        $specialization = trim(
            $_POST['specialization'] ?? ''
        );

        $qualification = trim(
            $_POST['qualification'] ?? ''
        );

        $experience_years = filter_input(
            INPUT_POST,
            'experience_years',
            FILTER_VALIDATE_INT
        );

        $phone = trim(
            $_POST['phone'] ?? ''
        );

        $email = trim(
            $_POST['email'] ?? ''
        );

        $consultation_fee = trim(
            $_POST['consultation_fee'] ?? ''
        );

        $bio = trim(
            $_POST['bio'] ?? ''
        );

        $status = $_POST['status'] ?? 'ACTIVE';

        if ($doctor_name === '') {

            $message = 'Doctor name is required.';
            $message_type = 'danger';

        } elseif (
            !in_array(
                $status,
                ['ACTIVE', 'INACTIVE'],
                true
            )
        ) {

            $message = 'Invalid doctor status.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    INSERT INTO doctors (
                        doctor_name,
                        department_id,
                        specialization,
                        qualification,
                        experience_years,
                        phone,
                        email,
                        consultation_fee,
                        bio,
                        status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $doctor_name,
                    $department_id ?: null,
                    $specialization !== ''
                        ? $specialization
                        : null,
                    $qualification !== ''
                        ? $qualification
                        : null,
                    $experience_years !== false
                        ? $experience_years
                        : null,
                    $phone !== ''
                        ? $phone
                        : null,
                    $email !== ''
                        ? $email
                        : null,
                    $consultation_fee !== ''
                        ? $consultation_fee
                        : null,
                    $bio !== ''
                        ? $bio
                        : null,
                    $status
                ]);

                $message =
                    'Doctor added successfully.';

                $message_type = 'success';

            } catch (PDOException $e) {

                $message =
                    'Unable to add doctor: ' .
                    $e->getMessage();

                $message_type = 'danger';
            }
        }
    }

    /*
    
    | EDIT DOCTOR
    
    */

    if ($action === 'edit') {

        $doctor_id = filter_input(
            INPUT_POST,
            'doctor_id',
            FILTER_VALIDATE_INT
        );

        $doctor_name = trim(
            $_POST['doctor_name'] ?? ''
        );

        $department_id = filter_input(
            INPUT_POST,
            'department_id',
            FILTER_VALIDATE_INT
        );

        $specialization = trim(
            $_POST['specialization'] ?? ''
        );

        $qualification = trim(
            $_POST['qualification'] ?? ''
        );

        $experience_years = filter_input(
            INPUT_POST,
            'experience_years',
            FILTER_VALIDATE_INT
        );

        $phone = trim(
            $_POST['phone'] ?? ''
        );

        $email = trim(
            $_POST['email'] ?? ''
        );

        $consultation_fee = trim(
            $_POST['consultation_fee'] ?? ''
        );

        $bio = trim(
            $_POST['bio'] ?? ''
        );

        $status = $_POST['status'] ?? 'ACTIVE';

        if (!$doctor_id) {

            $message = 'Invalid doctor.';
            $message_type = 'danger';

        } elseif ($doctor_name === '') {

            $message = 'Doctor name is required.';
            $message_type = 'danger';

        } elseif (
            !in_array(
                $status,
                ['ACTIVE', 'INACTIVE'],
                true
            )
        ) {

            $message = 'Invalid doctor status.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    UPDATE doctors
                    SET
                        doctor_name = ?,
                        department_id = ?,
                        specialization = ?,
                        qualification = ?,
                        experience_years = ?,
                        phone = ?,
                        email = ?,
                        consultation_fee = ?,
                        bio = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $doctor_name,
                    $department_id ?: null,
                    $specialization !== ''
                        ? $specialization
                        : null,
                    $qualification !== ''
                        ? $qualification
                        : null,
                    $experience_years !== false
                        ? $experience_years
                        : null,
                    $phone !== ''
                        ? $phone
                        : null,
                    $email !== ''
                        ? $email
                        : null,
                    $consultation_fee !== ''
                        ? $consultation_fee
                        : null,
                    $bio !== ''
                        ? $bio
                        : null,
                    $status,
                    $doctor_id
                ]);

                $message =
                    'Doctor updated successfully.';

                $message_type = 'success';

            } catch (PDOException $e) {

                $message =
                    'Unable to update doctor: ' .
                    $e->getMessage();

                $message_type = 'danger';
            }
        }
    }

    /*
    
    | DELETE DOCTOR
    
    */

    if ($action === 'delete') {

        $doctor_id = filter_input(
            INPUT_POST,
            'doctor_id',
            FILTER_VALIDATE_INT
        );

        if (!$doctor_id) {

            $message = 'Invalid doctor.';
            $message_type = 'danger';

        } else {

            try {

                $stmt = $pdo->prepare("
                    DELETE FROM doctors
                    WHERE id = ?
                ");

                $stmt->execute([
                    $doctor_id
                ]);

                $message =
                    'Doctor deleted successfully.';

                $message_type = 'success';

            } catch (PDOException $e) {

                $message =
                    'Unable to delete doctor: ' .
                    $e->getMessage();

                $message_type = 'danger';
            }
        }
    }
}

/*

| Edit Mode

*/

$edit_doctor = null;

$edit_id = filter_input(
    INPUT_GET,
    'edit',
    FILTER_VALIDATE_INT
);

if ($edit_id) {

    $stmt = $pdo->prepare("
        SELECT
            id,
            doctor_name,
            department_id,
            specialization,
            qualification,
            experience_years,
            phone,
            email,
            consultation_fee,
            bio,
            status
        FROM doctors
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([
        $edit_id
    ]);

    $edit_doctor = $stmt->fetch();
}

/*

| Fetch Departments

*/

$stmt = $pdo->query("
    SELECT
        id,
        department_name
    FROM departments
    WHERE status = 'ACTIVE'
    ORDER BY department_name ASC
");

$departments = $stmt->fetchAll();

/*

| Fetch Doctors

*/

$stmt = $pdo->query("
    SELECT
        d.id,
        d.doctor_name,
        d.department_id,
        d.specialization,
        d.qualification,
        d.experience_years,
        d.phone,
        d.email,
        d.consultation_fee,
        d.bio,
        d.status,
        d.created_at,
        d.updated_at,
        dp.department_name
    FROM doctors d
    LEFT JOIN departments dp
        ON dp.id = d.department_id
    ORDER BY d.doctor_name ASC
");

$doctors = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';

?>

<main class="container py-4">

    <!-- PAGE HEADER -->

    <div class="mb-4">

        

        <h1 class="hm-page-title mt-1">
            Doctors
        </h1>

        <p class="hm-muted">
            Manage hospital doctors, departments and professional details.
        </p>

    </div>

    <?php if ($message !== ''): ?>

        <div
            class="alert alert-<?php echo htmlspecialchars($message_type); ?>"
        >
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>

    <div class="row g-4">

        <!-- ADD / EDIT FORM -->

        <div class="col-lg-5">

            <div class="hm-card p-4">

                <h5 class="fw-bold mb-3">

                    <?php
                    echo $edit_doctor
                        ? 'Edit Doctor'
                        : 'Add Doctor';
                    ?>

                </h5>

                <form method="POST">

                    <input
                        type="hidden"
                        name="action"
                        value="<?php
                        echo $edit_doctor
                            ? 'edit'
                            : 'add';
                        ?>"
                    >

                    <?php if ($edit_doctor): ?>

                        <input
                            type="hidden"
                            name="doctor_id"
                            value="<?php
                            echo (int) $edit_doctor['id'];
                            ?>"
                        >

                    <?php endif; ?>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Doctor Name
                        </label>

                        <input
                            type="text"
                            name="doctor_name"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $edit_doctor['doctor_name']
                                ?? ''
                            );
                            ?>"
                            placeholder="e.g. Dr. Rahul Sharma"
                            required
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Department
                        </label>

                        <select
                            name="department_id"
                            class="form-select"
                        >

                            <option value="">
                                Select Department
                            </option>

                            <?php foreach (
                                $departments
                                as $department
                            ): ?>

                                <option
                                    value="<?php
                                    echo (int)
                                        $department['id'];
                                    ?>"
                                    <?php
                                    echo (
                                        isset(
                                            $edit_doctor[
                                                'department_id'
                                            ]
                                        )
                                        &&
                                        (int)
                                        $edit_doctor[
                                            'department_id'
                                        ]
                                        ===
                                        (int)
                                        $department['id']
                                    )
                                        ? 'selected'
                                        : '';
                                    ?>
                                >
                                    <?php
                                    echo htmlspecialchars(
                                        $department[
                                            'department_name'
                                        ]
                                    );
                                    ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Specialization
                        </label>

                        <input
                            type="text"
                            name="specialization"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $edit_doctor[
                                    'specialization'
                                ] ?? ''
                            );
                            ?>"
                            placeholder="e.g. Interventional Cardiology"
                        >

                    </div>

                    <div class="mb-3">

                        <label class="form-label fw-semibold">
                            Qualification
                        </label>

                        <input
                            type="text"
                            name="qualification"
                            class="form-control"
                            value="<?php
                            echo htmlspecialchars(
                                $edit_doctor[
                                    'qualification'
                                ] ?? ''
                            );
                            ?>"
                            placeholder="e.g. MBBS, MD"
                        >

                    </div>

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Experience (Years)
                            </label>

                            <input
                                type="number"
                                name="experience_years"
                                class="form-control"
                                min="0"
                                value="<?php
                                echo htmlspecialchars(
                                    $edit_doctor[
                                        'experience_years'
                                    ] ?? ''
                                );
                                ?>"
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Consultation Fee
                            </label>

                            <input
                                type="number"
                                name="consultation_fee"
                                class="form-control"
                                min="0"
                                step="0.01"
                                value="<?php
                                echo htmlspecialchars(
                                    $edit_doctor[
                                        'consultation_fee'
                                    ] ?? ''
                                );
                                ?>"
                            >

                        </div>

                    </div>

                    <div class="row g-3 mt-1">

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Phone
                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                value="<?php
                                echo htmlspecialchars(
                                    $edit_doctor['phone']
                                    ?? ''
                                );
                                ?>"
                            >

                        </div>

                        <div class="col-md-6">

                            <label class="form-label fw-semibold">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                value="<?php
                                echo htmlspecialchars(
                                    $edit_doctor['email']
                                    ?? ''
                                );
                                ?>"
                            >

                        </div>

                    </div>

                    <div class="mb-3 mt-3">

                        <label class="form-label fw-semibold">
                            Bio
                        </label>

                        <textarea
                            name="bio"
                            class="form-control"
                            rows="4"
                        ><?php
                        echo htmlspecialchars(
                            $edit_doctor['bio']
                            ?? ''
                        );
                        ?></textarea>

                    </div>

                    <div class="mb-4">

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
                                echo (
                                    ($edit_doctor[
                                        'status'
                                    ] ?? 'ACTIVE')
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
                                    ($edit_doctor[
                                        'status'
                                    ] ?? '')
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

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-hm-primary"
                        >

                            <?php
                            echo $edit_doctor
                                ? 'Update Doctor'
                                : 'Add Doctor';
                            ?>

                        </button>

                        <?php if ($edit_doctor): ?>

                            <a
                                href="<?php echo BASE_URL; ?>/admin/doctors/"
                                class="btn btn-outline-secondary"
                            >
                                Cancel
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </div>


        <!-- DOCTOR LIST -->

        <div class="col-lg-7">

            <div class="hm-card p-4">

                <div
                    class="d-flex justify-content-between align-items-center mb-3"
                >

                    <h5 class="fw-bold mb-0">
                        Doctor List
                    </h5>

                    <span class="badge text-bg-light">
                        <?php echo count($doctors); ?>
                        Doctors
                    </span>

                </div>

                <div class="table-responsive">

                    <table
                        class="table table-bordered align-middle"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Doctor
                                </th>

                                <th>
                                    Department
                                </th>

                                <th>
                                    Specialization
                                </th>

                                <th>
                                    Status
                                </th>

                                <th width="145">
                                    Action
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                        <?php if (!empty($doctors)): ?>

                            <?php foreach (
                                $doctors
                                as $doctor
                            ): ?>

                                <tr>

                                    <td>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $doctor[
                                                    'doctor_name'
                                                ]
                                            );
                                            ?>
                                        </strong>

                                        <?php
                                        if (
                                            !empty(
                                                $doctor[
                                                    'qualification'
                                                ]
                                            )
                                        ):
                                        ?>

                                            <div class="small text-muted">
                                                <?php
                                                echo htmlspecialchars(
                                                    $doctor[
                                                        'qualification'
                                                    ]
                                                );
                                                ?>
                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $doctor[
                                                'department_name'
                                            ] ?? '-'
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <?php
                                        echo htmlspecialchars(
                                            $doctor[
                                                'specialization'
                                            ] ?? '-'
                                        );
                                        ?>

                                    </td>

                                    <td>

                                        <?php if (
                                            $doctor['status']
                                            === 'ACTIVE'
                                        ): ?>

                                            <span class="badge text-bg-success">
                                                Active
                                            </span>

                                        <?php else: ?>

                                            <span class="badge text-bg-secondary">
                                                Inactive
                                            </span>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="d-flex gap-1">

                                            <a
                                                href="<?php echo BASE_URL; ?>/admin/doctors/?edit=<?php echo (int) $doctor['id']; ?>"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                Edit
                                            </a>

                                            <form
                                                method="POST"
                                                onsubmit="return confirm('Delete this doctor?');"
                                            >

                                                <input
                                                    type="hidden"
                                                    name="action"
                                                    value="delete"
                                                >

                                                <input
                                                    type="hidden"
                                                    name="doctor_id"
                                                    value="<?php
                                                    echo (int)
                                                        $doctor['id'];
                                                    ?>"
                                                >

                                                <button
                                                    type="submit"
                                                    class="btn btn-sm btn-outline-danger"
                                                >
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td
                                    colspan="5"
                                    class="text-center text-muted py-4"
                                >
                                    No doctors found.
                                </td>

                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

</main>

<?php

require_once __DIR__ . '/../../includes/footer.php';

?>