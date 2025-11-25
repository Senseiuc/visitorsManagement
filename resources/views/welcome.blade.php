<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome – Visitor Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(30px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .logo-container {
            animation: float 3s ease-in-out infinite;
        }

        .fade-in {
            animation: fadeIn 1s ease-out;
        }

        .slide-up {
            animation: slideUp 0.8s ease-out;
        }

        .slide-up-delay-1 {
            animation: slideUp 0.8s ease-out 0.2s backwards;
        }

        .slide-up-delay-2 {
            animation: slideUp 0.8s ease-out 0.4s backwards;
        }

        .slide-up-delay-3 {
            animation: slideUp 0.8s ease-out 0.6s backwards;
        }

        .btn-hover-effect {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .btn-hover-effect::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-hover-effect:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .feature-card {
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(100, 45, 134, 0.1);
        }
    </style>
</head>

<body class="bg-white min-h-screen flex items-center justify-center p-4 md:p-6">

<div class="max-w-6xl w-full">
    
    <!-- Main Content Container -->
    <div class="text-center mb-8 slide-up">
        
        <!-- Logo -->
        <div class="flex justify-center mb-6 logo-container">
            <div class="relative">
                <div class="absolute inset-0 bg-purple-400 rounded-full blur-2xl opacity-20"></div>
                <img src="{{ asset('images/image.png') }}" alt="VMS Logo" class="relative h-32 w-32 md:h-40 md:w-40 object-contain drop-shadow-2xl">
            </div>
        </div>

        <!-- Title -->
        <h1 class="text-4xl md:text-6xl font-bold text-gray-900 mb-4 tracking-tight">
            Visitor Management
            <span class="block mt-2" style="color: #642d86;">System</span>
        </h1>
    </div>

    <!-- Action Cards -->
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        
        <!-- Visitor Check-in Card -->
        <div class="glass-effect rounded-3xl p-8 shadow-2xl slide-up-delay-1 border-2" style="border-color: #642d86;">
            <div class="flex items-center justify-center w-16 h-16 rounded-2xl mb-6 mx-auto" style="background: linear-gradient(135deg, #642d86 0%, #8b4fb8 100%);">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 mb-3">Visitor Check-in</h2>
            <p class="text-gray-600 mb-6 leading-relaxed">
                Quick and easy self-service check-in for visitors. Get started in seconds with our streamlined process.
            </p>
            
            <a href="/visitor" class="btn-hover-effect block w-full px-6 py-4 text-white text-lg font-semibold rounded-xl shadow-lg transition-all relative" style="background: linear-gradient(135deg, #642d86 0%, #8b4fb8 100%);">
                <span class="relative z-10 flex items-center justify-center gap-2">
                    Start Check-in
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </span>
            </a>
        </div>

        <!-- Admin Dashboard Card -->
        <div class="glass-effect rounded-3xl p-8 shadow-2xl slide-up-delay-2 border-2" style="border-color: #F2BC14;">
            <div class="flex items-center justify-center w-16 h-16 rounded-2xl mb-6 mx-auto" style="background: linear-gradient(135deg, #F2BC14 0%, #f5d563 100%);">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            
            <h2 class="text-2xl font-bold text-gray-900 mb-3">Admin Dashboard</h2>
            <p class="text-gray-600 mb-6 leading-relaxed">
                Manage visitors, track check-ins, and access comprehensive analytics and reports from your dashboard.
            </p>
            
            <a href="/admin" class="btn-hover-effect block w-full px-6 py-4 text-gray-900 text-lg font-semibold rounded-xl shadow-lg transition-all relative" style="background: linear-gradient(135deg, #F2BC14 0%, #f5d563 100%);">
                <span class="relative z-10 flex items-center justify-center gap-2">
                    Admin Login
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-8 slide-up-delay-3">
        <p class="text-sm text-gray-700 font-medium">
            © {{ date('Y') }} Visitor Management System. All rights reserved.
        </p>
    </div>

</div>

</body>
</html>
