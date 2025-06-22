<?php
// index.php - Main routing file
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/include/function.php';
require_once __DIR__ . '/db/conn.php';

$option = null;

// Generate and store CSRF token if it doesn't exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

class Router {
    private $basePath;
    private $routes = [];
    private $companies = [];

    public function getAll($table) {
        global $conn;
        try {
            $stmt = $conn->prepare("SELECT * FROM $table");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            return [];
        }
    }

    public function __construct($basePath = '') {
        $this->basePath = $basePath;
    }

    public function addRoute($path, $handler) {
        $this->routes[$path] = $handler;
    }

    public function handleRequest() {

        $request = $_SERVER['REQUEST_URI'];
        $request = strtok($request, '?');
        $request = str_replace($this->basePath, '', $request);
        $request = '/' . ltrim($request, '/');

        // First check if it's a defined route
        if (isset($this->routes[$request])) {
            return $this->routes[$request]();
        }
        
        // Then check if it matches a company
        $companies = getAll('businesses');
        foreach ($companies as $company) {
            if ($request === '/' . $company['shortName']) {
                $_SESSION['Id'] = $company['business_id'];
                $_SESSION['Name'] = $company['name'];
                $type = geCompanyTypeById('business_types', $company['business_type_id']);
                require __DIR__ . '/src/company/'.$type[0]['name'].'/index.php';
                exit;
            } elseif ($request === '/' . $company['shortName'] . '/admin') {
                $_SESSION['Id'] = $company['business_id'];
                require __DIR__ . '/portal/compadmin/index.php';
                exit;
            }
        }

        //echo $request;
        
        return $this->handle404();
    }

    private function handle404() {
        http_response_code(404);
        require __DIR__ . '/src/404/404.php';
        return true;
    }
}

// Initialize router
$router = new Router('/site.cortxic.co.za');

// Define routes



$router->addRoute('/', function() {
    require __DIR__ . '/src/pages/blade.home.php';
    return true;
});

$router->addRoute('/admin', function() {
    require __DIR__ . '/portal/webadmin/index.php';
    return true;
});

$router->addRoute('/admin/company/view', function() {
    require __DIR__ . '/portal/webadmin/company/index.php';
    return true;
});
$router->addRoute('/admin/company/registration', function() {
    require __DIR__ . '/portal/webadmin/company/register.php';
    return true;
});
$router->addRoute('/admin/company/update', function() {
    require __DIR__ . '/portal/webadmin/company/update.php';
    return true;
});

$router->addRoute('/admin/user/view', function() {
    require __DIR__ . '/portal/webadmin/user/index.php';
    return true;
});
$router->addRoute('/admin/user/registration', function() {
    require __DIR__ . '/portal/webadmin/user/register.php';
    return true;
});
$router->addRoute('/admin/user/update', function() {
    require __DIR__ . '/portal/webadmin/user/update.php';
    return true;
});

$router->addRoute('/admin/order/view', function() {
    require __DIR__ . '/portal/webadmin/order/index.php';
    return true;
});

$router->addRoute('/client', function() {
    require __DIR__ . '/client/index.php';
    return true;
});

$router->addRoute('/login', function() {
    require __DIR__ . '/login.php';
    return true;
});

$router->addRoute('/register', function() {
    require __DIR__ . '/register.php';
    return true;
});

$router->addRoute('/register/business', function() {
    require __DIR__ . '/auth/register/register.php';
    return true;
});

$router->addRoute('/register/individual', function() {
    require __DIR__ . '/auth/register/individual.php';
    return true;
});



$router->addRoute('/auth/logout', function() {
    require __DIR__ .'/auth/logout/index.php';
    return;
});

// Add other routes similarly...

// Handle the request

// If no route was matched, display the custom 404 page

$router->handleRequest();
