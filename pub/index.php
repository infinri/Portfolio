<?php
declare(strict_types=1);
/**
 * Front Controller
 *
 * Application entry point with security headers and routing
 *
 * @package App
 */

// Load application bootstrap
require __DIR__ . '/../app/autoload.php';

use App\Core\Router;
use App\Helpers\{Session, Env};
use App\Base\Helpers\Assets;

// Generate CSP nonce for inline styles and scripts (before any output)
$cspNonce = base64_encode(random_bytes(16));

// Build CSP header with nonce (tightened security - reCAPTCHA v3 works without unsafe-inline/unsafe-eval)
$cspHeader = "Content-Security-Policy: default-src 'self'; img-src 'self' data:; " .
    "style-src 'self' 'nonce-" . $cspNonce . "'; " .
    "script-src 'self' 'nonce-" . $cspNonce . "' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/; " .
    "frame-src https://www.google.com/recaptcha/ https://recaptcha.google.com/; " .
    "connect-src 'self' https://www.google.com/recaptcha/; " .
    "base-uri 'self'; frame-ancestors 'none'; form-action 'self'";

// Security Headers (must be before any output)
header($cspHeader);
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

// HSTS only in production with HTTPS
if (Env::get('HTTPS_ONLY', false, 'bool') && Env::get('APP_ENV', 'production') === 'production') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Enable gzip compression for faster page loads
if (!ob_start('ob_gzhandler')) {
    ob_start();
}

// Configure session settings (deferred initialization for performance)
$sessionPath = dirname(__DIR__) . '/var/sessions';
$sessionLifetime = (int)Env::get('SESSION_LIFETIME', '7200');
$sessionDomain = Env::get('SESSION_DOMAIN', '');

// Set session path (directory created during setup via console commands)
session_save_path($sessionPath);
ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100'); // 1% chance of GC

// In production, default to true. In development, check HTTPS_ONLY setting.
$isProduction = Env::get('APP_ENV', 'production') === 'production';
$secureCookie = $isProduction ? true : Env::get('HTTPS_ONLY', false, 'bool');

// Set PHP ini directives for session security (ensures proper cookie flags)
ini_set('session.cookie_secure', $secureCookie ? '1' : '0');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Strict');

session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path' => '/',
    'domain' => $sessionDomain,
    'secure' => $secureCookie,     // Secure flag (HTTPS only)
    'httponly' => true,             // Prevent JavaScript access (XSS protection)
    'samesite' => 'Strict'          // Maximum CSRF protection
]);

// Store nonce and config as globals for template access
$GLOBALS['cspNonce'] = $cspNonce;

// Load configuration
$config = require __DIR__ . '/../app/config.php';
$GLOBALS['config'] = $config;

// Generate CSRF token (will call session_start())
$csrfToken = Session::csrf();
$GLOBALS['csrf'] = $csrfToken;

// Register individual asset files (development only - production uses bundles)
if (Env::get('APP_ENV', 'development') !== 'production') {
    Assets::addCss('/assets/base/css/critical-hero.css', 'base');
    Assets::addCss('/assets/base/css/reset.css', 'base');
    Assets::addCss('/assets/base/css/variables.css', 'base'); // System variables (font sizes, shadows, etc.)
    Assets::addCss('/assets/base/css/theme.css', 'base'); // Customer theme (colors, fonts, radius)
    Assets::addCss('/assets/base/css/base.css', 'base');
    Assets::addCss('/assets/frontend/css/theme.css', 'frontend');
    Assets::addJs('/assets/base/js/base.js', 'base');
    Assets::addJs('/assets/frontend/js/theme.js', 'frontend');
}

// Define routes
$router = new Router();

$router->get('/', 'home')
       ->get('/about', 'about')
       ->get('/services', 'services')
       ->get('/contact', 'contact')
       ->post('/contact', 'contact')
       ->get('/terms', 'legal')
       ->get('/privacy', 'legal')
       ->get('/cookies', 'legal')
       ->get('/disclaimer', 'legal')
       ->get('/refund', 'legal');

// Dispatch
$router->dispatch('error');
