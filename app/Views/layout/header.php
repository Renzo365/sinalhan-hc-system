<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? h($title) . ' - ' : '' ?>Barangay Sinalhan Health Center</title>
    <!-- Local CSS Vendor Files -->
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/bootstrap-icons/bootstrap-icons.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/sweetalert2/sweetalert2.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/flatpickr/flatpickr.min.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/datatables/css/dataTables.bootstrap5.min.css') ?>">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= asset('css/index.css') ?>">
</head>
<body>
<?php if (isset($_SESSION['user_id']) && !isset($disable_layout)): ?>
<div class="app-wrapper">
    <!-- Sidebar -->
    <?php require dirname(__DIR__) . '/layout/sidebar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Topbar -->
        <?php require dirname(__DIR__) . '/layout/topbar.php'; ?>
        
        <div class="app-content">
            <!-- Breadcrumbs / Page Header -->
            <?php if (isset($title)): ?>
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= url('/dashboard') ?>">Home</a></li>
                        <?php if (isset($breadcrumbs) && is_array($breadcrumbs)): ?>
                            <?php foreach ($breadcrumbs as $label => $link): ?>
                                <?php if ($link !== null): ?>
                                    <li class="breadcrumb-item"><a href="<?= url($link) ?>"><?= h($label) ?></a></li>
                                <?php else: ?>
                                    <li class="breadcrumb-item active" aria-current="page"><?= h($label) ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page"><?= h($title) ?></li>
                        <?php endif; ?>
                    </ol>
                </nav>
            <?php endif; ?>
<?php endif; ?>
