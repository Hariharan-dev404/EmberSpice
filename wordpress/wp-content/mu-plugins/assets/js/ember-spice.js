/**
 * EMBER SPICE - Client Interactions & Form Handling
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Menu Toggle & Click-Outside Handling
    const mobileToggle = document.querySelector('.ember-mobile-toggle');
    const navLinks = document.querySelector('.ember-nav-links');

    if (mobileToggle && navLinks) {
        mobileToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = navLinks.classList.toggle('is-open');
            mobileToggle.classList.toggle('is-active', isOpen);
            mobileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Close drawer on navigation link click
        navLinks.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function () {
                navLinks.classList.remove('is-open');
                mobileToggle.classList.remove('is-active');
                mobileToggle.setAttribute('aria-expanded', 'false');
            });
        });

        // Close drawer on tap outside
        document.addEventListener('click', function (e) {
            if (!mobileToggle.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('is-open');
                mobileToggle.classList.remove('is-active');
                mobileToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // 2. Menu Category Tabs Filter
    const tabBtns = document.querySelectorAll('.ember-menu-tab-btn');
    const menuCategories = document.querySelectorAll('.ember-menu-category');

    if (tabBtns.length > 0 && menuCategories.length > 0) {
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function () {
                const targetCat = this.getAttribute('data-category');

                tabBtns.forEach(b => b.classList.remove('is-active'));
                this.classList.add('is-active');

                menuCategories.forEach(cat => {
                    const catId = cat.getAttribute('data-category');
                    if (targetCat === 'all' || catId === targetCat) {
                        cat.style.display = 'block';
                    } else {
                        cat.style.display = 'none';
                    }
                });
            });
        });
    }

    // 3. Reservation Form AJAX Handling
    const resForm = document.getElementById('ember-reservation-form');
    if (resForm) {
        resForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const alertBox = document.getElementById('reservation-alert');
            const submitBtn = resForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Sending Request...';
            if (alertBox) {
                alertBox.className = 'ember-form-alert';
                alertBox.style.display = 'none';
            }

            const formData = new FormData(resForm);
            formData.append('action', 'ember_spice_reservation');

            const ajaxUrl = (typeof emberSpiceData !== 'undefined' && emberSpiceData.ajax_url) ? emberSpiceData.ajax_url : '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;

                if (alertBox) {
                    if (data.success) {
                        alertBox.className = 'ember-form-alert is-success';
                        alertBox.innerHTML = `<strong>Booking Request Received:</strong> ${data.data.message}`;
                        resForm.reset();
                    } else {
                        alertBox.className = 'ember-form-alert is-error';
                        alertBox.innerHTML = `<strong>Notice:</strong> ${data.data.message || 'Unable to process request. Please try again.'}`;
                    }
                    alertBox.style.display = 'block';
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                if (alertBox) {
                    alertBox.className = 'ember-form-alert is-error';
                    alertBox.innerHTML = '<strong>Connection Error:</strong> Could not connect to the server. Please call us directly.';
                    alertBox.style.display = 'block';
                }
            });
        });
    }

    // 4. Contact Form AJAX Handling
    const contactForm = document.getElementById('ember-contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const alertBox = document.getElementById('contact-alert');
            const submitBtn = contactForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Sending Message...';
            if (alertBox) {
                alertBox.className = 'ember-form-alert';
                alertBox.style.display = 'none';
            }

            const formData = new FormData(contactForm);
            formData.append('action', 'ember_spice_contact');

            const ajaxUrl = (typeof emberSpiceData !== 'undefined' && emberSpiceData.ajax_url) ? emberSpiceData.ajax_url : '/wp-admin/admin-ajax.php';

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;

                if (alertBox) {
                    if (data.success) {
                        alertBox.className = 'ember-form-alert is-success';
                        alertBox.innerHTML = `<strong>Message Sent:</strong> ${data.data.message}`;
                        contactForm.reset();
                    } else {
                        alertBox.className = 'ember-form-alert is-error';
                        alertBox.innerHTML = `<strong>Notice:</strong> ${data.data.message || 'Error sending message.'}`;
                    }
                    alertBox.style.display = 'block';
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                if (alertBox) {
                    alertBox.className = 'ember-form-alert is-error';
                    alertBox.innerHTML = '<strong>Connection Error:</strong> Could not submit form.';
                    alertBox.style.display = 'block';
                }
            });
        });
    }

    // 5. Newsletter Form Handling
    const newsletterForm = document.getElementById('ember-newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const msgBox = document.getElementById('newsletter-msg');
            const emailInput = newsletterForm.querySelector('input[type="email"]');
            if (emailInput && emailInput.value) {
                if (msgBox) {
                    msgBox.style.display = 'block';
                    msgBox.style.color = '#86efac';
                    msgBox.style.marginTop = '0.5rem';
                    msgBox.innerHTML = 'Thank you for subscribing to Ember Spice stories and culinary offers!';
                }
                newsletterForm.reset();
            }
        });
    }
});
