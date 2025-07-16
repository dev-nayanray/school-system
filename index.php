<?php
session_start();
if (isset($_SESSION['user_id'])) {
    // Redirect logged-in users to their dashboard based on role
    $role = $_SESSION['role'];
    switch ($role) {
        case 'admin':
            header('Location: dashboard/admin.php');
            break;
        case 'teacher':
            header('Location: dashboard/teacher.php');
            break;
        case 'student':
            header('Location: dashboard/student.php');
            break;
        default:
            header('Location: dashboard/user.php');
            break;
    }
    exit();
}

// Check for messages in session
$popupMessage = '';
$popupType = '';
if (isset($_SESSION['popup_message'])) {
    $popupMessage = $_SESSION['popup_message'];
    $popupType = $_SESSION['popup_type'];
    
    // Clear session variables after retrieval
    unset($_SESSION['popup_message']);
    unset($_SESSION['popup_type']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login - School Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#7c3aed',
                        dark: '#1e293b',
                        light: '#f8fafc',
                        success: '#10b981',
                        error: '#ef4444',
                        warning: '#f59e0b',
                        info: '#3b82f6'
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'slide-in': 'slideIn 0.3s ease-out forwards',
                        'slide-out': 'slideOut 0.3s ease-out forwards'
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translateY(0)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        slideIn: {
                            '0%': { transform: 'translateY(20px)', opacity: 0 },
                            '100%': { transform: 'translateY(0)', opacity: 1 }
                        },
                        slideOut: {
                            '0%': { transform: 'translateY(0)', opacity: 1 },
                            '100%': { transform: 'translateY(20px)', opacity: 0 }
                        }
                    }
                }
            }
        }
    </script>
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }
        body {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            min-height: 100vh;
            overflow-x: hidden;
        }
        .login-container {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 15px rgba(79, 70, 229, 0.3);
        }
        .input-field {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .input-field:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(124, 58, 237, 0.6);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.3);
        }
        .floating-label {
            pointer-events: none;
            transition: all 0.3s ease;
            transform-origin: left top;
        }
        .input-field:focus + .floating-label,
        .input-field:not(:placeholder-shown) + .floating-label {
            transform: translateY(-24px) scale(0.8);
            color: #c7d2fe;
        }
        .role-btn {
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .role-btn:hover, .role-btn.active {
            background: rgba(79, 70, 229, 0.2);
            border-color: rgba(124, 58, 237, 0.6);
            box-shadow: 0 0 15px rgba(124, 58, 237, 0.3);
        }
        .login-btn {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(124, 58, 237, 0.3);
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(124, 58, 237, 0.4);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        .animate-pulse-slow {
            animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(124, 58, 237, 0.6);
            animation: float 15s infinite linear;
            z-index: -1;
        }
        .toggle-password {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .toggle-password:hover {
            color: #c7d2fe;
        }
        .popup {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
            width: 90%;
            padding: 16px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            animation: slide-in 0.3s ease-out forwards;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .popup.success {
            border-left: 4px solid #10b981;
        }
        .popup.error {
            border-left: 4px solid #ef4444;
        }
        .popup.warning {
            border-left: 4px solid #f59e0b;
        }
        .popup.info {
            border-left: 4px solid #3b82f6;
        }
        .popup-content {
            flex: 1;
            padding-right: 15px;
        }
        .popup-icon {
            font-size: 24px;
            margin-right: 15px;
            flex-shrink: 0;
        }
        .popup.success .popup-icon {
            color: #10b981;
        }
        .popup.error .popup-icon {
            color: #ef4444;
        }
        .popup.warning .popup-icon {
            color: #f59e0b;
        }
        .popup.info .popup-icon {
            color: #3b82f6;
        }
        .popup-close {
            cursor: pointer;
            font-size: 18px;
            opacity: 0.7;
            transition: opacity 0.2s;
            padding: 5px;
        }
        .popup-close:hover {
            opacity: 1;
        }
        .popup-title {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }
        .popup-message {
            font-size: 14px;
            opacity: 0.9;
        }
        .popup-timer {
            height: 4px;
            background: rgba(255, 255, 255, 0.2);
            position: absolute;
            bottom: 0;
            left: 0;
            border-radius: 0 0 0 12px;
            width: 100%;
        }
        .popup-timer-progress {
            height: 100%;
            border-radius: 0 0 0 12px;
            transition: width 0.1s linear;
        }
        .popup.success .popup-timer-progress {
            background: #10b981;
        }
        .popup.error .popup-timer-progress {
            background: #ef4444;
        }
        .popup.warning .popup-timer-progress {
            background: #f59e0b;
        }
        .popup.info .popup-timer-progress {
            background: #3b82f6;
        }
        @keyframes progress {
            from { width: 100%; }
            to { width: 0%; }
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <!-- Popup Container -->
    <div id="popup-container"></div>

    <!-- Decorative particles -->
    <div class="particle" style="top: 20%; left: 10%; width: 40px; height: 40px; animation-duration: 18s;"></div>
    <div class="particle" style="top: 70%; left: 85%; width: 60px; height: 60px; animation-duration: 22s;"></div>
    <div class="particle" style="top: 40%; left: 75%; width: 30px; height: 30px; animation-duration: 15s;"></div>
    <div class="particle" style="top: 85%; left: 15%; width: 50px; height: 50px; animation-duration: 20s;"></div>
    
    <div class="w-full max-w-4xl flex flex-col md:flex-row rounded-2xl overflow-hidden">
        <!-- Left side - Branding and Info -->
        <div class="w-full md:w-1/2 p-10 flex flex-col justify-between bg-gradient-to-br from-primary to-secondary text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-grid-white/[0.05]"></div>
            <div class="relative z-10">
                <div class="flex items-center mb-8">
                    <div class="bg-white p-2 rounded-lg mr-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-primary" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.364 0l5.508-2.361a11.026 11.026 0 01.25 3.762 1 1 0 01-.89.89 8.968 8.968 0 00-5.35 2.524 1 1 0 01-1.4 0zM6 18a1 1 0 001-1v-2.065a8.935 8.935 0 00-2-.712V17a1 1 0 001 1z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold">EduManage Pro</h1>
                </div>
                
                <h2 class="text-3xl font-bold mb-4">Welcome Back!</h2>
                <p class="opacity-90 mb-8">Streamline your academic experience with our comprehensive school management system.</p>
                
                <div class="flex items-center mb-6">
                    <div class="mr-4">
                        <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fas fa-chalkboard-teacher text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold">For Teachers & Administrators</h3>
                        <p class="text-sm opacity-80">Manage classes, grades, and student information</p>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="mr-4">
                        <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                            <i class="fas fa-user-graduate text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-semibold">For Students</h3>
                        <p class="text-sm opacity-80">Access schedules, assignments, and resources</p>
                    </div>
                </div>
            </div>
            
            <div class="relative z-10 mt-8">
                <div class="flex justify-center">
                    <div class="bg-white/20 backdrop-blur-sm rounded-full p-1 flex">
                        <div class="bg-white text-primary rounded-full p-2 mx-1 animate-float">
                            <i class="fas fa-book text-lg"></i>
                        </div>
                        <div class="bg-white text-primary rounded-full p-2 mx-1 animate-float" style="animation-delay: 0.5s">
                            <i class="fas fa-chart-line text-lg"></i>
                        </div>
                        <div class="bg-white text-primary rounded-full p-2 mx-1 animate-float" style="animation-delay: 1s">
                            <i class="fas fa-calendar-alt text-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right side - Login Form -->
        <div class="w-full md:w-1/2 p-10 login-container">
            <div class="text-center mb-10">
                <h2 class="text-3xl font-bold text-white mb-2">Sign In</h2>
                <p class="text-gray-300">Enter your credentials to access your account</p>
            </div>
            
            <form action="login.php" method="post" class="space-y-6">
                <div class="relative">
                    <input type="email" id="email" name="email" required 
                           class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-transparent focus:outline-none focus:ring-0"
                           placeholder="Email Address">
                    <label for="email" class="floating-label absolute left-4 top-3 text-gray-400">Email Address</label>
                    <div class="absolute right-3 top-3 text-gray-400">
                        <i class="fas fa-envelope"></i>
                    </div>
                </div>
                
                <div class="relative">
                    <input type="password" id="password" name="password" required 
                           class="input-field w-full px-4 py-3 rounded-lg text-white placeholder-transparent focus:outline-none focus:ring-0"
                           placeholder="Password">
                    <label for="password" class="floating-label absolute left-4 top-3 text-gray-400">Password</label>
                    <div class="absolute right-3 top-3 text-gray-400 toggle-password" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
                
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" class="h-4 w-4 text-primary rounded focus:ring-primary border-gray-600 bg-gray-700">
                        <label for="remember" class="ml-2 text-sm text-gray-300">Remember me</label>
                    </div>
                    <a href="#" class="text-sm text-primary hover:text-indigo-300 transition">Forgot Password?</a>
                </div>
                
                <div>
                    <button type="submit" class="login-btn w-full py-3 rounded-lg text-white font-semibold flex items-center justify-center">
                        <span>Login to Account</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
                
                <div class="text-center mt-6">
                    <p class="text-gray-400">Or continue with</p>
                    <div class="flex justify-center space-x-4 mt-3">
                        <button type="button" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-gray-700 transition">
                            <i class="fab fa-google text-red-400"></i>
                        </button>
                        <button type="button" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-gray-700 transition">
                            <i class="fab fa-microsoft text-blue-400"></i>
                        </button>
                        <button type="button" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-gray-700 transition">
                            <i class="fab fa-apple text-gray-300"></i>
                        </button>
                    </div>
                </div>
            </form>
            
            <div class="mt-8 text-center">
                <p class="text-gray-400">Don't have an account? <a href="#" class="text-primary hover:text-indigo-300 transition">Contact Administrator</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Create animated particles
        function createParticles() {
            const container = document.body;
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size (10px to 60px)
                const size = Math.random() * 50 + 10;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                
                // Random animation duration (10s to 25s)
                const duration = Math.random() * 15 + 10;
                particle.style.animationDuration = `${duration}s`;
                
                // Random background color
                const colors = [
                    'rgba(124, 58, 237, 0.5)', 
                    'rgba(79, 70, 229, 0.6)',
                    'rgba(139, 92, 246, 0.4)',
                    'rgba(99, 102, 241, 0.5)'
                ];
                const randomColor = colors[Math.floor(Math.random() * colors.length)];
                particle.style.background = randomColor;
                
                container.appendChild(particle);
            }
        }
        
        // Initialize particles
        createParticles();
        
        // Add animation to inputs on focus
        const inputs = document.querySelectorAll('.input-field');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('animate-pulse-slow');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('animate-pulse-slow');
            });
        });
        
        // Popup message system
        const popupContainer = document.getElementById('popup-container');
        
        function showPopup(type, title, message, duration = 5000) {
            // Create popup element
            const popup = document.createElement('div');
            popup.className = `popup ${type}`;
            
            // Determine icon based on type
            let icon;
            switch(type) {
                case 'success':
                    icon = 'fa-circle-check';
                    break;
                case 'error':
                    icon = 'fa-circle-exclamation';
                    break;
                case 'warning':
                    icon = 'fa-triangle-exclamation';
                    break;
                case 'info':
                    icon = 'fa-circle-info';
                    break;
                default:
                    icon = 'fa-circle-info';
            }
            
            // Popup content
            popup.innerHTML = `
                <i class="popup-icon fas ${icon}"></i>
                <div class="popup-content">
                    <div class="popup-title">${title}</div>
                    <div class="popup-message">${message}</div>
                </div>
                <div class="popup-close">
                    <i class="fas fa-times"></i>
                </div>
                <div class="popup-timer">
                    <div class="popup-timer-progress"></div>
                </div>
            `;
            
            // Add to container
            popupContainer.appendChild(popup);
            
            // Start timer progress
            const progressBar = popup.querySelector('.popup-timer-progress');
            progressBar.style.animation = `progress ${duration}ms linear forwards`;
            
            // Auto remove after duration
            const timer = setTimeout(() => {
                popup.style.animation = 'slide-out 0.3s ease-out forwards';
                setTimeout(() => {
                    popup.remove();
                }, 300);
            }, duration);
            
            // Close button event
            const closeBtn = popup.querySelector('.popup-close');
            closeBtn.addEventListener('click', () => {
                clearTimeout(timer);
                popup.style.animation = 'slide-out 0.3s ease-out forwards';
                setTimeout(() => {
                    popup.remove();
                }, 300);
            });
        }
        
        // Show popup if message exists in PHP session
        <?php if (!empty($popupMessage)): ?>
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    showPopup(
                        '<?php echo $popupType; ?>', 
                        '<?php echo $popupType === "success" ? "Success" : ($popupType === "error" ? "Error" : ($popupType === "warning" ? "Warning" : "Information")); ?>',
                        '<?php echo addslashes($popupMessage); ?>',
                        4000
                    );
                }, 300);
            });
        <?php endif; ?>
        
        // Example popups for demonstration
        document.addEventListener('DOMContentLoaded', () => {
            // Show a welcome message after a short delay
            setTimeout(() => {
                showPopup(
                    'info', 
                    'Welcome to EduManage Pro', 
                    'Please sign in to access your personalized dashboard',
                    5000
                );
            }, 1000);
            
            // Show a redirect notification after 8 seconds
            setTimeout(() => {
                showPopup(
                    'info', 
                    'Redirect Notice', 
                    'You will be automatically redirected to your dashboard after successful login',
                    5000
                );
            }, 8000);
        });
    </script>
</body>
</html>