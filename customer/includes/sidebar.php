<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Bottom Navigation Bar -->
<div id="bottom-nav" class="fixed bottom-0 left-0 right-0  shadow-lg border-t border-gray-200 z-20">
    <div class="flex justify-around items-center h-16">
        <!-- Dashboard -->
        <a href="dashboard" class="flex flex-col items-center justify-center w-full h-full <?php echo $current_page == 'dashboard' ? 'text-[#FF6B45]' : 'text-gray-600'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>
            <span class="text-xs mt-1">Home</span>
        </a>
        
        <!-- Membership -->
        <a href="membership" class="flex flex-col items-center justify-center w-full h-full <?php echo $current_page == 'membership' ? 'text-[#FF6B45]' : 'text-gray-600'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
            </svg>
            <span class="text-xs mt-1">Membership</span>
        </a>
        
        <!-- Classes -->
        <a href="card" class="flex flex-col items-center justify-center w-full h-full <?php echo $current_page == 'classes' ? 'text-[#FF6B45]' : 'text-gray-600'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
            <span class="text-xs mt-1">Card</span>
        </a>
        
        <!-- Notifications -->
        <a href="notification" class="flex flex-col items-center justify-center w-full h-full <?php echo $current_page == 'notification' ? 'text-[#FF6B45]' : 'text-gray-600'; ?>">
            <div class="relative">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                <?php 
                // You can add notification count logic here
                $notification_count = 0; // Replace with actual count from database
                if ($notification_count > 0): 
                ?>
                <span class="absolute -top-1 -right-1 h-4 w-4 bg-[#FF6B45] text-white text-xs flex items-center justify-center rounded-full"><?php echo $notification_count; ?></span>
                <?php endif; ?>
            </div>
            <span class="text-xs mt-1">Alerts</span>
        </a>
        
        <!-- Profile -->
        <a href="profile" class="flex flex-col items-center justify-center w-full h-full <?php echo $current_page == 'profile' ? 'text-[#FF6B45]' : 'text-gray-600'; ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            <span class="text-xs mt-1">Profile</span>
        </a>
    </div>
</div>

<!-- More Menu Modal -->
<div id="more-menu-modal" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden flex items-end justify-center">
    <div class="bg-white rounded-t-xl w-full max-w-md p-4 animate-slide-up">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-800">More Options</h3>
            <button id="close-more-menu" class="text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <div class="grid grid-cols-3 gap-4">
            <!-- Workouts -->
            <a href="workouts" class="flex flex-col items-center justify-center p-3 rounded-lg hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-600 mb-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 18a3.75 3.75 0 00.495-7.467 5.99 5.99 0 00-1.925 3.546 5.974 5.974 0 01-2.133-1A3.75 3.75 0 0012 18z" />
                </svg>
                <span class="text-xs">Workouts</span>
            </a>
            
            <!-- WhatsApp Test -->
            <a href="test" class="flex flex-col items-center justify-center p-3 rounded-lg hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-600 mb-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                <span class="text-xs">WhatsApp</span>
            </a>
            
            <!-- Settings -->
            <a href="settings" class="flex flex-col items-center justify-center p-3 rounded-lg hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-600 mb-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span class="text-xs">Settings</span>
            </a>
            
            <!-- Help -->
            <a href="help" class="flex flex-col items-center justify-center p-3 rounded-lg hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-600 mb-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />
                </svg>
                <span class="text-xs">Help</span>
            </a>
            
            <!-- Logout -->
            <a href="logout" class="flex flex-col items-center justify-center p-3 rounded-lg hover:bg-gray-100">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-600 mb-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                </svg>
                <span class="text-xs">Logout</span>
            </a>
        </div>
    </div>
</div>

<!-- Add padding to the bottom of the content to prevent it from being hidden behind the bottom nav -->
<style>
    body {
        padding-bottom: 5rem; /* Adjust based on the height of your bottom nav */
    }
    
    @keyframes slide-up {
        from { transform: translateY(100%); }
        to { transform: translateY(0); }
    }
    
    .animate-slide-up {
        animation: slide-up 0.3s ease-out;
    }
    #botton-nav{
        
    }
</style>

<script>
    // More menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const moreMenuModal = document.getElementById('more-menu-modal');
        const closeMoreMenuBtn = document.getElementById('close-more-menu');
        
        // Close more menu when clicking the close button
        if (closeMoreMenuBtn) {
            closeMoreMenuBtn.addEventListener('click', function() {
                moreMenuModal.classList.add('hidden');
            });
        }
        
        // Close more menu when clicking outside the menu
        if (moreMenuModal) {
            moreMenuModal.addEventListener('click', function(e) {
                if (e.target === moreMenuModal) {
                    moreMenuModal.classList.add('hidden');
                }
            });
        }
    });
</script>