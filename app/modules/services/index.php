<?php
declare(strict_types=1);
/**
 * Services Module Controller
 *
 * Loads services page template and assets
 */

use App\Base\Helpers\{Meta, Assets};
use App\Helpers\Env;

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'Services | Affordable Web Development, Fixes, and Monthly Support | Infinri',
    'description' => 'Affordable website design, fixes, improvements, and monthly support for small businesses. Websites from $10, hosting from $15/mo, fast delivery, transparent pricing.',
    'og:title' => 'Services | Affordable Web Development for Small Businesses | Infinri',
    'og:description' => 'Websites from $10, monthly support from $10/mo, fixes from $20. Transparent pricing, fast delivery, no contracts.',
    'twitter:title' => 'Services | Affordable Web Development for Small Businesses | Infinri'
]);

// Load services-specific assets (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/services/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/services.css")) {
        Assets::addCss("{$assetBase}/css/services.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/services.js")) {
        Assets::addJs("{$assetBase}/js/services.js");
    }
}

// Load template
require __DIR__ . '/view/frontend/templates/services.php';
