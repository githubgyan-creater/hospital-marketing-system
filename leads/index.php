 <?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role(
    'admin',
    'manager',
    'telecaller',
    'marketing'
);

$page_title = 'Leads';


/*
|--------------------------------------------------------------------------
| Get Filter Values
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
| Get Staff
|--------------------------------------------------------------------------
*/

$staff_stmt = $pdo->query("
    SELECT
        u.id,
        u.name,
        r.name AS role_name
    FROM users u
    INNER JOIN roles r
        ON u.role_id = r.id
    WHERE
        u.status = 'active'
        AND r.name IN (
            'manager',
            'telecaller',
            'marketing'
        )
    ORDER BY u.name ASC
");

$staff = $staff_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Allowed Statuses
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
| Allowed Priorities
|--------------------------------------------------------------------------
*/

$priorities = [
    'Low',
    'Medium',
    'High'
];


/*
|--------------------------------------------------------------------------
| Build Lead Query
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

        u.name AS assigned_name

    FROM leads l

    LEFT JOIN marketing_sources ms
        ON l.source_id = ms.id

    LEFT JOIN users u
        ON l.assigned_to = u.id

    WHERE 1 = 1
";


$params = [];


/*
|--------------------------------------------------------------------------
| Search Filter
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            l.name LIKE :search
            OR l.phone LIKE :search
        )
    ";

    $params['search'] =
        '%' . $search . '%';
}


/*
|--------------------------------------------------------------------------
| Status Filter
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
| Priority Filter
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
| Assigned Staff Filter
|--------------------------------------------------------------------------
*/

if ($assigned_filter > 0) {

    $sql .= "
        AND l.assigned_to = :assigned_to
    ";

    $params['assigned_to'] =
        $assigned_filter;
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY l.created_at DESC
";


/*
|--------------------------------------------------------------------------
| Execute Query
|--------------------------------------------------------------------------
*/

$lead_stmt = $pdo->prepare($sql);

$lead_stmt->execute($params);

$leads = $lead_stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| Check Active Filters
|--------------------------------------------------------------------------
*/

$has_filters =
    $search !== '' ||
    $status_filter !== '' ||
    $priority_filter !== '' ||
    $assigned_filter > 0;


require_once __DIR__ . '/../includes/header.php';

?>


<div class="container py-4">


    <!-- Page Header -->

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
                Manage hospital marketing enquiries
                and lead activities.
            </p>

        </div>


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

    </div>



    <!-- Search & Filters -->

    <div class="hm-card p-4 mb-4">

        <h5
            class="mb-3"
            style="color: var(--hm-navy);"
        >
            Search & Filters
        </h5>


        <form method="GET">

            <div class="row g-3">


                <!-- Search -->

                <div class="col-md-6">

                    <label class="form-label">
                        Search Name / Phone
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control"
                        placeholder="Enter name or phone"
                        value="<?php
                            echo htmlspecialchars(
                                $search
                            );
                        ?>"
                    >

                </div>



                <!-- Status -->

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
                                        $status
                                    );
                                ?>"
                                <?php

                                echo (
                                    $status_filter
                                    === $status
                                )
                                    ? 'selected'
                                    : '';

                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $status
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- Priority -->

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
                                        $priority
                                    );
                                ?>"
                                <?php

                                echo (
                                    $priority_filter
                                    === $priority
                                )
                                    ? 'selected'
                                    : '';

                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $priority
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- Assigned Staff -->

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
                                    echo $member['id'];
                                ?>"
                                <?php

                                echo (
                                    $assigned_filter
                                    == $member['id']
                                )
                                    ? 'selected'
                                    : '';

                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $member['name']
                                );
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>



                <!-- Buttons -->

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



    <!-- Result Summary -->

    <div
        class="d-flex
               justify-content-between
               align-items-center
               mb-3"
    >

        <div>

            <strong>
                <?php
                echo count($leads);
                ?>
            </strong>

            lead(s) found

        </div>


        <?php if ($has_filters): ?>

            <span class="badge text-bg-light">

                Filters Applied

            </span>

        <?php endif; ?>

    </div>



    <!-- Lead Table -->

    <div class="hm-card">

        <div class="table-responsive">

            <table
                class="table table-hover
                       align-middle mb-0"
            >

                <thead>

                    <tr>

                        <th>
                            Name
                        </th>

                        <th>
                            Phone
                        </th>

                        <th>
                            Service
                        </th>

                        <th>
                            Source
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Priority
                        </th>

                        <th>
                            Assigned To
                        </th>

                        <th>
                            Next Action
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>


                    <?php if (
                        empty($leads)
                    ): ?>

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-5"
                            >

                                <div
                                    class="hm-muted"
                                >

                                    No leads found.

                                </div>

                            </td>

                        </tr>


                    <?php else: ?>


                        <?php foreach (
                            $leads
                            as $lead
                        ): ?>

                            <tr>


                                <!-- Name -->

                                <td>

                                    <strong>

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['name']
                                        );

                                        ?>

                                    </strong>

                                </td>



                                <!-- Phone -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $lead['phone']
                                    );

                                    ?>

                                </td>



                                <!-- Service -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $lead[
                                            'service_interest'
                                        ]
                                        ?? '-'
                                    );

                                    ?>

                                </td>



                                <!-- Source -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $lead[
                                            'source_name'
                                        ]
                                        ?? '-'
                                    );

                                    ?>

                                </td>



                                <!-- Status -->

                                <td>

                                    <?php

                                    $status_class =
                                        'secondary';

                                    if (
                                        $lead['status']
                                        === 'New'
                                    ) {

                                        $status_class =
                                            'primary';

                                    } elseif (
                                        $lead['status']
                                        === 'Contacted'
                                    ) {

                                        $status_class =
                                            'info';

                                    } elseif (
                                        $lead['status']
                                        === 'Interested'
                                    ) {

                                        $status_class =
                                            'success';

                                    } elseif (
                                        $lead['status']
                                        === 'Follow-up'
                                    ) {

                                        $status_class =
                                            'warning';

                                    } elseif (
                                        $lead['status']
                                        === 'Lost'
                                    ) {

                                        $status_class =
                                            'danger';

                                    }

                                    ?>


                                    <span
                                        class="badge
                                               text-bg-<?php
                                                   echo $status_class;
                                               ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['status']
                                        );

                                        ?>

                                    </span>

                                </td>



                                <!-- Priority -->

                                <td>

                                    <?php

                                    $priority_class =
                                        'secondary';

                                    if (
                                        $lead['priority']
                                        === 'High'
                                    ) {

                                        $priority_class =
                                            'danger';

                                    } elseif (
                                        $lead['priority']
                                        === 'Medium'
                                    ) {

                                        $priority_class =
                                            'warning';

                                    } elseif (
                                        $lead['priority']
                                        === 'Low'
                                    ) {

                                        $priority_class =
                                            'success';

                                    }

                                    ?>


                                    <span
                                        class="badge
                                               text-bg-<?php
                                                   echo $priority_class;
                                               ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $lead['priority']
                                        );

                                        ?>

                                    </span>

                                </td>



                                <!-- Assigned -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $lead[
                                            'assigned_name'
                                        ]
                                        ?? 'Not Assigned'
                                    );

                                    ?>

                                </td>



                                <!-- Next Action -->

                                <td>

                                    <?php if (
                                        !empty(
                                            $lead[
                                                'next_action_type'
                                            ]
                                        )
                                    ): ?>

                                        <div>

                                            <strong>

                                                <?php

                                                echo htmlspecialchars(
                                                    $lead[
                                                        'next_action_type'
                                                    ]
                                                );

                                                ?>

                                            </strong>

                                        </div>


                                        <?php if (
                                            !empty(
                                                $lead[
                                                    'next_action_at'
                                                ]
                                            )
                                        ): ?>

                                            <small
                                                class="hm-muted"
                                            >

                                                <?php

                                                echo date(
                                                    'd M Y, h:i A',
                                                    strtotime(
                                                        $lead[
                                                            'next_action_at'
                                                        ]
                                                    )
                                                );

                                                ?>

                                            </small>

                                        <?php endif; ?>


                                    <?php else: ?>

                                        <span
                                            class="hm-muted"
                                        >
                                            -
                                        </span>

                                    <?php endif; ?>

                                </td>



                                <!-- Action -->

                                <td>

                                    <a
                                        href="<?php
                                            echo BASE_URL;
                                        ?>/leads/view.php?id=<?php
                                            echo $lead['id'];
                                        ?>"
                                        class="btn
                                               btn-sm
                                               btn-outline-primary"
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