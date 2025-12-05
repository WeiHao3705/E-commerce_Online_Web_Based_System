<?php 
session_start();
require __DIR__ . '/database/connection.php';

$db = new Database();
$conn = $db->getConnection();

$page = $_GET['page'] ?? 'home'; // default page

$pageTitle = ucfirst($page);

include 'general/_header.php';
include 'general/_navbar.php';

// Display and clear success/error messages
if (isset($_SESSION['success_message'])) {
    echo '<div class="success-popup" style="position: fixed; top: 80px; right: 20px; background: #4CAF50; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; max-width: 400px; animation: slideIn 0.3s ease;">';
    echo '<i class="fas fa-check-circle" style="margin-right: 8px;"></i>';
    echo htmlspecialchars($_SESSION['success_message']);
    echo '</div>';
    unset($_SESSION['success_message']);
    
    // Auto-hide after 3 seconds
    echo '<script>
        setTimeout(function() {
            var popup = document.querySelector(".success-popup");
            if (popup) {
                popup.style.animation = "slideOut 0.3s ease";
                setTimeout(function() { popup.remove(); }, 300);
            }
        }, 3000);
    </script>';
}

if (isset($_SESSION['error_message'])) {
    echo '<div class="error-popup" style="position: fixed; top: 80px; right: 20px; background: #f44336; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); z-index: 1000; max-width: 400px; animation: slideIn 0.3s ease;">';
    echo '<i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>';
    echo htmlspecialchars($_SESSION['error_message']);
    echo '</div>';
    unset($_SESSION['error_message']);
    
    // Auto-hide after 5 seconds
    echo '<script>
        setTimeout(function() {
            var popup = document.querySelector(".error-popup");
            if (popup) {
                popup.style.animation = "slideOut 0.3s ease";
                setTimeout(function() { popup.remove(); }, 300);
            }
        }, 5000);
    </script>';
}

// ROUTING
switch ($page) {

    case 'home':
    default:
        if (empty($_SESSION['user'])) {
            require __DIR__ . '/views/guest/GuestHome.php';
        } else {
            require __DIR__ . '/views/member/MemberHome.php';
        }
        break;
}

include 'general/_footer.php';
?>
