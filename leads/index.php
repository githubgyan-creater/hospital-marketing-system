 
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';
require_once __DIR__ . '/../includes/permission_check.php';

require_login();

$current_user = current_user();

require_role(
    'admin',
    'manager',
    'telecaller',
    'marketing'
);

require_permission('leads.view');

$page_title = 'Leads';


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$current_user_id = (int) (
    $current_user['id'] ?? 0
);


/*
|--------------------------------------------------------------------------
| LOAD CURRENT USER ROLE DIRECTLY FROM DATABASE
|--------------------------------------------------------------------------
|
| This makes the Telecaller visibility restriction reliable even if
| current_user() does not contain the role field.
|
*/

$current_user_role = '';

if ($current_user_id > 0) {

    $role_stmt = $pdo->prepare("
        SELECT
            LOWER(TRIM(r.name)) AS role_name
        FROM users u
        INNER JOIN roles r
            ON u.role_id = r.id
        WHERE u.id = ?
        LIMIT 1
    ");

    $role_stmt->execute([
        $current_user_id
    ]);

    $current_user_role = (string) (
        $role_stmt->fetchColumn() ?? ''
    );
}


$is_telecaller = (
    $current_user_role === 'telecaller'
    && $current_user_id > 0
);


/*
|--------------------------------------------------------------------------
| GET FILTER VALUES
|--------------------------------------------------------------------------
*/

$search = trim(
    $_GET['search'] ?? ''
);

$status_filter = trim(
    $_GET['status'] ?? ''
);

$priority_filter = trim(
    $_GET['priority'] ?? ''
);

$assigned_filter = !empty(
    $_GET['assigned_to']
)
    ? (int) $_GET['assigned_to']
    : 0;


/*
|--------------------------------------------------------------------------
| TELECALLER SECURITY
|--------------------------------------------------------------------------
|
| Telecaller cannot use assigned_to from the URL to access another
| staff member's leads.
|
*/

if ($is_telecaller) {
    $assigned_filter = 0;
}


/*
|--------------------------------------------------------------------------
| GET ACTIVE STAFF
|--------------------------------------------------------------------------
|
| Used for Admin / Manager / Marketing users.
|
*/

$staff_stmt = $pdo->query("
    SELECT
        u.id,
        u.name,

        CASE

            WHEN LOWER(TRIM(r.name)) = 'manager'
                THEN 'Manager'

            WHEN LOWER(TRIM(r.name)) = 'telecaller'
                THEN 'Telecaller'

            WHEN LOWER(TRIM(r.name)) = 'marketing'
                THEN 'Marketing Executive'

            ELSE CONCAT(
                UPPER(LEFT(TRIM(r.name), 1)),
                LOWER(SUBSTRING(TRIM(r.name), 2))
            )

        END AS role_display

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE
        u.status = 'active'

        AND LOWER(TRIM(r.name)) IN (
            'manager',
            'telecaller',
            'marketing'
        )

    ORDER BY
        u.name ASC
");

$staff = $staff_stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| ALLOWED STATUSES
|--------------------------------------------------------------------------
*/

$statuses = [
    'New',
    'Contacted',
    'Interested',
    'Follow-up',
    'Not Interested',
    'Appointment',
    'Visited',
    'Converted',
    'Lost'
];


/*
|--------------------------------------------------------------------------
| ALLOWED PRIORITIES
|--------------------------------------------------------------------------
*/

$priorities = [
    'Low',
    'Medium',
    'High'
];


/*
|--------------------------------------------------------------------------
| BUILD LEAD QUERY
|--------------------------------------------------------------------------
*/

$sql = "

    SELECT

        l.id,
        l.name,
        l.phone,
        l.email,
        l.service_interest,
        l.status,
        l.priority,
        l.next_action_type,
        l.next_action_at,
        l.created_at,

        ms.name AS source_name,

        u.id AS assigned_user_id,
        u.name AS assigned_name,

        CASE

            WHEN LOWER(TRIM(r.name)) = 'manager'
                THEN 'Manager'

            WHEN LOWER(TRIM(r.name)) = 'telecaller'
                THEN 'Telecaller'

            WHEN LOWER(TRIM(r.name)) = 'marketing'
                THEN 'Marketing Executive'

            ELSE CONCAT(
                UPPER(LEFT(TRIM(r.name), 1)),
                LOWER(SUBSTRING(TRIM(r.name), 2))
            )

        END AS assigned_role_display

    FROM leads l

    LEFT JOIN marketing_sources ms
        ON l.source_id = ms.id

    LEFT JOIN users u
        ON l.assigned_to = u.id

    LEFT JOIN roles r
        ON u.role_id = r.id

    WHERE 1 = 1

";

$params = [];


/*
|--------------------------------------------------------------------------
| TELECALLER VISIBILITY RESTRICTION
|--------------------------------------------------------------------------
*/

if ($is_telecaller) {

    $sql .= "
        AND l.assigned_to = :current_user_id
    ";

    $params['current_user_id'] = $current_user_id;
}


/*
|--------------------------------------------------------------------------
| SEARCH FILTER
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "

        AND (

            l.name LIKE :search

            OR l.phone LIKE :search

            OR l.email LIKE :search

        )

    ";

    $params['search'] =
        '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| STATUS FILTER
|--------------------------------------------------------------------------
*/

if ($status_filter !== '') {

    $sql .= "
        AND l.status = :status
    ";

    $params['status'] =
        $status_filter;
}


/*
|--------------------------------------------------------------------------
| PRIORITY FILTER
|--------------------------------------------------------------------------
*/

if ($priority_filter !== '') {

    $sql .= "
        AND l.priority = :priority
    ";

    $params['priority'] =
        $priority_filter;
}


/*
|--------------------------------------------------------------------------
| ASSIGNED STAFF FILTER
|--------------------------------------------------------------------------
|
| Admin / Manager / Marketing only.
|
*/

if (
    !$is_telecaller
    &&
    $assigned_filter > 0
) {

    $sql .= "
        AND l.assigned_to = :assigned_to
    ";

    $params['assigned_to'] =
        $assigned_filter;
}


/*
|--------------------------------------------------------------------------
| ORDER
|--------------------------------------------------------------------------
*/

$sql .= "

    ORDER BY
        l.created_at DESC

";


/*
|--------------------------------------------------------------------------
| EXECUTE LEAD QUERY
|--------------------------------------------------------------------------
*/

$lead_stmt = $pdo->prepare($sql);

$lead_stmt->execute($params);

$leads = $lead_stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| ACTIVE FILTER CHECK
|--------------------------------------------------------------------------
*/

$has_filters =
    $search !== ''
    ||
    $status_filter !== ''
    ||
    $priority_filter !== ''
    ||
    $assigned_filter > 0;


require_once __DIR__ . '/../includes/header.php';

?>

<style>

/*
|--------------------------------------------------------------------------
| TABLE WRAPPER
|--------------------------------------------------------------------------
*/

.leads-table-wrap {
    width: 100%;
}


/*
|--------------------------------------------------------------------------
| TABLE
|--------------------------------------------------------------------------
*/

.leads-table {
    min-width: 1280px;
    margin-bottom: 0;
}


/*
|--------------------------------------------------------------------------
| TABLE HEADER
|--------------------------------------------------------------------------
*/

.leads-table thead th {
    font-size: 0.82rem;
    font-weight: 700;

    vertical-align: middle;

    white-space: nowrap;

    padding: 13px 14px;
}


/*
|--------------------------------------------------------------------------
| TABLE BODY
|--------------------------------------------------------------------------
*/

.leads-table tbody td {
    padding: 13px 14px;

    vertical-align: middle;

    font-size: 0.90rem;
}


/*
|--------------------------------------------------------------------------
| COLUMN WIDTHS
|--------------------------------------------------------------------------
*/

.leads-col-name {
    width: 180px;
    min-width: 180px;
}

.leads-col-phone {
    width: 145px;
    min-width: 145px;
}

.leads-col-service {
    width: 220px;
    min-width: 220px;
}

.leads-col-source {
    width: 140px;
    min-width: 140px;
}

.leads-col-status {
    width: 115px;
    min-width: 115px;
}

.leads-col-priority {
    width: 105px;
    min-width: 105px;
}

.leads-col-assigned {
    width: 190px;
    min-width: 190px;
}

.leads-col-next-action {
    width: 185px;
    min-width: 185px;
}

.leads-col-action {
    width: 90px;
    min-width: 90px;
}


/*
|--------------------------------------------------------------------------
| NAME
|--------------------------------------------------------------------------
*/

.leads-name {
    font-weight: 600;
    line-height: 1.35;
}


/*
|--------------------------------------------------------------------------
| PHONE
|--------------------------------------------------------------------------
*/

.leads-phone {
    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| SERVICE / SOURCE
|--------------------------------------------------------------------------
*/

.leads-truncate {
    max-width: 220px;

    overflow: hidden;
    text-overflow: ellipsis;

    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| ASSIGNED USER
|--------------------------------------------------------------------------
*/

.leads-assigned-cell {
    vertical-align: middle !important;
}

.leads-assigned-user {
    display: flex;

    flex-direction: column;

    align-items: flex-start;

    gap: 5px;
}


/*
|--------------------------------------------------------------------------
| ASSIGNED NAME
|--------------------------------------------------------------------------
*/

.leads-assigned-name {
    display: block;

    max-width: 175px;

    font-weight: 600;
    line-height: 1.25;

    white-space: normal;
}


/*
|--------------------------------------------------------------------------
| ASSIGNED ROLE
|--------------------------------------------------------------------------
*/

.leads-assigned-role {
    display: inline-flex;

    align-items: center;

    padding: 3px 8px;

    border-radius: 999px;

    background: #f1f3f5;

    color: #495057;

    font-size: 0.68rem;

    font-weight: 700;

    line-height: 1.15;

    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| NOT ASSIGNED
|--------------------------------------------------------------------------
*/

.leads-not-assigned {
    font-size: 0.72rem;
}


/*
|--------------------------------------------------------------------------
| STATUS / PRIORITY
|--------------------------------------------------------------------------
*/

.leads-status-badge,
.leads-priority-badge {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    min-width: 78px;

    font-size: 0.72rem;

    font-weight: 600;

    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| NEXT ACTION
|--------------------------------------------------------------------------
*/

.leads-next-action {
    display: flex;

    flex-direction: column;

    gap: 3px;
}

.leads-next-action-type {
    font-weight: 600;

    line-height: 1.25;
}

.leads-next-action-date {
    font-size: 0.75rem;

    white-space: nowrap;
}


/*
|--------------------------------------------------------------------------
| ACTION
|--------------------------------------------------------------------------
*/

.leads-action-cell {
    text-align: center;

    white-space: nowrap;
}

.leads-view-btn {
    min-width: 62px;
}


/*
|--------------------------------------------------------------------------
| TELECALLER INFO
|--------------------------------------------------------------------------
*/

.telecaller-leads-info {
    border-left: 4px solid #0d6efd;
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 768px) {

    .leads-table {
        min-width: 1180px;
    }

    .leads-table thead th,
    .leads-table tbody td {
        padding: 10px 11px;
    }

    .leads-assigned-name {
        max-width: 145px;
    }

    .leads-truncate {
        max-width: 180px;
    }

}


/*
|--------------------------------------------------------------------------
| VERY SMALL SCREENS
|--------------------------------------------------------------------------
*/

@media (max-width: 480px) {

    .leads-table {
        min-width: 1100px;
    }

}

</style>


<div class="container py-4">


    <!-- =========================================================
         PAGE HEADER
    ========================================================== -->

    <div
        class="d-flex flex-column flex-md-row
               justify-content-between
               align-items-md-center
               gap-3
               mb-4"
    >

        <div>

            <h1 class="hm-page-title mb-1">
                Leads
            </h1>

            <p class="hm-muted mb-0">

                <?php if ($is_telecaller): ?>

                    Manage your assigned hospital
                    marketing enquiries and lead activities.

                <?php else: ?>

                    Manage hospital marketing enquiries
                    and lead activities.

                <?php endif; ?>

            </p>

        </div>


        <!-- ADD LEAD -->

        <?php if (!$is_telecaller): ?>

            <div>

                <a
                    href="<?php
                        echo BASE_URL;
                    ?>/leads/add.php"
                    class="btn btn-hm-primary"
                >
                    + Add Lead
                </a>

            </div>

        <?php endif; ?>

    </div>


    <!-- =========================================================
         TELECALLER INFORMATION
    ========================================================== -->

    <?php if ($is_telecaller): ?>

        <div
            class="hm-card p-3 mb-4 telecaller-leads-info"
        >

            <div class="d-flex flex-column">

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $current_user['name']
                        ?? 'Telecaller',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                </strong>

                <small class="hm-muted">
                    Showing only leads assigned to you.
                </small>

            </div>

        </div>

    <?php endif; ?>


    <!-- =========================================================
         SEARCH & FILTERS
    ========================================================== -->

    <div class="hm-card p-4 mb-4">

        <h5
            class="mb-3"
            style="color: var(--hm-navy);"
        >
            Search & Filters
        </h5>


        <form method="GET">

            <div class="row g-3">


                <!-- SEARCH -->

                <div class="col-md-6">

                    <label class="form-label">
                        Search Name / Phone / Email
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Enter name, phone or email"
                        value="<?php

                            echo htmlspecialchars(
                                $search,
                                ENT_QUOTES,
                                'UTF-8'
                            );

                        ?>"
                    >

                </div>


                <!-- STATUS -->

                <div class="col-md-2">

                    <label class="form-label">
                        Status
                    </label>

                    <select
                        name="status"
                        class="form-select"
                    >

                        <option value="">
                            All Status
                        </option>

                        <?php foreach (
                            $statuses
                            as $status
                        ): ?>

                            <option
                                value="<?php

                                    echo htmlspecialchars(
                                        $status,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                ?>"
                                <?php

                                    echo (
                                        $status_filter === $status
                                    )
                                        ? 'selected'
                                        : '';

                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $status,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- PRIORITY -->

                <div class="col-md-2">

                    <label class="form-label">
                        Priority
                    </label>

                    <select
                        name="priority"
                        class="form-select"
                    >

                        <option value="">
                            All Priority
                        </option>

                        <?php foreach (
                            $priorities
                            as $priority
                        ): ?>

                            <option
                                value="<?php

                                    echo htmlspecialchars(
                                        $priority,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                ?>"
                                <?php

                                    echo (
                                        $priority_filter === $priority
                                    )
                                        ? 'selected'
                                        : '';

                                ?>
                            >

                                <?php

                                echo htmlspecialchars(
                                    $priority,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- ASSIGNED STAFF -->

                <?php if (!$is_telecaller): ?>

                    <div class="col-md-2">

                        <label class="form-label">
                            Assigned To
                        </label>

                        <select
                            name="assigned_to"
                            class="form-select"
                        >

                            <option value="">
                                All Staff
                            </option>

                            <?php foreach (
                                $staff
                                as $member
                            ): ?>

                                <option
                                    value="<?php

                                        echo (int)
                                            $member['id'];

                                    ?>"
                                    <?php

                                        echo (
                                            $assigned_filter
                                            ==
                                            $member['id']
                                        )
                                            ? 'selected'
                                            : '';

                                    ?>
                                >

                                    <?php

                                    echo htmlspecialchars(
                                        $member['name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                    -

                                    <?php

                                    echo htmlspecialchars(
                                        $member['role_display'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                <?php endif; ?>


                <!-- BUTTONS -->

                <div class="col-12">

                    <hr>

                    <button
                        type="submit"
                        class="btn btn-hm-primary"
                    >
                        🔎 Search / Filter
                    </button>


                    <a
                        href="<?php
                            echo BASE_URL;
                        ?>/leads/index.php"
                        class="btn btn-outline-secondary ms-2"
                    >
                        🔄 Reset Filters
                    </a>

                </div>

            </div>

        </form>

    </div>


    <!-- =========================================================
         RESULT SUMMARY
    ========================================================== -->

    <div
        class="d-flex
               flex-column flex-sm-row
               justify-content-between
               align-items-sm-center
               gap-2
               mb-3"
    >

        <div>

            <strong>
                <?php echo count($leads); ?>
            </strong>

            <?php if ($is_telecaller): ?>

                assigned lead(s) found

            <?php else: ?>

                lead(s) found

            <?php endif; ?>

        </div>


        <?php if ($has_filters): ?>

            <span class="badge text-bg-light">
                Filters Applied
            </span>

        <?php endif; ?>

    </div>


    <!-- =========================================================
         LEAD TABLE
    ========================================================== -->

    <div class="hm-card">

        <div class="table-responsive leads-table-wrap">

            <table
                class="table table-hover
                       align-middle
                       leads-table"
            >

                <thead>

                    <tr>

                        <th class="leads-col-name">
                            Name
                        </th>

                        <th class="leads-col-phone">
                            Phone
                        </th>

                        <th class="leads-col-service">
                            Service
                        </th>

                        <th class="leads-col-source">
                            Source
                        </th>

                        <th class="leads-col-status">
                            Status
                        </th>

                        <th class="leads-col-priority">
                            Priority
                        </th>

                        <th class="leads-col-assigned">
                            Assigned To
                        </th>

                        <th class="leads-col-next-action">
                            Next Action
                        </th>

                        <th class="leads-col-action">
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (empty($leads)): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-5"
                            >

                                <div class="hm-muted">

                                    <?php if ($is_telecaller): ?>

                                        No leads are currently
                                        assigned to you.

                                    <?php else: ?>

                                        No leads found.

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $leads
                            as $lead
                        ): ?>

                            <tr>


                                <!-- NAME -->

                                <td>

                                    <div
                                        class="leads-name"
                                        title="<?php
                                            echo htmlspecialchars(
                                                $lead['name'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </div>

                                </td>


                                <!-- PHONE -->

                                <td>

                                    <span class="leads-phone">

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['phone'] ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- SERVICE -->

                                <td>

                                    <div
                                        class="leads-truncate"
                                        title="<?php
                                            echo htmlspecialchars(
                                                $lead[
                                                    'service_interest'
                                                ] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $lead[
                                                'service_interest'
                                            ] ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </div>

                                </td>


                                <!-- SOURCE -->

                                <td>

                                    <div
                                        class="leads-truncate"
                                        title="<?php
                                            echo htmlspecialchars(
                                                $lead[
                                                    'source_name'
                                                ] ?? '-',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $lead[
                                                'source_name'
                                            ] ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </div>

                                </td>


                                <!-- STATUS -->

                                <td>

                                    <?php

                                    $status_class =
                                        'secondary';

                                    switch (
                                        $lead['status']
                                        ?? ''
                                    ) {

                                        case 'New':
                                            $status_class =
                                                'primary';
                                            break;

                                        case 'Contacted':
                                            $status_class =
                                                'info';
                                            break;

                                        case 'Interested':
                                            $status_class =
                                                'success';
                                            break;

                                        case 'Follow-up':
                                            $status_class =
                                                'warning';
                                            break;

                                        case 'Lost':
                                            $status_class =
                                                'danger';
                                            break;
                                    }

                                    ?>

                                    <span
                                        class="badge
                                               rounded-pill
                                               text-bg-<?php
                                                   echo $status_class;
                                               ?>
                                               leads-status-badge"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['status'] ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- PRIORITY -->

                                <td>

                                    <?php

                                    $priority_class =
                                        'secondary';

                                    switch (
                                        $lead['priority']
                                        ?? ''
                                    ) {

                                        case 'High':
                                            $priority_class =
                                                'danger';
                                            break;

                                        case 'Medium':
                                            $priority_class =
                                                'warning';
                                            break;

                                        case 'Low':
                                            $priority_class =
                                                'success';
                                            break;
                                    }

                                    ?>

                                    <span
                                        class="badge
                                               rounded-pill
                                               text-bg-<?php
                                                   echo $priority_class;
                                               ?>
                                               leads-priority-badge"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $lead['priority']
                                                ?? '-',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </span>

                                </td>


                                <!-- =================================================
                                     ASSIGNED TO
                                ================================================== -->

                                <td
                                    class="leads-assigned-cell"
                                >

                                    <?php if (
                                        !empty(
                                            $lead[
                                                'assigned_user_id'
                                            ]
                                        )
                                        &&
                                        !empty(
                                            $lead[
                                                'assigned_name'
                                            ]
                                        )
                                    ): ?>

                                        <div
                                            class="leads-assigned-user"
                                        >


                                            <!-- ASSIGNED NAME -->

                                            <span
                                                class="leads-assigned-name"
                                                title="<?php
                                                    echo htmlspecialchars(
                                                        $lead[
                                                            'assigned_name'
                                                        ],
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    );
                                                ?>"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        'assigned_name'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                                ?>

                                            </span>


                                            <!-- ASSIGNED ROLE -->
 


                                        </div>


                                    <?php else: ?>


                                        <span
                                            class="badge
                                                   rounded-pill
                                                   text-bg-secondary
                                                   leads-not-assigned"
                                        >
                                            Not Assigned
                                        </span>


                                    <?php endif; ?>

                                </td>


                                <!-- =================================================
                                     NEXT ACTION
                                ================================================== -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $lead[
                                                'next_action_type'
                                            ]
                                        )
                                    ): ?>

                                        <div
                                            class="leads-next-action"
                                        >

                                            <span
                                                class="leads-next-action-type"
                                            >

                                                <?php
                                                echo htmlspecialchars(
                                                    $lead[
                                                        'next_action_type'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );
                                                ?>

                                            </span>


                                            <?php if (
                                                !empty(
                                                    $lead[
                                                        'next_action_at'
                                                    ]
                                                )
                                            ): ?>

                                                <small
                                                    class="hm-muted
                                                           leads-next-action-date"
                                                >

                                                    <?php

                                                    $next_action_timestamp =
                                                        strtotime(
                                                            $lead[
                                                                'next_action_at'
                                                            ]
                                                        );

                                                    if (
                                                        $next_action_timestamp
                                                    ) {

                                                        echo date(
                                                            'd M Y, h:i A',
                                                            $next_action_timestamp
                                                        );

                                                    } else {

                                                        echo '-';

                                                    }

                                                    ?>

                                                </small>

                                            <?php endif; ?>


                                        </div>


                                    <?php else: ?>


                                        <span class="hm-muted">
                                            -
                                        </span>


                                    <?php endif; ?>

                                </td>


                                <!-- =================================================
                                     ACTION
                                ================================================== -->

                                <td
                                    class="leads-action-cell"
                                >

                                    <a
                                        href="<?php
                                            echo BASE_URL;
                                        ?>/leads/view.php?id=<?php
                                            echo (int)
                                                $lead['id'];
                                        ?>"
                                        class="btn
                                               btn-sm
                                               btn-outline-primary
                                               leads-view-btn"
                                    >
                                        View
                                    </a>

                                </td>


                            </tr>

                        <?php endforeach; ?>


                    <?php endif; ?>


                </tbody>

            </table>

        </div>

    </div>


</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
 
