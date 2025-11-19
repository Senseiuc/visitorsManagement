<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome – Visitor Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(25px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">

<div class="max-w-2xl w-full text-center" style="animation: fadeUp .6s ease-out">

    <!-- Logo / Title -->
    <h1 class="text-4xl font-bold text-gray-900 mb-3">
        Visitor Management System
    </h1>

    <p class="text-gray-600 mb-10 text-lg">
        Welcome! Please choose how you would like to continue.
    </p>

    <!-- Card -->
    <div class="bg-white p-8 rounded-2xl shadow-md border border-gray-100 space-y-6">

        <!-- Visitor Button -->
        <a href="/visitor"
           class="block w-full px-6 py-4 bg-amber-600 hover:bg-amber-700 text-white text-lg font-medium rounded-lg shadow transition">
            Visitor Check-in
        </a>

        <!-- Admin Button -->
        <a href="/admin"
           class="block w-full px-6 py-4 bg-gray-800 hover:bg-gray-900 text-white text-lg font-medium rounded-lg shadow transition">
            Admin Dashboard
        </a>

    </div>

    <p class="text-xs text-gray-400 mt-6">
        © {{ date('Y') }} Visitor Management System
    </p>
</div>

</body>
</html>
