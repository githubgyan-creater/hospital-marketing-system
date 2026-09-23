<?php

require_once __DIR__ . '/../config/config.php';

$page_title = $page_title ?? APP_NAME;

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?php echo htmlspecialchars($page_title); ?>
        | Hospital Marketing
    </title>

    <!-- Bootstrap 5 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Our custom CSS -->
    <link
        rel="stylesheet"
        href="<?php echo BASE_URL; ?>/assets/css/style.css"
    >

</head>

<body>