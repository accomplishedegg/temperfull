<?php
function can_user_search_temper($user, $models) {
    // If admin or sales manager, always true
    if (in_array($user['role'], ['admin', 'sales_manager'])) {
        return true;
    }

    // For user
    
    // 1. Check sessions (less than 3)
    // Note: getActiveSessions returns an array of session rows.
    $sessions = $models['UserSession']->getActiveSessions($user['id']);
    if (count($sessions) < 3) {
        return true;
    }

    // 2. Check valid subscription
    if ($models['UserSubscription']->hasValidSubscription($user['id'])) {
        return true;
    }

    return false;
}
?>
