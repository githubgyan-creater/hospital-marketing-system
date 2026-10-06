 <?php

/**
 * Hospital Marketing System
 * Shared Lead Workflow
 *
 * Responsibilities:
 * - Marketing source normalization
 * - Department / service resolution
 * - Telecaller balancing
 * - Active lead redistribution
 * - Duplicate lead detection
 */


/*
|--------------------------------------------------------------------------
| Normalize Marketing Source
|--------------------------------------------------------------------------
*/

function normalize_marketing_source(string $source): string
{
    $source = trim($source);

    if ($source === '') {
        return 'Other';
    }

    $normalized = strtolower(
        preg_replace('/\s+/', ' ', $source)
    );

    $aliases = [

        'google' => 'Google Ads',
        'google ad' => 'Google Ads',
        'google ads' => 'Google Ads',
        'google advertisement' => 'Google Ads',

        'facebook' => 'Facebook',
        'fb' => 'Facebook',
        'facebook ads' => 'Facebook',

        'instagram' => 'Instagram',
        'ig' => 'Instagram',
        'instagram ads' => 'Instagram',

        'website' => 'Website',
        'web' => 'Website',

        'whatsapp' => 'WhatsApp',
        'whats app' => 'WhatsApp',

        'referral' => 'Referral',
        'referred' => 'Referral',

        'walk in' => 'Walk-in',
        'walk-in' => 'Walk-in',
        'walkin' => 'Walk-in',

        'phone' => 'Phone',
        'call' => 'Phone',

        'event' => 'Event',
        'events' => 'Event',

        'bulk import' => 'Bulk Import',
        'bulk lead import' => 'Bulk Import',

        'public form' => 'Public Form',

        'manual' => 'Manual',

        'other' => 'Other'
    ];

    return $aliases[$normalized] ?? $source;
}


/*
|--------------------------------------------------------------------------
| Get / Create Marketing Source
|--------------------------------------------------------------------------
*/

function get_or_create_marketing_source(
    PDO $pdo,
    string $source_name
): int {

    $source_name = normalize_marketing_source(
        $source_name
    );

    /*
    | First try exact match
    */

    $stmt = $pdo->prepare("
        SELECT id
        FROM marketing_sources
        WHERE LOWER(name) = LOWER(?)
        LIMIT 1
    ");

    $stmt->execute([
        $source_name
    ]);

    $existing = $stmt->fetch();

    if ($existing) {
        return (int) $existing['id'];
    }

    /*
    | Create source if it does not exist
    */

    $stmt = $pdo->prepare("
        INSERT INTO marketing_sources (
            name,
            status
        )
        VALUES (
            ?,
            'ACTIVE'
        )
    ");

    $stmt->execute([
        $source_name
    ]);

    return (int) $pdo->lastInsertId();
}


/*
|--------------------------------------------------------------------------
| Resolve Department / Service
|--------------------------------------------------------------------------
*/

function resolve_lead_department_service(
    PDO $pdo,
    string $department_name = '',
    string $service_name = ''
): array {

    $department_id = null;
    $service_id = null;

    /*
    | Department
    */

    if ($department_name !== '') {

        $stmt = $pdo->prepare("
            SELECT id
            FROM departments
            WHERE LOWER(department_name) = LOWER(?)
            LIMIT 1
        ");

        $stmt->execute([
            trim($department_name)
        ]);

        $department = $stmt->fetch();

        if ($department) {
            $department_id = (int) $department['id'];
        }
    }


    /*
    | Service
    */

    if ($service_name !== '') {

        $stmt = $pdo->prepare("
            SELECT
                id,
                department_id
            FROM services
            WHERE LOWER(service_name) = LOWER(?)
            LIMIT 1
        ");

        $stmt->execute([
            trim($service_name)
        ]);

        $service = $stmt->fetch();

        if ($service) {

            $service_id = (int) $service['id'];

            if (
                $department_id === null &&
                !empty($service['department_id'])
            ) {
                $department_id =
                    (int) $service['department_id'];
            }
        }
    }

    return [
        'department_id' => $department_id,
        'service_id'    => $service_id
    ];
}


/*
|--------------------------------------------------------------------------
| Get Active Telecallers
|--------------------------------------------------------------------------
*/

function get_active_telecallers(
    PDO $pdo
): array {

    $stmt = $pdo->query("
        SELECT
            u.id,
            u.name,
            u.email
        FROM users u
        INNER JOIN roles r
            ON r.id = u.role_id
        WHERE u.status = 'active'
          AND LOWER(r.name) = 'telecaller'
        ORDER BY u.id ASC
    ");

    return $stmt->fetchAll();
}


/*
|--------------------------------------------------------------------------
| Get Active Lead Count
|--------------------------------------------------------------------------
|
| Converted and Lost leads are NOT counted.
|
*/

function get_telecaller_active_workloads(
    PDO $pdo
): array {

    $sql = "
        SELECT
            u.id,
            COUNT(l.id) AS active_leads

        FROM users u

        INNER JOIN roles r
            ON r.id = u.role_id

        LEFT JOIN leads l
            ON l.assigned_to = u.id
           AND l.status NOT IN (
                'Converted',
                'Lost'
           )

        WHERE u.status = 'active'
          AND LOWER(r.name) = 'telecaller'

        GROUP BY
            u.id

        ORDER BY
            active_leads ASC,
            u.id ASC
    ";

    $stmt = $pdo->query($sql);

    $workloads = [];

    foreach ($stmt->fetchAll() as $row) {

        $workloads[(int) $row['id']] =
            (int) $row['active_leads'];
    }

    return $workloads;
}


/*
|--------------------------------------------------------------------------
| Assign One New Lead
|--------------------------------------------------------------------------
|
| New leads are assigned to the Telecaller
| with the lowest current active workload.
|
| Because every previous assignment is balanced,
| this keeps the overall distribution balanced.
|
*/

function assign_lead_to_telecaller(
    PDO $pdo,
    ?int $department_id = null,
    ?int $service_id = null
): ?int {

    $telecallers =
        get_active_telecallers($pdo);

    if (!$telecallers) {
        return null;
    }

    $workloads =
        get_telecaller_active_workloads($pdo);

    /*
    | Find lowest workload
    */

    $selected_id = null;
    $lowest_count = PHP_INT_MAX;

    foreach ($telecallers as $telecaller) {

        $id = (int) $telecaller['id'];

        $count =
            $workloads[$id] ?? 0;

        if ($count < $lowest_count) {

            $lowest_count = $count;
            $selected_id = $id;
        }
    }

    return $selected_id;
}


/*
|--------------------------------------------------------------------------
| Redistribute ALL Active Leads
|--------------------------------------------------------------------------
|
| This is the important new functionality.
|
| Example:
|
| Before:
| A = 150
| B = 150
|
| New Telecaller D
|
| After:
| A = 100
| B = 100
| D = 100
|
| Converted and Lost leads are ignored.
|
*/

function rebalance_all_active_leads(
    PDO $pdo,
    ?int $performed_by = null
): array {

    /*
    | Get active Telecallers
    */

    $telecallers =
        get_active_telecallers($pdo);

    if (!$telecallers) {

        return [
            'success' => false,
            'total_leads' => 0,
            'telecallers' => 0,
            'message' =>
                'No active Telecallers available.'
        ];
    }


    /*
    | Get active leads
    */

    $lead_stmt = $pdo->query("
        SELECT
            id,
            assigned_to
        FROM leads
        WHERE status NOT IN (
            'Converted',
            'Lost'
        )
        ORDER BY
            id ASC
    ");

    $leads =
        $lead_stmt->fetchAll();


    if (!$leads) {

        return [
            'success' => true,
            'total_leads' => 0,
            'telecallers' => count($telecallers),
            'message' =>
                'No active leads require redistribution.'
        ];
    }


    /*
    | Start transaction if one is not already active
    */

    $started_transaction = false;

    if (!$pdo->inTransaction()) {

        $pdo->beginTransaction();

        $started_transaction = true;
    }


    try {

        /*
        | Prepare update
        */

        $update_stmt = $pdo->prepare("
            UPDATE leads
            SET
                assigned_to = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");


        /*
        | Activity statement
        |
        | Only create an activity when the assignment
        | actually changes.
        */

        $activity_stmt = $pdo->prepare("
            INSERT INTO lead_activities (
                lead_id,
                user_id,
                activity_type,
                description,
                activity_at
            )
            VALUES (
                ?,
                ?,
                'Lead Assigned',
                ?,
                NOW()
            )
        ");


        /*
        | Round-robin distribution
        |
        | Since leads are processed sequentially:
        |
        | 300 leads / 3 telecallers
        | = 100 each
        |
        | 301 leads / 3 telecallers
        | = 101 / 100 / 100
        */

        $telecaller_count =
            count($telecallers);

        $index = 0;

        $changed = 0;

        foreach ($leads as $lead) {

            $telecaller =
                $telecallers[
                    $index % $telecaller_count
                ];

            $new_assigned_to =
                (int) $telecaller['id'];

            $old_assigned_to =
                !empty($lead['assigned_to'])
                    ? (int) $lead['assigned_to']
                    : null;


            /*
            | Update assignment
            */

            if (
                $old_assigned_to !==
                $new_assigned_to
            ) {

                $update_stmt->execute([
                    $new_assigned_to,
                    (int) $lead['id']
                ]);

                /*
                | Activity user
                |
                | If performed_by exists, use it.
                | Otherwise use the new Telecaller's ID
                | to maintain valid user reference.
                */

                $activity_user_id =
                    $performed_by !== null
                        ? $performed_by
                        : $new_assigned_to;

                $activity_stmt->execute([
                    (int) $lead['id'],
                    $activity_user_id,
                    'Lead automatically redistributed to ' .
                    $telecaller['name'] .
                    ' to maintain balanced Telecaller workload.'
                ]);

                $changed++;
            }

            $index++;
        }


        if ($started_transaction) {
            $pdo->commit();
        }


        /*
        | Return final workload
        */

        $final_workloads =
            get_telecaller_active_workloads(
                $pdo
            );

        return [
            'success' => true,
            'total_leads' => count($leads),
            'changed_leads' => $changed,
            'telecallers' => $telecaller_count,
            'workloads' => $final_workloads,
            'message' =>
                'Active leads redistributed successfully.'
        ];


    } catch (Throwable $e) {

        if (
            $started_transaction &&
            $pdo->inTransaction()
        ) {
            $pdo->rollBack();
        }

        throw $e;
    }
}


/*
|--------------------------------------------------------------------------
| Duplicate Lead Detection
|--------------------------------------------------------------------------
*/

function normalize_lead_phone(
    string $phone
): string {

    return preg_replace(
        '/\D+/',
        '',
        $phone
    );
}


function find_duplicate_lead_by_phone(
    PDO $pdo,
    string $phone
): ?array {

    $normalized_phone =
        normalize_lead_phone($phone);

    if ($normalized_phone === '') {
        return null;
    }

    /*
    | Fetch phone numbers.
    |
    | PHP normalization is used because existing records
    | may contain spaces, +91, hyphens, etc.
    */

    $stmt = $pdo->query("
        SELECT
            id,
            name,
            phone,
            status,
            assigned_to
        FROM leads
        ORDER BY id DESC
    ");

    foreach ($stmt->fetchAll() as $lead) {

        $existing_phone =
            normalize_lead_phone(
                (string) $lead['phone']
            );

        /*
        | Indian 10-digit comparison
        */

        if (
            strlen($normalized_phone) > 10 &&
            substr(
                $normalized_phone,
                -10
            ) === substr(
                $existing_phone,
                -10
            )
        ) {

            return $lead;
        }

        if (
            $normalized_phone ===
            $existing_phone
        ) {
            return $lead;
        }
    }

    return null;
}