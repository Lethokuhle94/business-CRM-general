<?php
require_once 'includes/config.php';

// Redirect to login if not authenticated
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if username is set in session
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';

require_once 'includes/header.php';
require_once 'includes/navigation.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
            <p>This is your dashboard. From here you can manage your invoices.</p>
            
            <div class="row mt-4">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Create Invoice</h5>
                            <p class="card-text">Create a new invoice for your clients.</p>
                            <a href="#" class="btn btn-primary">New Invoice</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">View Invoices</h5>
                            <p class="card-text">View and manage your existing invoices.</p>
                            <a href="#" class="btn btn-primary">View All</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <h5 class="card-title">Clients</h5>
                            <p class="card-text">Manage your client information.</p>
                            <a href="#" class="btn btn-primary">Clients</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>