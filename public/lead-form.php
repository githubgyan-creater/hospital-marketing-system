<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/lead_workflow.php';

$form_token = trim($_GET['form'] ?? $_POST['form'] ?? '');
$error = '';
$success = '';

if ($form_token === '') {
    http_response_code(404);
    exit('Lead form not found.');
}

$stmt = $pdo->prepare("
    SELECT id, form_name
    FROM lead_forms
    WHERE form_token = ?
      AND status = 'ACTIVE'
    LIMIT 1
");
$stmt->execute([$form_token]);
$form = $stmt->fetch();

if (!$form) {
    http_response_code(404);
    exit('This lead form is no longer available.');
}

$departments = $pdo->query("
    SELECT id, department_name
    FROM departments
    WHERE status = 'ACTIVE'
    ORDER BY department_name
")->fetchAll();

$services = $pdo->query("
    SELECT id, service_name, department_id
    FROM services
    WHERE status = 'ACTIVE'
    ORDER BY service_name
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $department_id = (int) ($_POST['department_id'] ?? 0);
    $service_id = (int) ($_POST['service_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if ($name === '') {
        $error = 'Name is required.';
    } elseif ($phone === '') {
        $error = 'Phone number is required.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($age !== '' && (!ctype_digit($age) || (int) $age < 0 || (int) $age > 120)) {
        $error = 'Please enter a valid age.';
    }

    if ($error === '' && $department_id > 0) {
        $check = $pdo->prepare("
            SELECT id FROM departments
            WHERE id = ? AND status = 'ACTIVE'
            LIMIT 1
        ");
        $check->execute([$department_id]);
        if (!$check->fetchColumn()) {
            $error = 'Please select a valid department.';
        }
    }

    if ($error === '' && $service_id > 0) {
        $check = $pdo->prepare("
            SELECT id, department_id, service_name
            FROM services
            WHERE id = ? AND status = 'ACTIVE'
            LIMIT 1
        ");
        $check->execute([$service_id]);
        $service_row = $check->fetch();

        if (!$service_row) {
            $error = 'Please select a valid service.';
        } elseif ($department_id > 0 && (int) $service_row['department_id'] !== $department_id) {
            $error = 'Selected service does not belong to the selected department.';
        }
    }

    if ($error === '') {
        $duplicate = find_duplicate_lead_by_phone($pdo, $phone);

        if ($duplicate) {
            $success = 'Thank you. Your enquiry has already been received.';
        } else {
            try {
                $source_id = get_or_create_marketing_source($pdo, 'Public Form');

                $service_interest = null;
                if (!empty($service_row['service_name'])) {
                    $service_interest = $service_row['service_name'];
                }

                $assigned_to = assign_lead_to_telecaller(
                    $pdo,
                    $department_id > 0 ? $department_id : null,
                    $service_id > 0 ? $service_id : null
                );

                // leads.created_by is required, so the active manager is the system owner
                // for public submissions.
                $manager_id = $pdo->query("
                    SELECT u.id
                    FROM users u
                    INNER JOIN roles r ON r.id = u.role_id
                    WHERE u.status = 'active'
                      AND r.name = 'manager'
                    ORDER BY u.id ASC
                    LIMIT 1
                ")->fetchColumn();

                if (!$manager_id) {
                    throw new RuntimeException('No active manager is available for public lead intake.');
                }

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO leads (
                        name, phone, email, service_interest, source_id,
                        status, priority, assigned_to, notes, created_by,
                        location, address, age, gender, department_id, service_id
                    )
                    VALUES (?, ?, ?, ?, ?, 'New', 'Medium', ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $name,
                    $phone,
                    $email !== '' ? $email : null,
                    $service_interest,
                    $source_id,
                    $assigned_to,
                    $notes !== '' ? $notes : null,
                    $manager_id,
                    $location !== '' ? $location : null,
                    $address !== '' ? $address : null,
                    $age !== '' ? (int) $age : null,
                    $gender !== '' ? $gender : null,
                    $department_id > 0 ? $department_id : null,
                    $service_id > 0 ? $service_id : null
                ]);

                $lead_id = (int) $pdo->lastInsertId();

                $activity = $pdo->prepare("
                    INSERT INTO lead_activities
                        (lead_id, user_id, activity_type, description)
                    VALUES (?, ?, 'Lead Created', ?)
                ");
                $activity->execute([
                    $lead_id,
                    $manager_id,
                    'Lead received through Public Form.'
                ]);

                if ($assigned_to) {
                    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
                    $stmt->execute([$assigned_to]);
                    $assignee = $stmt->fetchColumn();

                    $activity->execute([
                        $lead_id,
                        $manager_id,
                        'Lead automatically assigned to ' . ($assignee ?: 'Telecaller') . '.'
                    ]);
                }

                $pdo->commit();
                $success = 'Thank you. Your enquiry has been submitted successfully.';
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Unable to submit your enquiry right now. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($form['form_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body>
<div class="container py-5" style="max-width: 850px;">
    <div class="hm-card p-4 p-md-5">
        <h1 class="hm-page-title mb-2"><?php echo htmlspecialchars($form['form_name']); ?></h1>
        <p class="hm-muted mb-4">Please submit your enquiry and our team will contact you.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($success === ''): ?>
        <form method="POST">
            <input type="hidden" name="form" value="<?php echo htmlspecialchars($form_token); ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Name *</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone No. *</label>
                    <input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email ID</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Age</label>
                    <input type="number" min="0" max="120" name="age" class="form-control" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">Select</option>
                        <?php foreach (['Male','Female','Other'] as $gender_option): ?>
                            <option value="<?php echo $gender_option; ?>" <?php echo ($_POST['gender'] ?? '') === $gender_option ? 'selected' : ''; ?>>
                                <?php echo $gender_option; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="0">Select</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?php echo (int) $department['id']; ?>" <?php echo (int) ($_POST['department_id'] ?? 0) === (int) $department['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Service</label>
                    <select name="service_id" id="service_id" class="form-select">
                        <option value="0">Select</option>
                        <?php foreach ($services as $service): ?>
                            <option
                                value="<?php echo (int) $service['id']; ?>"
                                data-department="<?php echo (int) $service['department_id']; ?>"
                                <?php echo (int) ($_POST['service_id'] ?? 0) === (int) $service['id'] ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($service['service_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Note</label>
                    <textarea name="notes" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-hm-primary">Submit Enquiry</button>
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const department = document.getElementById('department_id');
    const service = document.getElementById('service_id');

    if (!department || !service) return;

    function filterServices() {
        const selectedDepartment = department.value;

        Array.from(service.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = selectedDepartment !== '0'
                && option.dataset.department !== selectedDepartment;
        });

        const selected = service.options[service.selectedIndex];
        if (selected && selected.hidden) {
            service.value = '0';
        }
    }

    department.addEventListener('change', filterServices);
    filterServices();
})();
</script>
</body>
</html>
