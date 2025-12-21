/**
 * Login Page
 * Handles user authentication and registration
 * 
 * @package AnimalShelter
 */

const LoginPage = {
    /**
     * Current mode (login or register)
     */
    mode: 'login',

    /**
     * Render the page
     * @param {Object} params - Route parameters
     * @returns {string}
     */
    async render(params) {
        this.mode = Router.getCurrentPath() === '/register' ? 'register' : 'login';

        return `
            <div class="auth-card animate-fade-in">
                <div class="auth-logo">
                    <div class="auth-logo-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10 5.172C10 3.782 8.423 2.679 6.5 3c-2.823.47-4.113 6.006-4 7 .08.703 1.725 1.722 3.656 1 1.261-.472 1.96-1.45 2.344-2.5"></path>
                            <path d="M14.267 5.172c0-1.39 1.577-2.493 3.5-2.172 2.823.47 4.113 6.006 4 7-.08.703-1.725 1.722-3.656 1-1.261-.472-1.855-1.45-2.239-2.5"></path>
                            <path d="M8 14v.5"></path>
                            <path d="M16 14v.5"></path>
                            <path d="M11.25 16.25h1.5L12 17l-.75-.75Z"></path>
                            <path d="M4.42 11.247A13.152 13.152 0 0 0 4 14.556C4 18.728 7.582 21 12 21s8-2.272 8-6.444c0-1.061-.162-2.2-.493-3.309m-9.243-6.082A8.801 8.801 0 0 1 12 5c.78 0 1.5.108 2.161.306"></path>
                        </svg>
                    </div>
                    <span class="auth-logo-text">Catarman Dog Pound</span>
                </div>
                
                <h1 class="auth-title">${this.mode === 'login' ? 'Welcome Back' : 'Create Account'}</h1>
                <p class="auth-subtitle">
                    ${this.mode === 'login'
                ? 'Sign in to continue to your dashboard'
                : 'Register to start adopting pets'}
                </p>
                
                <form id="auth-form" class="auth-form">
                    ${this.mode === 'register' ? `
                        <div class="form-group">
                            <label class="form-label required" for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-input" placeholder="johndoe123" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label class="form-label required" for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-input" placeholder="John" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required" for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-input" placeholder="Doe" required>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${this.mode === 'login' ? `
                        <div class="form-group">
                            <label class="form-label required" for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-input" placeholder="Enter username" required>
                        </div>
                    ` : `
                        <div class="form-group">
                            <label class="form-label required" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="you@example.com" required>
                        </div>
                    `}
                    
                    ${this.mode === 'register' ? `
                        <div class="form-group">
                            <label class="form-label" for="contact_number">Contact Number</label>
                            <input type="tel" id="contact_number" name="contact_number" class="form-input" placeholder="09171234567">
                        </div>
                    ` : ''}
                    
                    <div class="form-group">
                        <label class="form-label required" for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password" class="form-input has-icon-right" placeholder="Enter password" required minlength="6">
                            <button type="button" class="input-icon-right btn-icon btn-ghost" onclick="LoginPage.togglePassword('password')" style="right: 8px; top: 50%; transform: translateY(-50%); position: absolute;">
                                <svg id="password-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                            </button>
                        </div>
                        ${this.mode === 'register' ? '<p class="form-hint">At least 6 characters</p>' : ''}
                    </div>
                    
                    ${this.mode === 'register' ? `
                        <div class="form-group">
                            <label class="form-label required" for="password_confirmation">Confirm Password</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" placeholder="Confirm password" required>
                        </div>
                    ` : ''}
                    
                    ${this.mode === 'login' ? `
                        <div class="flex items-center justify-between">
                            <label class="form-checkbox">
                                <input type="checkbox" name="remember">
                                <span>Remember me</span>
                            </label>
                            <a href="#" onclick="LoginPage.showForgotPassword()" class="text-link" style="font-size: var(--text-sm);">Forgot password?</a>
                        </div>
                    ` : `
                        <label class="form-checkbox">
                            <input type="checkbox" name="terms" required>
                            <span>I agree to the <a href="#" class="text-link">Terms of Service</a> and <a href="#" class="text-link">Privacy Policy</a></span>
                        </label>
                    `}
                    
                    <button type="submit" class="btn btn-primary btn-lg w-full" style="margin-top: var(--space-4);">
                        ${this.mode === 'login' ? 'Sign In' : 'Create Account'}
                    </button>
                </form>
                
                <div class="auth-footer">
                    ${this.mode === 'login'
                ? `Don't have an account? <a href="/register">Sign up</a>`
                : `Already have an account? <a href="/login">Sign in</a>`
            }
                </div>
            </div>
        `;
    },

    /**
     * After render callback
     */
    async afterRender() {
        const form = document.getElementById('auth-form');

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleSubmit(e);
            });
        }
    },

    /**
     * Handle form submission
     * @param {Event} e
     */
    async handleSubmit(e) {
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const formData = Form.getData(form);

        // Validate
        if (this.mode === 'register') {
            if (formData.password !== formData.password_confirmation) {
                Toast.error('Passwords do not match');
                return;
            }

            const passwordValidation = Utils.validatePassword(formData.password);
            if (!passwordValidation.isValid) {
                Toast.error(passwordValidation.message);
                return;
            }
        }

        try {
            Loading.setButtonLoading(submitBtn, true, this.mode === 'login' ? 'Signing in...' : 'Creating account...');

            if (this.mode === 'login') {
                // Send username (or email if user entered email in username field, backend handles both via 'username' identifier logic)
                // But wait, the backend `login` method expects `identifier`?
                // No, I updated backend to check `email` OR `username`.
                // If I send `username` in the payload, `AuthController` will read it via `$this->input('username')`.
                // So I just need to call Auth.login with username.

                await Auth.login(formData.username, formData.password);
                Toast.success('Welcome back!');
                Router.navigate('/dashboard');
            } else {
                await Auth.register({
                    username: formData.username,
                    first_name: formData.first_name,
                    last_name: formData.last_name,
                    email: formData.email,
                    password: formData.password,
                    contact_number: formData.contact_number
                });
                Toast.success('Account created successfully! Please sign in.');
                Router.navigate('/login');
            }
        } catch (error) {
            Toast.error(error.message || 'Authentication failed');
        } finally {
            Loading.setButtonLoading(submitBtn, false);
        }
    },

    /**
     * Toggle password visibility
     * @param {string} inputId
     */
    togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(`${inputId}-icon`);

        if (input.type === 'password') {
            input.type = 'text';
            if (icon) {
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            }
        } else {
            input.type = 'password';
            if (icon) {
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }
    },

    /**
     * Show forgot password modal
     */
    async showForgotPassword() {
        await Modal.alert(
            'Please contact the administrator or staff to reset or change your password.',
            'Forgot Password'
        );
    }
};

// Make LoginPage globally available
window.LoginPage = LoginPage;