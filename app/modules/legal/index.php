<?php
declare(strict_types=1);
/**
 * Legal Module Controller
 * 
 * Handles all legal documentation pages:
 * /terms, /privacy, /cookies, /disclaimer, /refund
 */

use App\Base\Helpers\{Assets, Meta};
use App\Helpers\Env;

// Determine which legal document to show from URL
$requestUri = $_SERVER['REQUEST_URI'] ?? '/terms';
$path = parse_url($requestUri, PHP_URL_PATH);
$page = basename($path);

// Map routes to template files
$legalPages = [
    'terms' => 'Terms & Conditions',
    'privacy' => 'Privacy Policy',
    'cookies' => 'Cookie Policy',
    'disclaimer' => 'Disclaimer',
    'refund' => 'Refund & Cancellation Policy'
];

// Validate page exists
if (!array_key_exists($page, $legalPages)) {
    http_response_code(404);
    $page = 'terms'; // fallback
}

$pageTitle = $legalPages[$page];

// Set last updated dates per document
$lastUpdatedDates = [
    'terms' => 'November 21, 2025',
    'privacy' => 'November 21, 2025',
    'cookies' => 'November 21, 2025',
    'disclaimer' => 'November 21, 2025',
    'refund' => 'November 21, 2025'
];
$lastUpdated = $lastUpdatedDates[$page] ?? date('F j, Y');

// Legal pages should not be indexed (boilerplate content)
Meta::set('robots', 'noindex, follow');

// Load legal-specific assets (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/legal/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/legal.css")) {
        Assets::addCss("{$assetBase}/css/legal.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/legal.js")) {
        Assets::addJs("{$assetBase}/js/legal.js");
    }
}

// Load template
require __DIR__ . "/view/frontend/templates/{$page}.php";
