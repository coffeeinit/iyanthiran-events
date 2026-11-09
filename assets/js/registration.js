// Event Registration JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const registrationForm = document.getElementById('registrationForm');
    const otpModal = document.getElementById('otpModal');
    let currentEventData = null;
    
    if (registrationForm) {
        registrationForm.addEventListener('submit', handleRegistration);
    }
    
    // Handle registration form submission
    async function handleRegistration(e) {
        e.preventDefault();
        
        const formData = new FormData(registrationForm);
        const submitBtn = registrationForm.querySelector('button[type="submit"]');
        let data = null; // <-- Add this line
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        
        try {
            const response = await fetch('register.php', {
                method: 'POST',
                body: formData
            });
            
            data = await response.json();
            
            if (data.success) {
                if (data.payment_required) {
                    // Handle payment for paid events
                    handlePayment(data.payment_data);
                } else if (data.show_otp) {
                    // Show OTP modal for free events
                    showOTPModal(formData.get('email'), formData.get('event_id'));
                } else {
                    showSuccessMessage(data.message);
                }
            } else {
                showErrorMessage(data.message);
            }
        } catch (error) {
            console.error('Registration error:', error);
            showErrorMessage('An error occurred. Please try again.');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = data && data.payment_required ? 
                '<i class="fas fa-user-plus me-2"></i>Register & Pay' : 
                '<i class="fas fa-user-plus me-2"></i>Register Now';
        }
    }
    
    // Handle Razorpay payment
    function handlePayment(paymentData) {
        if (typeof Razorpay === 'undefined') {
            showErrorMessage('Payment gateway not loaded. Please refresh and try again.');
            return;
        }
        
        const options = {
            key: paymentData.key_id,
            amount: paymentData.amount,
            currency: paymentData.currency,
            order_id: paymentData.order_id,
            name: paymentData.name,
            description: paymentData.description,
            prefill: paymentData.prefill,
            theme: {
                color: '#007bff'
            },
            handler: function(response) {
                // Payment successful
                verifyPayment(response);
            },
            modal: {
                ondismiss: function() {
                    showErrorMessage('Payment cancelled. Please try again.');
                }
            }
        };
        
        const rzp = new Razorpay(options);
        rzp.open();
    }
    
    // Verify payment with server
    async function verifyPayment(paymentResponse) {
        try {
            const formData = new FormData();
            formData.append('razorpay_payment_id', paymentResponse.razorpay_payment_id);
            formData.append('razorpay_order_id', paymentResponse.razorpay_order_id);
            formData.append('razorpay_signature', paymentResponse.razorpay_signature);
            
            const response = await fetch('register.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (data.show_otp) {
                    // Get email from original form
                    const email = registrationForm.querySelector('[name="email"]').value;
                    const eventId = registrationForm.querySelector('[name="event_id"]').value;
                    showOTPModal(email, eventId);
                } else {
                    showSuccessMessage(data.message);
                }
            } else {
                showErrorMessage(data.message);
            }
        } catch (error) {
            console.error('Payment verification error:', error);
            showErrorMessage('Payment verification failed. Please contact support.');
        }
    }
    
    // Show OTP verification modal
    function showOTPModal(email, eventId) {
        // Create OTP modal if it doesn't exist
        if (!document.getElementById('otpModal')) {
            createOTPModal();
        }
        
        const modal = document.getElementById('otpModal');
        const emailSpan = modal.querySelector('#otpEmail');
        const eventIdInput = modal.querySelector('#otpEventId');
        const emailInput = modal.querySelector('#otpEmailInput');
        
        emailSpan.textContent = email;
        eventIdInput.value = eventId;
        emailInput.value = email;
        
        // Show modal
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        
        // Focus on OTP input
        setTimeout(() => {
            modal.querySelector('#otpCode').focus();
        }, 500);
    }
    
    // Create OTP modal dynamically
    function createOTPModal() {
        const modalHtml = `
            <div class="modal fade" id="otpModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-shield-alt me-2"></i>
                                Email Verification
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                                <h6>Verification Code Sent</h6>
                                <p class="text-muted">We've sent a 6-digit code to <strong><span id="otpEmail"></span></strong></p>
                            </div>
                            
                            <form id="otpForm">
                                <input type="hidden" id="otpEventId" name="event_id">
                                <input type="hidden" id="otpEmailInput" name="email">
                                
                                <div class="mb-3">
                                    <label for="otpCode" class="form-label">Enter Verification Code</label>
                                    <input type="text" class="form-control form-control-lg text-center" 
                                           id="otpCode" name="otp" maxlength="6" pattern="[0-9]{6}" 
                                           placeholder="000000" required>
                                    <div class="form-text">Enter the 6-digit code from your email</div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-check me-2"></i>
                                        Verify & Complete Registration
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="resendOTP">
                                        <i class="fas fa-redo me-2"></i>
                                        Resend Code
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Add event listeners
        const otpForm = document.getElementById('otpForm');
        const resendBtn = document.getElementById('resendOTP');
        
        otpForm.addEventListener('submit', handleOTPVerification);
        resendBtn.addEventListener('click', handleResendOTP);
        
        // Format OTP input
        const otpInput = document.getElementById('otpCode');
        otpInput.addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
    
    // Handle OTP verification
    async function handleOTPVerification(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
        
        try {
            const response = await fetch('verify_otp.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Close modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('otpModal'));
                modal.hide();
                
                // Redirect to success page or show success message
                if (data.redirect_url) {
                    window.location.href = data.redirect_url;
                } else {
                    showSuccessMessage(data.message);
                }
            } else {
                showErrorMessage(data.message);
            }
        } catch (error) {
            console.error('OTP verification error:', error);
            showErrorMessage('Verification failed. Please try again.');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-check me-2"></i>Verify & Complete Registration';
        }
    }
    
    // Handle OTP resend
    async function handleResendOTP() {
        const modal = document.getElementById('otpModal');
        const email = modal.querySelector('#otpEmailInput').value;
        const eventId = modal.querySelector('#otpEventId').value;
        const resendBtn = document.getElementById('resendOTP');
        
        // Disable resend button temporarily
        resendBtn.disabled = true;
        resendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'resend_otp');
            formData.append('email', email);
            formData.append('event_id', eventId);
            
            const response = await fetch('resend_otp.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showSuccessMessage('New verification code sent to your email.');
                
                // Start countdown
                let countdown = 60;
                const interval = setInterval(() => {
                    resendBtn.innerHTML = `<i class="fas fa-clock me-2"></i>Resend in ${countdown}s`;
                    countdown--;
                    
                    if (countdown < 0) {
                        clearInterval(interval);
                        resendBtn.disabled = false;
                        resendBtn.innerHTML = '<i class="fas fa-redo me-2"></i>Resend Code';
                    }
                }, 1000);
            } else {
                showErrorMessage(data.message);
                resendBtn.disabled = false;
                resendBtn.innerHTML = '<i class="fas fa-redo me-2"></i>Resend Code';
            }
        } catch (error) {
            console.error('Resend OTP error:', error);
            showErrorMessage('Failed to resend code. Please try again.');
            resendBtn.disabled = false;
            resendBtn.innerHTML = '<i class="fas fa-redo me-2"></i>Resend Code';
        }
    }
    
    // Show success message
    function showSuccessMessage(message) {
        showAlert(message, 'success');
    }
    
    // Show error message
    function showErrorMessage(message) {
        showAlert(message, 'danger');
    }
    
    // Generic alert function
    function showAlert(message, type) {
        // Remove existing alerts
        document.querySelectorAll('.alert-auto').forEach(alert => alert.remove());
        
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show alert-auto" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Insert alert at the top of the registration section
        const registrationSection = document.getElementById('register') || document.querySelector('.registration-section');
        if (registrationSection) {
            registrationSection.insertAdjacentHTML('afterbegin', alertHtml);
        } else {
            // Fallback: insert at top of page
            document.body.insertAdjacentHTML('afterbegin', alertHtml);
        }
        
        // Auto-remove success alerts after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                const alert = document.querySelector('.alert-auto');
                if (alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    }
});

// Utility function to format phone numbers
function formatPhoneNumber(input) {
    // Remove all non-numeric characters
    let phone = input.value.replace(/\D/g, '');
    
    // Format as needed (basic formatting)
    if (phone.length >= 10) {
        input.value = phone;
    }
}

// Add phone formatting to phone inputs
document.addEventListener('DOMContentLoaded', function() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function() {
            formatPhoneNumber(this);
        });
    });
});