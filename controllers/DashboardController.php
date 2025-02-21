<!-- 
class DashboardController {
    public function showDashboard(): void {
        if (!isset($_SESSION['role'])) {
            header("Location: index.php");
            exit();
        }

        switch ($_SESSION['role']) {
            case 'admin':
                include 'views/dashboard_admin.php';
                break;
            case 'mp':
                include 'views/dashboard_mp.php';
                break;
            case 'reviewer':
                include 'views/dashboard_reviewer.php';
                break;
            default:
                session_destroy();
                header("Location: index.php");
        }
    }
}
 -->
