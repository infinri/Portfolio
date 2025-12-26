<?php
declare(strict_types=1);
/**
 * Error Module Controller
 *
 * Handles all error pages: 400, 404, 500, maintenance
 * Usage: Set $errorType before including this file
 */

use App\Base\Helpers\{Meta, Assets};
use App\Helpers\Env;

// Determine error type (can be set by Router or default to 404)
$errorType = $errorType ?? $_GET['type'] ?? '404';

// Validate error type (security)
$allowedTypes = ['400', '404', '500', 'maintenance'];
if (!in_array($errorType, $allowedTypes, true)) {
    $errorType = '404';
}

// Set meta tags based on error type
$metaConfig = [
    '400' => [
        'title' => '400 - Bad Request',
        'description' => 'Your browser sent a request that this server could not understand'
    ],
    '404' => [
        'title' => '404 - Page Not Found',
        'description' => 'The page you are looking for could not be found'
    ],
    '500' => [
        'title' => '500 - Internal Server Error',
        'description' => 'Something went wrong on our end'
    ],
    'maintenance' => [
        'title' => 'Maintenance Mode',
        'description' => 'We are currently performing scheduled maintenance'
    ]
];

Meta::setMultiple($metaConfig[$errorType]);
Meta::set('robots', 'noindex, follow');

// Load error-specific assets (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/error/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/error.css")) {
        Assets::addCss("{$assetBase}/css/error.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/error.js")) {
        Assets::addJs("{$assetBase}/js/error.js");
    }
}

// Load appropriate template
$templatePath = __DIR__ . "/view/frontend/templates/{$errorType}.php";
if (file_exists($templatePath)) {
    require $templatePath;
} else {
    // Fallback to 404 if template doesn't exist
    require __DIR__ . '/view/frontend/templates/404.php';
}
