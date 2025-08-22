<?php
/**
 * Under Construction Page
 * Displayed when site is in maintenance mode
 */

// Get site settings
$siteName = getSiteSetting('site_name', 'Our Website');
$companyName = getSiteSetting('company_name', 'Our Company');

// Get theme settings for styling
$themeSettings = getThemeSettings();
$backgroundColor = $themeSettings['full_bg_color'] ?? '#1a269b';
$loginLogo = $themeSettings['login_logo'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Construction - <?= e($siteName) ?></title>
    <meta name="description" content="<?= e($siteName) ?> is currently under construction. We'll be back soon!">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, <?= e($backgroundColor) ?>, #2d3db4);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            overflow: hidden;
            position: relative;
        }
        
        /* Animated background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }
        
        .construction-container {
            text-align: center;
            max-width: 600px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 2;
            animation: slideUp 1s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .logo-container {
            margin-bottom: 30px;
        }
        
        .logo-container img {
            max-width: 200px;
            max-height: 80px;
            object-fit: contain;
            filter: brightness(0) invert(1);
        }
        
        .logo-text {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .construction-icon {
            font-size: 4rem;
            color: #ffcc3f;
            margin-bottom: 30px;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }
        
        .main-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            background: linear-gradient(45deg, #ffcc3f, #ff6b35);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .subtitle {
            font-size: 1.3rem;
            margin-bottom: 30px;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .message {
            font-size: 1.1rem;
            margin-bottom: 40px;
            opacity: 0.8;
            line-height: 1.6;
        }
        
        .features-list {
            list-style: none;
            margin: 30px 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            transition: transform 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-3px);
        }
        
        .feature-icon {
            color: #ffcc3f;
            font-size: 1.2rem;
        }
        
        .contact-info {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .contact-item {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin: 0 20px;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            text-decoration: none;
            color: white;
            transition: all 0.3s ease;
        }
        
        .contact-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            color: white;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            overflow: hidden;
            margin: 30px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ffcc3f, #ff6b35);
            border-radius: 4px;
            animation: progress 3s ease-in-out infinite;
        }
        
        @keyframes progress {
            0% { width: 60%; }
            50% { width: 85%; }
            100% { width: 60%; }
        }
        
        .footer-text {
            margin-top: 40px;
            font-size: 0.9rem;
            opacity: 0.7;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .construction-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .main-title {
                font-size: 2.2rem;
            }
            
            .subtitle {
                font-size: 1.1rem;
            }
            
            .message {
                font-size: 1rem;
            }
            
            .construction-icon {
                font-size: 3rem;
            }
            
            .features-list {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .contact-item {
                margin: 10px 0;
                display: flex;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .construction-container {
                padding: 20px 15px;
            }
            
            .main-title {
                font-size: 1.8rem;
            }
            
            .logo-text {
                font-size: 2rem;
            }
            
            .construction-icon {
                font-size: 2.5rem;
            }
        }
        
        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            .construction-icon,
            .progress-fill,
            body::before {
                animation: none;
            }
            
            .construction-container {
                animation: none;
                opacity: 1;
                transform: none;
            }
        }
    </style>
</head>
<body>
    <div class="construction-container">
        <!-- Logo/Brand -->
        <div class="logo-container">
            <?php if ($loginLogo): ?>
                <img src="<?= UPLOADS_URL ?>/logos/<?= e($loginLogo) ?>" alt="<?= e($siteName) ?> Logo">
            <?php else: ?>
                <h1 class="logo-text"><?= e($siteName) ?></h1>
            <?php endif; ?>
        </div>
        
        <!-- Construction Icon -->
        <div class="construction-icon">
            <i class="fas fa-hard-hat"></i>
        </div>
        
        <!-- Main Content -->
        <h1 class="main-title">Under Construction</h1>
        
        <p class="subtitle">
            We're working hard to bring you something amazing!
        </p>
        
        <p class="message">
            Our website is currently undergoing scheduled maintenance and improvements. 
            We'll be back online soon with a better experience for you.
        </p>
        
        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="progress-fill"></div>
        </div>
        
        <!-- Features Coming Soon -->
        <ul class="features-list">
            <li class="feature-item">
                <i class="fas fa-rocket feature-icon"></i>
                <span>Enhanced Performance</span>
            </li>
            <li class="feature-item">
                <i class="fas fa-mobile-alt feature-icon"></i>
                <span>Mobile Optimized</span>
            </li>
            <li class="feature-item">
                <i class="fas fa-shield-alt feature-icon"></i>
                <span>Enhanced Security</span>
            </li>
            <li class="feature-item">
                <i class="fas fa-paint-brush feature-icon"></i>
                <span>Fresh New Design</span>
            </li>
        </ul>
        
        <!-- Contact Information -->
        <div class="contact-info">
            <p style="margin-bottom: 20px; font-weight: 600;">Need to reach us?</p>
            
            <?php if (getSiteSetting('site_phone')): ?>
                <a href="tel:<?= e(getSiteSetting('site_phone')) ?>" class="contact-item">
                    <i class="fas fa-phone"></i>
                    <span><?= e(getSiteSetting('site_phone')) ?></span>
                </a>
            <?php endif; ?>
            
            <a href="mailto:info@<?= e($_SERVER['HTTP_HOST'] ?? 'example.com') ?>" class="contact-item">
                <i class="fas fa-envelope"></i>
                <span>Email Us</span>
            </a>
        </div>
        
        <!-- Footer -->
        <div class="footer-text">
            <p>&copy; <?= date('Y') ?> <?= e($companyName) ?>. All rights reserved.</p>
            <p>We appreciate your patience!</p>
        </div>
    </div>
    
    <!-- Additional animations -->
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Animate feature items on load
            const featureItems = document.querySelectorAll('.feature-item');
            featureItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px)';
                    item.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 200);
            });
            
            // Update progress bar randomly
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                setInterval(() => {
                    const randomWidth = 60 + Math.random() * 25; // 60-85%
                    progressFill.style.width = randomWidth + '%';
                }, 3000);
            }
            
            // Add click effect to contact items
            document.querySelectorAll('.contact-item').forEach(item => {
                item.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
        
        // Easter egg: Konami code
        let konamiCode = [];
        const konami = [38, 38, 40, 40, 37, 39, 37, 39, 66, 65]; // Up Up Down Down Left Right Left Right B A
        
        document.addEventListener('keydown', function(e) {
            konamiCode.push(e.keyCode);
            if (konamiCode.length > konami.length) {
                konamiCode.shift();
            }
            
            if (JSON.stringify(konamiCode) === JSON.stringify(konami)) {
                // Easter egg activated
                document.body.style.animation = 'rainbow 2s infinite';
                document.querySelector('.construction-icon').innerHTML = '<i class="fas fa-unicorn"></i>';
                
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes rainbow {
                        0% { filter: hue-rotate(0deg); }
                        100% { filter: hue-rotate(360deg); }
                    }
                `;
                document.head.appendChild(style);
                
                setTimeout(() => {
                    document.body.style.animation = '';
                    document.querySelector('.construction-icon').innerHTML = '<i class="fas fa-hard-hat"></i>';
                    style.remove();
                }, 5000);
            }
        });
    </script>
</body>
</html>