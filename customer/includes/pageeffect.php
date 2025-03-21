<?php
/**
 * PageEffect - Smooth page transition system with Orange Theme
 * 
 * Include this file at the top of each page where you want transitions
 * 
 * @version 1.1
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration options (customize as needed)
$config = [
    'animationDuration' => 800, // increased for smoother transition
    'animationType' => 'slide', // slide, fade, etc.
    'defaultDirection' => 'left', // right, left, up, down
    'enableOnMobile' => true,
    'excludeUrls' => [], // URLs where transitions should be disabled
    'themeColor' => '#FF8C00' // Orange theme color
];

// Track previous and current page for direction detection
$currentUrl = $_SERVER['REQUEST_URI'];
$previousUrl = isset($_SESSION['current_page']) ? $_SESSION['current_page'] : '';
$_SESSION['previous_page'] = $previousUrl;
$_SESSION['current_page'] = $currentUrl;

// Determine transition direction based on navigation history
$direction = $config['defaultDirection'];
if (isset($_SESSION['direction'])) {
    $direction = $_SESSION['direction'];
    unset($_SESSION['direction']);
}

// Output the HTML, CSS, and JavaScript needed for transitions
?>
<!-- PageEffect: Smooth Page Transitions - Orange Theme -->
<style>
    /* Hide scrollbar during transitions */
    body.page-transitioning {
        overflow: hidden;
    }
    
    /* Main transition container */
    #page-transition-container {
        position: fixed;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        z-index: 9999;
        pointer-events: none;
    }
    
    /* Page overlay for transitions */
    #page-transition-overlay {
        position: absolute;
        width: 100%;
        height: 100%;
        top: 0;
        left: 0;
        background-image: repeating-radial-gradient(circle at 0 0, transparent 0, #ff6b45 100px), repeating-linear-gradient(#e74c3c, #ff6b45);
        background-color: #ff6b45;
        transform: translateX(100%);
        transition: transform <?php echo $config['animationDuration']/1000; ?>s cubic-bezier(0.65, 0, 0.35, 1);
        will-change: transform;
    }
    
    /* Active state for the overlay */
    #page-transition-overlay.active {
        transform: translateX(0);
        pointer-events: all;
    }
    
    /* Direction-specific animations */
    .direction-left #page-transition-overlay {
        transform: translateX(-100%);
    }
    .direction-left #page-transition-overlay.active {
        transform: translateX(0);
    }
    
    .direction-right #page-transition-overlay {
        transform: translateX(100%);
    }
    .direction-right #page-transition-overlay.active {
        transform: translateX(0);
    }
    
    .direction-up #page-transition-overlay {
        transform: translateY(-100%);
    }
    .direction-up #page-transition-overlay.active {
        transform: translateY(0);
    }
    
    .direction-down #page-transition-overlay {
        transform: translateY(100%);
    }
    .direction-down #page-transition-overlay.active {
        transform: translateY(0);
    }
    
    /* Content animation - modified to reduce blinking */
    .page-content {
        opacity: 1; /* Start visible to prevent blinking */
        transition: opacity 0.3s ease-in-out;
    }
    
    /* Only fade out content when transitioning */
    body.page-transitioning .page-content {
        opacity: 0;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        <?php if (!$config['enableOnMobile']): ?>
        #page-transition-container {
            display: none;
        }
        <?php endif; ?>
    }
</style>

<!-- Create the transition elements -->
<div id="page-transition-container" class="direction-<?php echo htmlspecialchars($direction); ?>">
    <div id="page-transition-overlay"></div>
</div>

<!-- Wrap the page content for animations -->
<script>
    // Immediately wrap the content
    document.addEventListener('DOMContentLoaded', function() {
        // Don't wrap if already wrapped
        if (!document.querySelector('.page-content')) {
            // Get all body children except our transition container
            const bodyChildren = Array.from(document.body.children).filter(
                child => child.id !== 'page-transition-container'
            );
            
            // Create wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'page-content';
            
            // Move all children to wrapper
            bodyChildren.forEach(child => {
                wrapper.appendChild(child);
            });
            
            // Add wrapper to body
            document.body.appendChild(wrapper);
        }
    });
    
    // Handle page transitions
    document.addEventListener('DOMContentLoaded', function() {
        // Function to handle navigation
        function handleNavigation(url, direction = null) {
            // Skip for excluded URLs
            const excludedUrls = <?php echo json_encode($config['excludeUrls']); ?>;
            if (excludedUrls.some(excluded => url.includes(excluded))) {
                window.location.href = url;
                return;
            }
            
            // Set direction in session via AJAX if provided
            if (direction) {
                const directionRequest = new XMLHttpRequest();
                directionRequest.open('POST', '<?php echo $_SERVER['PHP_SELF']; ?>', true);
                directionRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                directionRequest.send('set_direction=' + direction);
            }
            
            // Perform transition
            document.body.classList.add('page-transitioning');
            const overlay = document.getElementById('page-transition-overlay');
            overlay.classList.add('active');
            
            // Navigate after animation completes
            setTimeout(() => {
                window.location.href = url;
            }, <?php echo $config['animationDuration'] * 0.9; ?>); // Slightly shorter to prevent blinking
        }
        
        // Intercept link clicks
        document.body.addEventListener('click', function(e) {
            // Find closest anchor tag
            const link = e.target.closest('a');
            
            if (link && 
                link.href && 
                link.href.indexOf(window.location.origin) === 0 && // Same origin
                !link.getAttribute('target') && // Not opening in new tab
                !link.getAttribute('download') && // Not downloading
                !e.ctrlKey && !e.metaKey && !e.shiftKey) { // Not modified click
                
                e.preventDefault();
                
                // Determine direction based on link data attribute if available
                let direction = link.getAttribute('data-transition-direction');
                
                // If no direction specified, use default
                if (!direction) {
                    direction = '<?php echo $config['defaultDirection']; ?>';
                }
                
                handleNavigation(link.href, direction);
            }
        });
        
        // Intercept form submissions
        document.body.addEventListener('submit', function(e) {
            const form = e.target;
            
            // Only handle forms that navigate to same origin
            if (form.method.toLowerCase() === 'get' && 
                (!form.action || form.action.indexOf(window.location.origin) === 0)) {
                
                e.preventDefault();
                
                // Build URL from form data
                const formData = new FormData(form);
                const queryString = new URLSearchParams(formData).toString();
                const url = form.action || window.location.href.split('?')[0];
                const fullUrl = url + (queryString ? '?' + queryString : '');
                
                // Get direction from form data attribute
                const direction = form.getAttribute('data-transition-direction') || 
                                 '<?php echo $config['defaultDirection']; ?>';
                
                handleNavigation(fullUrl, direction);
            }
        });
    });
    
    // Handle back/forward browser navigation
    window.addEventListener('popstate', function(e) {
        // We can't prevent the navigation, but we can show the transition
        const overlay = document.getElementById('page-transition-overlay');
        overlay.classList.add('active');
    });
</script>

<?php
// Handle AJAX requests to set direction
if (isset($_POST['set_direction'])) {
    $_SESSION['direction'] = $_POST['set_direction'];
    exit;
}
?>