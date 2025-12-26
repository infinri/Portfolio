<?php
declare(strict_types=1);
/**
 * Home Module Controller
 *
 * Loads home page template and assets
 */

use App\Base\Helpers\{Meta, Assets};
use App\Helpers\Env;

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'Infinri | Affordable Websites for Small Businesses Starting at $10',
    'description' => 'Affordable website development starting at $10. Template sites in 24-48 hours, monthly support from $10/mo. No contracts, no tech jargon, transparent pricing.',
    'og:title' => 'Infinri | Affordable Websites for Small Businesses Starting at $10',
    'og:description' => 'Websites starting at $10. Template sites in 24-48 hours. Monthly support from $10/mo. No contracts, transparent pricing.',
    'twitter:title' => 'Infinri | Affordable Websites for Small Businesses Starting at $10'
]);

// Load home-specific assets (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/home/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/home.css")) {
        Assets::addCss("{$assetBase}/css/home.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/home.js")) {
        Assets::addJs("{$assetBase}/js/home.js");
    }
}

// Load template
require __DIR__ . '/view/frontend/templates/home.php';
