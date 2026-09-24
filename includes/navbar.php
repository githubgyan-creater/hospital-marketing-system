<?php

require_once __DIR__ . '/auth.php';

$user = current_user();

?>

<nav class="navbar navbar-expand-lg hm-navbar">

    <div class="container">

        <a
            class="hm-brand"
            href="<?php echo BASE_URL; ?>/index.php"
        >
            Hospital Marketing
        </a>


        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
        >

            <span class="navbar-toggler-icon"></span>

        </button>


        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >

            <?php if ($user): ?>

                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <?php if ($user['role'] === 'admin'): ?>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/admin/dashboard.php"
                            >
                                Dashboard
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/admin/users/index.php"
                            >
                                User Management
                            </a>

                        </li>


                    <?php elseif ($user['role'] === 'manager'): ?>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/manager/dashboard.php"
                            >
                                Dashboard
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/manager/team/index.php"
                            >
                                Team
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/manager/leads/index.php"
                            >
                                Leads
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/manager/reports/index.php"
                            >
                                Reports
                            </a>

                        </li>


                    <?php elseif ($user['role'] === 'telecaller'): ?>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/telecaller/dashboard.php"
                            >
                                My Day
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/telecaller/leads/index.php"
                            >
                                Leads
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/telecaller/calls/index.php"
                            >
                                Calls
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/telecaller/followups/index.php"
                            >
                                Follow-ups
                            </a>

                        </li>


                    <?php elseif ($user['role'] === 'marketing'): ?>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/marketing/dashboard.php"
                            >
                                My Day
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/marketing/leads/index.php"
                            >
                                Leads
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/marketing/tasks/index.php"
                            >
                                Tasks
                            </a>

                        </li>

                        <li class="nav-item">

                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/marketing/visits/index.php"
                            >
                                Visits
                            </a>

                        </li>

                    <?php endif; ?>

                </ul>


                <div class="d-flex align-items-center">

                    <span class="text-white me-3">

                        <?php
                        echo htmlspecialchars(
                            $user['name']
                        );
                        ?>

                    </span>

                    <a
                        href="<?php echo BASE_URL; ?>/logout.php"
                        class="btn btn-sm btn-outline-light"
                    >
                        Logout
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</nav>