<?php
/**
 * Build Manual Pages — Smart Home Architects Clone
 * 
 * Creates 8 premium hand-crafted pages at slug "smart-home-3"
 * Run: php scripts/build-manual-pages.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Page;

$BASE = '/smart-home-3';
$IMG = 'https://smarthomearchitects.co.za/wp-content/uploads';

// ═══════════════════════════════════════════════════════════════
// SHARED HTML FRAGMENTS
// ═══════════════════════════════════════════════════════════════

function head(string $title, string $description): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title} — Smart Home Architects</title>
    <meta name="description" content="{$description}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/sha-styles.css">
</head>
HTML;
}

function navHtml(string $base, string $active = '', bool $transparent = false): string {
    $cls = $transparent ? 'nav nav--transparent-init' : 'nav';
    $links = [
        'smart-home' => 'Smart Home',
        'smart-lighting' => 'Smart Lighting',
        'cinema-rooms' => 'Cinema Rooms',
        'entertainment' => 'Entertainment',
        'automated-blinds' => 'Automated Blinds',
        'security' => 'Security',
    ];

    $linkHtml = '';
    foreach ($links as $slug => $label) {
        $activeClass = ($slug === $active) ? ' class="active"' : '';
        $linkHtml .= "<a href=\"{$base}/{$slug}\"{$activeClass}>{$label}</a>\n                ";
    }

    $mobileLinks = '';
    foreach ($links as $slug => $label) {
        $mobileLinks .= "<a href=\"{$base}/{$slug}\">{$label}</a>\n    ";
    }
    $mobileLinks .= "<a href=\"{$base}/contact\" style=\"color:var(--color-gold);\">Contact</a>";

    // Logo URLs
    $logoDark = '/storage/media/2026/03/14500eeb-a07b-44c7-a541-abdd068e37cd.png'; // dark logo for white bg
    $logoLight = 'https://smarthomearchitects.co.za/wp-content/compressx-nextgen/uploads/2025/04/smarthomearchitects_footer_logo.png.webp'; // light logo for dark bg

    return <<<HTML
<nav class="{$cls}" id="mainNav">
    <div class="nav__top-bar">
        <div class="nav__top-inner">
            <a href="tel:+27825157437" class="nav__phone">+27 82 515 7437</a>
            <!-- Brand logo (light, visible by default) -->
            <a href="{$base}" class="nav__brand-logo nav__brand-logo--light">
                <img src="{$logoLight}" alt="Smart Home Architects" class="nav__logo-img">
            </a>
            <!-- Brand logo (dark, visible in solid state) -->
            <a href="{$base}" class="nav__brand-logo nav__brand-logo--dark">
                <img src="{$logoDark}" alt="Smart Home Architects" class="nav__logo-img">
            </a>
            <a href="{$base}/contact" class="nav__contact-btn">Contact</a>
            <button class="nav__toggle" id="navToggle" aria-label="Toggle navigation">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
    <div class="nav__link-bar">
        <div class="nav__link-inner">
            {$linkHtml}
        </div>
    </div>
</nav>
<div class="nav__mobile" id="mobileNav">
    {$mobileLinks}
</div>
HTML;
}

function footerHtml(string $base): string {
    return <<<HTML
<footer class="footer">
    <div class="container">
        <div class="footer__upper">
            <div class="footer__brand">
                <a href="{$base}" class="nav__logo" style="margin-bottom:0.5rem;">
                    <img src="https://smarthomearchitects.co.za/wp-content/compressx-nextgen/uploads/2025/04/smarthomearchitects_footer_logo.png.webp" alt="Smart Home Architects" class="nav__logo-img" style="max-height:40px;">
                </a>
                <p>Founded in 2019, Smart Home Architects is a leading home automation company in Cape Town and George, creating seamless living experiences.</p>
                <div class="footer__social">
                    <a href="https://www.facebook.com/Smarthomearchitects.sa" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg></a>
                    <a href="https://www.instagram.com/smart_home_architects_sa/" aria-label="Instagram"><svg viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/></svg></a>
                </div>
            </div>
            <div>
                <h4 class="footer__heading">Services</h4>
                <div class="footer__links">
                    <a href="{$base}/smart-home">Smart Home</a>
                    <a href="{$base}/smart-lighting">Smart Lighting</a>
                    <a href="{$base}/cinema-rooms">Cinema Rooms</a>
                    <a href="{$base}/entertainment">Entertainment &amp; AV</a>
                    <a href="{$base}/automated-blinds">Automated Blinds</a>
                    <a href="{$base}/security">Smart Security</a>
                </div>
            </div>
            <div>
                <h4 class="footer__heading">Company</h4>
                <div class="footer__links">
                    <a href="{$base}">Home</a>
                    <a href="{$base}/contact">Contact</a>
                </div>
            </div>
            <div>
                <h4 class="footer__heading">Contact</h4>
                <div class="footer__contact">
                    <p>Cape Town &amp; George (Garden Route)</p>
                    <p>T: <a href="tel:+27825157437">+27 82 515 7437</a></p>
                    <p>E: <a href="mailto:info@smarthomearchitects.co.za">info@smarthomearchitects.co.za</a></p>
                </div>
            </div>
        </div>
        <div class="footer__lower">
            <span>&copy; Smart Home Architects 2025</span>
            <span>Luxury Smart Home Automation</span>
        </div>
    </div>
</footer>
HTML;
}

function closingScripts(): string {
    return <<<HTML
<script src="/sha-main.js"></script>
</body>
</html>
HTML;
}

function arrowSvg(): string {
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14m-7-7l7 7-7 7"/></svg>';
}

function starSvg(): string {
    return '<svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
}

function faqPlusSvg(): string {
    return '<svg class="faq-item__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
}

// ═══════════════════════════════════════════════════════════════
// PAGE 1: HOMEPAGE
// ═══════════════════════════════════════════════════════════════

function buildHomepage(string $base, string $img): string {
    $arrow = arrowSvg();
    $stars = str_repeat(starSvg(), 5);
    return head('Luxury Smart Home Automation', 'Smart Home Architects redefines modern living through bespoke smart home solutions in Cape Town and the Garden Route.')
    . "\n<body>\n"
    . navHtml($base, '', true)
    . <<<HTML

<section class="hero">
    <div class="hero__bg" style="background-image:url('{$img}/2025/11/smart-home-remote-control.png');"></div>
    <div class="hero__overlay"></div>
    <div class="hero__content">
        <div class="container">
            <div class="hero__inner">
                <div class="hero__tagline">Cape Town &amp; Garden Route</div>
                <h1 class="hero__title">Luxury Smart Home<br><em>Automation</em></h1>
                <p class="hero__desc">We redefine modern living through bespoke smart home solutions that seamlessly integrate lighting, audio, security, and automation — creating intuitive spaces that reflect uncompromising luxury.</p>
                <div class="hero__actions">
                    <a href="{$base}/contact" class="btn btn--primary">Book a Consultation</a>
                    <a href="#services" class="btn btn--outline">Explore Services</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="features-ribbon">
    <div class="container">
        <div class="features-ribbon__grid">
            <div class="features-ribbon__item reveal">
                <div class="features-ribbon__icon"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m6-1v18m0 0h4a2 2 0 002-2V5a2 2 0 00-2-2h-4"/><circle cx="9" cy="9" r="2"/><path d="M9 15v2"/></svg></div>
                <div class="features-ribbon__title">Smart Lighting</div>
                <div class="features-ribbon__desc">Scenes that adapt to your lifestyle</div>
            </div>
            <div class="features-ribbon__item reveal reveal-delay-1">
                <div class="features-ribbon__icon"><svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div>
                <div class="features-ribbon__title">Multi-Room Audio</div>
                <div class="features-ribbon__desc">Immersive sound throughout your home</div>
            </div>
            <div class="features-ribbon__item reveal reveal-delay-2">
                <div class="features-ribbon__icon"><svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="12" rx="2"/><path d="M8 20h8m-4-4v4"/></svg></div>
                <div class="features-ribbon__title">Automated Blinds</div>
                <div class="features-ribbon__desc">Elegant shading at your fingertips</div>
            </div>
            <div class="features-ribbon__item reveal reveal-delay-3">
                <div class="features-ribbon__icon"><svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/><circle cx="12" cy="16" r="1"/></svg></div>
                <div class="features-ribbon__title">Smart Security</div>
                <div class="features-ribbon__desc">Peace of mind, always connected</div>
            </div>
        </div>
    </div>
</section>

<section class="services" id="services">
    <div class="container">
        <div class="services__header reveal">
            <div class="section-label section-label--center">What We Do</div>
            <h2 class="section-title">Our Services</h2>
            <p class="section-subtitle" style="margin:0 auto;">From concept to completion, we design and install luxury smart home systems tailored to your lifestyle, architecture, and vision.</p>
        </div>
        <div class="service-card reveal">
            <div class="service-card__image"><img src="{$img}/2025/11/smart-home-remote-control.png" alt="Complete smart home automation control" loading="lazy"></div>
            <div class="service-card__content">
                <div class="service-card__number">01</div>
                <h3 class="service-card__title">The Ultimate Smart Home Experience</h3>
                <p class="service-card__desc">Discover a fully integrated home where lighting, climate, security, and entertainment are controlled from a single interface. Our complete smart home experience is designed to simplify life and enhance your well-being.</p>
                <a href="{$base}/smart-home" class="btn-arrow">Discover Smart Living {$arrow}</a>
            </div>
        </div>
        <div class="service-card reveal">
            <div class="service-card__image"><img src="{$img}/2025/11/bespoke-cinema-rooms.jpg" alt="Bespoke cinema room" loading="lazy"></div>
            <div class="service-card__content">
                <div class="service-card__number">02</div>
                <h3 class="service-card__title">Bespoke Cinema Rooms</h3>
                <p class="service-card__desc">Immerse yourself in a private cinema designed for comfort and performance. From acoustic panelling and tailored seating to projection and surround sound, we deliver a luxury viewing experience unlike any other.</p>
                <a href="{$base}/cinema-rooms" class="btn-arrow">Explore Home Cinemas {$arrow}</a>
            </div>
        </div>
        <div class="service-card reveal">
            <div class="service-card__image"><img src="{$img}/2025/11/smart-home-lighting.png" alt="Smart lighting automation" loading="lazy"></div>
            <div class="service-card__content">
                <div class="service-card__number">03</div>
                <h3 class="service-card__title">End-to-End Smart Lighting</h3>
                <p class="service-card__desc">Great lighting is never static — it adapts to your lifestyle, creating the perfect atmosphere for any occasion, in any room. Our smart lighting systems use elegant controls that work in harmony throughout your home.</p>
                <a href="{$base}/smart-lighting" class="btn-arrow">Discover Smart Lighting {$arrow}</a>
            </div>
        </div>
        <div class="service-card reveal">
            <div class="service-card__image"><img src="{$img}/2025/11/video-audio-distribution.jpg" alt="Audio and video distribution" loading="lazy"></div>
            <div class="service-card__content">
                <div class="service-card__number">04</div>
                <h3 class="service-card__title">Entertainment &amp; Audio Visual</h3>
                <p class="service-card__desc">Fill your home with immersive audio and video distribution. Stream music from your favourite services, enjoy discreet in-ceiling or outdoor speakers, and distribute video across every screen — all controlled from a single system.</p>
                <a href="{$base}/entertainment" class="btn-arrow">Explore Entertainment {$arrow}</a>
            </div>
        </div>
        <div class="service-card reveal">
            <div class="service-card__image"><img src="{$img}/2025/11/automated-blinds.jpeg" alt="Automated motorised blinds" loading="lazy"></div>
            <div class="service-card__content">
                <div class="service-card__number">05</div>
                <h3 class="service-card__title">Simply Modern, Beautifully Automated</h3>
                <p class="service-card__desc">Our shading systems combine sleek design with intelligent control. With premium fabrics that regulate light and temperature, our motorised blinds blend seamlessly into your home's architecture.</p>
                <a href="{$base}/automated-blinds" class="btn-arrow">Discover Automated Blinds {$arrow}</a>
            </div>
        </div>
        <div class="service-card reveal">
            <div class="service-card__image"><img src="{$img}/2025/10/home-security-solutions.png" alt="Smart security systems" loading="lazy"></div>
            <div class="service-card__content">
                <div class="service-card__number">06</div>
                <h3 class="service-card__title">A New Standard in Home Security</h3>
                <p class="service-card__desc">Peace of mind is priceless. Our smart home security solutions protect what matters most, whether you're at home or away. From entry systems to integrated monitoring, we deliver reliable, always-connected protection.</p>
                <a href="{$base}/security" class="btn-arrow">Explore Smart Security {$arrow}</a>
            </div>
        </div>
    </div>
</section>

<section class="showcase reveal">
    <div class="showcase__inner">
        <div class="showcase__image"><img src="{$img}/2025/11/smart-home-lighting.png" alt="Smart home living room" loading="lazy"></div>
        <div class="showcase__content">
            <div class="container">
                <div class="section-label">Your Smart Home Starts Here</div>
                <h2 class="section-title" style="color:var(--color-white);">From Design to Installation</h2>
                <p class="section-subtitle">We create intuitive smart home systems tailored to you. Get in touch for a consultation to bring your vision to life.</p>
                <br>
                <a href="{$base}/contact" class="btn btn--primary">Enquire Now</a>
            </div>
        </div>
    </div>
</section>

<section class="partners reveal">
    <div class="container">
        <div class="partners__label">In Partnership With Industry Leaders</div>
        <div class="partners__grid">
            <img src="{$img}/2025/10/Savant-logo-1.jpg" alt="Savant" loading="lazy">
            <img src="{$img}/2025/10/Lutron-logo.jpg" alt="Lutron" loading="lazy">
            <img src="{$img}/2025/10/Sonos_Logo.png" alt="Sonos" loading="lazy">
            <img src="{$img}/2025/10/Monitor-Audio-logo-1.jpg" alt="Monitor Audio" loading="lazy">
            <img src="{$img}/2025/10/JVC-logo.jpg" alt="JVC" loading="lazy">
            <img src="{$img}/2025/10/Trinnov-Audio-logo.jpg" alt="Trinnov Audio" loading="lazy">
            <img src="{$img}/2025/10/Sonance-logo.jpg" alt="Sonance" loading="lazy">
            <img src="{$img}/2025/10/Faradite-logo-1.jpg" alt="Faradite" loading="lazy">
            <img src="{$img}/2025/10/HDA-logo.jpg" alt="HDA" loading="lazy">
        </div>
    </div>
</section>

<section class="why-choose" id="why-us">
    <div class="container">
        <div class="why-choose__header reveal">
            <div class="section-label section-label--center">Why Us</div>
            <h2 class="section-title">Why Choose Smart Home Architects?</h2>
            <p class="section-subtitle" style="margin:0 auto;">As a leading smart home automation company in Cape Town and the Garden Route, we combine technical expertise with design excellence.</p>
        </div>
        <div class="why-choose__grid">
            <div class="why-choose__card reveal">
                <div class="why-choose__icon"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
                <h3>Bespoke Design &amp; Installation</h3>
                <p>Every home is unique; our solutions are custom-designed to suit your lifestyle, architecture, and vision.</p>
            </div>
            <div class="why-choose__card reveal reveal-delay-1">
                <div class="why-choose__icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg></div>
                <h3>Seamless Integration</h3>
                <p>We connect lighting, audio, security, and entertainment into one intuitive system that works beautifully together.</p>
            </div>
            <div class="why-choose__card reveal reveal-delay-2">
                <div class="why-choose__icon"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <h3>Luxury Without Compromise</h3>
                <p>Our focus is on elegance and simplicity, delivering smart home automation that enhances comfort without complexity.</p>
            </div>
        </div>
    </div>
</section>

<section class="testimonials" id="testimonials">
    <div class="container">
        <div class="testimonials__header reveal">
            <div class="section-label section-label--center">Testimonials</div>
            <h2 class="section-title">What Our Clients Say</h2>
        </div>
        <div class="testimonials__grid">
            <div class="testimonial-card reveal">
                <div class="testimonial-card__stars">{$stars}</div>
                <p class="testimonial-card__text">"Greg and his team have done an amazing job at our residence with all of our home automation requirements, and much more besides! Thoroughly recommended."</p>
                <div class="testimonial-card__author">
                    <div class="testimonial-card__avatar">G</div>
                    <div class="testimonial-card__name">Gavin M</div>
                </div>
            </div>
            <div class="testimonial-card reveal reveal-delay-1">
                <div class="testimonial-card__stars">{$stars}</div>
                <p class="testimonial-card__text">"I am very happy with Greg's and the Smart Home Architects work. They helped me with home automation, IT infrastructure, renovation, lighting, home cinema and battery back up."</p>
                <div class="testimonial-card__author">
                    <div class="testimonial-card__avatar">A</div>
                    <div class="testimonial-card__name">Andreas Bach</div>
                </div>
            </div>
            <div class="testimonial-card reveal reveal-delay-2">
                <div class="testimonial-card__stars">{$stars}</div>
                <p class="testimonial-card__text">"Greg and his team are extremely professional, tidy and efficient. We are very happy with work done on our project. Definitely European quality!"</p>
                <div class="testimonial-card__author">
                    <div class="testimonial-card__avatar">Z</div>
                    <div class="testimonial-card__name">Zac Wrankmore</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-banner reveal" id="contact">
    <div class="container">
        <div class="cta-banner__content">
            <div class="section-label section-label--center">Get Started</div>
            <h2 class="section-title">Ready to Transform Your Home?</h2>
            <p class="section-subtitle">From design to installation, we create intuitive smart home systems tailored to you. Get in touch for a consultation.</p>
            <a href="{$base}/contact" class="btn btn--primary">Book a Consultation</a>
        </div>
    </div>
</section>

HTML
    . footerHtml($base)
    . closingScripts();
}


// ═══════════════════════════════════════════════════════════════
// PAGE 2: SMART HOME (service page)
// ═══════════════════════════════════════════════════════════════

function buildSmartHome(string $base, string $img): string {
    $arrow = arrowSvg();
    return head('Home Automation & Smart Home Systems', 'Intelligent control of lighting, climate, entertainment, and security — all from one app.')
    . "\n<body>\n"
    . navHtml($base, 'smart-home')
    . <<<HTML

<section class="inner-hero">
    <div class="inner-hero__bg" style="background-image:url('{$img}/2025/11/smart-home-remote-control.png');"></div>
    <div class="inner-hero__overlay"></div>
    <div class="inner-hero__content">
        <div class="container">
            <div class="inner-hero__breadcrumb"><a href="{$base}">Home</a> / Smart Home</div>
            <h1 class="inner-hero__title">Home Automation &amp;<br>Smart Home Systems</h1>
            <p class="inner-hero__subtitle">Intelligent control of lighting, climate, entertainment, and security — all from one app. Live Life Brilliantly.</p>
        </div>
    </div>
</section>

<section class="content-section content-section--cream">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Savant-Powered</div>
            <h2 class="section-title">Smart Home Services</h2>
            <p class="section-subtitle" style="margin:0 auto;">Smart Home Architects creates and installs bespoke home automation systems powered by Savant. Explore our complete range of services.</p>
        </div>
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/smart-home-remote-control.png" alt="Savant smart home control" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Savant Pro</div>
                <h3>Intelligent Living with Savant</h3>
                <p>Savant puts complete control of your home at your fingertips. Manage lighting, climate, security, and entertainment, all from one smart interface that enhances comfort, mood, and well-being every day.</p>
                <a href="{$base}/contact" class="btn-arrow">Learn More {$arrow}</a>
            </div>
        </div>
        <div class="alt-row alt-row--reverse reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/smart-home-lighting.png" alt="Smart lighting scenes" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Smart Scenes</div>
                <h3>The Perfect Atmosphere, One Tap Away</h3>
                <p>Create the ideal atmosphere with a single tap. Dim the lights, close the shades, light the fireplace, or start your favourite playlist. Savant's smart automation systems turn routine moments into effortless experiences.</p>
                <a href="{$base}/smart-lighting" class="btn-arrow">Explore Lighting {$arrow}</a>
            </div>
        </div>
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/video-audio-distribution.jpg" alt="Video and audio distribution" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Audio &amp; Video</div>
                <h3>Immersive Entertainment Throughout</h3>
                <p>Enjoy high-definition video from any source to any display and the music you love in every room with Savant's whole-home audio system. Stream your favourite playlists, create zones for any mood, and experience truly immersive sound.</p>
                <a href="{$base}/entertainment" class="btn-arrow">Explore Entertainment {$arrow}</a>
            </div>
        </div>
    </div>
</section>

<section class="content-section content-section--dark">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Complete Control</div>
            <h2 class="section-title" style="color:var(--color-white);">Security, Shades &amp; Peace of Mind</h2>
        </div>
        <div class="feature-grid">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></div>
                <h3>Smart Security</h3>
                <p>View cameras, control alarms, and lock doors from one interface. Instant feedback keeps you connected and in control, wherever you are.</p>
            </div>
            <div class="feature-card reveal reveal-delay-1">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="12" rx="2"/><path d="M8 20h8m-4-4v4"/></svg></div>
                <h3>Savant Shades</h3>
                <p>Motorised shading systems that combine minimalist style with seamless functionality, concealing hardware for a clean architectural finish.</p>
            </div>
            <div class="feature-card reveal reveal-delay-2">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><path d="M2 12h6a2 2 0 012 2v5a2 2 0 01-2 2H4a2 2 0 01-2-2v-7z"/><path d="M14 12h6a2 2 0 012 2v5a2 2 0 01-2 2h-4a2 2 0 01-2-2v-7z"/><path d="M8 5v3M16 5v3M12 2v6"/></svg></div>
                <h3>Pool &amp; Spa</h3>
                <p>Control your pool and spa from any device. Adjust temperature, lighting, jets, and fountains to create the perfect atmosphere.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-banner reveal">
    <div class="container">
        <div class="cta-banner__content">
            <div class="section-label section-label--center">Get Started</div>
            <h2 class="section-title">Ready to Design Your Smart Home?</h2>
            <p class="section-subtitle">Start your smart home journey today. Talk to our team about bringing Savant into your space.</p>
            <a href="{$base}/contact" class="btn btn--primary">Enquire Now</a>
        </div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="faq-section__header reveal">
            <div class="section-label section-label--center">FAQ</div>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="faq-list reveal">
            <div class="faq-item">
                <button class="faq-item__question">Can smart home systems be added to an existing home? FAQICON</button>
                <div class="faq-item__answer"><p>Yes. Smart home automation can be installed in both new builds and existing homes. Systems can be designed to work with current wiring where possible, or upgraded gradually as part of a renovation or phased rollout.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Will my smart home still work if the internet goes down? FAQICON</button>
                <div class="faq-item__answer"><p>Most core functions like lighting, climate control, and local audio continue to work via the local network. Cloud-dependent features like remote access and streaming services may be temporarily unavailable.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Is smart home automation complicated to use? FAQICON</button>
                <div class="faq-item__answer"><p>Not at all. Savant's interface is designed to be intuitive enough for every family member to use. One-tap scenes, voice control, and the Pro App make daily operation simple and elegant.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">What smart home brands do you work with? FAQICON</button>
                <div class="faq-item__answer"><p>We specialise in Savant as our primary smart home platform, complemented by industry leaders like Lutron, Sonos, Monitor Audio, and JVC for specific applications.</p></div>
            </div>
        </div>
    </div>
</section>

HTML
    . footerHtml($base)
    . closingScripts();
}


// ═══════════════════════════════════════════════════════════════
// PAGE 3: SMART LIGHTING
// ═══════════════════════════════════════════════════════════════

function buildSmartLighting(string $base, string $img): string {
    $arrow = arrowSvg();
    return head('Smart Lighting Automation & Control', 'End-to-end smart home lighting control for any mood. LED strips, smart bulbs, luxury keypads, and full colour control.')
    . "\n<body>\n"
    . navHtml($base, 'smart-lighting')
    . <<<HTML

<section class="inner-hero">
    <div class="inner-hero__bg" style="background-image:url('{$img}/2025/11/smart-home-lighting.png');"></div>
    <div class="inner-hero__overlay"></div>
    <div class="inner-hero__content">
        <div class="container">
            <div class="inner-hero__breadcrumb"><a href="{$base}">Home</a> / Smart Lighting</div>
            <h1 class="inner-hero__title">Smart Home Lighting<br>Automation &amp; Control</h1>
            <p class="inner-hero__subtitle">Smart. Elegant. Energy-Efficient. End-to-end home light control for any mood.</p>
        </div>
    </div>
</section>

<section class="content-section content-section--cream">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Lighting Solutions</div>
            <h2 class="section-title">Home Automation Lighting Control</h2>
            <p class="section-subtitle" style="margin:0 auto;">Discover bespoke Savant-powered lighting with LED strips, smart bulbs, and luxury keypads.</p>
        </div>
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/smart-home-lighting.png" alt="Smart LED lighting" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">LED Lighting</div>
                <h3>Go a Little Wild with Light Tonight</h3>
                <p>LED strips add personality to any space, from subtle elegance in bookshelves or along stairs, to dramatic accents across feature walls. Our smart LED lighting lets you create ambience for every mood, all controlled from your Savant app.</p>
            </div>
        </div>
        <div class="alt-row alt-row--reverse reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/smart-home-remote-control.png" alt="Smart bulbs colour control" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Smart Bulbs</div>
                <h3>Available in a Million Colours</h3>
                <p>Energy-efficient and ideal for retrofits, smart bulbs offer flexible installation and endless possibilities. Choose from millions of colours to personalise every room and create lighting that perfectly suits your style.</p>
            </div>
        </div>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Savant Pro App</div>
            <h2 class="section-title">Full Lighting Control at Your Fingertips</h2>
            <p class="section-subtitle" style="margin:0 auto;">Savant's Pro App offers luxury home lighting automation in one place.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 2a14 14 0 014 20M12 2a14 14 0 00-4 20"/></svg></div>
                <h3>Full Colour Control</h3>
                <p>An intuitive interface for complete colour control. Set the perfect mood or have fun experimenting with endless possibilities.</p>
            </div>
            <div class="feature-card reveal reveal-delay-1">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg></div>
                <h3>Daylight Mode</h3>
                <p>Intelligent colour temperature control to support natural rhythms, from cool light for wakefulness to warm light for relaxation.</p>
            </div>
            <div class="feature-card reveal reveal-delay-2">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><path d="M12 3a6 6 0 00-6 6c0 3.3 6 11 6 11s6-7.7 6-11a6 6 0 00-6-6z"/><circle cx="12" cy="9" r="2"/></svg></div>
                <h3>Savant Scenes</h3>
                <p>Add colour to any Scene to transform the atmosphere instantly. Touch the colour wheel to fine-tune lighting and set the perfect mood.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-banner reveal">
    <div class="container">
        <div class="cta-banner__content">
            <div class="section-label section-label--center">Get Started</div>
            <h2 class="section-title">Transform Your Home with Smart Lighting</h2>
            <p class="section-subtitle">From design to installation, we deliver complete home lighting automation across Cape Town.</p>
            <a href="{$base}/contact" class="btn btn--primary">Enquire Now</a>
        </div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="faq-section__header reveal">
            <div class="section-label section-label--center">FAQ</div>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="faq-list reveal">
            <div class="faq-item">
                <button class="faq-item__question">Can smart lighting work with existing light fittings? FAQICON</button>
                <div class="faq-item__answer"><p>In many cases, yes. Smart lighting systems can often integrate with existing fixtures, depending on the type of lighting and wiring. We assess this during the design phase.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Is smart lighting energy efficient? FAQICON</button>
                <div class="faq-item__answer"><p>Absolutely. LED-based smart lighting uses significantly less energy than traditional lighting and can be scheduled to turn off automatically when not needed.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Can smart lighting be controlled manually? FAQICON</button>
                <div class="faq-item__answer"><p>Yes. Our systems include elegant keypads and switches for manual control alongside app and voice control, so you always have options.</p></div>
            </div>
        </div>
    </div>
</section>

HTML
    . footerHtml($base)
    . closingScripts();
}


// ═══════════════════════════════════════════════════════════════
// PAGE 4: CINEMA ROOMS
// ═══════════════════════════════════════════════════════════════

function buildCinemaRooms(string $base, string $img): string {
    $arrow = arrowSvg();
    return head('Bespoke Cinema Rooms', 'Transform your entertainment space into a world-class cinema with breathtaking picture quality, surround sound and exceptional comfort.')
    . "\n<body>\n"
    . navHtml($base, 'cinema-rooms')
    . <<<HTML

<section class="inner-hero">
    <div class="inner-hero__bg" style="background-image:url('{$img}/2025/11/bespoke-cinema-rooms.jpg');"></div>
    <div class="inner-hero__overlay"></div>
    <div class="inner-hero__content">
        <div class="container">
            <div class="inner-hero__breadcrumb"><a href="{$base}">Home</a> / Cinema Rooms</div>
            <h1 class="inner-hero__title">Bespoke Cinema Rooms</h1>
            <p class="inner-hero__subtitle">Bespoke cinema rooms designed for immersive movie experiences. Breathtaking picture quality, enveloping surround sound and exceptional comfort.</p>
        </div>
    </div>
</section>

<section class="content-section content-section--cream">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Cinema Solutions</div>
            <h2 class="section-title">Home Cinema Solutions</h2>
            <p class="section-subtitle" style="margin:0 auto;">Whether you want a dedicated cinema room or a multi-purpose media space, we offer a complete solution that goes beyond basic installation.</p>
        </div>
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/bespoke-cinema-rooms.jpg" alt="Cinema room speakers" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Speakers</div>
                <h3>Precision Sound. Pure Emotion.</h3>
                <p>The heartbeat of every great cinema room is its sound. Our cinema speaker systems are designed to create a rich, detailed and enveloping audio environment, allowing every soundtrack, whisper and impact to be felt with absolute clarity.</p>
            </div>
        </div>
        <div class="alt-row alt-row--reverse reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/video-audio-distribution.jpg" alt="Home cinema projector" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Projectors</div>
                <h3>See Every Detail. Feel Every Moment.</h3>
                <p>A true home cinema is defined by its picture — breathtaking clarity, cinematic contrast, and lifelike motion that draws you in completely. We select and calibrate the perfect system to bring every scene to life.</p>
            </div>
        </div>
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/10/Guide-to-Home-Theatre-Lighting-V2_otnep.jpg.avif" alt="Cinema room lighting" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Cinema Lighting</div>
                <h3>Set the Mood. Enhance the Moment.</h3>
                <p>Lighting defines the atmosphere and elevates the experience of your home cinema. Dimming automatically as the movie starts or brightening slightly when someone enters the room — our lighting systems are elegant, dynamic and fully automated through Savant.</p>
                <a href="{$base}/smart-lighting" class="btn-arrow">Explore Smart Lighting {$arrow}</a>
            </div>
        </div>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Packages</div>
            <h2 class="section-title">Cinema Room Packages</h2>
            <p class="section-subtitle" style="margin:0 auto;">Choose from three carefully curated tiers — each representing a clear step up in performance and design.</p>
        </div>
        <div class="packages-grid">
            <div class="package-card reveal">
                <div class="package-card__tier">Tier One</div>
                <h3 class="package-card__name">Nano</h3>
                <div class="package-card__tagline">Your First Step Into Cinema</div>
                <p class="package-card__desc">A powerful introduction to home theatre, designed for clients who want true surround sound, a large screen and impactful bass in a cost-efficient package.</p>
                <p class="package-card__ideal"><strong>Perfect for:</strong> lounges adapted into theatres, small dedicated cinema rooms, family-oriented installations.</p>
                <a href="{$base}/contact" class="btn btn--dark" style="align-self:flex-start;">Enquire About Nano</a>
            </div>
            <div class="package-card package-card--featured reveal reveal-delay-1">
                <div class="package-card__tier">Tier Two</div>
                <h3 class="package-card__name">Stellar</h3>
                <div class="package-card__tagline">Where Cinema Becomes Art</div>
                <p class="package-card__desc">A fully engineered cinema with Atmos audio, premium acoustic treatment and luxury seating included. STELLAR delivers a refined visual and sonic experience.</p>
                <p class="package-card__ideal"><strong>Perfect for:</strong> homeowners seeking a high-performance theatre with comfort, design detail and architectural presence.</p>
                <a href="{$base}/contact" class="btn btn--primary" style="align-self:flex-start;">Enquire About Stellar</a>
            </div>
            <div class="package-card reveal reveal-delay-2">
                <div class="package-card__tier">Tier Three</div>
                <h3 class="package-card__name">Cosmos</h3>
                <div class="package-card__tagline">The Ultimate Private Cinema</div>
                <p class="package-card__desc">A no-compromise reference-grade installation built to outperform commercial cinemas. Exceptional dynamic range, precision audio and breathtaking visual clarity.</p>
                <p class="package-card__ideal"><strong>Perfect for:</strong> clients who want the highest standard available — nothing less.</p>
                <a href="{$base}/contact" class="btn btn--dark" style="align-self:flex-start;">Enquire About Cosmos</a>
            </div>
        </div>
    </div>
</section>

<section class="process-section reveal">
    <div class="container">
        <div class="process-section__header">
            <div class="section-label section-label--center">Our Process</div>
            <h2 class="section-title">How the Magic Happens</h2>
        </div>
        <div class="process-grid">
            <div class="process-step"><div class="process-step__number">1</div><div class="process-step__title">Consultation &amp; Room Planning</div></div>
            <div class="process-step"><div class="process-step__number">2</div><div class="process-step__title">Acoustic &amp; Lighting Design</div></div>
            <div class="process-step"><div class="process-step__number">3</div><div class="process-step__title">Technology Selection</div></div>
            <div class="process-step"><div class="process-step__number">4</div><div class="process-step__title">Installation &amp; Calibration</div></div>
            <div class="process-step"><div class="process-step__number">5</div><div class="process-step__title">After-care Support</div></div>
        </div>
        <div style="text-align:center;margin-top:var(--space-2xl);"><a href="{$base}/contact" class="btn btn--primary">Book a Consultation</a></div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="faq-section__header reveal">
            <div class="section-label section-label--center">FAQ</div>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="faq-list reveal">
            <div class="faq-item">
                <button class="faq-item__question">What's the difference between a home theatre and a cinema room? FAQICON</button>
                <div class="faq-item__answer"><p>A cinema room is purpose-designed for sound, lighting, seating and acoustics to deliver a truly immersive experience. It goes beyond a basic home theatre setup by focusing on performance, comfort and atmosphere.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Do I need a dedicated room for a cinema setup? FAQICON</button>
                <div class="faq-item__answer"><p>Not necessarily. While a dedicated room provides the best experience, we can also design multi-purpose spaces that perform exceptionally well for cinema viewing while serving other functions.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Can cinema rooms be integrated with the rest of my smart home? FAQICON</button>
                <div class="faq-item__answer"><p>Absolutely. Through Savant, your cinema room integrates seamlessly with the rest of your home — lighting, climate, and audio can all be controlled from one interface.</p></div>
            </div>
        </div>
    </div>
</section>

HTML
    . footerHtml($base)
    . closingScripts();
}


// ═══════════════════════════════════════════════════════════════
// PAGE 5: ENTERTAINMENT & AV
// ═══════════════════════════════════════════════════════════════

function buildEntertainment(string $base, string $img): string {
    $arrow = arrowSvg();
    return head('Audio & Visual Automation Systems', 'The best in luxury audio & video distribution for endless home entertainment.')
    . "\n<body>\n"
    . navHtml($base, 'entertainment')
    . <<<HTML

<section class="inner-hero">
    <div class="inner-hero__bg" style="background-image:url('{$img}/2025/11/video-audio-distribution.jpg');"></div>
    <div class="inner-hero__overlay"></div>
    <div class="inner-hero__content">
        <div class="container">
            <div class="inner-hero__breadcrumb"><a href="{$base}">Home</a> / Entertainment &amp; AV</div>
            <h1 class="inner-hero__title">Audio &amp; Visual<br>Automation Systems</h1>
            <p class="inner-hero__subtitle">The best in luxury audio & video distribution for endless home entertainment.</p>
        </div>
    </div>
</section>

<section class="content-section content-section--cream">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Video</div>
            <h2 class="section-title">Smart Video Entertainment for Every Room</h2>
        </div>
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/video-audio-distribution.jpg" alt="Multi-room video distribution" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Video Distribution</div>
                <h3>Seamless Multi-Room Video</h3>
                <p>Savant's intelligent video network delivers crystal-clear, high-definition visuals from any source to any screen. Perfect for homes of every scale, video distribution combines exceptional image clarity, effortless switching, and integrated control from a single interface.</p>
            </div>
        </div>
        <div class="alt-row alt-row--reverse reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/smart-home-remote-control.png" alt="Video tiling" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Video Tiling</div>
                <h3>Simultaneous Multi-Source Viewing</h3>
                <p>Watch multiple sources at once with Savant's video tiling. Follow several sports, monitor home cameras, or keep an eye on live news — all displayed together with seamless control.</p>
                <a href="{$base}/contact" class="btn-arrow">Enquire Now {$arrow}</a>
            </div>
        </div>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Audio</div>
            <h2 class="section-title">Audio Distribution for Immersive Experiences</h2>
        </div>
        <div class="feature-grid">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg></div>
                <h3>Whole-Home Music</h3>
                <p>Enjoy your favourite music indoors and out with our whole-home audio system. Create zones for every mood and experience truly immersive sound.</p>
            </div>
            <div class="feature-card reveal reveal-delay-1">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg></div>
                <h3>Architectural Speakers</h3>
                <p>Discreet in-ceiling and in-wall speakers deliver exceptional clarity while blending seamlessly into your home's architecture.</p>
            </div>
            <div class="feature-card reveal reveal-delay-2">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg></div>
                <h3>Outdoor Audio</h3>
                <p>Premium outdoor speakers blend naturally into their surroundings. Enjoy rich, even sound across your garden and entertaining spaces.</p>
            </div>
        </div>
    </div>
</section>

<section class="content-section content-section--dark">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Immersive Sound</div>
            <h2 class="section-title" style="color:var(--color-white);">Dolby Atmos &amp; Surround Sound</h2>
        </div>
        <div class="feature-grid">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg></div>
                <h3>Dolby Atmos</h3>
                <p>Dolby Atmos places sounds precisely around and above you for a more realistic, immersive audio experience.</p>
            </div>
            <div class="feature-card reveal reveal-delay-1">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg></div>
                <h3>7.1 Cinematic Sound</h3>
                <p>The audio standard for cinematic sound. Enhanced directionality with additional rear speakers deepens the three-dimensional ambience.</p>
            </div>
            <div class="feature-card reveal reveal-delay-2">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 2a14 14 0 014 20M12 2a14 14 0 00-4 20M2 12h20"/></svg></div>
                <h3>Immersive 360&deg;</h3>
                <p>Dolby Atmos expands 360&deg; sound into a full hemisphere, surrounding you with lifelike detail from every direction.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-banner reveal">
    <div class="container">
        <div class="cta-banner__content">
            <div class="section-label section-label--center">Get Started</div>
            <h2 class="section-title">Ready to Elevate Your Entertainment?</h2>
            <p class="section-subtitle">We design and install audio & video systems across Cape Town, from multi-room video to whole-home audio.</p>
            <a href="{$base}/contact" class="btn btn--primary">Enquire Now</a>
        </div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="faq-section__header reveal">
            <div class="section-label section-label--center">FAQ</div>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="faq-list reveal">
            <div class="faq-item">
                <button class="faq-item__question">What does a home entertainment system include? FAQICON</button>
                <div class="faq-item__answer"><p>A home entertainment system may include multi-room audio, TV and media systems, streaming services, and integrated control that allows content to be shared across different spaces.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Can I play music in multiple rooms at the same time? FAQICON</button>
                <div class="faq-item__answer"><p>Yes. With Savant's multi-room audio, you can play the same music throughout the house or different music in different zones simultaneously.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Can entertainment systems be upgraded over time? FAQICON</button>
                <div class="faq-item__answer"><p>Absolutely. Our systems are designed with future expansion in mind, so you can add new zones, upgrade speakers, or add video distribution as your needs evolve.</p></div>
            </div>
        </div>
    </div>
</section>

HTML
    . footerHtml($base)
    . closingScripts();
}


// ═══════════════════════════════════════════════════════════════
// PAGE 6: AUTOMATED BLINDS
// ═══════════════════════════════════════════════════════════════

function buildAutomatedBlinds(string $base, string $img): string {
    $arrow = arrowSvg();
    return head('Automated Blinds', 'Elegant, automated blinds designed for modern living. Custom-built motorised shades for effortless light and privacy control.')
    . "\n<body>\n"
    . navHtml($base, 'automated-blinds')
    . <<<HTML

<section class="inner-hero">
    <div class="inner-hero__bg" style="background-image:url('{$img}/2025/11/automated-blinds.jpeg');"></div>
    <div class="inner-hero__overlay"></div>
    <div class="inner-hero__content">
        <div class="container">
            <div class="inner-hero__breadcrumb"><a href="{$base}">Home</a> / Automated Blinds</div>
            <h1 class="inner-hero__title">Automated Blinds</h1>
            <p class="inner-hero__subtitle">Elegant, automated blinds designed for modern living. Custom-built motorised shades for effortless light and privacy control.</p>
        </div>
    </div>
</section>

<section class="content-section content-section--cream">
    <div class="container">
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/automated-blinds.jpeg" alt="Motorised blinds" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Motorised Shades</div>
                <h3>Intelligent Light &amp; Privacy Control</h3>
                <p>Our shading systems combine modern, minimalistic design with advanced functionality. Control natural light, temperature, and privacy throughout your home. Whether you prefer smart blinds in the living room or blackout options in the bedroom, every space adapts to your routine.</p>
            </div>
        </div>
        <div class="alt-row alt-row--reverse reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/smart-home-remote-control.png" alt="Savant app blind control" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Savant Integration</div>
                <h3>Seamless Home Automation Integration</h3>
                <p>The intuitive Savant app gives you precise control of your blinds. Adjust individual shade positions with a touch or a simple voice command, fine-tuning the light in every room exactly the way you like it.</p>
            </div>
        </div>
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/11/smart-home-lighting.png" alt="Designer fabrics" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Custom Design</div>
                <h3>Designer Fabrics &amp; Custom Finishes</h3>
                <p>Choose from premium fabrics and colours that are as fashionable as they are functional. Each shade is custom-made for a perfect fit and near-silent operation.</p>
            </div>
        </div>
    </div>
</section>

<section class="content-section">
    <div class="container">
        <div class="feature-grid">
            <div class="feature-card reveal">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
                <h3>Smart Scheduling</h3>
                <p>Savant's smart scheduling lets your motorised shades adjust automatically with the sun or activate preset scenes to match your mood.</p>
            </div>
            <div class="feature-card reveal reveal-delay-1">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2"/></svg></div>
                <h3>Energy Efficient</h3>
                <p>Automated blinds help regulate heat by responding to sunlight throughout the day, reducing glare and improving energy efficiency.</p>
            </div>
            <div class="feature-card reveal reveal-delay-2">
                <div class="feature-card__icon"><svg viewBox="0 0 24 24"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg></div>
                <h3>Near-Silent Operation</h3>
                <p>Premium motors ensure smooth, quiet operation. Your blinds adjust without disrupting conversations or sleep.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-banner reveal">
    <div class="container">
        <div class="cta-banner__content">
            <div class="section-label section-label--center">Get Started</div>
            <h2 class="section-title">Transform How You Control Light</h2>
            <p class="section-subtitle">From elegant motorised blinds to full home automation, our team creates intelligent shading solutions tailored to your lifestyle.</p>
            <a href="{$base}/contact" class="btn btn--primary">Enquire Now</a>
        </div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="faq-section__header reveal">
            <div class="section-label section-label--center">FAQ</div>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="faq-list reveal">
            <div class="faq-item">
                <button class="faq-item__question">Do automated blinds help with temperature control? FAQICON</button>
                <div class="faq-item__answer"><p>Yes. Automated blinds help regulate heat by responding to sunlight throughout the day, reducing glare and improving energy efficiency.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">What automated blinds do you install? FAQICON</button>
                <div class="faq-item__answer"><p>We install premium motorised shading systems that integrate seamlessly with Savant and other smart home platforms. We work with industry-leading blind manufacturers for quality and durability.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Can automated blinds still be operated manually? FAQICON</button>
                <div class="faq-item__answer"><p>Yes. While our blinds are designed for automated and remote control, manual operation is always available as a backup.</p></div>
            </div>
        </div>
    </div>
</section>

HTML
    . footerHtml($base)
    . closingScripts();
}


// ═══════════════════════════════════════════════════════════════
// PAGE 7: SECURITY
// ═══════════════════════════════════════════════════════════════

function buildSecurity(string $base, string $img): string {
    $arrow = arrowSvg();
    return head('Smart Home Security', 'Intelligent protection, professionally installed for peace of mind. Real-time alerts to home security camera installation.')
    . "\n<body>\n"
    . navHtml($base, 'security')
    . <<<HTML

<section class="inner-hero">
    <div class="inner-hero__bg" style="background-image:url('{$img}/2025/10/home-security-solutions.png');"></div>
    <div class="inner-hero__overlay"></div>
    <div class="inner-hero__content">
        <div class="container">
            <div class="inner-hero__breadcrumb"><a href="{$base}">Home</a> / Smart Security</div>
            <h1 class="inner-hero__title">Smart Home<br>Automation Security</h1>
            <p class="inner-hero__subtitle">Intelligent protection, professionally installed for peace of mind.</p>
        </div>
    </div>
</section>

<section class="content-section content-section--cream">
    <div class="container">
        <div class="content-section__header reveal">
            <div class="section-label section-label--center">Security Solutions</div>
            <h2 class="section-title">Smart Security Solutions</h2>
            <p class="section-subtitle" style="margin:0 auto;">Our integrated systems offer full visibility and control of your property. Monitor cameras, check alarm status, or receive instant notifications through the Savant app.</p>
        </div>
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/10/home-security-solutions.png" alt="Smart alarm control" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Alarm Control</div>
                <h3>Take Full Control of Your Alarm System</h3>
                <p>Whether you're home or away, manage your home security system directly from your smartphone. Arm or disarm your system, view entry history, and receive updates in real time.</p>
            </div>
        </div>
        <div class="alt-row alt-row--reverse reveal">
            <div class="alt-row__image"><img src="{$img}/2025/10/smart-garage-security-980x951.jpg.avif" alt="Smart garage access" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Access Control</div>
                <h3>Garage &amp; Entry at Your Fingertips</h3>
                <p>Lock and unlock doors or operate your garage remotely. Our home security system installers ensure seamless integration with Savant, giving you feedback on every door or gate's position — open or closed — for complete awareness at all times.</p>
            </div>
        </div>
        <div class="alt-row reveal">
            <div class="alt-row__image"><img src="{$img}/2025/10/home-security-camera-installation.jpg" alt="Door station camera" loading="lazy"></div>
            <div class="alt-row__text">
                <div class="section-label">Door Station</div>
                <h3>Eyes, Ears and Voice at Your Doorstep</h3>
                <p>Be instantly notified when someone approaches your door with video and audio feeds from your entry station. Answer visitors via two-way intercom, unlock the door, turn on lights, or activate a Savant scene — all from one interface.</p>
            </div>
        </div>
    </div>
</section>

<section class="cta-banner reveal">
    <div class="container">
        <div class="cta-banner__content">
            <div class="section-label section-label--center">Get Started</div>
            <h2 class="section-title">Ready to Enhance Your Home Security?</h2>
            <p class="section-subtitle">We design and install smart home security systems across Cape Town, from door entry to full camera integration.</p>
            <a href="{$base}/contact" class="btn btn--primary">Enquire Now</a>
        </div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="faq-section__header reveal">
            <div class="section-label section-label--center">FAQ</div>
            <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="faq-list reveal">
            <div class="faq-item">
                <button class="faq-item__question">What does a smart security system include? FAQICON</button>
                <div class="faq-item__answer"><p>Smart security systems may include CCTV, access control, alarm integration and remote monitoring, all managed through a single control platform.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Can security systems be integrated with smart home automation? FAQICON</button>
                <div class="faq-item__answer"><p>Yes. Through Savant, your security system integrates with lighting, cameras, and entry points, providing unified control from one app.</p></div>
            </div>
            <div class="faq-item">
                <button class="faq-item__question">Can I monitor my home remotely? FAQICON</button>
                <div class="faq-item__answer"><p>Absolutely. With the Savant app, you can view live camera feeds, check alarm status, and receive notifications from anywhere in the world.</p></div>
            </div>
        </div>
    </div>
</section>

HTML
    . footerHtml($base)
    . closingScripts();
}


// ═══════════════════════════════════════════════════════════════
// PAGE 8: CONTACT
// ═══════════════════════════════════════════════════════════════

function buildContact(string $base, string $img): string {
    return head('Contact Us', 'Get in touch to discuss your project or request a consultation. We design and install bespoke home automation systems across Cape Town and the Garden Route.')
    . "\n<body>\n"
    . navHtml($base, '')
    . <<<HTML

<section class="inner-hero">
    <div class="inner-hero__bg" style="background-image:url('{$img}/2025/11/smart-home-remote-control.png');"></div>
    <div class="inner-hero__overlay"></div>
    <div class="inner-hero__content">
        <div class="container">
            <div class="inner-hero__breadcrumb"><a href="{$base}">Home</a> / Contact</div>
            <h1 class="inner-hero__title">Contact Us</h1>
            <p class="inner-hero__subtitle">Get in touch to discuss your project or request a consultation. We design and install bespoke home automation systems across Cape Town and the Garden Route.</p>
        </div>
    </div>
</section>

<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <div class="contact-form reveal">
                <div class="section-label">Get In Touch</div>
                <h2 class="section-title" style="font-size:clamp(1.6rem,3vw,2.2rem);">Have a question or want to discuss your project?</h2>
                <p style="color:var(--color-warm-gray);margin-bottom:var(--space-xl);line-height:1.7;">We work with homeowners, architects, interior designers, and developers to create intelligent spaces that blend design and technology. Fill in the form below, and we'll get back to you within one working day.</p>
                <form onsubmit="event.preventDefault(); alert('Thank you! We will be in touch shortly.');">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" required placeholder="Your full name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label for="reason">Reason for Contact</label>
                        <select id="reason" name="reason">
                            <option value="">Select a reason</option>
                            <option value="consultation">Book a Consultation</option>
                            <option value="smart-home">Smart Home Project</option>
                            <option value="lighting">Smart Lighting</option>
                            <option value="cinema">Cinema Room</option>
                            <option value="entertainment">Entertainment & AV</option>
                            <option value="blinds">Automated Blinds</option>
                            <option value="security">Smart Security</option>
                            <option value="general">General Enquiry</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" placeholder="Tell us about your project..."></textarea>
                    </div>
                    <button type="submit" class="btn btn--primary">Submit Enquiry</button>
                </form>
            </div>
            <div class="contact-info reveal reveal-delay-1">
                <h3>Contact Details</h3>
                <div class="contact-info__item">
                    <div class="contact-info__label">Location</div>
                    <div class="contact-info__value">Cape Town &amp; George (Garden Route)</div>
                </div>
                <div class="contact-info__item">
                    <div class="contact-info__label">Phone</div>
                    <div class="contact-info__value"><a href="tel:+27825157437">+27 82 515 7437</a></div>
                </div>
                <div class="contact-info__item">
                    <div class="contact-info__label">General Enquiries</div>
                    <div class="contact-info__value"><a href="mailto:info@smarthomearchitects.co.za">info@smarthomearchitects.co.za</a></div>
                </div>
                <div class="contact-info__item">
                    <div class="contact-info__label">Director</div>
                    <div class="contact-info__value">
                        <a href="mailto:greg@smarthomearchitects.co.za">greg@smarthomearchitects.co.za</a><br>
                        <a href="tel:+27825157437">+27 82 515 7437</a>
                    </div>
                </div>
                <div style="margin-top:var(--space-xl);padding-top:var(--space-lg);border-top:1px solid rgba(0,0,0,0.08);">
                    <div class="contact-info__label" style="margin-bottom:var(--space-sm);">Follow Us</div>
                    <div style="display:flex;gap:1rem;">
                        <a href="https://www.facebook.com/Smarthomearchitects.sa" style="color:var(--color-gold);" aria-label="Facebook">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"/></svg>
                        </a>
                        <a href="https://www.instagram.com/smart_home_architects_sa/" style="color:var(--color-gold);" aria-label="Instagram">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1.5" fill="currentColor" stroke="none"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

HTML
    . footerHtml($base)
    . closingScripts();
}


// ═══════════════════════════════════════════════════════════════
// BUILD & INSERT ALL PAGES
// ═══════════════════════════════════════════════════════════════

// Replace FAQICON placeholder with actual SVG
function finalize(string $html): string {
    $icon = '<svg class="faq-item__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
    return str_replace('FAQICON', $icon, $html);
}

$pages = [
    [
        'slug'  => 'smart-home-3',
        'title' => 'Smart Home Architects',
        'body'  => finalize(buildHomepage($BASE, $IMG)),
    ],
    [
        'slug'  => 'smart-home-3/smart-home',
        'title' => 'Smart Home',
        'body'  => finalize(buildSmartHome($BASE, $IMG)),
    ],
    [
        'slug'  => 'smart-home-3/smart-lighting',
        'title' => 'Smart Lighting',
        'body'  => finalize(buildSmartLighting($BASE, $IMG)),
    ],
    [
        'slug'  => 'smart-home-3/cinema-rooms',
        'title' => 'Cinema Rooms',
        'body'  => finalize(buildCinemaRooms($BASE, $IMG)),
    ],
    [
        'slug'  => 'smart-home-3/entertainment',
        'title' => 'Entertainment & AV',
        'body'  => finalize(buildEntertainment($BASE, $IMG)),
    ],
    [
        'slug'  => 'smart-home-3/automated-blinds',
        'title' => 'Automated Blinds',
        'body'  => finalize(buildAutomatedBlinds($BASE, $IMG)),
    ],
    [
        'slug'  => 'smart-home-3/security',
        'title' => 'Smart Security',
        'body'  => finalize(buildSecurity($BASE, $IMG)),
    ],
    [
        'slug'  => 'smart-home-3/contact',
        'title' => 'Contact Us',
        'body'  => finalize(buildContact($BASE, $IMG)),
    ],
];

echo "Building " . count($pages) . " pages under '{$BASE}'...\n\n";

foreach ($pages as $pageData) {
    // Check if page already exists, update if so
    $existing = Page::where('slug', $pageData['slug'])->first();
    if ($existing) {
        $existing->update([
            'title'    => $pageData['title'],
            'body'     => $pageData['body'],
            'status'   => 'published',
            'template' => 'blank',
        ]);
        echo "  UPDATED: [{$existing->id}] {$pageData['slug']} ({$pageData['title']})\n";
    } else {
        $page = Page::create([
            'title'    => $pageData['title'],
            'slug'     => $pageData['slug'],
            'body'     => $pageData['body'],
            'status'   => 'published',
            'template' => 'blank',
        ]);
        echo "  CREATED: [{$page->id}] {$pageData['slug']} ({$pageData['title']})\n";
    }
}

echo "\nDone! Visit {$BASE} to see the homepage.\n";
