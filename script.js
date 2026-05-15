
// ========================================
// MindSpring Clinic - JavaScript
// ========================================

/* Selector Usage Map (JS -> CSS/HTML)
    - nav                : CSS `nav` (toggled classes: `scrolled`, optionally `is-active`)
    - .nav-links         : CSS `.nav-links` (mobile menu; toggled `active` by `.menu-toggle`)
    - .menu-toggle       : CSS `.menu-toggle` (button that toggles mobile menu)
    - .auth-btn          : CSS `.auth-btn` (triggers login modal)
    - #loginModal / .modal : HTML ID `loginModal` styled by CSS `.modal` (open/close via JS)
    - .notification      : CSS `.notification` (created dynamically by showNotification())
    - .importance-card   : CSS `.importance-card` (observed by setupScrollAnimations())
*/

// Global Variables
let isLoginMode = true;
let currentUser = null;

// ========================================
// Initialize on Page Load
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
    setupScrollAnimations();
    setupNavigation();
    setupMobileMenu();
});

// Mobile Menu Setup
// JS ↔ CSS selectors:
// - `.menu-toggle` : mobile hamburger button (click target)
// - `.nav-links`   : menu container; toggles `.active` when open
// Behavior: toggles mobile nav, closes on outside click or resize
function setupMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const navLinks = document.querySelector('.nav-links');

    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            navLinks.classList.toggle('active');
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-container')) {
                navLinks.classList.remove('active');
            }
        });

        // Close mobile menu when clicking a link
        navLinks.addEventListener('click', (e) => {
            if (e.target.tagName === 'A') {
                navLinks.classList.remove('active');
            }
        });

        // Close mobile menu on window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                navLinks.classList.remove('active');
            }
        });
    }
}

function initializeApp() {
    // Check if user is already logged in
    const userData = sessionStorage.getItem('currentUser');
    if (userData) {
        try {
            currentUser = JSON.parse(userData);
            updateUIForLoggedInUser();
        } catch (e) {
            console.error('Error parsing user data:', e);
            sessionStorage.removeItem('currentUser');
        }
    }
}

// ========================================
// Authentication Functions
// ========================================

// openLoginModal() -> opens the login modal (#loginModal)
// closeModal() -> closes the login modal
// These functions manipulate the DOM ID `loginModal` which is styled by CSS `.modal`

function openLoginModal() {
    const modal = document.getElementById('loginModal');
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Prevent background scroll
    
    // Reset form
    document.getElementById('authForm').reset();
}
function openAdminModal() {
    // Navigate to the admin login page
    window.location.href = 'admin_login.php';
}

function closeModal() {
    const modal = document.getElementById('loginModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto'; // Restore scroll
}

function toggleAuthMode(e) {
    e.preventDefault();
    isLoginMode = !isLoginMode;
    
    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.getElementById('modalSubtitle');
    const nameGroup = document.getElementById('nameGroup');
    const confirmPasswordGroup = document.getElementById('confirmPasswordGroup');
    const submitBtnText = document.getElementById('submitBtnText');
    const switchText = document.getElementById('switchText');
    const switchLink = document.getElementById('switchLink');
    
    if (isLoginMode) {
        // Switch to Login Mode
        modalTitle.textContent = 'Welcome Back';
        modalSubtitle.textContent = 'Sign in to access your account';
        nameGroup.style.display = 'none';
        confirmPasswordGroup.style.display = 'none';
        submitBtnText.textContent = 'Sign In';
        switchText.textContent = "Don't have an account?";
        switchLink.textContent = 'Sign Up';
        document.getElementById('name').required = false;
        document.getElementById('confirmPassword').required = false;
    } else {
        // Switch to Sign Up Mode
        modalTitle.textContent = 'Create Account';
        modalSubtitle.textContent = 'Join MindSpring Clinic today';
        nameGroup.style.display = 'block';
        confirmPasswordGroup.style.display = 'block';
        submitBtnText.textContent = 'Create Account';
        switchText.textContent = 'Already have an account?';
        switchLink.textContent = 'Sign In';
        document.getElementById('name').required = true;
        document.getElementById('confirmPassword').required = true;
    }
    
    // Clear form
    document.getElementById('authForm').reset();
}

function handleAuth(e) {
    e.preventDefault();
    
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const name = document.getElementById('name').value.trim();
    const confirmPassword = document.getElementById('confirmPassword').value;
    if (!isLoginMode) {
        // Sign Up Process (server)
        if (password.length < 6) {
            showNotification('Password must be at least 6 characters long', 'error');
            return;
        }

        if (password !== confirmPassword) {
            showNotification('Passwords do not match', 'error');
            return;
        }

        // Call server API to register
        fetch('api/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, email, password })
        }).then(res => res.json()).then(json => {
            if (!json.success) {
                showNotification(json.error || 'Registration failed', 'error');
                return;
            }
            showNotification('Account created successfully! Please sign in.', 'success');
            setTimeout(() => toggleAuthMode(new Event('click')), 1200);
        }).catch(err => {
            console.error(err);
            showNotification('Network or server error during registration', 'error');
        });

    } else {
        // Login Process (server)
        fetch('api/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, password })
        }).then(res => res.json()).then(json => {
            if (!json.success) {
                showNotification(json.error || 'Login failed', 'error');
                return;
            }
            const user = json.user;
            currentUser = user;
            sessionStorage.setItem('currentUser', JSON.stringify(currentUser));
            showNotification('Welcome back, ' + currentUser.name + '!', 'success');
            updateUIForLoggedInUser();
            setTimeout(() => closeModal(), 800);
        }).catch(err => {
            console.error(err);
            showNotification('Network or server error during login', 'error');
        });
    }
    
    document.getElementById('authForm').reset();
}

function updateUIForLoggedInUser() {
    const authButtonContainer = document.getElementById('authButtonContainer');
    const userInfo = document.getElementById('userInfo');
    const userName = document.getElementById('userName');
    const userInitial = document.getElementById('userInitial');
    
    if (authButtonContainer && userInfo && currentUser) {
        authButtonContainer.style.display = 'none';
        userInfo.classList.add('active');
        userName.textContent = currentUser.name;
        userInitial.textContent = currentUser.name.charAt(0).toUpperCase();
    }
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        sessionStorage.removeItem('currentUser');
        currentUser = null;
        
        const authButtonContainer = document.getElementById('authButtonContainer');
        const userInfo = document.getElementById('userInfo');
        
        if (authButtonContainer && userInfo) {
            authButtonContainer.style.display = 'block';
            userInfo.classList.remove('active');
        }
        
        showNotification('You have been logged out successfully', 'success');
    }
}

// ========================================
// Navigation & Scroll Functions
// ========================================

function setupNavigation() {
    // setupNavigation()
    // - toggles `nav.scrolled` on window scroll (>50px)
    // - can be extended to toggle `nav.is-active` for persistent click state
    const nav = document.querySelector('nav');
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        // Add shadow on scroll
        if (currentScroll > 50) {
            nav.classList.add('scrolled');
        } else {
            nav.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    });
}

function scrollToSection(sectionId) {
    const section = document.getElementById(sectionId);
    
    if (section) {
        const navHeight = document.querySelector('nav').offsetHeight;
        const sectionPosition = section.offsetTop - navHeight - 20;
        
        window.scrollTo({
            top: sectionPosition,
            behavior: 'smooth'
        });
    } else {
        showNotification('This section will be available soon!', 'info');
    }
}

// ========================================
// Scroll Animations (DHTML)
// ========================================
// setupScrollAnimations()
// - Observes elements and adds `.visible` when they enter viewport
// - Targets selectors: `.importance-card`, `.testimonial-card`, `.feature-item`
// - Also adds `.fade-in-on-scroll` class for initial hidden state
function setupScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                // Stagger animation for grid items
                if (entry.target.classList.contains('importance-card')) {
                    const delay = entry.target.getAttribute('data-delay') || 0;
                    entry.target.style.transitionDelay = delay + 'ms';
                }
            }
        });
    }, observerOptions);
    
    // Observe all cards
    document.querySelectorAll('.importance-card, .testimonial-card, .feature-item').forEach(el => {
        el.classList.add('fade-in-on-scroll');
        observer.observe(el);
    });
}

// ========================================
// Notification System
// ========================================
// showNotification(message, type)
// - Creates a `.notification` element and appends to document.body
// - CSS: `.notification` (style.css) controls appearance and `.notification.show` triggers animation
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotif = document.querySelector('.notification');
    if (existingNotif) {
        existingNotif.remove();
    }
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <span class="notification-icon">${getNotificationIcon(type)}</span>
            <span class="notification-message">${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">×</button>
    `;
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);
    
    // Auto remove after 4 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 4000);
}

function getNotificationIcon(type) {
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    return icons[type] || icons.info;
}

// ========================================
// Modal Close on Outside Click
// ========================================

window.onclick = function(event) {
    const modal = document.getElementById('loginModal');
    if (event.target === modal || event.target.classList.contains('modal-overlay')) {
        closeModal();
    }
};

// ========================================
// Keyboard Navigation
// ========================================

document.addEventListener('keydown', function(e) {
    // Close modal on Escape key
    if (e.key === 'Escape') {
        const modal = document.getElementById('loginModal');
        if (modal.style.display === 'flex') {
            closeModal();
        }
    }
});

// ========================================
// Form Validation Enhancement
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input');
    
    inputs.forEach(input => {
        // Add focus animation
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
        
        // Real-time validation
        input.addEventListener('input', function() {
            if (this.validity.valid) {
                this.classList.remove('invalid');
                this.classList.add('valid');
            } else {
                this.classList.remove('valid');
                if (this.value) {
                    this.classList.add('invalid');
                }
            }
        });
    });
});

// ========================================
// Smooth Page Load Animation
// ========================================

window.addEventListener('load', function() {
    document.body.classList.add('loaded');
});

// ========================================
// Performance: Debounce Function
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



function animateCounter(element, target, duration = 2000) {
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target + (element.textContent.includes('+') ? '+' : '') + 
                                 (element.textContent.includes('%') ? '%' : '');
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current) + (element.textContent.includes('+') ? '+' : '') + 
                                 (element.textContent.includes('%') ? '%' : '');
        }
    }, 16);
}

// Trigger counter animation when stats are visible
const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !entry.target.classList.contains('counted')) {
            entry.target.classList.add('counted');
            const statNumber = entry.target.querySelector('.stat-number');
            if (statNumber) {
                const text = statNumber.textContent;
                const number = parseInt(text.replace(/\D/g, ''));
                statNumber.textContent = '0';
                animateCounter(statNumber, number);
            }
        }
    });
}, { threshold: 0.5 });

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.stat-item').forEach(stat => {
        statsObserver.observe(stat);
    });
});

// ========================================
// Dynamic Year in Footer
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const yearElements = document.querySelectorAll('.current-year');
    const currentYear = new Date().getFullYear();
    yearElements.forEach(el => {
        el.textContent = currentYear;
    });
});

// ========================================
// Accessibility: Focus Trap in Modal
// ========================================

function trapFocus(element) {
    const focusableElements = element.querySelectorAll(
        'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstFocusable = focusableElements[0];
    const lastFocusable = focusableElements[focusableElements.length - 1];
    
    element.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            if (e.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    lastFocusable.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    firstFocusable.focus();
                    e.preventDefault();
                }
            }
        }
    });
}

// Apply focus trap to modal
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.querySelector('.modal-content');
    if (modal) {
        trapFocus(modal);
    }
});

// ========================================
// Hero Background Animation (DHTML)
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const shapes = document.querySelectorAll('.hero-shape');
    
    shapes.forEach((shape, index) => {
        // Random position animation
        setInterval(() => {
            const randomX = Math.random() * 100;
            const randomY = Math.random() * 100;
            shape.style.transform = `translate(${randomX}%, ${randomY}%) scale(${0.8 + Math.random() * 0.4})`;
        }, 3000 + index * 1000);
    });
});

// ========================================
// Lazy Loading for Images (if added)
// ========================================

function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                img.classList.add('loaded');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

document.addEventListener('DOMContentLoaded', lazyLoadImages);

// ========================================
// AJAX Simulation for Future Use
// ========================================

async function submitContactForm(formData) {
    // Simulate AJAX request
    return new Promise((resolve, reject) => {
        setTimeout(() => {
            // Simulate successful response
            resolve({
                success: true,
                message: 'Form submitted successfully'
            });
        }, 1000);
    });
}

// ========================================
// Local Storage Management
// ========================================

const storage = {
    set: function(key, value) {
        try {
            sessionStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            console.error('Storage error:', e);
            return false;
        }
    },
    
    get: function(key) {
        try {
            const item = sessionStorage.getItem(key);
            return item ? JSON.parse(item) : null;
        } catch (e) {
            console.error('Storage error:', e);
            return null;
        }
    },
    
    remove: function(key) {
        try {
            sessionStorage.removeItem(key);
            return true;
        } catch (e) {
            console.error('Storage error:', e);
            return false;
        }
    },
    
    clear: function() {
        try {
            sessionStorage.clear();
            return true;
        } catch (e) {
            console.error('Storage error:', e);
            return false;
        }
    }
};

// ========================================
// Error Handling
// ========================================

window.addEventListener('error', function(e) {
    console.error('Global error:', e.error);
    // Could send to error tracking service in production
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled promise rejection:', e.reason);
    // Could send to error tracking service in production
});

// ========================================
// Console Welcome Message
// ========================================

console.log('%c🌱 MindSpring Clinic', 'color: #6366f1; font-size: 24px; font-weight: bold;');
console.log('%cWelcome to MindSpring Clinic!', 'color: #8b5cf6; font-size: 16px;');
console.log('%cIf you\'re looking at this, you might be interested in web development.', 'color: #64748b; font-size: 12px;');
console.log('%cVisit our careers page to learn more!', 'color: #64748b; font-size: 12px;');

// ========================================
// Export functions for potential module use
// ========================================

if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        openLoginModal,
        closeModal,
        logout,
        scrollToSection,
        showNotification
    };
}
// ========================================s
function goToServices() {
    window.location.href = 'services.html';
}
function goToContact() {
    window.location.href = 'contact.html';
}
function goToServicesWithFade() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.3s';
    
    setTimeout(() => {
        window.location.href = 'services.html';
    }, 300);
}
function goToContactWithFade() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.3s';
    
    setTimeout(() => {
        window.location.href = 'contact.html';
    }, 300);
}

// Services Page JS

 // Smooth scroll helper
  function scrollToSection(id) {
    document.getElementById(id).scrollIntoView({ behavior: 'smooth' });
  }

  // Show alert message function
  function showAlert(message, type) {
    const alertBox = document.getElementById('alertMessage');
    const alertText = document.getElementById('alertText');
    
    alertBox.className = 'alert-message ' + type;
    alertText.textContent = message;
    alertBox.style.display = 'block';
    
    // Hide after 3 seconds
    setTimeout(() => {
      alertBox.style.display = 'none';
    }, 3000);
  }

  // Book therapy from service cards (new function)
  function bookTherapy(therapyType, doctorName) {
    document.getElementById("booking").style.display = "block";
    document.getElementById("selectedTherapy").textContent = therapyType;
    document.getElementById("selectedDoctor").textContent = doctorName;
    document.getElementById("doctorNameInput").value = doctorName;
    document.getElementById("therapyTypeInput").value = therapyType;
    scrollToSection("booking");
  }

  // Doctor booking handler (updated to include therapy type)
  function bookDoctor(doctorName, defaultTherapy) {
    document.getElementById("booking").style.display = "block";
    document.getElementById("selectedTherapy").textContent = defaultTherapy || "General Consultation";
    document.getElementById("selectedDoctor").textContent = doctorName;
    document.getElementById("doctorNameInput").value = doctorName;
    document.getElementById("therapyTypeInput").value = defaultTherapy || "General Consultation";
    scrollToSection("booking");
  }

  // Validate date and time
  function isValidDateTime(date, time) {
    const now = new Date();
    const selectedDate = new Date(date);
    const [hours, minutes] = time.split(':');
    const selectedDateTime = new Date(date);
    selectedDateTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);

    // Reset hours for date comparison
    const todayDate = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const selectedDateOnly = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), selectedDate.getDate());

    // If selected date is in the past
    if (selectedDateOnly < todayDate) {
      showAlert("Please select a future date", "error");
      return false;
    }

    // If selected date is today, check if time is in the past
    if (selectedDateOnly.getTime() === todayDate.getTime()) {
      if (selectedDateTime < now) {
        showAlert("Please select a future time for today's appointment", "error");
        return false;
      }
    }

    return true;
  }

  // Handle form submission (updated to include therapy type)
  function handleAppointmentSubmit(event) {
    event.preventDefault();

    const patientName = document.getElementById('patientName').value;
    const patientEmail = document.getElementById('patientEmail').value;
    const appointmentDate = document.getElementById('appointmentDate').value;
    const appointmentTime = document.getElementById('appointmentTime').value;
    const doctorName = document.getElementById('doctorNameInput').value;
    const therapyType = document.getElementById('therapyTypeInput').value;

    // Validate date and time
    if (!isValidDateTime(appointmentDate, appointmentTime)) {
      return;
    }

    // Proceed with form submission
    fetch('api/book_appointment.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        patientName,
        patientEmail,
        appointmentDate,
        appointmentTime,
        doctorName,
        therapyType
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        showAlert("Appointment booked successfully!", "success");
        document.getElementById("booking").style.display = "none";
        event.target.reset();
      } else {
        showAlert(data.message || "Error booking appointment", "error");
      }
    })
    .catch(error => {
      showAlert("Error booking appointment", "error");
      console.error('Error:', error);
    });
  }

  // Animate service cards on scroll
  window.addEventListener("scroll", () => {
    document.querySelectorAll(".importance-card").forEach(card => {
      const rect = card.getBoundingClientRect().top;
      if (rect < window.innerHeight * 0.85) card.classList.add("visible");
    });
  });

  // Set minimum date when the page loads
  document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('appointmentDate').min = today;
  });

// Animate service cards on scroll
window.addEventListener("scroll", () => {
  document.querySelectorAll(".importance-card").forEach(card => {
    const rect = card.getBoundingClientRect().top;
    if (rect < window.innerHeight * 0.85) card.classList.add("visible");
  });
});
