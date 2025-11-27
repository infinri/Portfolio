<?php
declare(strict_types=1);
/**
 * Contact API Handler
 *
 * Handles contact form POST requests
 */

use App\Base\Helpers\{Validator, Mail, Logger, RateLimiter, ReCaptcha, BrevoContacts};
use App\Helpers\Session;

// Set JSON response header
header('Content-Type: application/json');

try {
    // Get client IP
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // 1. Rate Limiting Check (5 attempts per 5 minutes)
    if (!RateLimiter::check($clientIp, 5, 300)) {
        $resetTime = RateLimiter::getResetTime($clientIp, 300);
        $minutes = ceil($resetTime / 60);
        
        Logger::warning('Rate limit exceeded', ['ip' => $clientIp]);
        http_response_code(429);
        echo json_encode([
            'success' => false, 
            'message' => "Too many attempts. Please try again in {$minutes} minute(s)."
        ]);
        exit;
    }
    
    // 2. Honeypot Check (anti-bot)
    $honeypot = $_POST['company_url'] ?? '';
    if (!empty($honeypot)) {
        Logger::warning('Bot detected via spam trap', [
            'ip' => $clientIp,
            'field_value' => substr($honeypot, 0, 50)
        ]);
        // Pretend success to confuse bots
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
        exit;
    }
    
    // 3. CSRF Token Verification
    $submittedToken = $_POST['csrf_token'] ?? '';
    
    if (!Session::verifyCsrf($submittedToken)) {
        Logger::warning('CSRF token verification failed', ['ip' => $clientIp]);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
        exit;
    }
    
    // 4. reCAPTCHA Verification (if enabled)
    if (ReCaptcha::isEnabled()) {
        $recaptchaToken = $_POST['recaptcha_token'] ?? '';
        
        if (!ReCaptcha::verify($recaptchaToken, 'contact_form')) {
            Logger::warning('reCAPTCHA verification failed', ['ip' => $clientIp]);
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Security verification failed. Please try again.']);
            exit;
        }
    }

    // Log form submission attempt
    Logger::info('Contact form submission started', ['ip' => $clientIp]);
    
    // 5. Input Validation
    $validator = new Validator($_POST);
    $validator->required(['name', 'email', 'service_interest', 'phone', 'subject', 'message', 'privacy_consent'])
              ->email('email')
              ->maxLength('name', 100)
              ->maxLength('email', 255)
              ->maxLength('phone', 20)
              ->maxLength('service_interest', 100)
              ->maxLength('subject', 200)
              ->maxLength('message', 2000)
              // Optional fields
              ->maxLength('business_name', 150)
              ->maxLength('website_url', 255)
              ->maxLength('urgency', 50);
    
    // Verify privacy consent is checked (value should be 'on' or '1')
    if (empty($_POST['privacy_consent']) || ($_POST['privacy_consent'] !== 'on' && $_POST['privacy_consent'] !== '1')) {
        Logger::warning('Privacy consent not accepted');
        echo json_encode(['success' => false, 'message' => 'You must agree to the Privacy Policy to submit this form.']);
        exit;
    }
    
    if ($validator->fails()) {
        Logger::warning('Contact form validation failed', [
            'errors' => $validator->errors()
        ]);
        echo json_encode(['success' => false, 'errors' => $validator->errors()]);
        exit;
    }
    
    Logger::info('Contact form validation passed');
    
    // 5. Get Validated & Sanitized Data
    $data = $validator->validated();
    
    // 6. Create contact in Brevo (triggers automation workflows)
    try {
        Logger::info('Creating Brevo contact', [
            'customer_name' => $data['name'],
            'customer_email' => $data['email']
        ]);
        
        // Add contact to Brevo database
        BrevoContacts::addContact($data);
        
        // Record this attempt for rate limiting
        RateLimiter::record($clientIp);
        
        Logger::info('Brevo contact created successfully');
        
        echo json_encode(['success' => true, 'message' => 'Message sent successfully!']);
        exit;
        
    } catch (Exception $e) {
        Logger::error('Brevo contact creation failed', [
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        echo json_encode(['success' => false, 'message' => 'Failed to send message. Please try again.']);
        exit;
    }
    
} catch (Throwable $e) {
    // Catch any PHP errors/exceptions
    Logger::error('Contact form fatal error', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    echo json_encode(['success' => false, 'message' => 'System error occurred. Please try again later.']);
    exit;
}
?>
