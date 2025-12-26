<?php
declare(strict_types=1);
/**
 * Meta Helper
 *
 * Centralized meta tag management with SEO support
 *
 * @package App\Base\Helpers
 */

namespace App\Base\Helpers;

use App\Helpers\Esc;
use App\Helpers\Env;

final class Meta
{
    private static array $tags = [
        // Defaults (DRY - set once, use everywhere)
        'title' => 'Infinri | Affordable Web Development for Small Businesses',
        'description' => 'Website development, hosting, and maintenance starting at $10. From quick templates to monthly support plans, transparent pricing, fast delivery, no surprises.',
        'author' => 'Lucio Saldivar',
        'viewport' => 'width=device-width, initial-scale=1.0',
        'charset' => 'UTF-8',

        // Open Graph
        'og:title' => 'Infinri | Affordable Web Development for Small Businesses',
        'og:description' => 'Website development, hosting, and maintenance starting at $10. Transparent pricing, fast delivery.',
        'og:image' => '/assets/base/images/og-image.jpg',
        'og:type' => 'website',

        // Twitter
        'twitter:card' => 'summary_large_image',
        'twitter:title' => 'Infinri | Affordable Web Development',
        'twitter:description' => 'Websites starting at $10. Monthly support plans from $10/mo. Transparent pricing, no surprises.',
        'twitter:image' => '/assets/base/images/og-image.jpg',

        // Canonical (null = auto-generate from current URL)
        'canonical' => null,

        // Robots directive
        'robots' => 'index, follow',
    ];

    /**
     * Set a single meta tag
     *
     * @param string $key Tag key
     * @param string $value Tag value
     * @return void
     */
    public static function set(string $key, string $value): void
    {
        self::$tags[$key] = $value;
    }

    /**
     * Set multiple meta tags at once
     *
     * @param array $data Associative array of meta tags
     * @return void
     */
    public static function setMultiple(array $data): void
    {
        foreach ($data as $key => $value) {
            self::$tags[$key] = $value;
        }
    }

    /**
     * Get a meta tag value
     *
     * @param string $key Tag key
     * @return string
     */
    public static function get(string $key): string
    {
        return self::$tags[$key] ?? '';
    }

    /**
     * Render all meta tags
     *
     * @return string
     */
    public static function render(): string
    {
        $output = '';

        // Charset
        $output .= '<meta charset="' . Esc::html(self::$tags['charset']) . '">' . PHP_EOL;

        // Viewport
        $output .= '<meta name="viewport" content="' . Esc::html(self::$tags['viewport']) . '">' . PHP_EOL;

        // Title
        $output .= '<title>' . Esc::html(self::$tags['title']) . '</title>' . PHP_EOL;

        // Standard meta tags
        foreach (['description', 'author', 'robots'] as $name) {
            if (self::$tags[$name] !== '' && self::$tags[$name] !== null) {
                $output .= '<meta name="' . $name . '" content="' . Esc::html(self::$tags[$name]) . '">' . PHP_EOL;
            }
        }

        // Open Graph
        foreach (self::$tags as $key => $value) {
            if (strpos($key, 'og:') === 0 && $value !== '' && $value !== null) {
                $output .= '<meta property="' . $key . '" content="' . Esc::html($value) . '">' . PHP_EOL;
            }
        }

        // Twitter
        foreach (self::$tags as $key => $value) {
            if (strpos($key, 'twitter:') === 0 && $value !== '' && $value !== null) {
                $output .= '<meta name="' . $key . '" content="' . Esc::html($value) . '">' . PHP_EOL;
            }
        }

        // Canonical URL
        $canonical = self::getCanonicalUrl();
        if ($canonical !== '') {
            $output .= '<link rel="canonical" href="' . Esc::html($canonical) . '">' . PHP_EOL;
        }

        // Favicon
        $output .= '<link rel="icon" type="image/png" href="/assets/base/images/favicon.png">' . PHP_EOL;
        $output .= '<link rel="apple-touch-icon" href="/assets/base/images/favicon.png">' . PHP_EOL;

        return $output;
    }

    /**
     * Clear all meta tags (useful for testing)
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$tags = [
            'title' => 'Infinri | Affordable Web Development for Small Businesses',
            'description' => 'Website development, hosting, and maintenance starting at $10. From quick templates to monthly support plans transparent pricing, fast delivery, no surprises.',
            'author' => 'Lucio Saldivar',
            'viewport' => 'width=device-width, initial-scale=1.0',
            'charset' => 'UTF-8',
            'og:title' => '',
            'og:description' => '',
            'og:image' => '/images/default-og.jpg',
            'og:type' => 'website',
            'twitter:card' => 'summary_large_image',
            'twitter:title' => '',
            'twitter:description' => '',
            'twitter:image' => '',
            'canonical' => null,
            'robots' => 'index, follow',
        ];
    }

    /**
     * Get canonical URL (auto-generate if not manually set)
     *
     * @return string
     */
    private static function getCanonicalUrl(): string
    {
        // Use manually set canonical if provided
        if (self::$tags['canonical'] !== null && self::$tags['canonical'] !== '') {
            return self::$tags['canonical'];
        }

        // Auto-generate from SITE_URL + current path
        $siteUrl = rtrim(Env::get('SITE_URL', ''), '/');
        if ($siteUrl === '') {
            return '';
        }

        // Get current path, normalize it
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = strtok($path, '?'); // Remove query string
        $path = $path === '/' ? '/' : rtrim($path, '/'); // Normalize trailing slash (keep for root)

        return $siteUrl . $path;
    }

    /**
     * Render Organization structured data (JSON-LD)
     *
     * @return string
     */
    public static function renderStructuredData(): string
    {
        $siteUrl = rtrim(Env::get('SITE_URL', ''), '/');
        if ($siteUrl === '') {
            return '';
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'Infinri',
            'url' => $siteUrl,
            'logo' => $siteUrl . '/assets/base/images/logo.png',
            'image' => $siteUrl . '/assets/base/images/og-image.jpg',
            'description' => 'Affordable website development, hosting, and maintenance for small businesses. Websites from $10, monthly support from $10/mo. Transparent pricing, fast delivery, no contracts.',
            'slogan' => 'Affordable Web Development for Small Businesses',
            'priceRange' => '$',
            'areaServed' => 'United States',
            'founder' => [
                '@type' => 'Person',
                'name' => 'Lucio Saldivar',
                'jobTitle' => 'Web Developer',
                'image' => $siteUrl . '/assets/base/images/avatar.webp',
            ],
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'url' => $siteUrl . '/contact',
                'availableLanguage' => ['English', 'Spanish'],
            ],
            'sameAs' => [
                'https://github.com/infinri',
                'https://www.facebook.com/people/Infinri/61585373532873/',
            ],
            'knowsAbout' => [
                'Web Development',
                'Website Design',
                'Small Business Websites',
                'PHP Development',
                'Website Maintenance',
                'Website Hosting',
            ],
        ];

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        return '<script type="application/ld+json">' . PHP_EOL . $json . PHP_EOL . '</script>' . PHP_EOL;
    }
}
