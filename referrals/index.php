<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role('admin', 'manager');

$error = '';

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$type_filter = trim($_GET['referral_type'] ?? '');

$status_filter = trim($_GET['status'] ?? '');

/*
|--------------------------------------------------------------------------
| Build Query
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        r.id,
        r.name,
        r.referral_type,
        r.organization,
        r.phone,
        r.email,
        r.specialty,
        r.status,
        r.created_at,

        u.name AS created_by_name

    FROM referrals r

    INNER JOIN users u
        ON r.created_by = u.id

    WHERE 1 = 1
";

$params = [];

/*
|--------------------------------------------------------------------------
| Search
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            r.name LIKE ?
            OR r.organization LIKE ?
            OR r.phone LIKE ?
            OR r.email LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
    $params[] = $search_value;
}

/*
|--------------------------------------------------------------------------
| Referral Type Filter
|--------------------------------------------------------------------------
*/

$referral_types = [
    'Doctor',
    'Clinic',
    'Hospital',
    'Corporate',
    'Health Professional',
    'Other'
];

if (
    $type_filter !== '' &&
    in_array($type_filter, $referral_types, true)
) {

    $sql .= "
        AND r.referral_type = ?
    ";

    $params[] = $type_filter;
}

/*
|--------------------------------------------------------------------------
| Status Filter
|--------------------------------------------------------------------------
*/

if (
    $status_filter !== '' &&
    in_array($status_filter, ['Active', 'Inactive'], true)
) {

    $sql .= "
        AND r.status = ?
    ";

    $params[] = $status_filter;
}

/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY r.created_at DESC
";

/*
|--------------------------------------------------------------------------
| Execute
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$referrals = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Summary
|--------------------------------------------------------------------------
*/

$stmt = $pdo->query("
    SELECT
        COUNT(*) AS total_referrals,

        SUM(status = 'Active') AS active_referrals,

        SUM(status = 'Inactive') AS inactive_referrals

    FROM referrals
");

$summary = $stmt->fetch();

$total_referrals =
    (int) ($summary['total_referrals'] ?? 0);

$active_referrals =
    (int) ($summary['active_referrals'] ?? 0);

$inactive_referrals =
    (int) ($summary['inactive_referrals'] ?? 0);

$page_title = 'Referral Management';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Page Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Referral Management
            </h2>

            <p class="hm-muted mb-0">
                Manage referral partners and referral sources.
            </p>

        </div>

        <div class="d-flex gap-2">

            <a
                href="<?php echo BASE_URL; ?>/referrals/add.php"
                class="btn btn-hm-primary"
            >
                + Add Referral
            </a>

            <a
                href="<?php echo BASE_URL; ?>/manager/dashboard.php"
                class="btn btn-outline-secondary"
            >
                Dashboard
            </a>

        </div>

    </div>

    <!-- Summary Cards -->

    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Total Referrals
                </div>

                <h2 class="mb-0">
                    <?php echo $total_referrals; ?>
                </h2>

            </div>

        </div>

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Active Referrals
                </div>

                <h2 class="mb-0 text-success">
                    <?php echo $active_referrals; ?>
                </h2>

            </div>

        </div>

        <div class="col-md-4">

            <div class="hm-card p-4 h-100">

                <div class="hm-muted">
                    Inactive Referrals
                </div>

                <h2 class="mb-0 text-secondary">
                    <?php echo $inactive_referrals; ?>
                </h2>

            </div>

        </div>

    </div>

    <!-- Filters -->

    <div class="hm-card p-4 mb-4">

        <form method="GET">

            <div class="row g-3 align-items-end">

                <!-- Search -->

                <div class="col-lg-5">

                    <label
                        for="search"
                        class="form-label"
                    >
                        Search
                    </label>

                    <input
                        type="text"
                        name="search"
                        id="search"
                        class="form-control"
                        placeholder="Name, organization, phone or email"
                        value="<?php echo htmlspecialchars($search); ?>"
                    >

                </div>

                <!-- Referral Type -->

                <div class="col-md-3">

                    <label
                        for="referral_type"
                        class="form-label"
                    >
                        Referral Type
                    </label>

                    <select
                        name="referral_type"
                        id="referral_type"
                        class="form-select"
                    >

                        <option value="">
                            All Types
                        </option>

                        <?php foreach ($referral_types as $type): ?>

                            <option
                                value="<?php echo htmlspecialchars($type); ?>"
                                <?php
                                echo $type_filter === $type
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

                <!-- Status -->

                <div class="col-md-2">

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

                        <option value="">
                            All
                        </option>

                        <option
                            value="Active"
                            <?php
                            echo $status_filter === 'Active'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Active
                        </option>

                        <option
                            value="Inactive"
                            <?php
                            echo $status_filter === 'Inactive'
                                ? 'selected'
                                : '';
                            ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>

                <!-- Buttons -->

                <div class="col-md-2 d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        Filter
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/referrals/"
                        class="btn btn-outline-secondary"
                    >
                        Reset
                    </a>

                </div>

            </div>

        </form>

    </div>

    <!-- Referral Table -->

    <div class="hm-card p-4">

        <div class="d-flex justify-content-between align-items-center mb-3">

            <h5 class="mb-0">
                Referral Partners
            </h5>

            <span class="hm-muted">
                <?php echo count($referrals); ?> referral(s)
            </span>

        </div>

        <?php if (empty($referrals)): ?>

            <div class="alert alert-light mb-0">
                No referral partners found.
            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table align-middle">

                    <thead>

                        <tr>

                            <th>
                                Name
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Organization
                            </th>

                            <th>
                                Contact
                            </th>

                            <th>
                                Specialty
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created By
                            </th>

                            <th>
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($referrals as $referral): ?>

                            <tr>

                                <!-- Name -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $referral['name']
                                        );
                                        ?>

                                    </strong>

                                </td>

                                <!-- Type -->

                                <td>

                                    <span class="badge bg-secondary">

                                        <?php
                                        echo htmlspecialchars(
                                            $referral['referral_type']
                                        );
                                        ?>

                                    </span>

                                </td>

                                <!-- Organization -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $referral['organization']
                                        ?: '-'
                                    );
                                    ?>

                                </td>

                                <!-- Contact -->

                                <td>

                                    <?php if (!empty($referral['phone'])): ?>

                                        <div>
                                            <?php
                                            echo htmlspecialchars(
                                                $referral['phone']
                                            );
                                            ?>
                                        </div>

                                    <?php endif; ?>

                                    <?php if (!empty($referral['email'])): ?>

                                        <small class="hm-muted">

                                            <?php
                                            echo htmlspecialchars(
                                                $referral['email']
                                            );
                                            ?>

                                        </small>

                                    <?php endif; ?>

                                    <?php if (
                                        empty($referral['phone']) &&
                                        empty($referral['email'])
                                    ): ?>

                                        <span class="hm-muted">
                                            -
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Specialty -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $referral['specialty']
                                        ?: '-'
                                    );
                                    ?>

                                </td>

                                <!-- Status -->

                                <td>

                                    <?php if (
                                        $referral['status'] === 'Active'
                                    ): ?>

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">
                                            Inactive
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <!-- Created By -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $referral['created_by_name']
                                    );
                                    ?>

                                </td>

                                <!-- Action -->

                                <td>

                                    <a
                                        href="<?php echo BASE_URL; ?>/referrals/view.php?id=<?php echo (int) $referral['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary"
                                    >
                                        View
                                    </a>

                                    <a
                                        href="<?php echo BASE_URL; ?>/referrals/edit.php?id=<?php echo (int) $referral['id']; ?>"
                                        class="btn btn-sm btn-outline-primary mt-1"
                                    >
                                        Edit
                                    </a>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>