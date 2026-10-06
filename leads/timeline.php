 
<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/role_check.php';

require_login();

$current_user = current_user();

require_role(
    'admin',
    'manager',
    'telecaller',
    'marketing'
);

$page_title = 'Lead Timeline';


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
| GET CURRENT USER ROLE DIRECTLY FROM DATABASE
|--------------------------------------------------------------------------
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


/*
|--------------------------------------------------------------------------
| LEAD ID
|--------------------------------------------------------------------------
*/

$lead_id = (int) (
    $_GET['id'] ?? 0
);

if ($lead_id <= 0) {
    exit('Invalid lead ID.');
}


/*
|--------------------------------------------------------------------------
| GET LEAD
|--------------------------------------------------------------------------
|
| Admin / Manager / Marketing:
|    Can open the normal lead timeline.
|
| Telecaller:
|    Can open only their own assigned lead timeline.
|
*/

$lead_sql = "

    SELECT
        l.id,
        l.name,
        l.phone,
        l.email,
        l.status,
        l.priority,
        l.assigned_to

    FROM leads l

    WHERE l.id = :id

";

$lead_params = [
    'id' => $lead_id
];


/*
|--------------------------------------------------------------------------
| TELECALLER LEAD ACCESS RESTRICTION
|--------------------------------------------------------------------------
*/

if (
    $current_user_role === 'telecaller'
    &&
    $current_user_id > 0
) {

    $lead_sql .= "
        AND l.assigned_to = :current_user_id
    ";

    $lead_params['current_user_id'] =
        $current_user_id;
}


$lead_sql .= "
    LIMIT 1
";


$lead_stmt = $pdo->prepare(
    $lead_sql
);

$lead_stmt->execute(
    $lead_params
);

$lead = $lead_stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$lead) {

    if ($current_user_role === 'telecaller') {
        exit('Lead not found or not assigned to you.');
    }

    exit('Lead not found.');
}


/*
|--------------------------------------------------------------------------
| GET ACTIVITIES
|--------------------------------------------------------------------------
|
| ADMIN:
|    Can see all activities.
|
| ALL OTHER ROLES:
|    Can see only activities created by the logged-in member.
|
| This prevents Rajesh from seeing Rahul's activities and vice versa.
|
*/

$activity_sql = "

    SELECT
        la.id,
        la.activity_type,
        la.description,
        la.activity_at,
        u.name AS user_name

    FROM lead_activities la

    INNER JOIN users u
        ON la.user_id = u.id

    WHERE
        la.lead_id = :lead_id

";


$activity_params = [
    'lead_id' => $lead_id
];


/*
|--------------------------------------------------------------------------
| ACTIVITY VISIBILITY
|--------------------------------------------------------------------------
*/

if (
    $current_user_role !== 'admin'
    &&
    $current_user_id > 0
) {

    $activity_sql .= "
        AND la.user_id = :current_user_id
    ";

    $activity_params['current_user_id'] =
        $current_user_id;
}


$activity_sql .= "

    ORDER BY
        la.activity_at DESC,
        la.id DESC

";


$activity_stmt = $pdo->prepare(
    $activity_sql
);

$activity_stmt->execute(
    $activity_params
);

$activities = $activity_stmt->fetchAll(
    PDO::FETCH_ASSOC
);


/*
|--------------------------------------------------------------------------
| ACTIVITY COUNT
|--------------------------------------------------------------------------
*/

$activity_count = count($activities);


require_once __DIR__ . '/../includes/header.php';

?>

<style>

/*
|--------------------------------------------------------------------------
| TIMELINE
|--------------------------------------------------------------------------
*/

.lead-timeline {
    position: relative;
    padding-left: 10px;
}


/*
|--------------------------------------------------------------------------
| TIMELINE ITEM
|--------------------------------------------------------------------------
*/

.lead-timeline-item {
    position: relative;
    padding-left: 38px;
    padding-bottom: 28px;
}


/*
|--------------------------------------------------------------------------
| TIMELINE LINE
|--------------------------------------------------------------------------
*/

.lead-timeline-item:not(:last-child)::before {
    content: '';

    position: absolute;

    left: 8px;
    top: 18px;
    bottom: 0;

    width: 2px;

    background-color: #dee2e6;
}


/*
|--------------------------------------------------------------------------
| TIMELINE DOT
|--------------------------------------------------------------------------
*/

.lead-timeline-dot {
    position: absolute;

    left: 0;
    top: 2px;

    width: 18px;
    height: 18px;

    border-radius: 50%;

    background-color: var(--hm-teal);

    border: 3px solid #ffffff;

    box-shadow: 0 0 0 1px #ced4da;

    z-index: 2;
}


/*
|--------------------------------------------------------------------------
| ACTIVITY CARD
|--------------------------------------------------------------------------
*/

.lead-activity-card {
    padding: 16px 18px;

    border: 1px solid #e9ecef;
    border-radius: 10px;

    background: #ffffff;

    box-shadow:
        0 2px 8px rgba(0, 0, 0, 0.04);
}


/*
|--------------------------------------------------------------------------
| ACTIVITY HEADER
|--------------------------------------------------------------------------
*/

.lead-activity-header {
    display: flex;

    justify-content: space-between;
    align-items: flex-start;

    gap: 15px;

    flex-wrap: wrap;
}


/*
|--------------------------------------------------------------------------
| ACTIVITY TYPE
|--------------------------------------------------------------------------
*/

.lead-activity-type {
    margin: 0;

    font-size: 1rem;

    font-weight: 700;

    color: var(--hm-navy);
}


/*
|--------------------------------------------------------------------------
| ACTIVITY DESCRIPTION
|--------------------------------------------------------------------------
*/

.lead-activity-description {
    margin-top: 8px;
    margin-bottom: 8px;

    line-height: 1.6;

    word-break: break-word;
}


/*
|--------------------------------------------------------------------------
| ACTIVITY META
|--------------------------------------------------------------------------
*/

.lead-activity-meta {
    color: #6c757d;

    font-size: 0.78rem;

    display: flex;

    flex-wrap: wrap;

    gap: 6px;
}


/*
|--------------------------------------------------------------------------
| VISIBILITY NOTICE
|--------------------------------------------------------------------------
*/

.lead-visibility-note {
    border-left: 4px solid #0d6efd;
}


/*
|--------------------------------------------------------------------------
| EMPTY STATE
|--------------------------------------------------------------------------
*/

.lead-empty-state {
    padding: 45px 20px;
}


/*
|--------------------------------------------------------------------------
| MOBILE
|--------------------------------------------------------------------------
*/

@media (max-width: 768px) {

    .lead-timeline-item {
        padding-left: 32px;
    }

    .lead-activity-card {
        padding: 14px;
    }

    .lead-activity-header {
        gap: 8px;
    }

}

</style>


<div class="container py-4">


    <!-- =========================================================
         HEADER
    ========================================================== -->

    <div class="mb-4">

        <a
            href="<?php
                echo BASE_URL;
            ?>/leads/view.php?id=<?php
                echo $lead_id;
            ?>"
            class="text-decoration-none"
        >
            ← Back to Lead Details
        </a>


        <div
            class="d-flex
                   justify-content-between
                   align-items-center
                   flex-wrap
                   gap-3
                   mt-3"
        >

            <div>

                <h1 class="hm-page-title mb-1">
                    Activity Timeline
                </h1>

                <p class="hm-muted mb-0">

                    Lead:
                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $lead['name'],
                            ENT_QUOTES,
                            'UTF-8'
                        );
                        ?>

                    </strong>

                    |

                    <?php
                    echo htmlspecialchars(
                        $lead['phone'] ?? '-',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                </p>

            </div>


            <!-- ADD ACTIVITY -->

            <a
                href="<?php
                    echo BASE_URL;
                ?>/leads/add-activity.php?id=<?php
                    echo $lead_id;
                ?>"
                class="btn btn-hm-primary"
            >
                + Add Activity
            </a>

        </div>

    </div>


    <!-- =========================================================
         VISIBILITY INFORMATION
    ========================================================== -->

    <div
        class="hm-card p-3 mb-4 lead-visibility-note"
    >

        <?php if ($current_user_role === 'admin'): ?>

            <div>

                <strong>
                    Activity Visibility
                </strong>

                <small class="hm-muted d-block mt-1">
                    Admin can view all activities for this lead.
                </small>

            </div>

        <?php else: ?>

            <div>

                <strong>

                    <?php
                    echo htmlspecialchars(
                        $current_user['name']
                        ?? 'Current User',
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    ?>

                </strong>

                <small class="hm-muted d-block mt-1">
                    Showing only activities created by you.
                </small>

            </div>

        <?php endif; ?>

    </div>


    <!-- =========================================================
         TIMELINE CARD
    ========================================================== -->

    <div class="hm-card p-4">


        <?php if (empty($activities)): ?>


            <!-- EMPTY STATE -->

            <div
                class="text-center
                       lead-empty-state"
            >

                <h5>
                    No activities visible
                </h5>

                <?php if (
                    $current_user_role === 'admin'
                ): ?>

                    <p class="hm-muted mb-0">
                        No activities have been recorded
                        for this lead yet.
                    </p>

                <?php else: ?>

                    <p class="hm-muted mb-0">
                        You have not recorded any activity
                        for this lead yet.
                    </p>

                <?php endif; ?>

            </div>


        <?php else: ?>


            <!-- TIMELINE -->

            <div class="lead-timeline">


                <?php foreach (
                    $activities
                    as $activity
                ): ?>


                    <div
                        class="lead-timeline-item"
                    >


                        <!-- DOT -->

                        <div
                            class="lead-timeline-dot"
                        ></div>


                        <!-- ACTIVITY CARD -->

                        <div
                            class="lead-activity-card"
                        >


                            <div
                                class="lead-activity-header"
                            >


                                <!-- ACTIVITY TYPE -->

                                <h5
                                    class="lead-activity-type"
                                >

                                    <?php
                                    echo htmlspecialchars(
                                        $activity[
                                            'activity_type'
                                        ],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                    ?>

                                </h5>


                                <!-- DATE -->

                                <small
                                    class="text-muted"
                                >

                                    <?php

                                    $activity_timestamp =
                                        strtotime(
                                            $activity[
                                                'activity_at'
                                            ]
                                        );

                                    if (
                                        $activity_timestamp
                                    ) {

                                        echo date(
                                            'd M Y, h:i A',
                                            $activity_timestamp
                                        );

                                    } else {

                                        echo '-';

                                    }

                                    ?>

                                </small>


                            </div>


                            <!-- DESCRIPTION -->

                            <div
                                class="lead-activity-description"
                            >

                                <?php
                                echo nl2br(
                                    htmlspecialchars(
                                        $activity[
                                            'description'
                                        ] ?? '',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                );
                                ?>

                            </div>


                            <!-- META -->

                            <div
                                class="lead-activity-meta"
                            >

                                <span>
                                    By
                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $activity[
                                                'user_name'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );
                                        ?>

                                    </strong>
                                </span>

                            </div>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </div>


</div>


<?php

require_once __DIR__ . '/../includes/footer.php';

?>
 
