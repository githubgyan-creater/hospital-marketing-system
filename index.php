 <?php

$page_title = 'Welcome';

require_once __DIR__ . '/includes/header.php';

?>

<nav class="navbar hm-navbar">
    <div class="container">

        <a
            href="<?php echo BASE_URL; ?>/"
            class="hm-brand"
        >
            Hospital Marketing
        </a>

    </div>
</nav>


<main class="container py-5">

    <div class="hm-card p-5 text-center">

        <div class="mb-3">

            <span class="badge text-bg-light">
                Hospital Marketing System
            </span>

        </div>

        <h1 class="hm-page-title mb-3">
            Hospital Marketing Team Management System
        </h1>

        <p class="hm-muted mb-4">
            Centralized management for leads, follow-ups,
            campaigns, referrals, activities and team operations.
        </p>

        <a
            href="<?php echo BASE_URL; ?>/login.php"
            class="btn btn-hm-primary px-4"
        >
            Staff Login
        </a>

    </div>

</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>  