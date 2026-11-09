// Event Registration System - Frontend JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize components
    initializeNavigation();
    initializeEventCards();
    initializeRegistrationForm();
    initializeOTPForm();
    initializeGallery();
    initializeYouTubeTracking();
    trackPageView();
});

// Navigation functionality
function initializeNavigation() {
    const navbar = document.querySelector('.navbar');
    
    if (navbar) {
        // Add scroll effect to navbar
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
}

// Event cards animations and interactions
function initializeEventCards() {
    const eventCards = document.querySelectorAll('.event-card');
    
    eventCards.forEach(card => {
        // Add hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
        
        // Add click analytics
        card.addEventListener('click', function(e) {
            if (!e.target.closest('.btn')) {
                const eventId = this.dataset.eventId;
                if (eventId) {
                    trackEvent('event_card_click', {
                        event_id: eventId,
                        timestamp: new Date().toISOString()
                    });
                }
            }
        });
    });
    
    // Animate cards on scroll
    if ('IntersectionObserver' in window) {
        const cardObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('fade-in');
                }
            });
        }, {
            threshold: 0.1
        });
        
        eventCards.forEach(card => {
            cardObserver.observe(card);
        });
    }
}

// Registration form handling
function initializeRegistrationForm() {
    const registrationForm = document.getElementById('registrationForm');
    
    if (registrationForm) {
        registrationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form
            if (!validateRegistrationForm()) {
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Processing...';
            submitBtn.disabled = true;
            
            // Submit form data
            const formData = new FormData(this);
            
            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.payment_required) {
                        // Initialize Razorpay payment
                        initializePayment(data.payment_data);
                    } else {
                        // Show OTP form
                        showOTPForm(data.message);
                    }
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
    }
}

// Form validation
function validateRegistrationForm() {
    const form = document.getElementById('registrationForm');
    const name = form.querySelector('[name="name"]');
    const email = form.querySelector('[name="email"]');
    const phone = form.querySelector('[name="phone"]');
    
    let isValid = true;
    
    // Reset previous errors
    clearFormErrors();
    
    // Validate name
    if (!name.value.trim()) {
        showFieldError(name, 'Name is required');
        isValid = false;
    }
    
    // Validate email
    if (!email.value.trim()) {
        showFieldError(email, 'Email is required');
        isValid = false;
    } else if (!isValidEmail(email.value)) {
        showFieldError(email, 'Please enter a valid email');
        isValid = false;
    }
    
    // Validate phone
    if (!phone.value.trim()) {
        showFieldError(phone, 'Phone number is required');
        isValid = false;
    } else if (!isValidPhone(phone.value)) {
        showFieldError(phone, 'Please enter a valid phone number');
        isValid = false;
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    let errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        field.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function clearFormErrors() {
    const invalidFields = document.querySelectorAll('.is-invalid');
    const errorMessages = document.querySelectorAll('.invalid-feedback');
    
    invalidFields.forEach(field => field.classList.remove('is-invalid'));
    errorMessages.forEach(message => message.remove());
}

function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function isValidPhone(phone) {
    const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
    return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
}

// OTP form handling
function initializeOTPForm() {
    const otpForm = document.getElementById('otpForm');
    
    if (otpForm) {
        const otpInput = otpForm.querySelector('input[name="otp"]');
        
        // Auto-focus OTP input
        otpInput.focus();
        
        // Handle OTP input
        otpInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').substring(0, 6);
            
            if (this.value.length === 6) {
                // Auto-submit when 6 digits entered
                setTimeout(() => {
                    otpForm.dispatchEvent(new Event('submit'));
                }, 500);
            }
        });
        
        otpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const otp = otpInput.value;
            if (otp.length !== 6) {
                showAlert('Please enter a valid 6-digit OTP', 'danger');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner"></span> Verifying...';
            submitBtn.disabled = true;
            
            // Submit OTP
            const formData = new FormData(this);
            
            fetch('verify_otp.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(() => {
                        window.location.href = data.redirect_url || 'success.php';
                    }, 2000);
                } else {
                    showAlert(data.message, 'danger');
                    otpInput.value = '';
                    otpInput.focus();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('An error occurred. Please try again.', 'danger');
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        });
        
        // Resend OTP functionality
        const resendBtn = document.getElementById('resendOTP');
        if (resendBtn) {
            let countdown = 60;
            const timer = setInterval(() => {
                countdown--;
                resendBtn.textContent = `Resend OTP (${countdown}s)`;
                
                if (countdown <= 0) {
                    clearInterval(timer);
                    resendBtn.textContent = 'Resend OTP';
                    resendBtn.disabled = false;
                }
            }, 1000);
            
            resendBtn.addEventListener('click', function() {
                // Implement resend OTP logic
                fetch('register.php', {
                    method: 'POST',
                    body: new FormData(document.getElementById('registrationForm'))
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('OTP sent successfully', 'success');
                        // Restart countdown
                        countdown = 60;
                        this.disabled = true;
                    }
                });
            });
        }
    }
}

// Razorpay payment integration
function initializePayment(paymentData) {
    const options = {
        key: paymentData.key_id,
        amount: paymentData.amount,
        currency: paymentData.currency,
        name: paymentData.name,
        description: paymentData.description,
        order_id: paymentData.order_id,
        handler: function(response) {
            // Payment success
            const formData = new FormData();
            formData.append('razorpay_payment_id', response.razorpay_payment_id);
            formData.append('razorpay_order_id', response.razorpay_order_id);
            formData.append('razorpay_signature', response.razorpay_signature);
            
            fetch('register.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showOTPForm('Payment successful! Please verify your OTP.');
                } else {
                    showAlert('Payment verification failed', 'danger');
                }
            });
        },
        prefill: {
            name: paymentData.prefill.name,
            email: paymentData.prefill.email,
            contact: paymentData.prefill.contact
        },
        theme: {
            color: '#007bff'
        }
    };
    
    const rzp = new Razorpay(options);
    rzp.open();
    
    rzp.on('payment.failed', function(response) {
        showAlert('Payment failed: ' + response.error.description, 'danger');
    });
}

// Gallery functionality
function initializeGallery() {
    const galleryItems = document.querySelectorAll('.gallery-item');
    
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            const mediaType = this.dataset.mediaType;
            const mediaSrc = this.dataset.mediaSrc;
            
            if (mediaType === 'photo') {
                openLightbox(mediaSrc, 'image');
            } else if (mediaType === 'video') {
                openLightbox(mediaSrc, 'video');
            }
        });
    });
}

function openLightbox(src, type) {
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            ${type === 'image' 
                ? `<img src="${src}" alt="Gallery Image">` 
                : `<video src="${src}" controls autoplay>`
            }
        </div>
    `;
    
    document.body.appendChild(lightbox);
    
    // Close lightbox
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox || e.target.className === 'lightbox-close') {
            document.body.removeChild(lightbox);
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (document.body.contains(lightbox)) {
                document.body.removeChild(lightbox);
            }
        }
    });
}

// YouTube tracking
function initializeYouTubeTracking() {
    // Load YouTube IFrame API
    if (document.querySelector('.youtube-container iframe')) {
        const tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        const firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    }
}

// YouTube API callback
function onYouTubeIframeAPIReady() {
    const iframes = document.querySelectorAll('.youtube-container iframe');
    
    iframes.forEach((iframe, index) => {
        const videoId = iframe.src.match(/embed\/([^?]+)/)?.[1];
        if (videoId) {
            new YT.Player(iframe, {
                events: {
                    onStateChange: function(event) {
                        trackYouTubeInteraction(videoId, event.data);
                    }
                }
            });
        }
    });
}

function trackYouTubeInteraction(videoId, state) {
    let action = '';
    
    switch (state) {
        case YT.PlayerState.PLAYING:
            action = 'play';
            break;
        case YT.PlayerState.PAUSED:
            action = 'pause';
            break;
        case YT.PlayerState.ENDED:
            action = 'complete';
            break;
        default:
            return;
    }
    
    // Send tracking data
    fetch('includes/track_youtube.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            video_id: videoId,
            action: action,
            event_id: document.body.dataset.eventId
        })
    });
}

// Page view tracking
function trackPageView() {
    const eventId = document.body.dataset.eventId;
    const pageName = document.body.dataset.pageName || window.location.pathname;
    
    // Get UTM parameters
    const urlParams = new URLSearchParams(window.location.search);
    const utmData = {
        utm_source: urlParams.get('utm_source'),
        utm_medium: urlParams.get('utm_medium'),
        utm_campaign: urlParams.get('utm_campaign')
    };
    
    fetch('includes/track_view.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            event_id: eventId,
            page_name: pageName,
            utm_data: utmData
        })
    });
}

// Generic event tracking
function trackEvent(eventName, data) {
    fetch('includes/track_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            event_name: eventName,
            data: data
        })
    });
}

// Alert system
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        max-width: 400px;
    `;
    document.body.appendChild(container);
    return container;
}

// Show OTP form
function showOTPForm(message) {
    const registrationSection = document.querySelector('.registration-section');
    if (registrationSection) {
        registrationSection.innerHTML = `
            <div class="otp-section">
                <h3>Verify OTP</h3>
                <p>${message}</p>
                <form id="otpForm" method="POST">
                    <div class="mb-3">
                        <input type="text" name="otp" class="form-control otp-input" 
                               placeholder="Enter 6-digit OTP" maxlength="6" required>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success">Verify OTP</button>
                        <button type="button" id="resendOTP" class="btn btn-outline-primary" disabled>
                            Resend OTP (60s)
                        </button>
                    </div>
                </form>
            </div>
        `;
        
        // Re-initialize OTP form
        initializeOTPForm();
    }
}

// Utility functions
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR'
    }).format(amount);
}

function formatDate(dateString) {
    return new Intl.DateTimeFormat('en-IN', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }).format(new Date(dateString));
}

function formatTime(timeString) {
    return new Intl.DateTimeFormat('en-IN', {
        hour: 'numeric',
        minute: '2-digit',
        hour12: true
    }).format(new Date('2000-01-01 ' + timeString));
}

// Export functions for external use
window.EventRegistration = {
    showAlert,
    trackEvent,
    formatCurrency,
    formatDate,
    formatTime
};
