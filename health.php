<?php
// ==========================================
// فحص صحة الموقع - لـ Render
// ==========================================

header('Content-Type: application/json');

$status = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown'
];

echo json_encode($status, JSON_PRETTY_PRINT);
?>