<?php

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/role_check.php';
require_once __DIR__ . '/../../config/database.php';

require_role('admin');

$page_title = 'Settings';

$user = current_user();

$message = '';
$message_type = '';

/*

| Default Settings

|
| These are created automatically the first time the page is opened.
|
*/

$default_settings = [

    [
        'setting_key'   => 'hospital_name',
        'setting_value' => '',
        'setting_group' => 'Hospital',
        'description'   => 'Primary hospital name'
    ],

    [
        'setting_key'   => 'hospital_phone',
        'setting_value' => '',
        'setting_group' => 'Hospital',
        'description'   => 'Primary hospital contact number'
    ],

    [
        'setting_key'   => 'hospital_email',
        'setting_value' => '',
        'setting_group' => 'Hospital',
        'description'   => 'Primary hospital email address'
    ],

    [
        'setting_key'   => 'website_url',
        'setting_value' => '',
        'setting_group' => 'Hospital',
        'description'   => 'Hospital website URL'
    ],

    [
        'setting_key'   => 'timezone',
        'setting_value' => 'Asia/Kolkata',
        'setting_group' => 'System',
        'description'   => 'System timezone'
    ],

    [
        'setting_key'   => 'default_currency',
        'setting_value' => 'INR',
        'setting_group' => 'System',
        'description'   => 'Default currency used by the system'
    ],

    [
        'setting_key'   => 'lead_auto_assignment',
        'setting_value' => 'OFF',
        'setting_group' => 'Leads',
        'description'   => 'Enable automatic lead assignment'
    ]

];


/*

| Insert Missing Default Settings

*/

try {

    $insert_stmt = $pdo->prepare("
        INSERT IGNORE INTO settings (
            setting_key,
            setting_value,
            setting_group,
            description
        )
        VALUES (?, ?, ?, ?)
    ");

    foreach ($default_settings as $default_setting) {

        $insert_stmt->execute([
            $default_setting['setting_key'],
            $default_setting['setting_value'],
            $default_setting['setting_group'],
            $default_setting['description']
        ]);
    }

} catch (PDOException $e) {

    $message =
        'Unable to initialize settings: ' .
        $e->getMessage();

    $message_type = 'danger';
}


/*

| Handle Save

*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $settings = $_POST['settings'] ?? [];

    if (!is_array($settings)) {
        $settings = [];
    }

    try {

        $pdo->beginTransaction();

        $update_stmt = $pdo->prepare("
            UPDATE settings
            SET
                setting_value = ?
            WHERE setting_key = ?
        ");

        foreach ($settings as $setting_key => $setting_value) {

            $setting_key = trim(
                (string) $setting_key
            );

            $setting_value = trim(
                (string) $setting_value
            );

            if ($setting_key === '') {
                continue;
            }

            $update_stmt->execute([
                $setting_value,
                $setting_key
            ]);
        }

        $pdo->commit();

        $message =
            'Settings updated successfully.';

        $message_type = 'success';

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $message =
            'Unable to update settings: ' .
            $e->getMessage();

        $message_type = 'danger';
    }
}


/*

| Fetch Settings

*/

$stmt = $pdo->query("
    SELECT
        id,
        setting_key,
        setting_value,
        setting_group,
        description
    FROM settings
    ORDER BY
        setting_group ASC,
        id ASC
");

$settings_rows = $stmt->fetchAll();


/*

| Group Settings

*/

$grouped_settings = [];

foreach ($settings_rows as $setting) {

    $group = $setting['setting_group'];

    if (!isset($grouped_settings[$group])) {
        $grouped_settings[$group] = [];
    }

    $grouped_settings[$group][] = $setting;
}


require_once __DIR__ . '/../../includes/header.php';

?>

<main class="container py-4">

    <!-- PAGE HEADER -->

    <div class="mb-4">

        <!-- <span class="badge text-bg-light">
            ADMINISTRATOR
        </span> -->

        <h1 class="hm-page-title mt-1">
            Settings
        </h1>

        <p class="hm-muted">
            Manage system-wide hospital marketing configuration.
        </p>

    </div>


    <?php if ($message !== ''): ?>

        <div
            class="alert alert-<?php echo htmlspecialchars($message_type); ?>"
        >
            <?php echo htmlspecialchars($message); ?>
        </div>

    <?php endif; ?>


    <form method="POST">

        <?php foreach (
            $grouped_settings
            as $group_name => $group_settings
        ): ?>

            <div class="hm-card p-4 mb-4">

                <h5 class="fw-bold mb-4">
                    <?php
                    echo htmlspecialchars($group_name);
                    ?>
                </h5>


                <div class="row g-4">

                    <?php foreach (
                        $group_settings
                        as $setting
                    ): ?>

                        <?php
                        $key = $setting['setting_key'];

                        $value =
                            $setting['setting_value']
                            ?? '';
                        ?>


                        <div class="col-md-6">

                            <label
                                class="form-label fw-semibold"
                                for="setting_<?php echo htmlspecialchars($key); ?>"
                            >

                                <?php
                                echo htmlspecialchars(
                                    ucwords(
                                        str_replace(
                                            '_',
                                            ' ',
                                            $key
                                        )
                                    )
                                );
                                ?>

                            </label>


                            <?php if (
                                $key === 'lead_auto_assignment'
                            ): ?>

                                <select
                                    name="settings[<?php echo htmlspecialchars($key); ?>]"
                                    id="setting_<?php echo htmlspecialchars($key); ?>"
                                    class="form-select"
                                >

                                    <option
                                        value="ON"
                                        <?php
                                        echo $value === 'ON'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        ON
                                    </option>

                                    <option
                                        value="OFF"
                                        <?php
                                        echo $value !== 'ON'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        OFF
                                    </option>

                                </select>


                            <?php elseif (
                                $key === 'timezone'
                            ): ?>

                                <select
                                    name="settings[<?php echo htmlspecialchars($key); ?>]"
                                    id="setting_<?php echo htmlspecialchars($key); ?>"
                                    class="form-select"
                                >

                                    <option
                                        value="Asia/Kolkata"
                                        <?php
                                        echo $value === 'Asia/Kolkata'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        Asia/Kolkata
                                    </option>

                                    <option
                                        value="UTC"
                                        <?php
                                        echo $value === 'UTC'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        UTC
                                    </option>

                                </select>


                            <?php elseif (
                                $key === 'default_currency'
                            ): ?>

                                <select
                                    name="settings[<?php echo htmlspecialchars($key); ?>]"
                                    id="setting_<?php echo htmlspecialchars($key); ?>"
                                    class="form-select"
                                >

                                    <option
                                        value="INR"
                                        <?php
                                        echo $value === 'INR'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        INR - Indian Rupee
                                    </option>

                                    <option
                                        value="USD"
                                        <?php
                                        echo $value === 'USD'
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        USD - US Dollar
                                    </option>

                                </select>


                            <?php else: ?>

                                <input
                                    type="<?php
                                    echo $key === 'hospital_email'
                                        ? 'email'
                                        : (
                                            $key === 'website_url'
                                                ? 'url'
                                                : 'text'
                                        );
                                    ?>"
                                    name="settings[<?php echo htmlspecialchars($key); ?>]"
                                    id="setting_<?php echo htmlspecialchars($key); ?>"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($value); ?>"
                                >

                            <?php endif; ?>


                            <?php if (
                                !empty(
                                    $setting['description']
                                )
                            ): ?>

                                <div class="form-text">

                                    <?php
                                    echo htmlspecialchars(
                                        $setting[
                                            'description'
                                        ]
                                    );
                                    ?>

                                </div>

                            <?php endif; ?>

                        </div>

                    <?php endforeach; ?>

                </div>

            </div>

        <?php endforeach; ?>


        <div class="d-flex justify-content-end">

            <button
                type="submit"
                class="btn btn-hm-primary"
            >
                Save Settings
            </button>

        </div>

    </form>

</main>


<?php

require_once __DIR__ . '/../../includes/footer.php';

?>