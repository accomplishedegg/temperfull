<?php
// backend/views/user.php

function handle_user_info($sqlManager, $models, $data) {
    if (!isset($_SESSION['user_id'])) return ['code' => 401, 'body' => ['message' => 'Not authenticated.']];
    
    $user = $models['User']->getById($_SESSION['user_id']);
    if (!$user) return ['code' => 404, 'body' => ['message' => 'User not found.']];
    
    unset($user['password']);
    
    if (isset($_SESSION['db_session_id'])) {
        $user['current_session_id'] = $_SESSION['db_session_id'];
    }

    return ['code' => 200, 'body' => $user];
}

function handle_user_subscriptions($sqlManager, $models, $data) {
    if (!isset($_SESSION['user_id'])) return ['code' => 401, 'body' => ['message' => 'Not authenticated.']];
    
    // Get active subscriptions
    $subs = $models['UserSubscription']->getActiveByUserId($_SESSION['user_id']);
    
    // Enrich with Plan Details
    foreach ($subs as &$sub) {
        $plan = $models['Subscription']->getById($sub['subscription_id']);
        if ($plan) {
            $sub['plan_name'] = $plan['name'];
            $sub['plan_days'] = $plan['number_of_days'];
        }
        unset($sub['id']);
        unset($sub['user_id']);
    }
    
    return ['code' => 200, 'body' => $subs];
}

function handle_user_search($sqlManager, $models, $data) {
    if (!isset($_SESSION['user_id'])) return ['code' => 401, 'body' => ['message' => 'Not authenticated.']];
    
    $user = $models['User']->getById($_SESSION['user_id']);
    if (!can_user_search_temper($user, $models)) {
        return ['code' => 403, 'body' => ['message' => 'Search limit exceeded or no active subscription.']];
    }
    
    $q = isset($data->q) ? $data->q : (isset($_GET['q']) ? $_GET['q'] : '');
    if (empty($q)) return ['code' => 400, 'body' => ['message' => 'Query required.']];
    
    // Search Supported Phones
    // We want to return {id: temper.uuid, name: supported_phone_name}
    $sql = "SELECT t.uuid as id, sp.name 
            FROM supported_phones sp 
            JOIN temper t ON sp.temper_id = t.id 
            WHERE sp.name LIKE :search 
            LIMIT 50";
    $results = $sqlManager->query($sql, ['search' => "%$q%"]);
    
    return ['code' => 200, 'body' => $results];
}

function handle_user_temperinfo($sqlManager, $models, $data) {
    if (!isset($_SESSION['user_id'])) return ['code' => 401, 'body' => ['message' => 'Not authenticated.']];

    $user = $models['User']->getById($_SESSION['user_id']);
    if (!can_user_search_temper($user, $models)) {
        return ['code' => 403, 'body' => ['message' => 'Search limit exceeded or no active subscription.']];
    }

    $id = isset($data->id) ? $data->id : (isset($_GET['id']) ? $_GET['id'] : '');
    if (empty($id)) return ['code' => 400, 'body' => ['message' => 'ID required.']];
    
    $temper = $models['Temper']->getByUuid($id);
    if (!$temper) return ['code' => 404, 'body' => ['message' => 'Temper not found.']];
    
    $phones = $models['SupportedPhone']->getByTemperId($temper['id']);
    $phoneNames = array_column($phones, 'name');
    
    $temper['supportedPhones'] = $phoneNames;

    // Log the search? (Optional requirement, but good practice given table exists)
    // Note: User table schema had number_of_searchs field. We might want to increment that?
    // And Log to UserLog? UserLog is for "actions".
    // user_log table exists. user_stocks exists.
    // Let's increment number_of_searchs in Users table if simple field.
    // Updating count:
    $sqlManager->execute("UPDATE users SET number_of_searchs = number_of_searchs + 1 WHERE id = :id", ['id' => $user['id']]);
    
    return ['code' => 200, 'body' => $temper];
}
?>
