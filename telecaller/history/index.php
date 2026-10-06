<?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('telecaller');

$user = current_user();
$user_id = (int) $user['id'];

$type = trim($_GET['type'] ?? '');
$search = trim($_GET['search'] ?? '');
$limit = 150;

$allowed_types = ['Call', 'Follow-up', 'WhatsApp', 'Appointment'];
if ($type !== '' && !in_array($type, $allowed_types, true)) {
    $type = '';
}

/* Each source is stored separately, then presented as one read-only history stream. */
$union = "
    SELECT
        lc.lead_id,
        'Call' AS history_type,
        lc.call_at AS happened_at,
        CONCAT(
            'Outcome: ', lc.call_outcome,
            CASE
                WHEN COALESCE(lc.call_notes, '') <> ''
                THEN CONCAT('. ', lc.call_notes)
                ELSE ''
            END
        ) AS description
    FROM lead_calls lc
    INNER JOIN leads l ON l.id = lc.lead_id
    WHERE lc.user_id = ? AND l.assigned_to = ?

    UNION ALL

    SELECT
        a.lead_id,
        'Appointment' AS history_type,
        a.created_at AS happened_at,
        CONCAT(
            'Appointment: ',
            COALESCE(a.appointment_type, 'Hospital appointment'),
            '. Status: ', a.status,
            CASE
                WHEN COALESCE(a.notes, '') <> ''
                THEN CONCAT('. ', a.notes)
                ELSE ''
            END
        ) AS description
    FROM appointments a
    INNER JOIN leads l ON l.id = a.lead_id
    WHERE a.created_by = ? AND l.assigned_to = ?

    UNION ALL

    SELECT
        la.lead_id,
        la.activity_type AS history_type,
        la.activity_at AS happened_at,
        la.description
    FROM lead_activities la
    INNER JOIN leads l ON l.id = la.lead_id
    WHERE la.user_id = ?
      AND l.assigned_to = ?
      AND la.activity_type IN (
          'Call',
          'Telecaller Call',
          'Follow-up',
          'WhatsApp',
          'Appointment'
      )
";

$params = [$user_id, $user_id, $user_id, $user_id, $user_id, $user_id];

$sql = "SELECT h.lead_id, h.history_type, h.happened_at, h.description, l.name AS lead_name, l.phone AS lead_phone FROM (" . $union . ") h INNER JOIN leads l ON l.id = h.lead_id WHERE 1 = 1";

if ($type !== '') {
    $sql .= ' AND h.history_type = ?';
    $params[] = $type;
}

if ($search !== '') {
    $sql .= ' AND (l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ?)';
    $search_value = '%' . $search . '%';
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
}

$sql .= ' ORDER BY h.happened_at DESC LIMIT ' . $limit;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'My History';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="hm-page-title mb-1">My History</h1>
            <p class="hm-muted mb-0">Calls, follow-ups, appointments and meaningful lead activities recorded by you.</p>
        </div>
        <a href="<?php echo BASE_URL; ?>/telecaller/dashboard.php" class="btn btn-outline-secondary">My Day</a>
    </div>

    <div class="hm-card p-4 mb-4">
        <form method="GET">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Patient name, mobile or email">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select">
                        <option value="">All History</option>
                        <?php foreach ($allowed_types as $type_option): ?>
                            <option value="<?php echo htmlspecialchars($type_option); ?>" <?php echo $type === $type_option ? 'selected' : ''; ?>><?php echo htmlspecialchars($type_option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-hm-primary w-100" type="submit">Search</button>
                </div>
            </div>
        </form>
    </div>

    <div class="hm-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Activity History</h5>
            <span class="small text-muted"><?php echo count($history); ?> record(s)</span>
        </div>

        <?php if (empty($history)): ?>
            <div class="alert alert-light mb-0">No history found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead><tr><th>Date & Time</th><th>Patient</th><th>Type</th><th>Details</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach ($history as $item): ?>
                        <tr>
                            <td class="text-nowrap"><?php echo date('d M Y, h:i A', strtotime($item['happened_at'])); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['lead_name']); ?></strong>
                                <div class="small text-muted"><?php echo htmlspecialchars($item['lead_phone']); ?></div>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($item['history_type']); ?></span></td>
                            <td><?php echo nl2br(htmlspecialchars($item['description'] ?? '')); ?></td>
                            <td><a href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo (int) $item['lead_id']; ?>" class="btn btn-sm btn-outline-secondary">Open</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
