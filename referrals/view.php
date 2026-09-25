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

/*
|--------------------------------------------------------------------------
| Fetch Referral
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        r.*,
        u.name AS created_by_name

    FROM referrals r

    INNER JOIN users u
        ON r.created_by = u.id

    WHERE r.id = ?

    LIMIT 1
");

$stmt->execute([$id]);

$referral = $stmt->fetch();

if (!$referral) {

    http_response_code(404);

    exit('Referral not found.');
}

$page_title = 'Referral Details';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Header -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="hm-page-title mb-1">
                Referral Details
            </h2>

            <p class="hm-muted mb-0">
                View complete referral partner information.
            </p>

        </div>

        <div class="d-flex gap-2">

            <a
                href="<?php echo BASE_URL; ?>/referrals/edit.php?id=<?php echo $id; ?>"
                class="btn btn-hm-primary"
            >
                Edit Referral
            </a>
            <a
    href="<?php echo BASE_URL; ?>/referrals/delete.php?id=<?php echo $id; ?>"
    class="btn btn-outline-danger"
>
    Delete
</a>

            <a
                href="<?php echo BASE_URL; ?>/referrals/"
                class="btn btn-outline-secondary"
            >
                Back
            </a>

        </div>

    </div>

    <!-- Referral Information -->

    <div class="hm-card p-4">

        <div class="row g-4">

            <!-- Name -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Referral Name
                </div>

                <h5 class="mb-0">

                    <?php
                    echo htmlspecialchars(
                        $referral['name']
                    );
                    ?>

                </h5>

            </div>

            <!-- Type -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Referral Type
                </div>

                <span class="badge bg-secondary">

                    <?php
                    echo htmlspecialchars(
                        $referral['referral_type']
                    );
                    ?>

                </span>

            </div>

            <!-- Organization -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Organization
                </div>

                <div>

                    <?php
                    echo htmlspecialchars(
                        $referral['organization']
                        ?: '-'
                    );
                    ?>

                </div>

            </div>

            <!-- Specialty -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Specialty
                </div>

                <div>

                    <?php
                    echo htmlspecialchars(
                        $referral['specialty']
                        ?: '-'
                    );
                    ?>

                </div>

            </div>

            <!-- Phone -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Phone
                </div>

                <div>

                    <?php
                    echo htmlspecialchars(
                        $referral['phone']
                        ?: '-'
                    );
                    ?>

                </div>

            </div>

            <!-- Email -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Email
                </div>

                <div>

                    <?php
                    echo htmlspecialchars(
                        $referral['email']
                        ?: '-'
                    );
                    ?>

                </div>

            </div>

            <!-- Status -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Status
                </div>

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

            </div>

            <!-- Created By -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Created By
                </div>

                <div>

                    <?php
                    echo htmlspecialchars(
                        $referral['created_by_name']
                    );
                    ?>

                </div>

            </div>

            <!-- Address -->

            <div class="col-12">

                <div class="hm-muted mb-1">
                    Address
                </div>

                <div>

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $referral['address']
                            ?: '-'
                        )
                    );
                    ?>

                </div>

            </div>

            <!-- Notes -->

            <div class="col-12">

                <div class="hm-muted mb-1">
                    Notes
                </div>

                <div>

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $referral['notes']
                            ?: '-'
                        )
                    );
                    ?>

                </div>

            </div>

            <!-- Created At -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Created At
                </div>

                <div>

                    <?php
                    echo htmlspecialchars(
                        $referral['created_at']
                    );
                    ?>

                </div>

            </div>

            <!-- Updated At -->

            <div class="col-md-6">

                <div class="hm-muted mb-1">
                    Updated At
                </div>

                <div>

                    <?php
                    echo htmlspecialchars(
                        $referral['updated_at']
                    );
                    ?>

                </div>

            </div>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>