<?php
// Include all model definitions
include_once __DIR__ . '/User.php';
include_once __DIR__ . '/UserOtp.php';
include_once __DIR__ . '/UserLog.php';
include_once __DIR__ . '/UserSession.php';
include_once __DIR__ . '/UserStock.php';
include_once __DIR__ . '/Temper.php';
include_once __DIR__ . '/Subscription.php';
include_once __DIR__ . '/UserSubscription.php';
include_once __DIR__ . '/SupportedPhone.php';
include_once __DIR__ . '/LeadForm.php';

// Initialize Models Bundle
// Assumes $sqlManager is available in the scope where this file is included
$models = [
    'User' => new User($sqlManager),
    'UserOtp' => new UserOtp($sqlManager),
    'UserLog' => new UserLog($sqlManager),
    'UserSession' => new UserSession($sqlManager),
    'Temper' => new Temper($sqlManager),
    'Subscription' => new Subscription($sqlManager),
    'UserSubscription' => new UserSubscription($sqlManager),
    'UserStock' => new UserStock($sqlManager),
    'SupportedPhone' => new SupportedPhone($sqlManager),
    'LeadForm' => new LeadForm($sqlManager),
];
?>
