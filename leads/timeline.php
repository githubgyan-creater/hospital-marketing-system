<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/role_check.php';

require_role(
    'admin',
    'manager',
    'telecaller',
    'marketing'
);

$page_title = 'Lead Timeline';

$lead_id = (int) ($_GET['id'] ?? 0);

if ($lead_id <= 0) {
    exit('Invalid lead ID.');
}


/*

 Get Lead

*/

$lead_stmt = $pdo->prepare("
    SELECT
        l.id,
        l.name,
        l.phone
    FROM leads l
    WHERE l.id = :id
    LIMIT 1
");

$lead_stmt->execute([
    'id' => $lead_id
]);

$lead = $lead_stmt->fetch();

if (!$lead) {
    exit('Lead not found.');
}


/*

 Get Activities

*/

$activity_stmt = $pdo->prepare("
    SELECT
        la.id,
        la.activity_type,
        la.description,
        la.activity_at,
        u.name AS user_name
    FROM lead_activities la
    INNER JOIN users u
        ON la.user_id = u.id
    WHERE la.lead_id = :lead_id
    ORDER BY la.activity_at DESC, la.id DESC
");

$activity_stmt->execute([
    'lead_id' => $lead_id
]);

$activities = $activity_stmt->fetchAll();


require_once __DIR__ . '/../includes/header.php';

?>

<div class="container py-4">

    <!-- Header -->

    <div class="mb-4">

        <a
            href="<?php echo BASE_URL; ?>/leads/view.php?id=<?php echo $lead_id; ?>"
            class="text-decoration-none"
        >
            ← Back to Lead Details
        </a>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

    <div>

        <h1 class="hm-page-title mt-3 mb-1">
            Activity Timeline
        </h1>

        <p class="hm-muted mb-0">

            Lead:
            <strong>
                <?php
                echo htmlspecialchars(
                    $lead['name']
                );
                ?>
            </strong>

            |

            <?php
            echo htmlspecialchars(
                $lead['phone']
            );
            ?>

        </p>

    </div>

    <a
        href="<?php echo BASE_URL; ?>/leads/add-activity.php?id=<?php echo $lead_id; ?>"
        class="btn btn-hm-primary"
    >
        + Add Activity
    </a>

</div>

   

    </div>


    <!-- Timeline Card -->

    <div class="hm-card p-4">

        <?php if (empty($activities)): ?>

            <div class="text-center py-5">

                <h5>
                    No activities yet
                </h5>

                <p class="hm-muted mb-0">
                    Activities related to this lead
                    will appear here.
                </p>

            </div>

        <?php else: ?>

            <div class="timeline">

                <?php foreach ($activities as $activity): ?>

                    <div class="timeline-item mb-4">

                        <div class="d-flex">

                            <!-- Timeline Dot -->

                            <div
                                class="me-3"
                                style="min-width: 14px;"
                            >

                                <div
                                    style="
                                        width: 14px;
                                        height: 14px;
                                        background-color: var(--hm-teal);
                                        border-radius: 50%;
                                        margin-top: 5px;
                                    "
                                ></div>

                            </div>


                            <!-- Activity Content -->

                            <div>

                                <h5 class="mb-1">

                                    <?php
                                    echo htmlspecialchars(
                                        $activity[
                                            'activity_type'
                                        ]
                                    );
                                    ?>

                                </h5>

                                <p class="mb-1">

                                    <?php

                                    echo nl2br(
                                        htmlspecialchars(
                                            $activity[
                                                'description'
                                            ]
                                            ?? ''
                                        )
                                    );

                                    ?>

                                </p>

                                <small class="text-muted">

                                    <?php

                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $activity[
                                                'activity_at'
                                            ]
                                        )
                                    );

                                    ?>

                                    ·

                                    <?php

                                    echo htmlspecialchars(
                                        $activity[
                                            'user_name'
                                        ]
                                    );

                                    ?>

                                </small>

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