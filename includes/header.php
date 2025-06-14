<?php 
$APP_NAME = "Probie-Notes";
$ROOT_PATH = $_SERVER['DOCUMENT_ROOT'] . '/business-CRM-general';
$BASE_URL = 'http://' . $_SERVER['HTTP_HOST'] . '/business-CRM-general';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $APP_NAME ?> - <?= $page_title ?? 'Dashboard' ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= $BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            opacity: 0;
            transition: opacity 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container-lg">