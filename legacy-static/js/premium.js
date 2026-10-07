/* ========================================
   LSA PREMIUM - INTERACTIVE JAVASCRIPT
   Smooth animations, scroll effects, and interactions
   ======================================== */

// ========================================
// NAVIGATION SCROLL EFFECT
// ========================================
const nav = document.getElementById('mainNav');
let lastScroll = 0;

window.addEventListener('scroll', () => {
    const currentScroll = window.pageYOffset;
    
    // Add shadow when scrolled
    if (currentScroll > 50) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
    
    lastScroll = currentScroll;
});

// ========================================
// MOBILE MENU TOGGLE
// ========================================
const mobileToggle = document.querySelector('.mobile-toggle');
const navLinks = document.querySelector('.nav-links');

if (mobileToggle) {
    mobileToggle.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        mobileToggle.classList.toggle('active');
        
        // Animate hamburger icon
        const spans = mobileToggle.querySelectorAll('span');
        if (mobileToggle.classList.contains('active')) {
            spans[0].style.transform = 'rotate(45deg) translateY(10px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translateY(-10px)';
        } else {
            spans[0].style.transform = '';
            spans[1].style.opacity = '';
            spans[2].style.transform = '';
        }
    });
    
    // Close menu when clicking outside
    document.addEventListener('click', (e) => {
        if (!mobileToggle.contains(e.target) && !navLinks.contains(e.target)) {
            navLinks.classList.remove('active');
            mobileToggle.classList.remove('active');
            const spans = mobileToggle.querySelectorAll('span');
            spans[0].style.transform = '';
            spans[1].style.opacity = '';
            spans[2].style.transform = '';
        }
    });
    
    // Close menu when clicking on a link
    const navLinkItems = navLinks.querySelectorAll('.nav-link');
    navLinkItems.forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('active');
            mobileToggle.classList.remove('active');
            const spans = mobileToggle.querySelectorAll('span');
            spans[0].style.transform = '';
            spans[1].style.opacity = '';
            spans[2].style.transform = '';
        });
    });
}

// ========================================
// SMOOTH SCROLL FOR ANCHOR LINKS
// ========================================
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        
        if (href === '#' || href === '') return;
        
        const target = document.querySelector(href);
        
        if (target) {
            e.preventDefault();
            const navHeight = nav.offsetHeight;
            const targetPosition = target.offsetTop - navHeight - 20;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// ========================================
// SCROLL ANIMATION OBSERVER
// Animate elements when they come into view
// ========================================
const observerOptions = {
    root: null,
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const animateOnScroll = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animated');
        }
    });
}, observerOptions);

// Observe all elements with scroll-animate class
document.addEventListener('DOMContentLoaded', () => {
    const animatedElements = document.querySelectorAll('.fade-in-up, .fade-in, .fade-in-right');
    
    animatedElements.forEach(el => {
        // Add scroll-animate class for intersection observer
        el.classList.add('scroll-animate');
        animateOnScroll.observe(el);
    });
});

// ========================================
// FLOATING CARDS INTERACTIVE HOVER
// Enhanced hover effects for hero cards
// ========================================
const floatingCards = document.querySelectorAll('.floating-card');

floatingCards.forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px) scale(1.05)';
        this.style.transition = 'all 0.3s ease';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = '';
    });
});

// ========================================
// FEATURE CARDS TILT EFFECT
// Subtle 3D tilt on mouse move
// ========================================
const featureCards = document.querySelectorAll('.feature-card');

featureCards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
        const rect = card.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        const rotateX = (y - centerY) / 20;
        const rotateY = (centerX - x) / 20;
        
        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-8px)`;
    });
    
    card.addEventListener('mouseleave', () => {
        card.style.transform = '';
    });
});

// ========================================
// ANALYSIS CARDS HOVER EFFECT
// Enhanced interaction for analysis cards
// ========================================
const analysisCards = document.querySelectorAll('.analysis-card');

analysisCards.forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    });
});

// ========================================
// BUTTON RIPPLE EFFECT
// Material-style ripple on button click
// ========================================
const buttons = document.querySelectorAll('.btn-premium');

buttons.forEach(button => {
    button.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';
        ripple.classList.add('ripple');
        
        this.appendChild(ripple);
        
        setTimeout(() => {
            ripple.remove();
        }, 600);
    });
});

// Add ripple CSS dynamically
const style = document.createElement('style');
style.textContent = `
    .btn-premium {
        position: relative;
        overflow: hidden;
    }
    
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// ========================================
// PARALLAX EFFECT FOR GRADIENT ORBS
// Subtle parallax scrolling for hero orbs
// ========================================
const orbs = document.querySelectorAll('.gradient-orb');

window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    const parallaxSpeed = 0.5;
    
    orbs.forEach((orb, index) => {
        const speed = parallaxSpeed * (index + 1) * 0.3;
        orb.style.transform = `translateY(${scrolled * speed}px)`;
    });
});

// ========================================
// NUMBER COUNTER ANIMATION
// Animate numbers counting up when visible
// ========================================
const animateCounter = (element, target, duration = 2000) => {
    let start = 0;
    const increment = target / (duration / 16);
    
    const timer = setInterval(() => {
        start += increment;
        if (start >= target) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(start);
        }
    }, 16);
};

// Observe stat numbers
const statObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
            entry.target.classList.add('counted');
            const text = entry.target.textContent;
            const number = parseInt(text.replace(/\D/g, ''));
            
            if (!isNaN(number) && number > 0) {
                entry.target.textContent = '0';
                animateCounter(entry.target, number);
                
                // Add back any suffix
                if (text.includes('%')) {
                    setTimeout(() => {
                        entry.target.textContent = entry.target.textContent + '%';
                    }, 2000);
                } else if (text.includes('+')) {
                    setTimeout(() => {
                        entry.target.textContent = entry.target.textContent + '+';
                    }, 2000);
                }
            }
        }
    });
}, { threshold: 0.5 });

// Observe stat numbers when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const statNumbers = document.querySelectorAll('.stat-number, .big-stat .number, .mini-stat-box .value');
    statNumbers.forEach(stat => statObserver.observe(stat));
});

// ========================================
// MINI CHART ANIMATION
// Animate bars growing when visible
// ========================================
const chartObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const bars = entry.target.querySelectorAll('.bar');
            bars.forEach((bar, index) => {
                setTimeout(() => {
                    const height = bar.style.height;
                    bar.style.height = '0';
                    setTimeout(() => {
                        bar.style.transition = 'height 0.8s ease-out';
                        bar.style.height = height;
                    }, 50);
                }, index * 100);
            });
            chartObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

document.addEventListener('DOMContentLoaded', () => {
    const charts = document.querySelectorAll('.mini-chart');
    charts.forEach(chart => chartObserver.observe(chart));
});

// ========================================
// PROGRESSIVE ENHANCEMENT
// Check for reduced motion preference
// ========================================
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (prefersReducedMotion) {
    // Disable animations for users who prefer reduced motion
    document.querySelectorAll('.fade-in-up, .fade-in, .fade-in-right').forEach(el => {
        el.style.animation = 'none';
        el.style.opacity = '1';
        el.style.transform = 'none';
    });
    
    // Disable parallax
    orbs.forEach(orb => {
        orb.style.transform = 'none';
    });
}

// ========================================
// PERFORMANCE OPTIMIZATION
// Debounce scroll events
// ========================================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Optimize scroll listeners
const optimizedScrollHandler = debounce(() => {
    // Your scroll handling code here
}, 10);

// ========================================
// PAGE LOAD ANIMATION
// Fade in page content smoothly
// ========================================
window.addEventListener('load', () => {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease-in';
    
    requestAnimationFrame(() => {
        document.body.style.opacity = '1';
    });
});

// ========================================
// KEYBOARD ACCESSIBILITY
// Enhance keyboard navigation
// ========================================
document.addEventListener('keydown', (e) => {
    // Close mobile menu with Escape key
    if (e.key === 'Escape' && navLinks.classList.contains('active')) {
        navLinks.classList.remove('active');
        mobileToggle.classList.remove('active');
        const spans = mobileToggle.querySelectorAll('span');
        spans.forEach(span => span.style.transform = '');
    }
    
    // Focus trap for mobile menu
    if (navLinks.classList.contains('active') && e.key === 'Tab') {
        const focusableElements = navLinks.querySelectorAll('a, button');
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.shiftKey && document.activeElement === firstElement) {
            e.preventDefault();
            lastElement.focus();
        } else if (!e.shiftKey && document.activeElement === lastElement) {
            e.preventDefault();
            firstElement.focus();
        }
    }
});

// ========================================
// FAQ ACCORDION
// ========================================
const faqItems = document.querySelectorAll('.faq-item');

faqItems.forEach(item => {
    const question = item.querySelector('.faq-question');
    
    question.addEventListener('click', () => {
        // Close other items
        faqItems.forEach(otherItem => {
            if (otherItem !== item && otherItem.classList.contains('active')) {
                otherItem.classList.remove('active');
            }
        });
        
        // Toggle current item
        item.classList.toggle('active');
    });
});

// ========================================
// CONSOLE BRANDING
// ========================================
console.log(
    '%cLSA - Legit Source Analysis',
    'font-size: 24px; font-weight: bold; background: linear-gradient(135deg, #6366f1, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent;'
);
console.log(
    '%cPremium Football Analysis Platform',
    'font-size: 14px; color: #64748b; font-weight: 500;'
);
console.log('%c✓ Premium UI Loaded Successfully', 'color: #10b981; font-weight: 600;');

// ========================================
// ERROR HANDLING
// Graceful error handling for production
// ========================================
window.addEventListener('error', (e) => {
    console.error('An error occurred:', e.error);
    // You can add error reporting service here
});

// ========================================
// ANALYTICS TRACKING (Placeholder)
// Add your analytics code here
// ========================================
/*
// Example: Google Analytics
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'YOUR-GA-ID');

// Track button clicks
buttons.forEach(button => {
    button.addEventListener('click', () => {
        gtag('event', 'button_click', {
            'event_category': 'engagement',
            'event_label': button.textContent.trim()
        });
    });
});
*/