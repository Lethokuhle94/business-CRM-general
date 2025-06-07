document.addEventListener('DOMContentLoaded', function() {
    // Get current page URL to determine active link
    const currentUrl = window.location.pathname.split('/').pop() || 'index.php';
    
    // Find all nav links
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        // Check if this link's href matches current page
        const linkUrl = link.getAttribute('href').split('/').pop();
        
        link.addEventListener('click', function(e) {
            // Reset all LEDs to red blinking
            document.querySelectorAll('.nav-led').forEach(led => {
                led.classList.remove('led-green');
                led.classList.add('led-red', 'blink');
            });
            
            // Find the LED in this clicked link and make it solid green
            const led = this.querySelector('.nav-led');
            if (led) {
                led.classList.remove('led-red', 'blink');
                led.classList.add('led-green');
            }
            
            // For dropdown items, also highlight the parent dropdown LED
            if (this.closest('.dropdown-menu')) {
                const parentDropdown = this.closest('.dropdown-menu').previousElementSibling;
                if (parentDropdown) {
                    const parentLed = parentDropdown.querySelector('.nav-led');
                    if (parentLed) {
                        parentLed.classList.remove('led-red', 'blink');
                        parentLed.classList.add('led-green');
                    }
                }
            }
        });
        
        // Highlight current page on load
        if (linkUrl === currentUrl || 
            (currentUrl === '' && linkUrl === 'index.php') ||
            (link.href.includes(currentUrl) && currentUrl !== '')) {
            const led = link.querySelector('.nav-led');
            if (led) {
                led.classList.remove('led-red', 'blink');
                led.classList.add('led-green');
            }
        }
    });
});