<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/role_check.php';

require_role('manager');


/*
|--------------------------------------------------------------------------
| Filter Inputs
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$role_filter = trim($_GET['role'] ?? '');

$period = trim($_GET['period'] ?? 'all');


/*
|--------------------------------------------------------------------------
| Allowed Filters
|--------------------------------------------------------------------------
*/

$allowed_roles = [
    'telecaller',
    'marketing executive'
];

$allowed_periods = [
    'all',
    'today',
    '7',
    '30',
    'month'
];


/*
|--------------------------------------------------------------------------
| Validate Role
|--------------------------------------------------------------------------
*/

if (
    $role_filter !== ''
    && !in_array(
        $role_filter,
        $allowed_roles,
        true
    )
) {
    $role_filter = '';
}


/*
|--------------------------------------------------------------------------
| Validate Period
|--------------------------------------------------------------------------
*/

if (
    !in_array(
        $period,
        $allowed_periods,
        true
    )
) {
    $period = 'all';
}


/*
|--------------------------------------------------------------------------
| Performance Date Range
|--------------------------------------------------------------------------
*/

$date_start = null;
$date_end = null;

if ($period === 'today') {

    $date_start = date('Y-m-d 00:00:00');

    $date_end = date(
        'Y-m-d 00:00:00',
        strtotime('+1 day')
    );
}

if ($period === '7') {

    $date_start = date(
        'Y-m-d 00:00:00',
        strtotime('-6 days')
    );

    $date_end = date(
        'Y-m-d 00:00:00',
        strtotime('+1 day')
    );
}

if ($period === '30') {

    $date_start = date(
        'Y-m-d 00:00:00',
        strtotime('-29 days')
    );

    $date_end = date(
        'Y-m-d 00:00:00',
        strtotime('+1 day')
    );
}

if ($period === 'month') {

    $date_start = date(
        'Y-m-01 00:00:00'
    );

    $date_end = date(
        'Y-m-d 00:00:00',
        strtotime('+1 day')
    );
}


/*
|--------------------------------------------------------------------------
| Get Staff
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        u.id,
        u.name,
        u.email,
        r.name AS role_name,
        r.display_name

    FROM users u

    INNER JOIN roles r
        ON u.role_id = r.id

    WHERE u.status = 'active'

      AND (
          LOWER(r.name) = 'telecaller'
          OR LOWER(r.name) = 'marketing executive'
          OR LOWER(r.name) = 'marketing'
      )
";

$params = [];


/*
|--------------------------------------------------------------------------
| Search Staff
|--------------------------------------------------------------------------
*/

if ($search !== '') {

    $sql .= "
        AND (
            u.name LIKE ?
            OR u.email LIKE ?
        )
    ";

    $search_value = '%' . $search . '%';

    $params[] = $search_value;
    $params[] = $search_value;
}


/*
|--------------------------------------------------------------------------
| Role Filter
|--------------------------------------------------------------------------
*/

if ($role_filter === 'telecaller') {

    $sql .= "
        AND LOWER(r.name) = 'telecaller'
    ";
}

if ($role_filter === 'marketing executive') {

    $sql .= "
        AND (
            LOWER(r.name) = 'marketing executive'
            OR LOWER(r.name) = 'marketing'
        )
    ";
}


/*
|--------------------------------------------------------------------------
| Order
|--------------------------------------------------------------------------
*/

$sql .= "
    ORDER BY u.name ASC
";


/*
|--------------------------------------------------------------------------
| Execute Staff Query
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare($sql);

$stmt->execute($params);

$team_members = $stmt->fetchAll();


/*
|--------------------------------------------------------------------------
| CSV File Headers
|--------------------------------------------------------------------------
*/

$filename =
    'staff-performance-' .
    date('Y-m-d-H-i-s') .
    '.csv';

header(
    'Content-Type: text/csv; charset=UTF-8'
);

header(
    'Content-Disposition: attachment; filename="' .
    $filename .
    '"'
);

header('Pragma: no-cache');

header('Expires: 0');


/*
|--------------------------------------------------------------------------
| Open Output
|--------------------------------------------------------------------------
*/

$output = fopen('php://output', 'w');


/*
|--------------------------------------------------------------------------
| UTF-8 BOM
|--------------------------------------------------------------------------
|
| Helps Microsoft Excel recognize UTF-8 properly.
|--------------------------------------------------------------------------
*/

fwrite(
    $output,
    "\xEF\xBB\xBF"
);


/*
|--------------------------------------------------------------------------
| Report Information
|--------------------------------------------------------------------------
*/

$period_label = 'All Time';

switch ($period) {

    case 'today':
        $period_label = 'Today';
        break;

    case '7':
        $period_label = 'Last 7 Days';
        break;

    case '30':
        $period_label = 'Last 30 Days';
        break;

    case 'month':
        $period_label = 'This Month';
        break;
}


/*
|--------------------------------------------------------------------------
| Report Header
|--------------------------------------------------------------------------
*/

fputcsv(
    $output,
    [
        'Staff Performance Report'
    ]
);

fputcsv(
    $output,
    [
        'Generated On',
        date('d M Y, h:i A')
    ]
);

fputcsv(
    $output,
    [
        'Performance Period',
        $period_label
    ]
);

if ($search !== '') {

    fputcsv(
        $output,
        [
            'Search',
            $search
        ]
    );
}

if ($role_filter !== '') {

    fputcsv(
        $output,
        [
            'Role',
            $role_filter
        ]
    );
}


fputcsv(
    $output,
    []
);


/*
|--------------------------------------------------------------------------
| CSV Column Headers
|--------------------------------------------------------------------------
*/

fputcsv(
    $output,
    [
        'Staff Name',
        'Email',
        'Role',
        'Leads',
        'New Leads',
        'Converted Leads',
        'Calls',
        'Activities',
        'Appointments',
        'Overdue Follow-ups',
        'Conversion Rate'
    ]
);


/*
|--------------------------------------------------------------------------
| Export Staff Performance
|--------------------------------------------------------------------------
*/

foreach ($team_members as $member) {

    $staff_id = (int) $member['id'];


    /*
    |--------------------------------------------------------------------------
    | Leads
    |--------------------------------------------------------------------------
    */

    $lead_sql = "
        SELECT COUNT(*)

        FROM leads

        WHERE assigned_to = ?
    ";

    $lead_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $lead_sql .= "
            AND created_at >= ?
            AND created_at < ?
        ";

        $lead_params[] = $date_start;
        $lead_params[] = $date_end;
    }

    $stmt = $pdo->prepare($lead_sql);

    $stmt->execute($lead_params);

    $total_leads = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | New Leads
    |--------------------------------------------------------------------------
    */

    $new_sql = "
        SELECT COUNT(*)

        FROM leads

        WHERE assigned_to = ?
          AND status = 'New'
    ";

    $new_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $new_sql .= "
            AND created_at >= ?
            AND created_at < ?
        ";

        $new_params[] = $date_start;
        $new_params[] = $date_end;
    }

    $stmt = $pdo->prepare($new_sql);

    $stmt->execute($new_params);

    $new_leads = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Converted Leads
    |--------------------------------------------------------------------------
    */

    $converted_sql = "
        SELECT COUNT(*)

        FROM leads

        WHERE assigned_to = ?
          AND status = 'Converted'
    ";

    $converted_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $converted_sql .= "
            AND updated_at >= ?
            AND updated_at < ?
        ";

        $converted_params[] = $date_start;
        $converted_params[] = $date_end;
    }

    $stmt = $pdo->prepare($converted_sql);

    $stmt->execute($converted_params);

    $converted_leads = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Calls
    |--------------------------------------------------------------------------
    */

    $calls_sql = "
        SELECT COUNT(*)

        FROM lead_calls

        WHERE user_id = ?
    ";

    $calls_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $calls_sql .= "
            AND call_at >= ?
            AND call_at < ?
        ";

        $calls_params[] = $date_start;
        $calls_params[] = $date_end;
    }

    $stmt = $pdo->prepare($calls_sql);

    $stmt->execute($calls_params);

    $total_calls = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Activities
    |--------------------------------------------------------------------------
    */

    $activities_sql = "
        SELECT COUNT(*)

        FROM lead_activities

        WHERE user_id = ?
    ";

    $activities_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $activities_sql .= "
            AND activity_at >= ?
            AND activity_at < ?
        ";

        $activities_params[] = $date_start;
        $activities_params[] = $date_end;
    }

    $stmt = $pdo->prepare($activities_sql);

    $stmt->execute($activities_params);

    $total_activities = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Appointments
    |--------------------------------------------------------------------------
    */

    $appointments_sql = "
        SELECT COUNT(*)

        FROM appointments a

        INNER JOIN leads l
            ON a.lead_id = l.id

        WHERE l.assigned_to = ?

          AND a.status NOT IN (
              'Cancelled',
              'No Show'
          )
    ";

    $appointments_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $appointments_sql .= "
            AND a.appointment_date >= ?
            AND a.appointment_date < ?
        ";

        $appointments_params[] = $date_start;
        $appointments_params[] = $date_end;
    }

    $stmt = $pdo->prepare($appointments_sql);

    $stmt->execute($appointments_params);

    $total_appointments = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Overdue Follow-ups
    |--------------------------------------------------------------------------
    */

    $overdue_sql = "
        SELECT COUNT(*)

        FROM leads

        WHERE assigned_to = ?

          AND next_action_at IS NOT NULL

          AND next_action_at < NOW()

          AND status NOT IN (
              'Converted',
              'Lost'
          )
    ";

    $overdue_params = [
        $staff_id
    ];

    if ($date_start !== null) {

        $overdue_sql .= "
            AND next_action_at >= ?
            AND next_action_at < ?
        ";

        $overdue_params[] = $date_start;
        $overdue_params[] = $date_end;
    }

    $stmt = $pdo->prepare($overdue_sql);

    $stmt->execute($overdue_params);

    $overdue_followups = (int) $stmt->fetchColumn();


    /*
    |--------------------------------------------------------------------------
    | Conversion Rate
    |--------------------------------------------------------------------------
    */

    $conversion_rate = 0;

    if ($total_leads > 0) {

        $conversion_rate =
            ($converted_leads / $total_leads) * 100;
    }


    /*
    |--------------------------------------------------------------------------
    | Write CSV Row
    |--------------------------------------------------------------------------
    */

    fputcsv(
        $output,
        [
            $member['name'],

            $member['email'],

            (
                $member['display_name']
                ?: $member['role_name']
            ),

            $total_leads,

            $new_leads,

            $converted_leads,

            $total_calls,

            $total_activities,

            $total_appointments,

            $overdue_followups,

            number_format(
                $conversion_rate,
                1
            ) . '%'
        ]
    );
}


/*
|--------------------------------------------------------------------------
| Close Output
|--------------------------------------------------------------------------
*/

fclose($output);

exit;