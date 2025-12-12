<?php
// api/log_access.php

require_once __DIR__ . '/../lib/AccessLogManager.php';

header('Content-Type: application/json');

// User UUID for PWA logic
if (!isset($_COOKIE['sensei_omoi_uuid'])) {
    $userUuid = bin2hex(random_bytes(16));
    setcookie('sensei_omoi_uuid', $userUuid, time() + 60 * 60 * 24 * 365, '/');
} else {
    $userUuid = $_COOKIE['sensei_omoi_uuid'];
}

$accessLogManager = new AccessLogManager();
$accessLogManager->logAccess($userUuid);
$accessCount = $accessLogManager->getAccessCount($userUuid);

$showPwaPrompt = false;
// Trigger on exactly 5th view
if ($accessCount === 5) {
    $showPwaPrompt = true;
}

echo json_encode(['showPwaPrompt' => $showPwaPrompt]);
