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

| Check Referral Exists

*/

$stmt = $pdo->prepare("
    SELECT id, name
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

| Delete Referral

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $pdo->prepare("
        DELETE FROM referrals
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    header(
        'Location: ' .
        BASE_URL .
        '/referrals/'
    );

    exit;
}

$page_title = 'Delete Referral';

require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="hm-card p-4">

                <h2 class="hm-page-title mb-3">
                    Delete Referral
                </h2>

                <div class="alert alert-warning">

                    Are you sure you want to delete this referral?

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $referral['name']
                        );
                        ?>
                    </strong>

                </div>

                <p class="hm-muted">
                    This action cannot be undone.
                </p>

                <form method="POST">

                    <button
                        type="submit"
                        class="btn btn-danger"
                    >
                        Yes, Delete
                    </button>

                    <a
                        href="<?php echo BASE_URL; ?>/referrals/view.php?id=<?php echo $id; ?>"
                        class="btn btn-outline-secondary ms-2"
                    >
                        Cancel
                    </a>

                </form>

            </div>

        </div>

    </div>

</div>

<?php

require_once __DIR__ . '/../includes/footer.php';

?>