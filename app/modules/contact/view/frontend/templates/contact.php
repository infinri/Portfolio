<?php
declare(strict_types=1);
/**
 * Contact Template
 *
 * Pure HTML template for contact page
 * Meta and assets loaded in index.php
 */

use App\Helpers\{Session, Esc};
use App\Base\Helpers\ReCaptcha;
?>

<!-- Contact Hero -->
<section class="page-hero contact-hero">
    <div class="container">
        <h1 class="page-title contact-title">Get In Touch</h1>
        <p class="page-subtitle contact-subtitle">
            Fast, friendly website help for small businesses. Whether you need a new site, a quick fix, or monthly support I'm here to help.
        </p>
        <p class="page-subtitle contact-subtitle">
            <strong>Not sure what you need?</strong> No problem. Just describe your situation in simple words and I'll guide you.
        </p>
    </div>
</section>

<!-- Contact Section -->
<section class="page-section contact-section">
    <div class="container">
        <div class="contact-wrapper">
            <!-- Contact Info -->
            <div class="contact-info">
                <h2 class="info-section-title">Contact Information</h2>
                <div class="info-card-container">
                    
                <div class="info-card">
                    <svg class="info-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="m2 7 10 6 10-6"/>
                    </svg>
                    <div class="info-content">
                        <h3 class="info-title">Email</h3>
                        <p class="info-text">lucio.saldivar@infinri.com</p>
                        <p class="info-subtitle">Best for detailed questions</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <svg class="info-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div class="info-content">
                        <h3 class="info-title">Response Time</h3>
                        <p class="info-text">Within 24 hours</p>
                        <p class="info-subtitle">Guaranteed reply</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <svg class="info-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="2" y1="12" x2="22" y2="12"/>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                    <div class="info-content">
                        <h3 class="info-title">Location</h3>
                        <p class="info-text">United States</p>
                        <p class="info-subtitle">Serving small businesses across the US</p>
                    </div>
                </div>
                
                <div class="info-card">
                    <svg class="info-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    <div class="info-content">
                        <h3 class="info-title">Availability</h3>
                        <p class="info-text">Taking new clients</p>
                        <p class="info-subtitle">This month</p>
                    </div>
                </div>
            </div>
            </div>
            
            <!-- Contact Form -->
            <div class="contact-form-wrapper">
                <h2 class="form-title">Send a Message</h2>
                <p class="form-description">
                    Tell me what you need even if you're not sure how to describe it. I'll ask clear follow-up questions and guide you through everything step-by-step.
                </p>
                <div class="common-requests">
                    <p><strong>Common things people contact me for:</strong></p>
                    <ul>
                        <li>"My site is broken"</li>
                        <li>"I need a quick update"</li>
                        <li>"I need a simple website"</li>
                        <li>"I want monthly help"</li>
                        <li>"My forms aren't working"</li>
                        <li>"I need hosting or setup"</li>
                        <li>"My last developer disappeared"</li>
                    </ul>
                    <p class="trust-line"><strong>I reply to every message personally no bots, no outsourcing.</strong></p>
                </div>
                
                <form method="POST" action="/contact" class="contact-form" id="contactForm">
                    <input type="hidden" name="csrf_token" value="<?php echo Esc::html($csrf ?? Session::csrf()); ?>">
                    <input type="hidden" name="recaptcha_token" id="recaptchaToken" data-sitekey="<?php echo Esc::html(ReCaptcha::getSiteKey()); ?>">
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Name *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-input"
                            required
                            placeholder="Your name"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email *</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-input"
                            required
                            placeholder="your.email@example.com"
                        >
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="business_name" class="form-label">Business Name</label>
                            <input 
                                type="text" 
                                id="business_name" 
                                name="business_name" 
                                class="form-input"
                                placeholder="Your company name"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="website_url" class="form-label">Current Website</label>
                            <input 
                                type="text" 
                                id="website_url" 
                                name="website_url" 
                                class="form-input"
                                placeholder="yoursite.com"
                            >
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_interest" class="form-label">I'm Interested In *</label>
                        <select 
                            id="service_interest" 
                            name="service_interest" 
                            class="form-input"
                            required
                        >
                            <option value="">-- Select a Service --</option>
                            <?php
                            $services = require __DIR__ . '/../../../../../../config/services.php';
                            foreach ($services as $value => $label): ?>
                                <option value="<?php echo Esc::html($value); ?>">
                                    <?php echo Esc::html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Phone *</label>
                        <input 
                            type="tel" 
                            id="phone" 
                            name="phone" 
                            class="form-input"
                            required
                            placeholder="(555) 123-4567"
                        >
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="subject" class="form-label">Subject *</label>
                            <input 
                                type="text" 
                                id="subject" 
                                name="subject" 
                                class="form-input"
                                required
                                placeholder="What's this about?"
                            >
                        </div>
                        
                        <div class="form-group">
                            <label for="urgency" class="form-label">Timeline</label>
                            <select 
                                id="urgency" 
                                name="urgency" 
                                class="form-input"
                            >
                                <option value="">-- Select Timeline --</option>
                                <option value="asap">ASAP / Urgent</option>
                                <option value="1_2_weeks">1-2 Weeks</option>
                                <option value="1_month">Within a Month</option>
                                <option value="flexible">Flexible / No Rush</option>
                                <option value="just_exploring">Just Exploring</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message *</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            class="form-textarea"
                            required
                            placeholder="Tell me about your project..."
                            rows="6"
                        ></textarea>
                    </div>
                    
                    <!-- Company verification field -->
                    <div class="company-info" aria-hidden="true">
                        <input type="text" name="company_url" id="comp_url_verify" value="" tabindex="-1" autocomplete="new-password" aria-hidden="true">
                    </div>
                    
                    <!-- Privacy & Consent -->
                    <div class="form-group form-consent">
                        <label class="consent-wrapper">
                            <input 
                                type="checkbox" 
                                id="privacy_consent" 
                                name="privacy_consent" 
                                class="consent-checkbox"
                                required
                            >
                            <span class="consent-text">
                                * I agree to the <a href="/privacy" target="_blank" rel="noopener">Privacy Policy</a> and consent to Infinri collecting and storing my information for the purpose of responding to this inquiry.
                            </span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg form-submit">
                        <span>Send Message</span>
                        <span class="btn-icon">→</span>
                    </button>
                    
                    <p class="form-note">
                        * Required fields
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- reCAPTCHA is lazy-loaded by contact-lazy.js on user interaction -->
