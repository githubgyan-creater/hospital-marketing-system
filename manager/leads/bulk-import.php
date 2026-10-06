 <?php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../includes/lead_workflow.php';

require_login();

$user = current_user();

if (
    !$user ||
    !in_array(
        strtolower((string) $user['role']),
        ['manager', 'admin'],
        true
    )
) {

    http_response_code(403);

    exit('Access denied.');
}

$user_id = (int) $user['id'];

$page_title = 'Bulk Lead Import';

$error = '';
$success = '';

$total_rows = 0;
$imported_rows = 0;
$duplicate_rows = 0;
$invalid_rows = 0;

$duplicate_details = [];
$invalid_details = [];


/*
|--------------------------------------------------------------------------
| Helper: Normalize CSV Header
|--------------------------------------------------------------------------
*/

function normalize_csv_header(
    string $header
): string {

    $header = trim($header);

    $header = strtolower($header);

    $header = str_replace(
        [
            '_',
            '-',
            '/',
            '\\'
        ],
        ' ',
        $header
    );

    $header = preg_replace(
        '/\s+/',
        ' ',
        $header
    );

    return trim($header);
}


/*
|--------------------------------------------------------------------------
| Helper: Read CSV Value
|--------------------------------------------------------------------------
*/

function csv_value(
    array $row,
    array $headers,
    array $possible_headers
): string {

    foreach ($possible_headers as $possible) {

        $possible =
            normalize_csv_header($possible);

        if (
            isset($headers[$possible]) &&
            isset($row[$headers[$possible]])
        ) {

            return trim(
                (string) $row[
                    $headers[$possible]
                ]
            );
        }
    }

    return '';
}


/*
|--------------------------------------------------------------------------
| Helper: Validate Email
|--------------------------------------------------------------------------
*/

function valid_import_email(
    string $email
): bool {

    if ($email === '') {
        return true;
    }

    return filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    ) !== false;
}


/*
|--------------------------------------------------------------------------
| Helper: Normalize Source
|--------------------------------------------------------------------------
*/

function normalize_import_source(
    string $source
): string {

    $source = trim($source);

    if ($source === '') {
        return 'Other';
    }

    $normalized =
        strtolower(
            preg_replace(
                '/\s+/',
                ' ',
                $source
            )
        );

    $aliases = [

        'google' => 'Google Ads',
        'google ad' => 'Google Ads',
        'google ads' => 'Google Ads',
        'google advertisement' => 'Google Ads',

        'facebook' => 'Facebook',
        'fb' => 'Facebook',

        'instagram' => 'Instagram',
        'ig' => 'Instagram',

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

        'health camp' => 'Health Camp',

        'other' => 'Other'
    ];

    if (isset($aliases[$normalized])) {

        return $aliases[$normalized];
    }

    /*
    | Preserve unknown source names.
    |
    | Example:
    | YouTube
    | LinkedIn
    | Newspaper
    | Doctor Referral
    |
    | These can automatically become new
    | marketing source records.
    */

    return ucwords($source);
}


/*
|--------------------------------------------------------------------------
| Process Import
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Validate File
    |--------------------------------------------------------------------------
    */

    if (
        !isset($_FILES['csv_file']) ||
        $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK
    ) {

        $error =
            'Please select a valid CSV file.';

    } elseif (
        $_FILES['csv_file']['size'] >
        10 * 1024 * 1024
    ) {

        $error =
            'CSV file size must not exceed 10 MB.';

    } else {

        $file_tmp =
            $_FILES['csv_file']['tmp_name'];

        $file_name =
            $_FILES['csv_file']['name'];

        $extension =
            strtolower(
                pathinfo(
                    $file_name,
                    PATHINFO_EXTENSION
                )
            );

        if ($extension !== 'csv') {

            $error =
                'Only CSV files are allowed.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Start Import
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $handle =
            fopen(
                $_FILES['csv_file']['tmp_name'],
                'r'
            );

        if (!$handle) {

            $error =
                'Unable to read the CSV file.';

        } else {

            /*
            |--------------------------------------------------------------------------
            | Read Header
            |--------------------------------------------------------------------------
            */

            $raw_headers =
                fgetcsv($handle);

            if (
                !$raw_headers ||
                count($raw_headers) === 0
            ) {

                $error =
                    'CSV file is empty or has no header row.';

                fclose($handle);

            } else {

                /*
                |--------------------------------------------------------------------------
                | Normalize Headers
                |--------------------------------------------------------------------------
                */

                $headers = [];

                foreach (
                    $raw_headers as $index => $header
                ) {

                    $normalized_header =
                        normalize_csv_header(
                            (string) $header
                        );

                    if (
                        $normalized_header !== ''
                    ) {

                        $headers[
                            $normalized_header
                        ] = $index;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Required Columns
                |--------------------------------------------------------------------------
                */

                $name_column = null;
                $phone_column = null;

                foreach (
                    [
                        'name',
                        'lead name',
                        'patient name',
                        'patient'
                    ] as $header
                ) {

                    $header =
                        normalize_csv_header(
                            $header
                        );

                    if (
                        isset(
                            $headers[$header]
                        )
                    ) {

                        $name_column =
                            $headers[$header];

                        break;
                    }
                }

                foreach (
                    [
                        'phone',
                        'mobile',
                        'mobile number',
                        'phone number',
                        'contact number'
                    ] as $header
                ) {

                    $header =
                        normalize_csv_header(
                            $header
                        );

                    if (
                        isset(
                            $headers[$header]
                        )
                    ) {

                        $phone_column =
                            $headers[$header];

                        break;
                    }
                }


                if (
                    $name_column === null ||
                    $phone_column === null
                ) {

                    $error =
                        'CSV must contain Name and Phone columns.';

                    fclose($handle);

                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | Import Transaction
                    |--------------------------------------------------------------------------
                    */

                    try {

                        $pdo->beginTransaction();

                        /*
                        | Prepared Lead Insert
                        */

                        $insert_lead =
                            $pdo->prepare("
                                INSERT INTO leads (
                                    name,
                                    phone,
                                    email,
                                    location,
                                    address,
                                    age,
                                    gender,
                                    service_interest,
                                    service_id,
                                    department_id,
                                    source_id,
                                    lead_source_type,
                                    campaign_id,
                                    referral_id,
                                    status,
                                    priority,
                                    assigned_to,
                                    next_action_type,
                                    next_action_at,
                                    notes,
                                    created_by
                                )
                                VALUES (
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    ?,
                                    NULL,
                                    NULL,
                                    'New',
                                    'Medium',
                                    ?,
                                    NULL,
                                    NULL,
                                    ?,
                                    ?
                                )
                            ");


                        /*
                        |--------------------------------------------------------------------------
                        | Activity Statement
                        |--------------------------------------------------------------------------
                        */

                        $activity_stmt =
                            $pdo->prepare("
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
                                    ?,
                                    ?,
                                    NOW()
                                )
                            ");


                        /*
                        |--------------------------------------------------------------------------
                        | Process Every CSV Row
                        |--------------------------------------------------------------------------
                        */

                        while (
                            ($row = fgetcsv($handle))
                            !== false
                        ) {

                            /*
                            | Skip completely empty rows.
                            */

                            $has_data = false;

                            foreach ($row as $cell) {

                                if (
                                    trim(
                                        (string) $cell
                                    ) !== ''
                                ) {

                                    $has_data = true;

                                    break;
                                }
                            }

                            if (!$has_data) {
                                continue;
                            }


                            $total_rows++;


                            /*
                            |--------------------------------------------------------------------------
                            | Basic Fields
                            |--------------------------------------------------------------------------
                            */

                            $name =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Name',
                                        'Lead Name',
                                        'Patient Name',
                                        'Patient'
                                    ]
                                );

                            $phone =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Phone',
                                        'Mobile',
                                        'Mobile Number',
                                        'Phone Number',
                                        'Contact Number'
                                    ]
                                );

                            $email =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Email',
                                        'Email Address'
                                    ]
                                );

                            $location =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Location',
                                        'City',
                                        'Area'
                                    ]
                                );

                            $address =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Address'
                                    ]
                                );

                            $age_text =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Age'
                                    ]
                                );

                            $gender =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Gender',
                                        'Sex'
                                    ]
                                );

                            $department =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Department',
                                        'Department Name'
                                    ]
                                );

                            $service =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Service',
                                        'Service Name',
                                        'Service Interest',
                                        'Department / Service Interest'
                                    ]
                                );

                            $notes =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Note',
                                        'Notes',
                                        'Remarks'
                                    ]
                                );

                            /*
                            |--------------------------------------------------------------------------
                            | IMPORTANT:
                            | Read actual Lead Source from CSV.
                            |--------------------------------------------------------------------------
                            */

                            $lead_source =
                                csv_value(
                                    $row,
                                    $headers,
                                    [
                                        'Lead Source',
                                        'Lead source',
                                        'Source',
                                        'Marketing Source',
                                        'Source Name',
                                        'Acquisition Source'
                                    ]
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | Required Validation
                            |--------------------------------------------------------------------------
                            */

                            if ($name === '') {

                                $invalid_rows++;

                                $invalid_details[] =
                                    'Row ' .
                                    ($total_rows + 1) .
                                    ': Name is missing.';

                                continue;
                            }

                            if ($phone === '') {

                                $invalid_rows++;

                                $invalid_details[] =
                                    'Row ' .
                                    ($total_rows + 1) .
                                    ': Phone is missing.';

                                continue;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Email Validation
                            |--------------------------------------------------------------------------
                            */

                            if (
                                !valid_import_email(
                                    $email
                                )
                            ) {

                                $invalid_rows++;

                                $invalid_details[] =
                                    'Row ' .
                                    ($total_rows + 1) .
                                    ': Invalid email address.';

                                continue;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Age Validation
                            |--------------------------------------------------------------------------
                            */

                            $age = null;

                            if ($age_text !== '') {

                                if (
                                    !ctype_digit(
                                        $age_text
                                    )
                                ) {

                                    $invalid_rows++;

                                    $invalid_details[] =
                                        'Row ' .
                                        ($total_rows + 1) .
                                        ': Invalid age.';

                                    continue;
                                }

                                $age =
                                    (int) $age_text;

                                if (
                                    $age < 0 ||
                                    $age > 120
                                ) {

                                    $invalid_rows++;

                                    $invalid_details[] =
                                        'Row ' .
                                        ($total_rows + 1) .
                                        ': Age must be between 0 and 120.';

                                    continue;
                                }
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Duplicate Check
                            |--------------------------------------------------------------------------
                            */

                            $duplicate =
                                find_duplicate_lead_by_phone(
                                    $pdo,
                                    $phone
                                );

                            if ($duplicate) {

                                $duplicate_rows++;

                                $duplicate_details[] =
                                    $name .
                                    ' (' .
                                    $phone .
                                    ') - already exists.';

                                continue;
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Resolve Department / Service
                            |--------------------------------------------------------------------------
                            */

                            $resolved =
                                resolve_lead_department_service(
                                    $pdo,
                                    $department,
                                    $service
                                );

                            $department_id =
                                $resolved['department_id'];

                            $service_id =
                                $resolved['service_id'];


                            /*
                            |--------------------------------------------------------------------------
                            | Resolve Source
                            |--------------------------------------------------------------------------
                            |
                            | THIS IS THE IMPORTANT CHANGE.
                            |
                            | If CSV says:
                            |
                            | Facebook
                            | Instagram
                            | Google Ads
                            | Website
                            | Referral
                            | Walk-in
                            |
                            | those exact sources are stored in source_id.
                            |
                            | "Bulk Import" is NOT used as Lead Source.
                            |
                            | lead_source_type stores how the lead entered
                            | the system: Bulk Import.
                            |
                            */

                            $lead_source =
                                normalize_import_source(
                                    $lead_source
                                );

                            $source_id =
                                get_or_create_marketing_source(
                                    $pdo,
                                    $lead_source
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | Balanced Telecaller Assignment
                            |--------------------------------------------------------------------------
                            */

                            $assigned_to =
                                assign_lead_to_telecaller(
                                    $pdo,
                                    $department_id,
                                    $service_id
                                );


                            /*
                            |--------------------------------------------------------------------------
                            | Insert Lead
                            |--------------------------------------------------------------------------
                            */

                            $insert_lead->execute([

                                $name,

                                $phone,

                                $email !== ''
                                    ? $email
                                    : null,

                                $location !== ''
                                    ? $location
                                    : null,

                                $address !== ''
                                    ? $address
                                    : null,

                                $age,

                                $gender !== ''
                                    ? $gender
                                    : null,

                                $service !== ''
                                    ? $service
                                    : null,

                                $service_id,

                                $department_id,

                                $source_id,

                                /*
                                | Import Method
                                */

                                'Bulk Import',

                                $assigned_to,

                                $notes !== ''
                                    ? $notes
                                    : null,

                                $user_id
                            ]);


                            /*
                            |--------------------------------------------------------------------------
                            | New Lead ID
                            |--------------------------------------------------------------------------
                            */

                            $lead_id =
                                (int) $pdo->lastInsertId();


                            /*
                            |--------------------------------------------------------------------------
                            | Lead Created Activity
                            |--------------------------------------------------------------------------
                            */

                            $activity_stmt->execute([

                                $lead_id,

                                $user_id,

                                'Lead Created',

                                'Lead imported through bulk CSV import.'
                            ]);


                            /*
                            |--------------------------------------------------------------------------
                            | Assignment Activity
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $assigned_to !== null
                            ) {

                                $assigned_name_stmt =
                                    $pdo->prepare("
                                        SELECT
                                            name
                                        FROM users
                                        WHERE id = ?
                                        LIMIT 1
                                    ");

                                $assigned_name_stmt->execute([
                                    $assigned_to
                                ]);

                                $assigned_user =
                                    $assigned_name_stmt->fetch();

                                if ($assigned_user) {

                                    $activity_stmt->execute([

                                        $lead_id,

                                        $user_id,

                                        'Lead Assigned',

                                        'Lead automatically assigned to ' .
                                        $assigned_user['name'] .
                                        ' through balanced Telecaller distribution.'
                                    ]);
                                }
                            }


                            /*
                            |--------------------------------------------------------------------------
                            | Source Activity
                            |--------------------------------------------------------------------------
                            */

                            $activity_stmt->execute([

                                $lead_id,

                                $user_id,

                                'Source Assigned',

                                'Lead source captured as: ' .
                                $lead_source .
                                '. Import method: Bulk Import.'
                            ]);


                            $imported_rows++;
                        }


                        fclose($handle);

                        $pdo->commit();


                        /*
                        |--------------------------------------------------------------------------
                        | Success Message
                        |--------------------------------------------------------------------------
                        */

                        $success =
                            'Bulk import completed successfully. ' .
                            $imported_rows .
                            ' lead(s) imported. ' .
                            $duplicate_rows .
                            ' duplicate(s) skipped. ' .
                            $invalid_rows .
                            ' invalid row(s) skipped.';


                    } catch (Throwable $e) {

                        if (
                            $pdo->inTransaction()
                        ) {

                            $pdo->rollBack();
                        }

                        fclose($handle);

                        $error =
                            'Unable to import leads: ' .
                            $e->getMessage();
                    }
                }
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../../includes/header.php';

?>

<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h1 class="hm-page-title mb-1">
                Bulk Lead Import
            </h1>

            <p class="hm-muted mb-0">
                Import multiple leads from a CSV file with automatic source mapping and Telecaller assignment.
            </p>

        </div>

        <a
            href="<?php echo BASE_URL; ?>/manager/leads/"
            class="btn btn-outline-secondary"
        >
            Back to Leads
        </a>

    </div>


    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">

            <?php
            echo htmlspecialchars($error);
            ?>

        </div>

    <?php endif; ?>


    <?php if ($success !== ''): ?>

        <div class="alert alert-success">

            <?php
            echo htmlspecialchars($success);
            ?>

        </div>

    <?php endif; ?>


    <div class="row g-4">

        <!-- IMPORT FORM -->

        <div class="col-lg-7">

            <div class="hm-card p-4">

                <h5 class="fw-bold mb-3">
                    Import Leads
                </h5>

                <form
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <div class="mb-3">

                        <label
                            for="csv_file"
                            class="form-label"
                        >
                            CSV File
                        </label>

                        <input
                            type="file"
                            name="csv_file"
                            id="csv_file"
                            class="form-control"
                            accept=".csv"
                            required
                        >

                        <div class="form-text">
                            Maximum file size: 10 MB.
                        </div>

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Import Leads
                    </button>

                </form>

            </div>

        </div>


        <!-- FORMAT -->

        <div class="col-lg-5">

            <div class="hm-card p-4">

                <h5 class="fw-bold mb-3">
                    Supported CSV Columns
                </h5>

                <div class="small">

                    <p class="mb-2">
                        <strong>Required:</strong>
                    </p>

                    <ul>

                        <li>Name</li>

                        <li>Phone</li>

                    </ul>


                    <p class="mb-2">
                        <strong>Optional:</strong>
                    </p>

                    <ul>

                        <li>Email</li>

                        <li>Location / City</li>

                        <li>Address</li>

                        <li>Age</li>

                        <li>Gender</li>

                        <li>Department</li>

                        <li>Service / Service Interest</li>

                        <li>Note / Notes</li>

                        <li>
                            <strong>Lead Source</strong>
                        </li>

                    </ul>

                </div>

            </div>

        </div>

    </div>


    <?php if (!empty($duplicate_details)): ?>

        <div class="hm-card p-4 mt-4">

            <h5 class="fw-bold">
                Duplicate Leads Skipped
            </h5>

            <ul class="mb-0">

                <?php foreach (
                    $duplicate_details
                    as $detail
                ): ?>

                    <li>
                        <?php
                        echo htmlspecialchars($detail);
                        ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <?php if (!empty($invalid_details)): ?>

        <div class="hm-card p-4 mt-4">

            <h5 class="fw-bold">
                Invalid Rows Skipped
            </h5>

            <ul class="mb-0">

                <?php foreach (
                    $invalid_details
                    as $detail
                ): ?>

                    <li>
                        <?php
                        echo htmlspecialchars($detail);
                        ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>

</div>

<?php

require_once __DIR__ . '/../../includes/footer.php';

?>