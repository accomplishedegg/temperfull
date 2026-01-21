<?php
// backend/views/auth.php

function set_session_code(){
    $sessionCode = bin2hex(random_bytes(16));
    setcookie('session_code', $sessionCode, time() + 60 * 60 * 24 * 60, '/', null, true, true); 
    return $sessionCode;
}

function auth_login_by_password($sqlManager, $models, $data) {
    if (!isset($data->email) || !isset($data->password)) {
        return ['code' => 400, 'body' => ['message' => 'Email and password required.']];
    }

    $user = $models['User']->login($data->email, $data->password);
    if ($user && !isset($user['error'])) {
        // Success
        $_SESSION['user_id'] = $user['id'];
        
        // Create User Session record
        $device = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        // Create User Session record
        $device = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $sessionCode = set_session_code();
        $sessionId = $models['UserSession']->create($user['id'], $device, $ip, $sessionCode);

        // Log
        $models['UserLog']->log($user['id'], 'LOGIN_PASSWORD', ['ip' => $ip]);
        
        // Add session_id to user object for frontend usage
        $user['current_session_id'] = $sessionId;
        $_SESSION['db_session_id'] = $sessionId; // Persist for subsequent requests

        return ['code' => 200, 'body' => ['message' => 'Login successful', 'user' => $user]];
    }

    $msg = isset($user['error']) ? $user['error'] : 'Invalid email or password.';
    return ['code' => 401, 'body' => ['message' => $msg]];
}

function auth_login_by_otp($sqlManager, $models, $data) {
    if (!isset($data->email)) {
        return ['code' => 400, 'body' => ['message' => 'Email required.']];
    }

    // Check user exists
    $u = $sqlManager->getSingle('users', '*', ['email' => $data->email]);
    if ($u) {
        $otp = rand(100000, 999999);
        $expiry = date("Y-m-d H:i:s", strtotime("+10 minutes"));
        $models['UserOtp']->generate($u['id'], $otp, $expiry);
        
        // Log
        $models['UserLog']->log($u['id'], 'OTP_GENERATED');
        
        // Send Email
        EmailHelper::sendOtpEmail($u['email'], $otp);
        
        return ['code' => 200, 'body' => ['message' => 'OTP sent to your email.']];
    }

    // Return success even if user not found to prevent enumeration (security best practice), 
    // BUT for this specific task user asked "if email exists... return if user exists otp will be sent".
    // I will return user not found for now to match previous behavior or just generic message.
    // User request: "always return if user exists otp will be sent" - implies generic success.
    
    return ['code' => 200, 'body' => ['message' => 'If user exists, OTP will be sent.']];
}

function auth_verify_otp($sqlManager, $models, $data) {
    if (!isset($data->email) || !isset($data->otp)) {
        return ['code' => 400, 'body' => ['message' => 'Email and OTP required.']];
    }

    $u = $sqlManager->getSingle('users', '*', ['email' => $data->email]);
    if ($u) {
        if ($models['UserOtp']->verify($u['id'], $data->otp)) {
             // Success
             $_SESSION['user_id'] = $u['id'];

             // Create User Session
             $device = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
             $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
             // Create User Session
             $device = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
             $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
             // create custom cookie for 60 days with 32 random characters
             $sessionCode = set_session_code();

             $sessionId = $models['UserSession']->create($u['id'], $device, $ip, $sessionCode);

             // Log
             $models['UserLog']->log($u['id'], 'LOGIN_OTP', ['ip' => $ip]);
             
             // Add session_id to user object for frontend usage
             $u['current_session_id'] = $sessionId;
             $_SESSION['db_session_id'] = $sessionId; // Persist for subsequent requests

             return ['code' => 200, 'body' => ['message' => 'Login successful', 'user' => $u]];
        }
        return ['code' => 401, 'body' => ['message' => 'Invalid or expired OTP.']];
    }
    return ['code' => 404, 'body' => ['message' => 'User not found.']];
}

function auth_check_sessions($sqlManager, $models, $data) {
    if (!isset($_SESSION['user_id'])) {
        return ['code' => 401, 'body' => ['message' => 'Not authenticated.']];
    }
    
    $userId = $_SESSION['user_id'];
    $sessions = $models['UserSession']->getActiveSessions($userId);
    
    return ['code' => 200, 'body' => [
        'message' => 'Sessions retrieved.', 
        'count' => count($sessions),
        'sessions' => $sessions
    ]];
}

function auth_delete_session($sqlManager, $models, $data) {
    if (!isset($_SESSION['user_id'])) {
        return ['code' => 401, 'body' => ['message' => 'Not authenticated.']];
    }
    
    if (!isset($data->session_id)) {
        return ['code' => 400, 'body' => ['message' => 'Session ID required.']];
    }

    $userId = $_SESSION['user_id'];
    $sessionId = $data->session_id;

    // Verify session belongs to user
    $session = $sqlManager->getSingle('user_session', '*', ['id' => $sessionId, 'user_id' => $userId]);

    if ($session) {
        // "Delete" from DB (mark inactive?) or hard delete? User request said "delete".
        // Usually soft delete (is_active=0) is better for history but request said delete.
        // Let's hard delete to match "delete" verb strictness, or use SqlManager delete.
        $sqlManager->delete('user_session', ['id' => $sessionId]);

        // If same session then delete from php session also
        if (isset($_SESSION['db_session_id']) && $_SESSION['db_session_id'] == $sessionId) {
            session_unset();
            session_destroy();
            setcookie('session_code', '', time() - 3600, '/');
        }
        
        return ['code' => 200, 'body' => ['message' => 'Session deleted.']];
    }

    return ['code' => 404, 'body' => ['message' => 'Session not found or access denied.']];
}

?>
