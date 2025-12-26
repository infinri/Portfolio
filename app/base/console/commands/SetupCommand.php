<?php
declare(strict_types=1);
/**
 * Setup Command
 *
 * Handles project setup and update operations
 *
 * @package App\Console\Commands
 */

namespace App\Console\Commands;

final class SetupCommand
{
    private string $envFile;
    private array $envVars = [];

    public function __construct()
    {
        $this->envFile = __DIR__ . '/../../../../.env';
        $this->loadEnvFile();
    }

    public function execute(string $command, array $args): void
    {
        switch ($command) {
            case 'setup:update':
            case 's:up':
                $this->setupUpdate();
                break;
            default:
                echo "Unknown setup command: {$command}" . PHP_EOL;
                exit(1);
        }
    }

    private function loadEnvFile(): void
    {
        if (!file_exists($this->envFile)) {
            return;
        }
        
        $lines = file($this->envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $this->envVars[$key] = $value;
            }
        }
    }

    private function getEnv(string $key, string $default = ''): string
    {
        return $this->envVars[$key] ?? $default;
    }

    private function setupUpdate(): void
    {
        echo "🚀 Running project setup..." . PHP_EOL;
        echo str_repeat('=', 50) . PHP_EOL;

        // Step 1: Check environment
        $this->checkEnvironment();

        // Step 2: Determine environment mode
        $appEnv = $this->getEnv('APP_ENV', 'development');
        $isProduction = ($appEnv === 'production');
        $hasProductionAssets = $this->hasProductionAssets();

        // Step 3: Publish assets based on environment
        if ($isProduction && $hasProductionAssets) {
            echo "📦 Production environment - using minified bundles only" . PHP_EOL;
            echo "  ℹ️  Skipping source asset publishing (APP_ENV=production)" . PHP_EOL;
        } else {
            if ($isProduction && !$hasProductionAssets) {
                echo "⚠️  Production environment but no minified bundles found!" . PHP_EOL;
                echo "  → Publishing source assets as fallback" . PHP_EOL;
            } else {
                echo "📦 Development environment - publishing source assets..." . PHP_EOL;
                echo "  ℹ️  Source assets for easier debugging (APP_ENV={$appEnv})" . PHP_EOL;
            }
            $this->publishAssets();
        }

        // Step 4: Update APP_VERSION for cache busting
        $this->updateAppVersion();

        // Step 5: Fix permissions
        $this->fixPermissions();

        // Step 6: Clear caches (if any)
        $this->clearCaches();

        // Step 7: Generate sitemap and robots.txt
        $this->generateSitemap();

        // Step 8: Verify setup
        $this->verifySetup($isProduction && $hasProductionAssets);

        echo str_repeat('=', 50) . PHP_EOL;
        echo "✅ Project setup completed successfully!" . PHP_EOL;
        echo "" . PHP_EOL;
        
        if ($isProduction && $hasProductionAssets) {
            echo "🎯 Production mode - using minified bundles" . PHP_EOL;
        } else {
            echo "🔧 Development mode - using source assets" . PHP_EOL;
            if ($hasProductionAssets) {
                echo "  ℹ️  Minified bundles also available at pub/assets/dist/" . PHP_EOL;
            } else {
                echo "   To build production assets locally:" . PHP_EOL;
                echo "   → php bin/console setup:minify" . PHP_EOL;
            }
        }
        
        echo "" . PHP_EOL;
        echo "🌐 Visit http://localhost:8080 to view your portfolio" . PHP_EOL;
    }

    private function checkEnvironment(): void
    {
        echo "📋 Checking environment..." . PHP_EOL;

        // Check PHP version
        $phpVersion = PHP_VERSION;
        echo "  ✓ PHP version: {$phpVersion}" . PHP_EOL;

        if (version_compare($phpVersion, '8.1.0', '<')) {
            echo "  ❌ PHP 8.1+ required" . PHP_EOL;
            exit(1);
        }

        // Check .env file and show environment
        if (file_exists($this->envFile)) {
            $appEnv = $this->getEnv('APP_ENV', 'development');
            echo "  ✓ Environment file exists (APP_ENV={$appEnv})" . PHP_EOL;
        } else {
            echo "  ⚠️  .env file not found, using defaults (APP_ENV=development)" . PHP_EOL;
        }

        // Check pub directory permissions
        $pubDir = __DIR__ . '/../../../../pub';
        if (is_writable($pubDir)) {
            echo "  ✓ Public directory is writable" . PHP_EOL;
        } else {
            echo "  ❌ Public directory is not writable" . PHP_EOL;
            exit(1);
        }
    }

    private function publishAssets(): void
    {
        echo "📦 Publishing assets..." . PHP_EOL;
        
        $assetsCommand = new AssetsCommand();
        $assetsCommand->execute('assets:publish', []);
    }

    private function clearCaches(): void
    {
        echo "🧹 Clearing caches..." . PHP_EOL;
        
        // Clear OPcache (PHP bytecode cache)
        if (function_exists('opcache_reset')) {
            if (opcache_reset()) {
                echo "  ✓ OPcache cleared (PHP bytecode cache)" . PHP_EOL;
            } else {
                echo "  ⚠️  OPcache clear failed (may need sudo/restart)" . PHP_EOL;
            }
        } else {
            echo "  ℹ️  OPcache not enabled" . PHP_EOL;
        }
        
        // Clear APCu cache (if installed)
        if (function_exists('apcu_clear_cache')) {
            if (apcu_clear_cache()) {
                echo "  ✓ APCu cache cleared" . PHP_EOL;
            }
        }
        
        // Clear any cache directories if they exist
        $cacheDirectories = [
            __DIR__ . '/../../../../cache',
            __DIR__ . '/../../../../tmp',
        ];

        foreach ($cacheDirectories as $cacheDir) {
            if (is_dir($cacheDir)) {
                $this->clearDirectory($cacheDir);
                echo "  ✓ Cleared cache: " . basename($cacheDir) . PHP_EOL;
            }
        }

        echo "  ✓ Cache clearing completed" . PHP_EOL;
    }

    private function hasProductionAssets(): bool
    {
        $distDir = __DIR__ . '/../../../../pub/assets/dist';
        $requiredFiles = ['all.min.css', 'all.min.js'];
        
        if (!is_dir($distDir)) {
            return false;
        }
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($distDir . '/' . $file)) {
                return false;
            }
        }
        
        return true;
    }

    private function verifySetup(bool $hasProductionAssets): void
    {
        echo "🔍 Verifying setup..." . PHP_EOL;

        if ($hasProductionAssets) {
            // Verify production bundles
            $distDir = __DIR__ . '/../../../../pub/assets/dist';
            $requiredBundles = ['all.min.css', 'all.min.js'];
            
            foreach ($requiredBundles as $bundle) {
                $bundlePath = $distDir . '/' . $bundle;
                if (file_exists($bundlePath)) {
                    $size = filesize($bundlePath);
                    $formatted = $this->formatBytes($size);
                    echo "  ✓ Production bundle: {$bundle} ({$formatted})" . PHP_EOL;
                } else {
                    echo "  ❌ Missing bundle: {$bundle}" . PHP_EOL;
                }
            }
        } else {
            // Verify development source assets
            $assetsDir = __DIR__ . '/../../../../pub/assets';
            $requiredAssets = [
                'base/css/reset.css',
                'base/css/variables.css', 
                'base/css/base.css',
                'frontend/css/theme.css'
            ];

            foreach ($requiredAssets as $asset) {
                $assetPath = $assetsDir . '/' . $asset;
                if (file_exists($assetPath)) {
                    echo "  ✓ Asset exists: {$asset}" . PHP_EOL;
                } else {
                    echo "  ❌ Missing asset: {$asset}" . PHP_EOL;
                }
            }

            // Check if modules have assets
            $modulesDir = $assetsDir . '/modules';
            if (is_dir($modulesDir)) {
                $moduleCount = 0;
                $modules = array_filter(
                    scandir($modulesDir),
                    fn($item) => $item !== '.' && $item !== '..' && is_dir($modulesDir . '/' . $item)
                );
                
                foreach ($modules as $module) {
                    $moduleViewDir = $modulesDir . '/' . $module . '/view';
                    if (is_dir($moduleViewDir)) {
                        $moduleCount++;
                        // Check for specific module assets
                        if (file_exists($moduleViewDir . '/frontend/css/' . $module . '.css')) {
                            echo "  ✓ {$module} module CSS published" . PHP_EOL;
                        }
                    }
                }
                
                echo "  ✓ Module assets published: {$moduleCount} modules" . PHP_EOL;
            }
        }
    }

    private function updateAppVersion(): void
    {
        echo "🔄 Updating APP_VERSION..." . PHP_EOL;
        
        if (!file_exists($this->envFile)) {
            echo "  ⚠️  .env file not found - skipping version update" . PHP_EOL;
            return;
        }
        
        $newVersion = time();
        $content = file_get_contents($this->envFile);
        
        // Update or add APP_VERSION
        if (preg_match('/^APP_VERSION=.*/m', $content)) {
            $content = preg_replace('/^APP_VERSION=.*/m', 'APP_VERSION=' . $newVersion, $content);
            echo "  ✓ Updated APP_VERSION to {$newVersion}" . PHP_EOL;
        } else {
            // Add APP_VERSION if it doesn't exist
            $content .= "\nAPP_VERSION=" . $newVersion . "\n";
            echo "  ✓ Added APP_VERSION={$newVersion}" . PHP_EOL;
        }
        
        file_put_contents($this->envFile, $content);
        echo "  ✓ Cache busting enabled for all assets" . PHP_EOL;
    }

    private function fixPermissions(): void
    {
        echo "🔒 Fixing permissions..." . PHP_EOL;
        
        $permsCommand = new PermissionsCommand();
        $permsCommand->execute('setup:permissions', []);
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function clearDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $filePath = $dir . '/' . $file;
            if (is_dir($filePath)) {
                $this->clearDirectory($filePath);
                rmdir($filePath);
            } else {
                unlink($filePath);
            }
        }
    }

    private function generateSitemap(): void
    {
        echo "🗺️  Generating sitemap..." . PHP_EOL;

        $siteUrl = rtrim($this->getEnv('SITE_URL', 'https://www.infinri.com'), '/');
        $pubDir = __DIR__ . '/../../../../pub';
        $sitemapPath = $pubDir . '/sitemap.xml';
        $robotsPath = $pubDir . '/robots.txt';

        // Indexable pages (exclude legal, error pages)
        $pages = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => '/about', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => '/services', 'priority' => '0.9', 'changefreq' => 'monthly'],
            ['loc' => '/contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ];

        // Generate sitemap XML
        $lastmod = date('Y-m-d');
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        foreach ($pages as $page) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . $siteUrl . $page['loc'] . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>' . $page['changefreq'] . '</changefreq>' . PHP_EOL;
            $xml .= '    <priority>' . $page['priority'] . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }

        $xml .= '</urlset>' . PHP_EOL;

        // Remove existing sitemap if present
        if (file_exists($sitemapPath)) {
            unlink($sitemapPath);
            echo "  ✓ Removed existing sitemap.xml" . PHP_EOL;
        }

        // Write new sitemap
        file_put_contents($sitemapPath, $xml);
        echo "  ✓ Generated sitemap.xml (" . count($pages) . " URLs)" . PHP_EOL;

        // Generate robots.txt
        $robots = "# robots.txt for Infinri" . PHP_EOL;
        $robots .= "# Generated: " . date('Y-m-d H:i:s') . PHP_EOL;
        $robots .= PHP_EOL;
        $robots .= "User-agent: *" . PHP_EOL;
        $robots .= "Allow: /" . PHP_EOL;
        $robots .= PHP_EOL;
        $robots .= "# Disallow non-indexed pages" . PHP_EOL;
        $robots .= "Disallow: /terms" . PHP_EOL;
        $robots .= "Disallow: /privacy" . PHP_EOL;
        $robots .= "Disallow: /cookies" . PHP_EOL;
        $robots .= "Disallow: /disclaimer" . PHP_EOL;
        $robots .= "Disallow: /refund" . PHP_EOL;
        $robots .= PHP_EOL;
        $robots .= "# Sitemap location" . PHP_EOL;
        $robots .= "Sitemap: " . $siteUrl . "/sitemap.xml" . PHP_EOL;

        // Remove existing robots.txt if present
        if (file_exists($robotsPath)) {
            unlink($robotsPath);
            echo "  ✓ Removed existing robots.txt" . PHP_EOL;
        }

        // Write new robots.txt
        file_put_contents($robotsPath, $robots);
        echo "  ✓ Generated robots.txt" . PHP_EOL;
    }
}
