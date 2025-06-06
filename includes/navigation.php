<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="/index.php">
            <i class="fas fa-file-invoice me-2"></i>InvoicePro
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                    </li>
                    
                    <!-- Invoices Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="invoicesDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-file-invoice me-1"></i> Invoices
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="invoicesDropdown">
                            <li><a class="dropdown-item" href="/invoices/create.php"><i class="fas fa-plus-circle me-1"></i> Create New</a></li>
                            <li><a class="dropdown-item" href="/invoices/list.php"><i class="fas fa-list me-1"></i> View All</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/invoices/reports.php"><i class="fas fa-chart-bar me-1"></i> Reports</a></li>
                        </ul>
                    </li>
                    
                    <!-- Clients Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="clientsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users me-1"></i> Clients
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="clientsDropdown">
                            <li><a class="dropdown-item" href="/clients/create.php"><i class="fas fa-user-plus me-1"></i> Add Client</a></li>
                            <li><a class="dropdown-item" href="/clients/list.php"><i class="fas fa-address-book me-1"></i> Client List</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/clients/groups.php"><i class="fas fa-object-group me-1"></i> Groups</a></li>
                        </ul>
                    </li>
                    
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                    </li>
                <?php endif; ?>
                    <!-- Add this to the navbar-nav section where other main links are -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $BASE_URL ?>/settings.php">
                            <i class="fas fa-cog me-1"></i> Settings
                        </a>
                    </li>
            </ul>
        </div>
    </div>
</nav>