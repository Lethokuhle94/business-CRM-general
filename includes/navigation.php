<?php
// Ensure BASE_URL is properly set
if (!isset($BASE_URL)) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $BASE_URL = $protocol . $_SERVER['HTTP_HOST'] . '/business-CRM-general';
}
?>

<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $BASE_URL ?>/index.php">
            <i class="fas fa-book me-2"></i>
            <b style="color:grey;">PROBI NOTES</b>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Mobile close button -->
            <button type="button" class="btn-close d-lg-none mobile-close-btn" aria-label="Close menu"></button>
            
            <ul class="navbar-nav me-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $BASE_URL ?>/dashboard.php">
                            <span class="nav-led" data-target="dashboard-led"></span>
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    
                    <!-- Business Management Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="businessDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="nav-led" data-target="business-led"></span>
                            <i class="fas fa-briefcase me-1"></i>Business
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="businessDropdown">
                            <li><h6 class="dropdown-header">Financial Documents</h6></li>
                            <li>
                                <a class="dropdown-item" href="<?= $BASE_URL ?>/invoices/list.php">
                                    <i class="fas fa-file-invoice me-2"></i>Invoices
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $BASE_URL ?>/quotations/list.php">
                                    <i class="fas fa-file-signature me-2"></i>Quotations
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <li><h6 class="dropdown-header">Relationships</h6></li>
                            <li>
                                <a class="dropdown-item" href="<?= $BASE_URL ?>/clients/list.php">
                                    <i class="fas fa-users me-2"></i>Customers
                                </a>
                            </li>
                            
                            <li><hr class="dropdown-divider"></li>
                            
                            <li>
                                <a class="dropdown-item" href="<?= $BASE_URL ?>/financial/list.php">
                                    <i class="fas fa-chart-line me-2"></i>Financial Overview
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- System Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="systemDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="nav-led" data-target="system-led"></span>
                            <i class="fas fa-cog me-1"></i>System
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="systemDropdown">
                            <li>
                                <a class="dropdown-item" href="<?= $BASE_URL ?>/recycle_bin/recycle_bin.php">
                                    <i class="fas fa-trash-restore me-2"></i>Recycle Bin
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $BASE_URL ?>/settings.php">
                                    <i class="fas fa-sliders-h me-2"></i>Settings
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $BASE_URL ?>/index.php">
                            <span class="nav-led" data-target="home-led"></span>
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="nav-led" data-target="user-led"></span>
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= $BASE_URL ?>/settings.php">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?= $BASE_URL ?>/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $BASE_URL ?>/login.php">
                            <span class="nav-led" data-target="login-led"></span>
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $BASE_URL ?>/register.php">
                            <span class="nav-led" data-target="register-led"></span>
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<style>
    .nav-led {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 8px;
        vertical-align: middle;
        background-color: #ff0000;
        box-shadow: 0 0 5px #ff0000;
        transition: all 0.3s ease;
    }
    
    .nav-led.active {
        background-color: #00ff00;
        box-shadow: 0 0 5px #00ff00;
        animation: none;
    }
    
    /* Pulsing effect for inactive LEDs */
    .nav-led:not(.active) {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 0.7; transform: scale(0.95); }
        50% { opacity: 1; transform: scale(1.05); }
        100% { opacity: 0.7; transform: scale(0.95); }
    }
    
    /* Dropdown menu styling */
    .dropdown-menu {
        border: 1px solid rgba(0,0,0,0.1);
        box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    }
    
    .dropdown-item {
        padding: 0.5rem 1.5rem;
        transition: all 0.2s;
    }
    
    .dropdown-item:hover {
        background-color: #f8f9fa;
        padding-left: 1.75rem;
    }
    
    .dropdown-header {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #6c757d;
        padding: 0.5rem 1.5rem;
    }
    
    /* Mobile menu improvements */
    @media (max-width: 991.98px) {
        .navbar-collapse {
            position: fixed;
            top: 56px;
            left: 0;
            right: 0;
            background-color: white;
            z-index: 1000;
            padding: 15px;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
            height: 0 !important;
        }
        
        .navbar-collapse.show {
            height: auto !important;
            max-height: calc(100vh - 56px);
            overflow-y: auto;
        }
        
        .navbar-nav {
            padding: 10px 0;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: none;
            padding-left: 20px;
        }
        
        /* Mobile close button styling */
        .mobile-close-btn {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 1.25rem;
            padding: 0.5rem;
            line-height: 1;
            z-index: 1001;
        }
    }
    
    @media (min-width: 992px) {
        .mobile-close-btn {
            display: none !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap collapse with proper configuration
    const navbarCollapse = new bootstrap.Collapse(document.getElementById('navbarNav'), {
        toggle: false
    });
    
    // Get current page URL to determine active link
    const currentUrl = window.location.pathname;
    
    // Find all nav links
    const navLinks = document.querySelectorAll('.nav-link, .dropdown-item');
    
    // Set initial active state
    navLinks.forEach(link => {
        const linkUrl = link.getAttribute('href');
        if (currentUrl.includes(linkUrl) || 
            (currentUrl === '/' && linkUrl === '/index.php') ||
            (linkUrl.includes(currentUrl) && currentUrl !== '/')) {
            const led = link.querySelector('.nav-led');
            if (led) {
                led.classList.add('active');
            }
            // Highlight parent dropdown if child is active
            if (link.classList.contains('dropdown-item')) {
                const dropdown = link.closest('.dropdown-menu');
                if (dropdown) {
                    const dropdownToggle = dropdown.previousElementSibling;
                    if (dropdownToggle) {
                        const parentLed = dropdownToggle.querySelector('.nav-led');
                        if (parentLed) {
                            parentLed.classList.add('active');
                        }
                    }
                }
            }
        }
    });
    
    // Mobile close button functionality
    document.querySelector('.mobile-close-btn').addEventListener('click', function() {
        navbarCollapse.hide();
        document.querySelector('.navbar-toggler').setAttribute('aria-expanded', 'false');
    });
    
    // Handle link clicks
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Close mobile menu if open
            const toggler = document.querySelector('.navbar-toggler');
            if (window.innerWidth < 992 && toggler.getAttribute('aria-expanded') === 'true') {
                navbarCollapse.hide();
                toggler.setAttribute('aria-expanded', 'false');
            }
            
            // Update LED states
            document.querySelectorAll('.nav-led').forEach(led => {
                led.classList.remove('active');
            });
            
            const led = this.querySelector('.nav-led');
            if (led) {
                led.classList.add('active');
            }
        });
    });
    
    // Properly handle hamburger menu click
    document.querySelector('.navbar-toggler').addEventListener('click', function() {
        const isExpanded = this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', !isExpanded);
    });
    
    // Close menu when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth < 992) {
            const isNavbarClick = e.target.closest('.navbar') !== null;
            const isTogglerClick = e.target.closest('.navbar-toggler') !== null;
            const isCloseBtnClick = e.target.closest('.mobile-close-btn') !== null;
            
            if (!isNavbarClick && !isTogglerClick && !isCloseBtnClick) {
                const toggler = document.querySelector('.navbar-toggler');
                if (toggler.getAttribute('aria-expanded') === 'true') {
                    navbarCollapse.hide();
                    toggler.setAttribute('aria-expanded', 'false');
                }
            }
        }
    });
});
</script>