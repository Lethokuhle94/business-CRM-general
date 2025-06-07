<?php
require_once 'includes/config.php';
require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 mx-auto text-center">
            <h1 class="display-4 mb-4">Welcome to Probi Notes</h1>
            <p class="lead">A simple solution for creating and managing invoices</p>
            
            <!-- Company Logo -->
            <div class="my-4">
                <img src="assets/images/logos/1.png" alt="Company Logo" class="img-fluid" style="max-height: 140px;">
            </div>
            
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mt-4">
                <a href="register.php" class="btn btn-primary btn-lg px-4 gap-3">Get Started</a>
                <a href="login.php" class="btn btn-outline-secondary btn-lg px-4">Login</a>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>