<?php
declare(strict_types=1);
/**
 * About Module Controller
 *
 * Loads about page template and assets
 */

use App\Base\Helpers\{Meta, Assets};
use App\Helpers\Env;

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'About | Affordable Web Developer for Small Businesses | Infinri',
    'description' => 'Affordable website development, hosting, and maintenance for small businesses. Websites from $10–$50, monthly support from $10/mo, fast delivery, transparent pricing.',
    'og:title' => 'About | Affordable Web Developer for Small Businesses | Infinri',
    'og:description' => 'Affordable website development for small businesses. Websites from $10, monthly support from $10/mo. Transparent pricing, fast delivery.',
    'twitter:title' => 'About | Affordable Web Developer for Small Businesses | Infinri'
]);

// Load about-specific assets (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/about/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/about.css")) {
        Assets::addCss("{$assetBase}/css/about.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/about.js")) {
        Assets::addJs("{$assetBase}/js/about.js");
    }
}

// Load template
require __DIR__ . '/view/frontend/templates/about.php';
