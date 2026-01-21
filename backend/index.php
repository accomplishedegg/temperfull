<?php
// Handle CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header("X-Debug-Origin: " . ($origin ?: 'NOT-SET'));

if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    header("Access-Control-Allow-Origin: *");
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, Origin, Accept");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configure Session Lifetime (30 days)
ini_set('session.gc_maxlifetime', 2592000);
ini_set('session.cookie_lifetime', 2592000);

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug Logging
error_log("CORS API REQUEST: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . " FROM " . ($origin ?: 'NO-ORIGIN'));
$log_msg = "[" . date('Y-m-d H:i:s') . "] " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . " FROM " . ($origin ?: 'NO-ORIGIN') . "\n";
file_put_contents(__DIR__ . '/api.log', $log_msg, FILE_APPEND);

// Helpers
include_once 'helpers/UserHelper.php';
include_once 'helpers/EmailHelper.php';

// Config & Classes
include_once 'config/Database.php';
include_once 'config/SMTP.php';
include_once 'classes/SqlManager.php';



// Initialize DB & Manager
$database = new Database();
$db = $database->getConnection();
$sqlManager = new SqlManager($db);

// Initialize Models
include_once 'models/index.php';
// Router
include_once 'views/index.php';

// Get JSON input
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input);
file_put_contents(__DIR__ . '/api.log', "INPUT: " . ($raw_input ?: 'EMPTY') . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/api.log', "DECODED: " . ($data ? 'OK' : 'FAIL') . "\n", FILE_APPEND);

// Determine Path
// 1. Try $_GET['path'] (explicit param)
// 2. Try URL parsing (for clean URLs via .htaccess)
$path = isset($_GET['path']) ? $_GET['path'] : '';
if (empty($path)) {
    // Basic Request URI parsing fallback
    // e.g. /backend/auth/login -> /auth/login
    // This depends heavily on where the app is hosted.
    // For safety/predictability with the provided .htaccess, usually query string is safer or PATH_INFO.
    // Let's rely on the user passing ?path=... OR assuming the .htaccess RewriteRule ^(.*)$ index.php [QSA,L] 
    // passes the path implicitly? Actually .htaccess usually needs modification to pass path as param 
    // OR we parse REQUEST_URI.
    // rewrite: RewriteRule ^(.*)$ index.php [QSA,L] -> content comes in REQUEST_URI.
    
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    
    // Remove script directory prefix to get relative path
    if ($scriptName !== '/' && strpos($requestUri, $scriptName) === 0) {
        $path = substr($requestUri, strlen($scriptName));
    } else {
        $path = $requestUri; 
    }
}

// Ensure path starts with /
if (empty($path) || strpos($path, '/') !== 0) {
    $path = '/' . ($path ?? '');
}

// Log resolved path
file_put_contents(__DIR__ . '/api.log', "[$path] ROUTED\n", FILE_APPEND);

try {
    // Dispatch

    // check if session code is set
    if (isset($_COOKIE['session_code'])) {
        $sessionCode = $_COOKIE['session_code'];
        $userSession = $models['UserSession']->getBySessionCode($sessionCode);

        if ($userSession) {
            
            $_SESSION['user_id'] = $userSession[0]['user_id'];
            $_SESSION['db_session_id'] = $userSession[0]['id'];
        }else{
            // delete user_id from session
            unset($_SESSION['user_id']);
            unset($_SESSION['db_session_id']);
        }
    }

    $response = dispatch($path, $sqlManager, $models, $data);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/api.log', "EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
    $response = ['code' => 500, 'body' => ['message' => 'Internal Server Error', 'error' => $e->getMessage()]];
}

// Send Response
http_response_code($response['code']);
echo json_encode($response['body']);
exit();
?>
