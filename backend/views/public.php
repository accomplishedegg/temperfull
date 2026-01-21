<?php
// backend/views/public.php

function handle_public_plans($sqlManager, $models, $data) {
    // No Auth Check required
    
    // Get active subscription plans
    $plans = $models['Subscription']->getActive();
    
    return ['code' => 200, 'body' => $plans];
}
?>
