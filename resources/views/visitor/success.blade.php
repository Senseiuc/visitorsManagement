<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check-in Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pop {
            0% { transform: scale(0.5); opacity: 0; }
            60% { transform: scale(1.15); opacity: 1; }
            100% { transform: scale(1); }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">

<div class="max-w-md w-full">
    <div class="bg-white p-8 shadow-lg rounded-2xl border border-gray-100"
         style="animation: fadeUp .6s ease-out">

        <!-- Animated Checkmark -->
        <div class="flex justify-center">
            <div class="h-14 w-14 rounded-full bg-amber-100 flex items-center justify-center"
                 style="animation: pop .6s ease-out .1s both;">
                <svg class="h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        <h1 class="text-2xl font-semibold mt-6 text-gray-900">
            Thank you{{ $name ? ', ' . e($name) : '' }}!
        </h1>

        <p class="text-gray-600 mt-2 leading-relaxed">
            Your check-in has been submitted. Please wait while our reception team approves your visit.
        </p>

        <!-- Button -->
        <div class="mt-8">
            <a href="{{ route('visitor.lookup') }}"
               class="block w-full text-center px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg shadow-sm transition">
                New Check-in
            </a>
        </div>
    </div>
</div>

</body>
</html>
