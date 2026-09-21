<?php

// ===================================
// Application Constants
// ===================================
define('APP_NAME', 'AssetGuard');
define('APP_URL', 'http://localhost/AssetGuard');  // Update if using a different URL

// ===================================
// Brand Colors (used in CSS variables too — see assets/css/style.css)
// ===================================
define('COLOR_PRIMARY', '#B34700');     // Dark orange
define('COLOR_ACCENT', '#F2A65A');      // Light orange / gold

// ===================================
// Timezone
// ===================================
date_default_timezone_set('Asia/Riyadh');

// ===================================
// Error Reporting (Development mode — disable in production)
// ===================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===================================
// Session
// ===================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
