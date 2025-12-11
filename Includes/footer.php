<!-- Footer -->
<footer>
    <div class="container">
        <!-- Main Footer Content -->
        <div class="footer-grid">
            <!-- Brand Section -->
            <div class="footer-section">
                <div class="footer-brand">
                    <img src="https://static.readdy.ai/image/4ca41f25234899b8f8c841da212115f9/0edf46ff3dcee02b8c0543247a6a8d9c.png" alt="Farm Fresh Market">
                    <h3>Farm Fresh Market</h3>
                </div>
                <p class="footer-description">
                    Connecting communities with fresh, local, and sustainable farm products since 2010. 
                    We bring the farm to your table.
                </p>
                
                <!-- Newsletter Subscription -->
                <div class="newsletter-section">
                    <h4>Stay Fresh with Our Newsletter</h4>
                    <div class="newsletter-form">
                        <input type="email" placeholder="Your email address" class="newsletter-input">
                        <button type="submit" class="newsletter-btn">
                            <i class="ri-send-plane-line"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Social Links -->
                <div class="social-section">
                    <h4>Follow Us</h4>
                    <div class="social-links">
                        <a href="#" class="social-link facebook">
                            <i class="ri-facebook-fill"></i>
                        </a>
                        <a href="#" class="social-link twitter">
                            <i class="ri-twitter-fill"></i>
                        </a>
                        <a href="#" class="social-link instagram">
                            <i class="ri-instagram-line"></i>
                        </a>
                        <a href="#" class="social-link youtube">
                            <i class="ri-youtube-fill"></i>
                        </a>
                        <a href="#" class="social-link tiktok">
                            <i class="ri-tiktok-fill"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-section">
                <h4>Shop Categories</h4>
                <a href="#" class="footer-link">
                    <i class="ri-fruit-bowl-line"></i>
                    Seasonal Fruits
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-leaf-line"></i>
                    Organic Vegetables
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-egg-line"></i>
                    Farm Fresh Eggs
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-cup-line"></i>
                    Dairy Products
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-heart-pulse-line"></i>
                    Herbs & Spices
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-fish-line"></i>
                    Fresh Fish
                </a>
            </div>

            <!-- Livestock Section -->
            <div class="footer-section">
                <h4>Livestock & Meat</h4>
                <a href="#" class="footer-link">
                    <i class="ri-cow-line"></i>
                    Premium Beef
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-chicken-line"></i>
                    Free-Range Chicken
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-pig-money-line"></i>
                    Farm-Raised Pork
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-goat-line"></i>
                    Goat Meat
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-turkey-line"></i>
                    Turkey
                </a>
                <a href="#" class="footer-link">
                    <i class="ri-rabbit-line"></i>
                    Rabbit Meat
                </a>
            </div>

            <!-- Contact & Info -->
            <div class="footer-section">
                <h4>Contact & Info</h4>
                <div class="contact-info">
                    <a href="#" class="contact-item">
                        <i class="ri-map-pin-2-fill"></i>
                        <div>
                            <strong>Farm Location</strong>
                            <span>123 Farm Road, Green Valley, CA 95066</span>
                        </div>
                    </a>
                    <a href="tel:555123FARM" class="contact-item">
                        <i class="ri-phone-fill"></i>
                        <div>
                            <strong>Call Us</strong>
                            <span>(555) 123-FARM</span>
                        </div>
                    </a>
                    <a href="mailto:info@farmfreshmarket.com" class="contact-item">
                        <i class="ri-mail-fill"></i>
                        <div>
                            <strong>Email Us</strong>
                            <span>info@farmfreshmarket.com</span>
                        </div>
                    </a>
                    <div class="contact-item">
                        <i class="ri-time-fill"></i>
                        <div>
                            <strong>Store Hours</strong>
                            <span>Mon-Sat: 6AM-8PM<br>Sunday: 8AM-6PM</span>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="copyright">
                    © 2024 Farm Fresh Market. All rights reserved.
                </div>
                
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Cookie Policy</a>
                </div>
                
                <div class="app-badges">
                    <a href="#" class="app-badge">
                        <i class="ri-google-play-fill"></i>
                        <div>
                            <span>GET IT ON</span>
                            <strong>Google Play</strong>
                        </div>
                    </a>
                    <a href="#" class="app-badge">
                        <i class="ri-apple-fill"></i>
                        <div>
                            <span>Download on the</span>
                            <strong>App Store</strong>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Back to Top Button -->
        <button class="back-to-top" id="backToTop">
            <i class="ri-arrow-up-line"></i>
        </button>
    </div>
</footer>

<style>
/* Footer Styling */
footer {
    background: linear-gradient(180deg, #0F2E15 0%, #153020 100%);
    color: white;
    padding: 5rem 1rem 2rem;
    position: relative;
    overflow: hidden;
}

footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(218, 226, 203, 0.3), transparent);
}

/* Footer Grid */
.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 3rem;
    margin-bottom: 3rem;
}

/* Footer Sections */
.footer-section h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: #DAE2CB;
    margin-bottom: 1.5rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid rgba(218, 226, 203, 0.2);
    position: relative;
}

.footer-section h4::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 40px;
    height: 2px;
    background: #DAE2CB;
}

/* Brand Section */
.footer-brand {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.footer-brand img {
    height: 2.5rem;
    width: auto;
    filter: brightness(0) invert(1);
}

.footer-brand h3 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #DAE2CB;
    margin: 0;
}

.footer-description {
    color: rgba(218, 226, 203, 0.8);
    font-size: 0.95rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}

/* Newsletter */
.newsletter-section {
    background: rgba(218, 226, 203, 0.1);
    padding: 1.5rem;
    border-radius: 12px;
    margin-bottom: 2rem;
    border: 1px solid rgba(218, 226, 203, 0.2);
}

.newsletter-section h4 {
    font-size: 1rem;
    color: #DAE2CB;
    margin-bottom: 1rem;
    border: none;
    padding: 0;
}

.newsletter-section h4::after {
    display: none;
}

.newsletter-form {
    display: flex;
    gap: 0.5rem;
}

.newsletter-input {
    flex: 1;
    padding: 0.75rem 1rem;
    border: 1px solid rgba(218, 226, 203, 0.3);
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    color: #DAE2CB;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.newsletter-input:focus {
    outline: none;
    border-color: #DAE2CB;
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 0 0 3px rgba(218, 226, 203, 0.1);
}

.newsletter-input::placeholder {
    color: rgba(218, 226, 203, 0.5);
}

.newsletter-btn {
    background: linear-gradient(135deg, #DAE2CB, #c0d0af);
    color: #0F2E15;
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 600;
}

.newsletter-btn:hover {
    background: linear-gradient(135deg, #c0d0af, #DAE2CB);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(218, 226, 203, 0.2);
}

/* Social Links */
.social-section h4 {
    font-size: 1rem;
    margin-bottom: 1rem;
    border: none;
    padding: 0;
}

.social-section h4::after {
    display: none;
}

.social-links {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(218, 226, 203, 0.1);
    color: #DAE2CB;
    transition: all 0.3s ease;
    font-size: 1.25rem;
}

.social-link:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.social-link.facebook:hover {
    background: #1877F2;
    color: white;
}

.social-link.twitter:hover {
    background: #1DA1F2;
    color: white;
}

.social-link.instagram:hover {
    background: linear-gradient(45deg, #405DE6, #5851DB, #833AB4, #C13584, #E1306C, #FD1D1D);
    color: white;
}

.social-link.youtube:hover {
    background: #FF0000;
    color: white;
}

.social-link.tiktok:hover {
    background: #000000;
    color: white;
}

/* Footer Links */
.footer-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: rgba(218, 226, 203, 0.8);
    text-decoration: none;
    padding: 0.5rem 0;
    transition: all 0.3s ease;
    border-radius: 6px;
    padding-left: 0.5rem;
}

.footer-link:hover {
    color: #DAE2CB;
    background: rgba(218, 226, 203, 0.1);
    padding-left: 1rem;
    transform: translateX(5px);
}

.footer-link i {
    font-size: 1.25rem;
    width: 24px;
    color: #DAE2CB;
}

/* Contact Info */
.contact-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.75rem;
    background: rgba(218, 226, 203, 0.05);
    border-radius: 10px;
    color: rgba(218, 226, 203, 0.9);
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.contact-item:hover {
    background: rgba(218, 226, 203, 0.1);
    border-color: rgba(218, 226, 203, 0.2);
    transform: translateY(-2px);
}

.contact-item i {
    font-size: 1.5rem;
    color: #DAE2CB;
    margin-top: 0.25rem;
}

.contact-item div {
    flex: 1;
}

.contact-item strong {
    display: block;
    font-size: 0.9rem;
    color: #DAE2CB;
    margin-bottom: 0.25rem;
}

.contact-item span {
    font-size: 0.85rem;
    color: rgba(218, 226, 203, 0.7);
    line-height: 1.4;
}

/* Payment Methods */
.payment-methods {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(218, 226, 203, 0.2);
}

.payment-methods h4 {
    font-size: 1rem;
    margin-bottom: 1rem;
}

.payment-icons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.payment-icons i {
    font-size: 2rem;
    color: rgba(218, 226, 203, 0.7);
    transition: all 0.3s ease;
}

.payment-icons i:hover {
    color: #DAE2CB;
    transform: translateY(-2px);
}

/* Footer Bottom */
.footer-bottom {
    padding-top: 2rem;
    border-top: 1px solid rgba(218, 226, 203, 0.2);
    margin-top: 2rem;
}

.footer-bottom-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    align-items: center;
}

@media (min-width: 768px) {
    .footer-bottom-content {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}

.copyright {
    color: rgba(218, 226, 203, 0.7);
    font-size: 0.9rem;
    text-align: center;
}

.footer-links {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

.footer-links a {
    color: rgba(218, 226, 203, 0.7);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    position: relative;
    padding: 0.25rem 0;
}

.footer-links a:hover {
    color: #DAE2CB;
}

.footer-links a::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 1px;
    background: #DAE2CB;
    transition: width 0.3s ease;
}

.footer-links a:hover::after {
    width: 100%;
}

/* App Badges */
.app-badges {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.app-badge {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1.25rem;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(218, 226, 203, 0.2);
    border-radius: 10px;
    color: #DAE2CB;
    text-decoration: none;
    transition: all 0.3s ease;
    min-width: 160px;
}

.app-badge:hover {
    background: rgba(255, 255, 255, 0.15);
    border-color: rgba(218, 226, 203, 0.3);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.app-badge i {
    font-size: 2rem;
}

.app-badge div {
    display: flex;
    flex-direction: column;
}

.app-badge span {
    font-size: 0.7rem;
    opacity: 0.8;
}

.app-badge strong {
    font-size: 1rem;
    font-weight: 600;
}

/* Back to Top Button */
.back-to-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #DAE2CB, #c0d0af);
    color: #0F2E15;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    transition: all 0.3s ease;
    opacity: 0;
    visibility: hidden;
    z-index: 1000;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
}

.back-to-top:hover {
    background: linear-gradient(135deg, #c0d0af, #DAE2CB);
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
    footer {
        padding: 3rem 1rem 1.5rem;
    }
    
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .footer-section h4 {
        font-size: 1.125rem;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
    
    .app-badges {
        flex-direction: column;
        align-items: center;
    }
    
    .app-badge {
        width: 100%;
        max-width: 250px;
        justify-content: center;
    }
    
    .footer-links {
        gap: 1rem;
    }
    
    .back-to-top {
        width: 45px;
        height: 45px;
        bottom: 1rem;
        right: 1rem;
    }
}

/* Animation for footer elements */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.footer-section {
    animation: fadeInUp 0.6s ease forwards;
    opacity: 0;
}

.footer-section:nth-child(1) { animation-delay: 0.1s; }
.footer-section:nth-child(2) { animation-delay: 0.2s; }
.footer-section:nth-child(3) { animation-delay: 0.3s; }
.footer-section:nth-child(4) { animation-delay: 0.4s; }

/* Hover effects for icons */
.footer-link i,
.contact-item i,
.payment-icons i {
    transition: transform 0.3s ease;
}

.footer-link:hover i {
    transform: scale(1.1) rotate(5deg);
}

.contact-item:hover i {
    transform: scale(1.1);
}
</style>

<script>
// Back to Top functionality
document.addEventListener('DOMContentLoaded', function() {
    const backToTopButton = document.getElementById('backToTop');
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.scrollY > 500) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    });
    
    // Scroll to top when clicked
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Newsletter form submission
    const newsletterForm = document.querySelector('.newsletter-form');
    const newsletterInput = document.querySelector('.newsletter-input');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = newsletterInput.value.trim();
            
            if (email && validateEmail(email)) {
                // Here you would typically send the email to your server
                newsletterInput.value = '';
                alert('Thank you for subscribing to our newsletter!');
            } else {
                alert('Please enter a valid email address.');
            }
        });
    }
    
    // Email validation function
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    // Add hover effect to all footer links
    const footerLinks = document.querySelectorAll('.footer-link, .social-link, .contact-item');
    footerLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });
});
</script>