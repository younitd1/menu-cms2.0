/**
 * Frontend JavaScript - Interactive functionality
 * Handles mobile menu, analytics, animations, and user interactions
 */

// Global frontend object
window.FrontendCMS = {
    init: function() {
        this.initMobileMenu();
        this.initSmoothScrolling();
        this.initAnimations();
        this.initLazyLoading();
        this.bindEvents();
    },
    
    // Mobile hamburger menu functionality
    initMobileMenu: function() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const mobileMenu = document.querySelector('.mobile-menu');
        const mobileOverlay = document.querySelector('.mobile-menu-overlay');
        const menuClose = document.querySelector('.mobile-menu-close');
        
        if (menuToggle && mobileMenu) {
            // Open menu
            menuToggle.addEventListener('click', function() {
                mobileMenu.classList.add('active');
                if (mobileOverlay) {
                    mobileOverlay.classList.add('active');
                }
                document.body.style.overflow = 'hidden';
            });
            
            // Close menu
            const closeMenu = function() {
                mobileMenu.classList.remove('active');
                if (mobileOverlay) {
                    mobileOverlay.classList.remove('active');
                }
                document.body.style.overflow = '';
            };
            
            if (menuClose) {
                menuClose.addEventListener('click', closeMenu);
            }
            
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', closeMenu);
            }
            
            // Close menu when clicking menu links
            const mobileMenuLinks = document.querySelectorAll('.mobile-menu-items a');
            mobileMenuLinks.forEach(link => {
                link.addEventListener('click', closeMenu);
            });
            
            // Close menu on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                    closeMenu();
                }
            });
        }
    },
    
    // Smooth scrolling for navigation links
    initSmoothScrolling: function() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const headerOffset = 100; // Account for sticky menu
                    const elementPosition = target.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    },
    
    // Initialize scroll-triggered animations
    initAnimations: function() {
        // Intersection Observer for fade-in animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, observerOptions);
        
        // Observe elements for animation
        const animateElements = document.querySelectorAll(
            '.product-item, .category-section, .module-container'
        );
        animateElements.forEach(el => observer.observe(el));
    },
    
    // Lazy loading for images
    initLazyLoading: function() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for browsers without IntersectionObserver
            document.querySelectorAll('img[data-src]').forEach(img => {
                img.src = img.dataset.src;
            });
        }
    },
    
    // Bind event listeners
    bindEvents: function() {
        // Window resize handler
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                FrontendCMS.handleResize();
            }, 250);
        });
        
        // Scroll handler for performance
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            if (!scrollTimeout) {
                scrollTimeout = setTimeout(function() {
                    FrontendCMS.handleScroll();
                    scrollTimeout = null;
                }, 10);
            }
        });
    },
    
    // Handle window resize
    handleResize: function() {
        // Close mobile menu on resize to desktop
        if (window.innerWidth > 768) {
            const mobileMenu = document.querySelector('.mobile-menu');
            const mobileOverlay = document.querySelector('.mobile-menu-overlay');
            
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                if (mobileOverlay) {
                    mobileOverlay.classList.remove('active');
                }
                document.body.style.overflow = '';
            }
        }
    },
    
    // Handle scroll events
    handleScroll: function() {
        // This is called by the main index.php scroll handlers
        // Additional scroll-based functionality can be added here
    },
    
    // Analytics tracking functions
    trackEvent: function(eventType, eventData = {}) {
        const data = {
            event_type: eventType,
            timestamp: Date.now(),
            url: window.location.href,
            user_agent: navigator.userAgent,
            ...eventData
        };
        
        // Send to analytics endpoint
        fetch('ajax/track_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        }).catch(error => {
            console.warn('Analytics tracking failed:', error);
        });
    },
    
    // Track page view
    trackPageView: function() {
        this.trackEvent('page_view', {
            page_title: document.title,
            referrer: document.referrer
        });
    },
    
    // Utility functions
    utils: {
        // Debounce function
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = function() {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        // Throttle function
        throttle: function(func, limit) {
            let inThrottle;
            return function() {
                const args = arguments;
                const context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },
        
        // Format currency
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },
        
        // Get cookie value
        getCookie: function(name) {
            const value = `; ${document.cookie}`;
            const parts = value.split(`; ${name}=`);
            if (parts.length === 2) {
                return parts.pop().split(';').shift();
            }
            return null;
        },
        
        // Set cookie
        setCookie: function(name, value, days = 7) {
            const expires = new Date();
            expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
            document.cookie = `${name}=${value};expires=${expires.toUTCString()};path=/`;
        }
    }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    FrontendCMS.init();
    FrontendCMS.trackPageView();
});

// Slideshow functionality (enhanced)
window.SlideshowManager = {
    instances: new Map(),
    
    init: function() {
        document.querySelectorAll('.slideshow-container').forEach((container, index) => {
            this.createSlideshow(container, index);
        });
    },
    
    createSlideshow: function(container, index) {
        const slides = container.querySelectorAll('.slide');
        const dots = container.querySelectorAll('.slideshow-dot');
        
        if (slides.length === 0) return;
        
        const slideshow = {
            container: container,
            slides: slides,
            dots: dots,
            currentSlide: 0,
            autoplaySpeed: parseInt(container.dataset.autoplaySpeed || '5') * 1000,
            autoplayTimer: null,
            isPlaying: true
        };
        
        this.instances.set(index, slideshow);
        this.setupSlideshow(slideshow);
        this.startAutoplay(slideshow);
    },
    
    setupSlideshow: function(slideshow) {
        // Add navigation arrows if not present
        if (!slideshow.container.querySelector('.slideshow-nav')) {
            const nav = document.createElement('div');
            nav.className = 'slideshow-nav';
            nav.innerHTML = `
                <button class="slideshow-prev" aria-label="Previous slide">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="slideshow-next" aria-label="Next slide">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
            slideshow.container.appendChild(nav);
        }
        
        // Setup navigation
        const prevBtn = slideshow.container.querySelector('.slideshow-prev');
        const nextBtn = slideshow.container.querySelector('.slideshow-next');
        
        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.prevSlide(slideshow));
        }
        
        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.nextSlide(slideshow));
        }
        
        // Setup dot navigation
        slideshow.dots.forEach((dot, index) => {
            dot.addEventListener('click', () => this.goToSlide(slideshow, index));
        });
        
        // Pause on hover
        slideshow.container.addEventListener('mouseenter', () => {
            this.pauseAutoplay(slideshow);
        });
        
        slideshow.container.addEventListener('mouseleave', () => {
            this.startAutoplay(slideshow);
        });
        
        // Touch/swipe support
        this.addTouchSupport(slideshow);
        
        // Show first slide
        this.showSlide(slideshow, 0);
    },
    
    showSlide: function(slideshow, index) {
        // Hide all slides
        slideshow.slides.forEach(slide => {
            slide.classList.remove('active');
        });
        
        // Remove active class from dots
        slideshow.dots.forEach(dot => {
            dot.classList.remove('active');
        });
        
        // Show current slide
        if (slideshow.slides[index]) {
            slideshow.slides[index].classList.add('active');
        }
        
        // Activate current dot
        if (slideshow.dots[index]) {
            slideshow.dots[index].classList.add('active');
        }
        
        slideshow.currentSlide = index;
    },
    
    nextSlide: function(slideshow) {
        const nextIndex = (slideshow.currentSlide + 1) % slideshow.slides.length;
        this.showSlide(slideshow, nextIndex);
    },
    
    prevSlide: function(slideshow) {
        const prevIndex = slideshow.currentSlide === 0 
            ? slideshow.slides.length - 1 
            : slideshow.currentSlide - 1;
        this.showSlide(slideshow, prevIndex);
    },
    
    goToSlide: function(slideshow, index) {
        this.showSlide(slideshow, index);
    },
    
    startAutoplay: function(slideshow) {
        if (slideshow.autoplayTimer) {
            clearInterval(slideshow.autoplayTimer);
        }
        
        if (slideshow.autoplaySpeed > 0 && slideshow.slides.length > 1) {
            slideshow.autoplayTimer = setInterval(() => {
                this.nextSlide(slideshow);
            }, slideshow.autoplaySpeed);
            slideshow.isPlaying = true;
        }
    },
    
    pauseAutoplay: function(slideshow) {
        if (slideshow.autoplayTimer) {
            clearInterval(slideshow.autoplayTimer);
            slideshow.autoplayTimer = null;
            slideshow.isPlaying = false;
        }
    },
    
    addTouchSupport: function(slideshow) {
        let startX = 0;
        let startY = 0;
        let endX = 0;
        let endY = 0;
        
        slideshow.container.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });
        
        slideshow.container.addEventListener('touchend', function(e) {
            endX = e.changedTouches[0].clientX;
            endY = e.changedTouches[0].clientY;
            
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            
            // Only handle horizontal swipes
            if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
                if (deltaX > 0) {
                    SlideshowManager.prevSlide(slideshow);
                } else {
                    SlideshowManager.nextSlide(slideshow);
                }
            }
        });
    }
};

// Menu functionality (enhanced)
window.MenuManager = {
    init: function() {
        this.initStickyMenu();
        this.initSmoothScroll();
    },
    
    initStickyMenu: function() {
        const menuModule = document.querySelector('.navigation-menu');
        if (!menuModule) return;
        
        const menuTop = menuModule.offsetTop;
        let isSticky = false;
        
        const handleScroll = FrontendCMS.utils.throttle(function() {
            if (window.pageYOffset > menuTop && !isSticky) {
                menuModule.classList.add('sticky');
                isSticky = true;
                // Add padding to body to prevent jump
                document.body.style.paddingTop = menuModule.offsetHeight + 'px';
            } else if (window.pageYOffset <= menuTop && isSticky) {
                menuModule.classList.remove('sticky');
                isSticky = false;
                document.body.style.paddingTop = '0';
            }
        }, 10);
        
        window.addEventListener('scroll', handleScroll);
    },
    
    initSmoothScroll: function() {
        document.querySelectorAll('.menu-items a[href^="#"]').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                
                if (targetElement) {
                    const headerOffset = 120; // Account for sticky menu height
                    const elementPosition = targetElement.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                    
                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });
                    
                    // Track navigation click
                    FrontendCMS.trackEvent('navigation_click', {
                        target: targetId,
                        link_text: this.textContent
                    });
                }
            });
        });
    }
};

// Analytics and tracking
window.AnalyticsManager = {
    init: function() {
        this.trackUserBehavior();
        this.trackPerformance();
    },
    
    trackUserBehavior: function() {
        // Track time on page
        let startTime = Date.now();
        
        window.addEventListener('beforeunload', function() {
            const timeOnPage = Date.now() - startTime;
            FrontendCMS.trackEvent('time_on_page', {
                duration: timeOnPage
            });
        });
        
        // Track scroll depth
        let maxScroll = 0;
        window.addEventListener('scroll', FrontendCMS.utils.throttle(function() {
            const scrollPercent = Math.round(
                (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100
            );
            
            if (scrollPercent > maxScroll) {
                maxScroll = scrollPercent;
            }
        }, 1000));
        
        window.addEventListener('beforeunload', function() {
            FrontendCMS.trackEvent('scroll_depth', {
                max_scroll_percent: maxScroll
            });
        });
    },
    
    trackPerformance: function() {
        // Track page load time
        window.addEventListener('load', function() {
            setTimeout(function() {
                const perfData = performance.timing;
                const loadTime = perfData.loadEventEnd - perfData.navigationStart;
                
                FrontendCMS.trackEvent('page_performance', {
                    load_time: loadTime,
                    dom_ready: perfData.domContentLoadedEventEnd - perfData.navigationStart
                });
            }, 0);
        });
    }
};

// Initialize additional managers when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    SlideshowManager.init();
    MenuManager.init();
    AnalyticsManager.init();
});

// Export for global access
window.Frontend = {
    CMS: FrontendCMS,
    Slideshow: SlideshowManager,
    Menu: MenuManager,
    Analytics: AnalyticsManager
};