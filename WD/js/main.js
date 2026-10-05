// Theme Initialization (Run early to prevent flashing)
(function() {
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  }
})();
/**
 * CHARUSAT ITUE203 - StudentHub Master JavaScript File
 * Practical 4: DOM Manipulation, Event Handling & UI Interactivity
 * Practical 5: Frontend Form Validation with Regular Expressions & Password Strength Meter
 */

document.addEventListener('DOMContentLoaded', () => {
  // ==========================================================================
  // PRACTICAL 4: DOM MANIPULATION & INTERACTIVITY
  // ==========================================================================



  // 2. Dismissible Notification Banner (Practical 4: localStorage preference memory)
  const bannerCloseBtn = document.querySelector('.banner-close-btn');
  const notificationBanner = document.querySelector('.notification-banner');

  if (notificationBanner) {
    if (localStorage.getItem('studenthub_banner_closed') === 'true') {
      notificationBanner.style.display = 'none';
    }
  }

  if (bannerCloseBtn && notificationBanner) {
    bannerCloseBtn.addEventListener('click', () => {
      notificationBanner.style.display = 'none';
      localStorage.setItem('studenthub_banner_closed', 'true');
    });
  }

  // 3. Mobile Hamburger Menu Toggle
  const menuToggleBtn = document.querySelector('.mobile-menu-btn');
  const mainNav = document.querySelector('.main-nav');

  if (menuToggleBtn && mainNav) {
    menuToggleBtn.addEventListener('click', () => {
      const isExpanded = menuToggleBtn.getAttribute('aria-expanded') === 'true';
      menuToggleBtn.setAttribute('aria-expanded', !isExpanded);
      mainNav.classList.toggle('is-open');
    });
  }

  // 4. Modal Popup Dialog (Open/Close, Backdrop Click, Escape Key)
  const openModalBtn = document.getElementById('open-modal-btn');
  const closeModalBtn = document.getElementById('close-modal-btn');
  const modalOverlay = document.getElementById('modal-overlay');

  const openModal = () => {
    if (modalOverlay) {
      modalOverlay.classList.add('is-active');
      modalOverlay.removeAttribute('hidden');
    }
  };

  const closeModal = () => {
    if (modalOverlay) {
      modalOverlay.classList.remove('is-active');
      modalOverlay.setAttribute('hidden', '');
    }
  };

  if (openModalBtn) openModalBtn.addEventListener('click', openModal);
  if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);

  if (modalOverlay) {
    // Backdrop click dismiss
    modalOverlay.addEventListener('click', (e) => {
      if (e.target === modalOverlay) closeModal();
    });

    // Escape key press dismiss
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && (modalOverlay.classList.contains('is-active') || !modalOverlay.hasAttribute('hidden'))) {
        closeModal();
      }
    });
  }

  // 5. Collapsible FAQ Accordion Component
  const accordionItems = document.querySelectorAll('.accordion-item');

  accordionItems.forEach(item => {
    const header = item.querySelector('.accordion-header');
    const panel = item.querySelector('.accordion-panel');
    if (header) {
      header.addEventListener('click', () => {
        const isActive = item.classList.contains('active');
        
        // Close all accordion items
        accordionItems.forEach(i => {
          i.classList.remove('active');
          const h = i.querySelector('.accordion-header');
          const p = i.querySelector('.accordion-panel');
          if (h) h.setAttribute('aria-expanded', 'false');
          if (p) p.setAttribute('hidden', '');
        });
        
        // Toggle clicked item
        if (!isActive) {
          item.classList.add('active');
          header.setAttribute('aria-expanded', 'true');
          if (panel) panel.removeAttribute('hidden');
        }
      });
    }
  });

  // 6. Accessible Tabs Component
  const tabNavs = document.querySelectorAll('.tabs-nav');
  
  tabNavs.forEach(tabNav => {
    const tabButtons = tabNav.querySelectorAll('.tab-btn');
    
    tabButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        const targetPanelId = btn.getAttribute('aria-controls');
        
        tabButtons.forEach(b => {
          b.setAttribute('aria-selected', 'false');
          b.setAttribute('tabindex', '-1');
        });
        
        btn.setAttribute('aria-selected', 'true');
        btn.removeAttribute('tabindex');

        const container = tabNav.closest('.tabs-container') || document;
        const panels = container.querySelectorAll('.tab-panel');
        panels.forEach(panel => {
          if (panel.id === targetPanelId) {
            panel.removeAttribute('hidden');
          } else {
            panel.setAttribute('hidden', '');
          }
        });
      });
    });
  });

  // 7. Image / Content Slider Component (Practical 4: Interactive Carousel)
  const sliders = document.querySelectorAll('.slider-container');
  sliders.forEach(slider => {
    const track = slider.querySelector('.slider-track');
    const slides = slider.querySelectorAll('.slide');
    const prevBtn = slider.querySelector('.prev-btn');
    const nextBtn = slider.querySelector('.next-btn');
    const indicatorsContainer = slider.querySelector('.slider-indicators');
    let currentSlide = 0;
    let autoSlideInterval = null;

    if (!track || slides.length === 0) return;

    // Build indicators if container exists
    if (indicatorsContainer && indicatorsContainer.children.length === 0) {
      slides.forEach((_, idx) => {
        const dot = document.createElement('button');
        dot.className = `slider-dot ${idx === 0 ? 'active' : ''}`;
        dot.setAttribute('aria-label', `Go to slide ${idx + 1}`);
        dot.addEventListener('click', () => {
          goToSlide(idx);
          resetAutoSlide();
        });
        indicatorsContainer.appendChild(dot);
      });
    }

    const updateSlider = () => {
      track.style.transform = `translateX(-${currentSlide * 100}%)`;
      if (indicatorsContainer) {
        const dots = indicatorsContainer.querySelectorAll('.slider-dot');
        dots.forEach((dot, idx) => {
          dot.classList.toggle('active', idx === currentSlide);
        });
      }
    };

    const goToSlide = (index) => {
      currentSlide = (index + slides.length) % slides.length;
      updateSlider();
    };

    const nextSlide = () => goToSlide(currentSlide + 1);
    const prevSlide = () => goToSlide(currentSlide - 1);

    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        nextSlide();
        resetAutoSlide();
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        prevSlide();
        resetAutoSlide();
      });
    }

    // Auto-advance slides every 5 seconds
    const startAutoSlide = () => {
      if (!autoSlideInterval) {
        autoSlideInterval = setInterval(nextSlide, 5000);
      }
    };

    const stopAutoSlide = () => {
      if (autoSlideInterval) {
        clearInterval(autoSlideInterval);
        autoSlideInterval = null;
      }
    };

    const resetAutoSlide = () => {
      stopAutoSlide();
      startAutoSlide();
    };

    slider.addEventListener('mouseenter', stopAutoSlide);
    slider.addEventListener('mouseleave', startAutoSlide);

    // Keyboard navigation when slider is focused
    slider.setAttribute('tabindex', '0');
    slider.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowRight') {
        nextSlide();
        resetAutoSlide();
      } else if (e.key === 'ArrowLeft') {
        prevSlide();
        resetAutoSlide();
      }
    });

    startAutoSlide();
  });

  // ==========================================================================
  // PRACTICAL 5: REGISTRATION FORM REGEX VALIDATION & CANVAS CAPTCHA
  // ==========================================================================

  const registrationForm = document.getElementById('registration-form');

  if (registrationForm) {
    const nameInput = document.getElementById('reg-name');
    const emailInput = document.getElementById('reg-email');
    const mobileInput = document.getElementById('reg-mobile');
    const passwordInput = document.getElementById('reg-password');
    const confirmPasswordInput = document.getElementById('reg-confirm-password');
    const courseInput = document.getElementById('reg-course');
    const yearInput = document.getElementById('reg-year');
    const termsInput = document.getElementById('reg-terms');
    const strengthFill = document.getElementById('strength-fill');
    const strengthLabel = document.getElementById('strength-label');
    const captchaCanvas = document.getElementById('captcha-canvas');
    const captchaInput = document.getElementById('reg-captcha');
    const refreshCaptchaBtn = document.getElementById('refresh-captcha-btn');

    let generatedCaptcha = '';

    // Advanced Extension: HTML5 Canvas Custom CAPTCHA Generator
    const generateCaptcha = () => {
      if (!captchaCanvas) return;
      const ctx = captchaCanvas.getContext('2d');
      const chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
      generatedCaptcha = '';
      for (let i = 0; i < 6; i++) {
        generatedCaptcha += chars.charAt(Math.floor(Math.random() * chars.length));
      }

      ctx.clearRect(0, 0, captchaCanvas.width, captchaCanvas.height);

      // Background decorative lines
      for (let i = 0; i < 5; i++) {
        ctx.strokeStyle = `rgba(${Math.floor(Math.random() * 200)}, ${Math.floor(Math.random() * 200)}, ${Math.floor(Math.random() * 200)}, 0.3)`;
        ctx.lineWidth = 1 + Math.random() * 2;
        ctx.beginPath();
        ctx.moveTo(Math.random() * captchaCanvas.width, Math.random() * captchaCanvas.height);
        ctx.lineTo(Math.random() * captchaCanvas.width, Math.random() * captchaCanvas.height);
        ctx.stroke();
      }

      // Draw random noise dots
      for (let i = 0; i < 40; i++) {
        ctx.fillStyle = `rgba(${Math.floor(Math.random() * 200)}, ${Math.floor(Math.random() * 200)}, ${Math.floor(Math.random() * 200)}, 0.4)`;
        ctx.beginPath();
        ctx.arc(Math.random() * captchaCanvas.width, Math.random() * captchaCanvas.height, 1.5, 0, Math.PI * 2);
        ctx.fill();
      }

      // Draw stylized characters
      ctx.font = 'bold 24px monospace';
      ctx.textBaseline = 'middle';
      for (let i = 0; i < generatedCaptcha.length; i++) {
        ctx.save();
        const x = 20 + i * 22;
        const y = captchaCanvas.height / 2 + (Math.random() * 8 - 4);
        const angle = (Math.random() * 30 - 15) * Math.PI / 180;
        ctx.translate(x, y);
        ctx.rotate(angle);
        ctx.fillStyle = document.documentElement.getAttribute('data-theme') === 'dark' ? '#38bdf8' : '#312e81';
        ctx.fillText(generatedCaptcha[i], 0, 0);
        ctx.restore();
      }
    };

    if (captchaCanvas) {
      generateCaptcha();
      if (refreshCaptchaBtn) {
        refreshCaptchaBtn.addEventListener('click', (e) => {
          e.preventDefault();
          generateCaptcha();
          if (captchaInput) captchaInput.value = '';
        });
      }
    }

    // Regular Expression Patterns
    const patterns = {
      name: /^[A-Za-z\s]{3,50}$/,                                          // 3-50 letters and spaces only
      email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,           // Standard email format
      mobile: /^[0-9]{10}$/,                                               // Exactly 10 digits
      password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/ // Min 8 chars, 1 upper, 1 lower, 1 digit, 1 special char
    };

    // Error helper functions
    const showError = (input, message) => {
      const formGroup = input.closest('.form-group');
      if (formGroup) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');
        let errorSpan = formGroup.querySelector('.form-error') || formGroup.querySelector('.error-text');
        if (!errorSpan) {
          errorSpan = document.createElement('span');
          errorSpan.className = 'form-error';
          formGroup.appendChild(errorSpan);
        }
        errorSpan.textContent = message;
      }
    };

    const clearError = (input) => {
      const formGroup = input.closest('.form-group');
      if (formGroup) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        const errorSpan = formGroup.querySelector('.form-error') || formGroup.querySelector('.error-text');
        if (errorSpan) {
          errorSpan.textContent = '';
        }
      }
    };

    // Password Strength Meter Logic (Real-time Evaluation)
    if (passwordInput && strengthFill && strengthLabel) {
      passwordInput.addEventListener('input', () => {
        const val = passwordInput.value;
        let score = 0;

        if (!val) {
          strengthFill.className = 'strength-bar-fill';
          strengthLabel.textContent = '';
          return;
        }

        if (val.length >= 8) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[a-z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[@$!%*?&#]/.test(val)) score++;

        if (score <= 2) {
          strengthFill.className = 'strength-bar-fill weak';
          strengthLabel.className = 'strength-label weak';
          strengthLabel.textContent = 'Weak (Must have 8+ chars, upper, lower, digit & special char)';
        } else if (score === 3 || score === 4) {
          strengthFill.className = 'strength-bar-fill medium';
          strengthLabel.className = 'strength-label medium';
          strengthLabel.textContent = 'Medium (Good, almost there)';
        } else {
          strengthFill.className = 'strength-bar-fill strong';
          strengthLabel.className = 'strength-label strong';
          strengthLabel.textContent = 'Strong Password (Excellent)';
        }
      });
    }

    // Intermediate Extension: Real-Time validation listeners on keyup / input / change
    if (nameInput) {
      const validateName = () => {
        if (!patterns.name.test(nameInput.value.trim())) {
          showError(nameInput, 'Full Name must contain only letters and spaces (min 3 characters).');
          return false;
        }
        clearError(nameInput);
        return true;
      };
      nameInput.addEventListener('input', validateName);
      nameInput.addEventListener('blur', validateName);
    }

    if (emailInput) {
      const validateEmail = () => {
        if (!patterns.email.test(emailInput.value.trim())) {
          showError(emailInput, 'Enter a valid email address (e.g. student@charusat.ac.in).');
          return false;
        }
        clearError(emailInput);
        return true;
      };
      emailInput.addEventListener('input', validateEmail);
      emailInput.addEventListener('blur', validateEmail);
    }

    if (mobileInput) {
      const validateMobile = () => {
        if (!patterns.mobile.test(mobileInput.value.trim())) {
          showError(mobileInput, 'Mobile Number must be exactly 10 digits.');
          return false;
        }
        clearError(mobileInput);
        return true;
      };
      mobileInput.addEventListener('input', validateMobile);
      mobileInput.addEventListener('blur', validateMobile);
    }

    if (passwordInput) {
      const validatePassword = () => {
        if (!patterns.password.test(passwordInput.value)) {
          showError(passwordInput, 'Min 8 chars, 1 uppercase, 1 lowercase, 1 digit, and 1 special char required.');
          return false;
        }
        clearError(passwordInput);
        return true;
      };
      passwordInput.addEventListener('input', validatePassword);
      passwordInput.addEventListener('blur', validatePassword);
    }

    if (confirmPasswordInput) {
      const validateConfirmPassword = () => {
        if (!confirmPasswordInput.value || confirmPasswordInput.value !== passwordInput.value) {
          showError(confirmPasswordInput, 'Passwords do not match.');
          return false;
        }
        clearError(confirmPasswordInput);
        return true;
      };
      confirmPasswordInput.addEventListener('input', validateConfirmPassword);
      confirmPasswordInput.addEventListener('blur', validateConfirmPassword);
    }

    if (courseInput) {
      courseInput.addEventListener('change', () => {
        if (!courseInput.value) showError(courseInput, 'Please select your course.');
        else clearError(courseInput);
      });
    }

    if (yearInput) {
      yearInput.addEventListener('change', () => {
        if (!yearInput.value) showError(yearInput, 'Please select your academic year.');
        else clearError(yearInput);
      });
    }

    if (termsInput) {
      termsInput.addEventListener('change', () => {
        if (!termsInput.checked) showError(termsInput, 'You must accept the terms & conditions to register.');
        else clearError(termsInput);
      });
    }

    // On Form Submission Handler
    registrationForm.addEventListener('submit', (e) => {
      let isValid = true;

      // Validate Name
      if (!patterns.name.test(nameInput.value.trim())) {
        showError(nameInput, 'Full Name must contain only letters and spaces (min 3 characters).');
        isValid = false;
      } else clearError(nameInput);

      // Validate Email
      if (!patterns.email.test(emailInput.value.trim())) {
        showError(emailInput, 'Enter a valid email address.');
        isValid = false;
      } else clearError(emailInput);

      // Validate Mobile
      if (!patterns.mobile.test(mobileInput.value.trim())) {
        showError(mobileInput, 'Mobile Number must be exactly 10 digits.');
        isValid = false;
      } else clearError(mobileInput);

      // Validate Password
      if (!patterns.password.test(passwordInput.value)) {
        showError(passwordInput, 'Password does not meet complexity requirements.');
        isValid = false;
      } else clearError(passwordInput);

      // Validate Confirm Password
      if (!confirmPasswordInput.value || confirmPasswordInput.value !== passwordInput.value) {
        showError(confirmPasswordInput, 'Passwords do not match.');
        isValid = false;
      } else clearError(confirmPasswordInput);

      // Validate Course Select
      if (!courseInput.value) {
        showError(courseInput, 'Please select a course.');
        isValid = false;
      } else clearError(courseInput);

      // Validate Year Select
      if (!yearInput.value) {
        showError(yearInput, 'Please select your academic year.');
        isValid = false;
      } else clearError(yearInput);

      // Validate Gender Radio Selection
      const genderSelected = registrationForm.querySelector('input[name="gender"]:checked');
      const genderErrorSpan = document.getElementById('gender-group-error');
      if (!genderSelected) {
        if (genderErrorSpan) genderErrorSpan.textContent = 'Please select a gender.';
        isValid = false;
      } else if (genderErrorSpan) {
        genderErrorSpan.textContent = '';
      }

      // Validate Terms Checkbox
      if (!termsInput.checked) {
        showError(termsInput, 'You must accept the terms & conditions to register.');
        isValid = false;
      } else clearError(termsInput);

      // Validate Canvas CAPTCHA (if present)
      if (captchaCanvas && captchaInput) {
        if (!captchaInput.value || captchaInput.value.trim().toLowerCase() !== generatedCaptcha.toLowerCase()) {
          showError(captchaInput, 'Incorrect CAPTCHA text. Please try again.');
          isValid = false;
        } else {
          clearError(captchaInput);
        }
      }

      // If invalid, block form submission
      if (!isValid) {
        e.preventDefault();
      } else {
        e.preventDefault();
        alert('🎉 Practical 5: Registration form validated successfully with client-side regex and Canvas CAPTCHA!');
      }
    });
  }

  // 8. Light/Dark Theme Switcher (Practical 4)
  const themeToggleBtn = document.getElementById('theme-toggle-btn');
  if (themeToggleBtn) {
    const updateThemeButtonText = (theme) => {
      themeToggleBtn.textContent = theme === 'dark' ? '☀️ Light' : '🌙 Dark';
    };

    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
      document.documentElement.setAttribute('data-theme', 'dark');
      updateThemeButtonText('dark');
    } else {
      updateThemeButtonText('light');
    }

    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      if (currentTheme === 'dark') {
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('theme', 'light');
        updateThemeButtonText('light');
      } else {
        document.documentElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
        updateThemeButtonText('dark');
      }
    });
  }
});


