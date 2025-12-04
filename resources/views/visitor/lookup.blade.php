<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Check-in</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">

<div class="max-w-lg w-full" style="animation: fadeUp .6s ease-out">

    <!-- Logo -->
    <div class="flex justify-center mb-6">
        <img src="{{ asset('images/image.png') }}" alt="VMS Logo" class="h-20 w-20">
    </div>

    <h1 class="text-3xl font-semibold mb-2 text-gray-900 text-center">Visitor Check-in</h1>
    
    @if(session('checkin_location_name'))
        <div class="mb-4 p-3 rounded-lg border flex items-center gap-2" style="background-color: rgba(100, 45, 134, 0.1); color: #642d86; border-color: rgba(100, 45, 134, 0.3);">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
            </svg>
            <span class="font-medium">Checking in at: {{ session('checkin_location_name') }}</span>
        </div>
    @endif

    <p class="text-gray-600 mb-6">
        Enter your email address or phone number.
        If you've visited before, we'll find your profile automatically.
    </p>

    <form action="{{ route('visitor.postLookup') }}" method="post"
          class="space-y-5 bg-white p-6 rounded-2xl shadow border border-gray-100">
        @csrf

        @if ($errors->any())
            <div class="p-3 rounded-md bg-red-50 text-red-700 text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Email -->
        <div>
            <label for="email" class="block text-sm font-medium text-gray-800">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300" style="--tw-ring-color: #642d86;" onfocus="this.style.borderColor='#642d86'" onblur="this.style.borderColor=''"
                   placeholder="you@example.com" />
        </div>

        <!-- OR Divider -->
        <div class="flex items-center my-2">
            <div class="flex-grow border-t border-gray-200"></div>
            <span class="px-3 text-xs text-gray-500">OR</span>
            <div class="flex-grow border-t border-gray-200"></div>
        </div>

        <!-- Phone -->
        <div>
            <label for="mobile" class="block text-sm font-medium text-gray-800">Phone number</label>
            <input id="mobile" name="mobile" type="text" value="{{ old('mobile') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300" style="--tw-ring-color: #642d86;" onfocus="this.style.borderColor='#642d86'" onblur="this.style.borderColor=''"
                   placeholder="0801 234 5678" />
        </div>

        <!-- Captcha -->
        <div x-data="{ 
            question: 'Loading...', 
            init() { this.refresh(); },
            async refresh() {
                this.question = '...';
                const res = await fetch('{{ route('captcha.image') }}');
                const data = await res.json();
                this.question = data.question;
            }
        }">
            <label for="captcha" class="block text-sm font-medium text-gray-800">Security Check</label>
            <div class="flex gap-3 mt-1">
                <div class="bg-gray-100 rounded-lg px-4 py-2 flex items-center justify-center border border-gray-200 min-w-[100px] cursor-pointer" 
                     @click="refresh" title="Click to refresh">
                    <span x-text="question" class="font-bold text-gray-700 tracking-wider"></span>
                </div>
                <input id="captcha" name="captcha" type="number" required
                       class="block w-full rounded-lg border-gray-300" style="--tw-ring-color: #642d86;" onfocus="this.style.borderColor='#642d86'" onblur="this.style.borderColor=''"
                       placeholder="Answer" />
            </div>
        </div>

        <!-- Submit -->
        <div class="pt-2">
            <button type="submit"
                    class="w-full flex justify-center items-center px-4 py-3 text-white font-medium rounded-lg shadow transition" style="background-color: #642d86;" onmouseover="this.style.backgroundColor='#7d3aa3'" onmouseout="this.style.backgroundColor='#642d86'">
                Continue
            </button>
        </div>
    </form>
</div>

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
