 <?php

require_once __DIR__ . '/auth.php';

$user = current_user();

?>

<nav class="navbar navbar-expand-lg hm-navbar">

    <div class="container">

        <!-- BRAND -->

        <a
            class="hm-brand"
            href="<?php echo BASE_URL; ?>/index.php"
        >
            Hospital Marketing
        </a>


        <!-- MOBILE TOGGLER -->

        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#mainNavbar"
            aria-controls="mainNavbar"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="navbar-toggler-icon"></span>
        </button>


        <!-- NAVIGATION -->

        <div
            class="collapse navbar-collapse"
            id="mainNavbar"
        >

            <?php if ($user): ?>

                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

<!-- ADMIN -->

<?php if ($user['role'] === 'admin'): ?>

    <!-- DASHBOARD -->

    <li class="nav-item">

        <a
            class="nav-link text-white"
            href="<?php echo BASE_URL; ?>/admin/dashboard.php"
        >
            Dashboard
        </a>

    </li>


    <!-- MASTER DATA DROPDOWN -->

    <li class="nav-item dropdown">

        <a
            class="nav-link dropdown-toggle text-white"
            href="#"
            id="adminMasterDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            Master Data
        </a>

        <ul
            class="dropdown-menu"
            aria-labelledby="adminMasterDropdown"
        >

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/admin/hospital/"
                >
                    Hospital Master
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/admin/departments/"
                >
                    Departments
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/admin/doctors/"
                >
                    Doctors
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/admin/services/"
                >
                    Services
                </a>
            </li>

        </ul>

    </li>


    <!-- OPERATIONS DROPDOWN -->

    <li class="nav-item dropdown">

        <a
            class="nav-link dropdown-toggle text-white"
            href="#"
            id="adminOperationsDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            Operations
        </a>

        <ul
            class="dropdown-menu"
            aria-labelledby="adminOperationsDropdown"
        >

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/leads/index.php"
                >
                    Leads
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/referrals/index.php"
                >
                    Referrals
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/campaigns/index.php"
                >
                    Campaigns
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/events/index.php"
                >
                    Events
                </a>
            </li>

        </ul>

    </li>


    <!-- ADMINISTRATION DROPDOWN -->

    <li class="nav-item dropdown">

        <a
            class="nav-link dropdown-toggle text-white"
            href="#"
            id="adminAdministrationDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            Administration
        </a>

        <ul
            class="dropdown-menu"
            aria-labelledby="adminAdministrationDropdown"
        >

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/admin/users/index.php"
                >
                    Users
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/admin/roles/"
                >
                    Roles & Permissions
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/admin/settings/"
                >
                    Settings
                </a>
            </li>

        </ul>

    </li>


    <!-- REPORTS -->

    <li class="nav-item">

        <a
            class="nav-link text-white"
            href="<?php echo BASE_URL; ?>/admin/reports/index.php"
        >
            Reports
        </a>

    </li>
                     

                    <!-- MANAGER -->
 
                    <?php elseif ($user['role'] === 'manager'): ?>

    <!-- DASHBOARD -->

    <li class="nav-item">
        <a
            class="nav-link text-white"
            href="<?php echo BASE_URL; ?>/manager/dashboard.php"
        >
            Dashboard
        </a>
    </li>


    <!-- MANAGEMENT DROPDOWN -->

    <li class="nav-item dropdown">

        <a
            class="nav-link dropdown-toggle text-white"
            href="#"
            id="managerManagementDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            Management
        </a>

        <ul
            class="dropdown-menu"
            aria-labelledby="managerManagementDropdown"
        >

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/manager/control-center.php"
                >
                    Control Center
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/manager/action-required.php"
                >
                    Action Required
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/manager/review.php"
                >
                    Management Review
                </a>
            </li>

        </ul>

    </li>


    <!-- PLANNING DROPDOWN -->

    <li class="nav-item dropdown">

        <a
            class="nav-link dropdown-toggle text-white"
            href="#"
            id="managerPlanningDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            Planning
        </a>

        <ul
            class="dropdown-menu"
            aria-labelledby="managerPlanningDropdown"
        >

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/marketing/marketing-plans/index.php"
                >
                    Marketing Plans
                </a>
            </li>

        </ul>

    </li>


    <!-- OPERATIONS DROPDOWN -->

    <li class="nav-item dropdown">

        <a
            class="nav-link dropdown-toggle text-white"
            href="#"
            id="managerOperationsDropdown"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
        >
            Operations
        </a>

        <ul
            class="dropdown-menu"
            aria-labelledby="managerOperationsDropdown"
        >

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/manager/leads/bulk-import.php"
                >
                    Bulk Lead Import
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/manager/forms/index.php"
                >
                    Generate Form
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/manager/team/index.php"
                >
                    Team
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/manager/leads/index.php"
                >
                    Leads
                </a>
            </li>


            <li>
    <a
        class="dropdown-item"
        href="<?php echo BASE_URL; ?>/referrals/index.php"
    >
        Referrals
    </a>
</li>


<li>
    <a
        class="dropdown-item"
        href="<?php echo BASE_URL; ?>/campaigns/index.php"
    >
        Campaigns
    </a>
</li>

            <li>
    <a
        class="dropdown-item"
        href="<?php echo BASE_URL; ?>/manager/tasks/index.php"
    >
        Tasks
    </a>
</li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/telecaller/appointments/index.php"
                >
                    Appointments
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/manager/visits/index.php"
                >
                    Visits
                </a>
            </li>

            <li>
                <a
                    class="dropdown-item"
                    href="<?php echo BASE_URL; ?>/events/index.php"
                >
                    Events
                </a>
            </li>

        </ul>

    </li>


    <!-- REPORTS -->

    <li class="nav-item">

        <a
            class="nav-link text-white"
            href="<?php echo BASE_URL; ?>/manager/reports/index.php"
        >
            Reports
        </a>

    </li> 

                    <!-- TELECALLER -->

                    <?php elseif ($user['role'] === 'telecaller'): ?>

                        <li class="nav-item">
                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/telecaller/dashboard.php"
                            >
                                  Dashboard
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

                        <li class="nav-item">
                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/telecaller/appointments/index.php"
                            >
                                Appointments
                            </a>
                        </li>


                    <!-- MARKETING EXECUTIVE -->

                    <?php elseif ($user['role'] === 'marketing'): ?>

                        <li class="nav-item">
                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/marketing/dashboard.php"
                            >
                            Dashboard
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
                                href="<?php echo BASE_URL; ?>/manager/forms/index.php"
                            >
                                Generate Form
                            </a>
                        </li>

<li class="nav-item">
    <a
        class="nav-link text-white"
        href="<?php echo BASE_URL; ?>/marketing/followups/index.php"
    >
        Follow-ups
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

                        <li class="nav-item">
    <a
        class="nav-link text-white"
        href="<?php echo BASE_URL; ?>/marketing/events/"
    >
        Events
    </a>
</li>


                        <li class="nav-item">
    <a
        class="nav-link text-white"
        href="<?php echo BASE_URL; ?>/marketing/performance.php"
    >
        My Performance
    </a>
</li>

                        <li class="nav-item">
                            <a
                                class="nav-link text-white"
                                href="<?php echo BASE_URL; ?>/telecaller/appointments/index.php"
                            >
                                Appointments
                            </a>
                        </li>

                    <?php endif; ?>


                </ul>


                <!-- USER + LOGOUT -->

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