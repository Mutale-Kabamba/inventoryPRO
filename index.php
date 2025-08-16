<?php
// This preloader will show for 7 seconds before redirecting to the landing page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading InventoryPro...</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        emerald: {
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                        }
                    },
                    animation: {
                        'spin-slow': 'spin 3s linear infinite',
                        'pulse-fast': 'pulse 1s ease-in-out infinite',
                        'bounce-slow': 'bounce 2s infinite',
                        'fade-in': 'fadeIn 0.8s ease-in-out forwards',
                        'slide-up': 'slideUp 0.8s ease-out forwards',
                        'rotate-y': 'rotateY 2s ease-in-out infinite',
                        'scale-bounce': 'scaleBounce 1.5s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes rotateY {
            0% { transform: rotateY(0deg); }
            50% { transform: rotateY(180deg); }
            100% { transform: rotateY(360deg); }
        }
        
        @keyframes scaleBounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .loading-bar {
            animation: loadingProgress 7s ease-in-out forwards;
        }
        
        @keyframes loadingProgress {
            0% { width: 0%; }
            100% { width: 100%; }
        }
        
        .feature-animate-1 { animation-delay: 1s; }
        .feature-animate-2 { animation-delay: 2s; }
        .feature-animate-3 { animation-delay: 3s; }
    </style>
</head>
<body class="font-inter min-h-screen bg-gray-900 flex items-center justify-center overflow-hidden">
    <!-- Background Particles -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-1/4 left-1/4 w-4 h-4 bg-white rounded-full opacity-20 animate-bounce" style="animation-delay: 0.5s;"></div>
        <div class="absolute top-1/3 right-1/4 w-3 h-3 bg-white rounded-full opacity-30 animate-bounce" style="animation-delay: 1.5s;"></div>
        <div class="absolute bottom-1/4 left-1/3 w-2 h-2 bg-white rounded-full opacity-25 animate-bounce" style="animation-delay: 2.5s;"></div>
        <div class="absolute top-1/2 right-1/3 w-5 h-5 bg-white rounded-full opacity-15 animate-bounce" style="animation-delay: 3.5s;"></div>
    </div>

    <div class="text-center text-white relative z-10 max-w-2xl px-6">
        <!-- Logo Section -->
        <div class="mb-12 animate-fade-in">
            <div class="text-6xl mb-4">
                <i class="fas fa-chart-line text-white animate-rotate-y"></i>
            </div>
            <h1 class="text-4xl md:text-5xl font-bold mb-2">InventoryPro</h1>
            <p class="text-xl text-white/80">Preparing your workspace...</p>
        </div>

        <!-- Feature Icons Only -->
        <div class="flex justify-center items-center space-x-12 mb-12">
            <div class="feature-animate-1 opacity-0 animate-slide-up">
                <i class="fas fa-boxes text-emerald-400 text-4xl animate-scale-bounce"></i>
            </div>
            <div class="feature-animate-2 opacity-0 animate-slide-up">
                <i class="fas fa-chart-bar text-blue-400 text-4xl animate-pulse-fast"></i>
            </div>
            <div class="feature-animate-3 opacity-0 animate-slide-up">
                <i class="fas fa-clock text-purple-400 text-4xl animate-spin-slow"></i>
            </div>
        </div>

        <!-- Loading Progress -->
        <div class="mb-8 animate-fade-in" style="animation-delay: 0.5s;">
            <div class="bg-white/20 rounded-full h-2 mb-4 overflow-hidden">
                <div class="loading-bar h-full bg-gradient-to-r from-emerald-400 to-blue-500 rounded-full"></div>
            </div>
            <p class="text-white/80">Loading your dashboard...</p>
        </div>

        <!-- Loading Dots -->
        <div class="flex justify-center space-x-2">
            <div class="w-3 h-3 bg-white rounded-full animate-bounce" style="animation-delay: 0s;"></div>
            <div class="w-3 h-3 bg-white rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
            <div class="w-3 h-3 bg-white rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
        </div>
    </div>

    <script>
        // Auto redirect after 7 seconds
        setTimeout(function() {
            // Add fade out effect before redirect
            document.body.style.transition = 'opacity 0.5s ease-out';
            document.body.style.opacity = '0';
            
            setTimeout(function() {
                window.location.href = 'index.html';
            }, 500);
        }, 7000);

        // Add some interactive particles
        function createParticle() {
            const particle = document.createElement('div');
            particle.className = 'absolute w-1 h-1 bg-white rounded-full opacity-40';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animation = 'float 3s ease-in-out infinite';
            document.querySelector('.absolute.inset-0').appendChild(particle);
            
            setTimeout(() => {
                particle.remove();
            }, 3000);
        }

        // Create particles every 500ms
        setInterval(createParticle, 500);

        // Add floating animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float {
                0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.4; }
                50% { transform: translateY(-20px) rotate(180deg); opacity: 0.8; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
