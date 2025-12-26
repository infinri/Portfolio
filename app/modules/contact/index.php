<?php
declare(strict_types=1);
/**
 * Contact Module Controller
 *
 * Handles contact form display and submission
 */

use App\Base\Helpers\{Meta, Assets, ReCaptcha};
use App\Helpers\Session;

// Handle POST requests (form submission)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/api.php';
    exit; // Must exit to prevent Router from wrapping JSON in HTML layout
}

// Set page-specific meta tags
Meta::setMultiple([
    'title' => 'Contact | Affordable Website Help & Support | Infinri',
    'description' => 'Get affordable website design, fixes, and monthly support. Websites from $10. Hosting from $15/mo. Contact Infinri for fast, friendly help with your small business website.',
    'og:title' => 'Contact | Affordable Website Help for Small Businesses | Infinri',
    'og:description' => 'Get fast, friendly help with your small business website. Websites from $10, monthly support from $10/mo. I reply to every message personally.',
    'twitter:title' => 'Contact | Affordable Website Help for Small Businesses | Infinri'
]);

// Load contact-specific assets (development only - production uses bundles)
use App\Helpers\Env;

if (Env::get('APP_ENV', 'development') !== 'production') {
    $modulePath = __DIR__;
    $assetBase = '/assets/modules/contact/view/frontend';

    if (file_exists("{$modulePath}/view/frontend/css/contact.css")) {
        Assets::addCss("{$assetBase}/css/contact.css");
    }

    if (file_exists("{$modulePath}/view/frontend/js/contact.js")) {
        Assets::addJs("{$assetBase}/js/contact.js");
    }
}

// Add preconnect hints for reCAPTCHA (lazy-loaded on form interaction)
if (ReCaptcha::isEnabled() && !empty(ReCaptcha::getSiteKey())) {
    Assets::addPreconnect('https://www.google.com');
    Assets::addPreconnect('https://www.gstatic.com');
}

// Generate CSRF token for the form
$csrf = Session::csrf();

// Load template
require __DIR__ . '/view/frontend/templates/contact.php';
