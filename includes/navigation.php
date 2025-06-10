<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="/index.php">
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
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">
                            <span class="nav-led" data-target="dashboard-led"></span>
                            Dashboard
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="/invoices/list.php">
                            <span class="nav-led" data-target="invoices-led"></span>
                            Invoice Management
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="/clients/list.php">
                            <span class="nav-led" data-target="clients-led"></span>
                            Customer Management
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="/financial/list.php">
                            <span class="nav-led" data-target="finance-led"></span>
                            Financial Management
                        </a>
                    </li>
                    
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/index.php">
                            <span class="nav-led" data-target="home-led"></span>
                            Home
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php">
                            <span class="nav-led" data-target="logout-led"></span>
                            Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login.php">
                            <span class="nav-led" data-target="login-led"></span>
                            Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/register.php">
                            <span class="nav-led" data-target="register-led"></span>
                            Register
                        </a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?= $BASE_URL ?>/settings.php">
                        <span class="nav-led" data-target="settings-led"></span>
                        Settings
                    </a>
                </li>
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
    
    /* Mobile menu improvements */
    .navbar-collapse {
        transition: height 0.3s ease;
        overflow: hidden;
    }
    
    @media (max-width: 991.98px) {
        .navbar-collapse {
            position: fixed;
            top: 56px; /* Height of navbar */
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
        
        /* Mobile menu hint */
        .mobile-menu-hint {
            display: block;
            font-size: 0.8rem;
            text-align: center;
            color: #6c757d;
            padding: 5px 0 15px;
        }
    }
    
    @media (min-width: 992px) {
        .mobile-close-btn {
            display: none !important;
        }
        
        .mobile-menu-hint {
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
    const navLinks = document.querySelectorAll('.nav-link');
    
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