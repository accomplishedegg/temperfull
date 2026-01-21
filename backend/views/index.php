<?php
// backend/views/index.php

// Include View Files
include_once __DIR__ . '/admin.php';
include_once __DIR__ . '/temper.php';
include_once __DIR__ . '/user.php';
include_once __DIR__ . '/leads.php';
include_once __DIR__ . '/public.php';
include_once __DIR__ . '/auth.php';

// Route Dictionary
$routes = [
    '/auth/login_by_password' => 'auth_login_by_password',
    '/auth/login_by_otp'      => 'auth_login_by_otp',
    '/auth/verify_otp'        => 'auth_verify_otp',
    '/auth/check_sessions'    => 'auth_check_sessions',
    '/auth/delete_session'    => 'auth_delete_session',
    
    // Public Routes
    '/public/plans'           => 'handle_public_plans',
    
    // User Routes
    '/user/info'              => 'handle_user_info',
    '/user/manage_subscriptions'=> 'handle_user_subscriptions',
    '/user/search'            => 'handle_user_search',
    '/user/temperinfo'        => 'handle_user_temperinfo',
    
    // Admin CRUD
    '/admin/crud'             => 'handle_admin_crud',
    '/admin/temper'           => 'handle_temper_crud',
    '/admin/leads'            => 'handle_leads_crud',
];

function dispatch($path, $sqlManager, $models, $data) {
    global $routes;

    // Normalize path (remove base path if needed, here assuming path matches exactly or we strip prefix)
    // For simplicity, we'll assume the client sends the full relative path e.g. /auth/login_by_password
    // or we might need to parse $_SERVER['REQUEST_URI'].
    // Let's assume the router in index.php passes the specific action path.
    
    if (array_key_exists($path, $routes)) {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return ['code' => 200, 'body' => []];
        }
        $handler = $routes[$path];
        if (function_exists($handler)) {
            return call_user_func($handler, $sqlManager, $models, $data);
        } else {
            return ['code' => 500, 'body' => ['message' => "Handler function '$handler' not found."]];
        }
    }

    return ['code' => 404, 'body' => ['message' => "Route '$path' not found."]];
}
?>
