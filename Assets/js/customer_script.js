// Index Page Specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Product card click handlers
    document.querySelectorAll('.product-card').forEach(card => {
        card.addEventListener('click', function() {
            const title = this.querySelector('.card-title').textContent;
            console.log('Product clicked:', title);
            // Add your product click logic here
            // window.location.href = `../Pages/products.php?category=${encodeURIComponent(title)}`;
        });
    });

    // Month card interactions
    document.querySelectorAll('.month-card').forEach(card => {
        card.addEventListener('click', function() {
            const month = this.querySelector('.month-name').textContent;
            console.log('Month selected:', month);
            // Add seasonal filtering logic here
        });
    });

    // Hero button animation
    const heroBtn = document.querySelector('.btn-hero');
    if (heroBtn) {
        heroBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px) scale(1.05)';
        });

        heroBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(-3px)';
        });

        heroBtn.addEventListener('click', function() {
            // Redirect to products page or show featured products
            window.location.href = '../Pages/products.php';
        });
    }

    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe elements for scroll animations
    document.querySelectorAll('.product-card, .month-card').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // Add loading animation
    window.addEventListener('load', function() {
        document.body.style.opacity = '0';
        document.body.style.transition = 'opacity 0.3s ease';

        setTimeout(() => {
            document.body.style.opacity = '1';
        }, 100);
    });

    // Seasonal highlights - update based on current month
    function highlightCurrentSeason() {
        const currentMonth = new Date().getMonth();
        const monthCards = document.querySelectorAll('.month-card');
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                           'July', 'August', 'September', 'October', 'November', 'December'];

        monthCards.forEach(card => {
            const cardMonth = card.querySelector('.month-name').textContent;
            if (cardMonth === monthNames[currentMonth]) {
                card.style.borderColor = 'var(--accent-gold)';
                card.style.boxShadow = '0 10px 30px rgba(212, 175, 55, 0.3)';

                // Add current season badge
                const badge = document.createElement('div');
                badge.className = 'position-absolute top-0 start-50 translate-middle px-3 py-1';
                badge.style.background = 'var(--accent-gold)';
                badge.style.color = 'var(--dark-green)';
                badge.style.borderRadius = '15px';
                badge.style.fontSize = '0.8rem';
                badge.style.fontWeight = '600';
                badge.style.zIndex = '10';
                badge.textContent = 'Current Season';
                card.style.position = 'relative';
                card.appendChild(badge);
            }
        });
    }

    // Call seasonal highlighting
    highlightCurrentSeason();
});

// Additional utility functions
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

// Window resize handler
window.addEventListener('resize', debounce(function() {
    // Handle responsive behaviors
    console.log('Window resized');
}, 250));
