<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('manager', 'marketing');

$user = current_user();
$page_title = 'Generate Form';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $form_name = trim($_POST['form_name'] ?? '');

        if ($form_name === '') {
            $error = 'Please enter a form name.';
        } else {
            try {
                $token = bin2hex(random_bytes(16));

                $stmt = $pdo->prepare("
                    INSERT INTO lead_forms (form_token, form_name, created_by, status)
                    VALUES (?, ?, ?, 'ACTIVE')
                ");
                $stmt->execute([$token, $form_name, $user['id']]);

                $success = 'Lead form generated successfully.';
            } catch (Throwable $e) {
                $error = 'Unable to generate form.';
            }
        }
    }

    if ($action === 'toggle') {
        $form_id = (int) ($_POST['form_id'] ?? 0);

        if ($form_id > 0) {
            $stmt = $pdo->prepare("
                UPDATE lead_forms
                SET status = CASE WHEN status = 'ACTIVE' THEN 'INACTIVE' ELSE 'ACTIVE' END
                WHERE id = ?
                  AND created_by = ?
            ");
            $stmt->execute([$form_id, $user['id']]);
        }
    }
}

$stmt = $pdo->prepare("
    SELECT id, form_token, form_name, status, created_at
    FROM lead_forms
    WHERE created_by = ?
    ORDER BY id DESC
");
$stmt->execute([$user['id']]);
$forms = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h1 class="hm-page-title mb-1">Generate Form</h1>
            <p class="hm-muted mb-0">Create a shareable public lead form URL.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/<?php echo $user['role'] === 'marketing' ? 'marketing/dashboard.php' : 'manager/dashboard.php'; ?>" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="hm-card p-4 mb-4">
        <h5 class="mb-3">Generate Form</h5>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="row g-3 align-items-end">
                <div class="col-md-8">
                    <label class="form-label" for="form_name">Form Name</label>
                    <input type="text" name="form_name" id="form_name" class="form-control" placeholder="e.g. Website Enquiry Form" required>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-hm-primary w-100">Generate Form</button>
                </div>
            </div>
        </form>
    </div>

    <div class="hm-card p-4">
        <h5 class="mb-3">Your Public Forms</h5>

        <?php if (empty($forms)): ?>
            <div class="text-muted">No public forms generated yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Form</th>
                            <th>Status</th>
                            <th>Shareable URL</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($forms as $form): ?>
                        <?php
                        $url = BASE_URL . '/public/lead-form.php?form=' . rawurlencode($form['form_token']);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($form['form_name']); ?></strong>
                            </td>
                            <td>
                                <span class="badge <?php echo $form['status'] === 'ACTIVE' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo htmlspecialchars($form['status']); ?>
                                </span>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($url); ?></code>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="btn btn-sm btn-outline-primary">Open</a>
                                    <form method="POST">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="form_id" value="<?php echo (int) $form['id']; ?>">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">
                                            <?php echo $form['status'] === 'ACTIVE' ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
