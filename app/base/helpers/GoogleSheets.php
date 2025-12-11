<?php
declare(strict_types=1);
/**
 * Google Sheets Helper
 *
 * Appends contact form submissions to a Google Sheet via Apps Script webhook
 * No API credentials needed - just deploy the Apps Script as a web app
 *
 * @package App\Base\Helpers
 */

namespace App\Base\Helpers;

use App\Helpers\Env;

class GoogleSheets
{
    /**
     * Append a contact form submission to Google Sheets via Apps Script webhook
     *
     * Columns: Timestamp, Client Name, Email, Phone, Business Name, 
     *          Current Website, Interested In, Subject, Message, Lead Source
     *
     * @param array $contactData Contact information from form
     * @return bool True if successful, false otherwise
     */
    public static function appendContact(array $contactData): bool
    {
        if (!self::isEnabled()) {
            Logger::info('Google Sheets integration is disabled, skipping');
            return true;
        }

        $webhookUrl = Env::get('GOOGLE_SHEETS_WEBHOOK_URL');

        if (!$webhookUrl) {
            Logger::error('Google Sheets webhook URL not configured');
            return false;
        }

        try {
            Logger::info('Posting contact to Google Sheets webhook', [
                'email' => $contactData['email'] ?? 'N/A'
            ]);

            // Prepare payload matching Apps Script expected format
            $payload = self::preparePayload($contactData);

            // POST to Apps Script webhook
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => json_encode($payload),
                    'timeout' => 30,
                    'ignore_errors' => true
                ]
            ]);

            $response = file_get_contents($webhookUrl, false, $context);

            if ($response === false) {
                Logger::error('Google Sheets webhook request failed');
                return false;
            }

            $result = json_decode($response, true);

            if (isset($result['status']) && $result['status'] === 'success') {
                Logger::info('Google Sheets contact appended successfully', [
                    'email' => $contactData['email']
                ]);
                return true;
            }

            Logger::error('Google Sheets webhook returned error', [
                'response' => $response
            ]);
            return false;

        } catch (\Exception $e) {
            Logger::error('Google Sheets append failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * Prepare payload for Apps Script webhook
     *
     * @param array $data Form data
     * @return array Payload matching Apps Script expected keys
     */
    private static function preparePayload(array $data): array
    {
        // Get service interest label from config
        $serviceValue = $data['service_interest'] ?? '';
        $servicesConfigPath = __DIR__ . '/../../../config/services.php';
        $services = file_exists($servicesConfigPath) ? require $servicesConfigPath : [];
        $serviceLabel = $services[$serviceValue] ?? $serviceValue;

        return [
            'client_name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'business_name' => $data['business_name'] ?? '',
            'current_website' => $data['website_url'] ?? '',
            'interested_in' => $serviceLabel,
            'subject' => $data['subject'] ?? '',
            'message' => $data['message'] ?? '',
            'lead_source' => self::detectLeadSource()
        ];
    }

    /**
     * Detect lead source from referrer, UTM parameters, or session data
     *
     * @return string Lead source identifier
     */
    private static function detectLeadSource(): string
    {
        // 1. Check UTM source parameter (highest priority)
        $utmSource = $_GET['utm_source'] ?? $_SESSION['utm_source'] ?? null;
        if (!empty($utmSource)) {
            return self::normalizeSource($utmSource);
        }

        // 2. Check HTTP referrer
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        if (!empty($referrer)) {
            $source = self::parseReferrerSource($referrer);
            if ($source) {
                return $source;
            }
        }

        // 3. Default fallback
        return 'website_direct';
    }

    /**
     * Parse referrer URL to determine lead source
     *
     * @param string $referrer Full referrer URL
     * @return string|null Source name or null if same-site/unknown
     */
    private static function parseReferrerSource(string $referrer): ?string
    {
        $host = parse_url($referrer, PHP_URL_HOST);
        if (!$host) {
            return null;
        }

        $host = strtolower($host);

        // Ignore same-site referrers
        $ownDomain = $_SERVER['HTTP_HOST'] ?? '';
        if (str_contains($host, $ownDomain) || str_contains($ownDomain, $host)) {
            return null;
        }

        // Map known referrer domains to sources
        $sourceMap = [
            // Search engines
            'google' => 'google_organic',
            'bing' => 'bing_organic',
            'duckduckgo' => 'duckduckgo_organic',
            'yahoo' => 'yahoo_organic',
            // Social media
            'facebook' => 'facebook',
            'instagram' => 'instagram',
            'linkedin' => 'linkedin',
            'twitter' => 'twitter',
            'x.com' => 'twitter',
            'tiktok' => 'tiktok',
            'youtube' => 'youtube',
            'reddit' => 'reddit',
        ];

        foreach ($sourceMap as $domain => $source) {
            if (str_contains($host, $domain)) {
                return $source;
            }
        }

        // Unknown external referrer
        return 'referral_' . preg_replace('/[^a-z0-9]/', '_', $host);
    }

    /**
     * Normalize UTM source value
     *
     * @param string $source Raw UTM source
     * @return string Normalized source name
     */
    private static function normalizeSource(string $source): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($source)));
    }

    /**
     * Check if Google Sheets integration is enabled
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return Env::get('GOOGLE_SHEETS_ENABLED', 'true') === 'true';
    }
}
