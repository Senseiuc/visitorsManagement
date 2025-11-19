<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Your Visit</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fade + Slide Animation -->
    <style>
        .fade-in {
            animation: fadeIn 0.6s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-start md:items-center justify-center py-10">

<div class="w-full max-w-xl p-6 fade-in">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center gap-2">
            <span class="inline-block w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span>
            Welcome back, {{ $visitor->full_name }}
        </h1>
        <p class="text-gray-600 mt-1">
            Enter the staff's phone number (optional) and choose your reason for visiting.
        </p>
    </div>

    <!-- Form Card -->
    <form action="{{ route('visitor.checkin') }}" method="post"
          class="bg-white border border-gray-100 shadow-lg rounded-xl p-6 space-y-6 fade-in"
          style="animation-delay: 0.15s">
        @csrf
        <input type="hidden" name="mode" value="existing">
        <input type="hidden" name="visitor_id" value="{{ $visitor->id }}">

        @if ($errors->any())
            <div class="p-3 rounded-xl bg-red-50 text-red-700 text-sm border border-red-100">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Staff Phone -->
        <div>
            <label for="staff_phone" class="block text-sm font-medium text-gray-700">Staff phone number <span class="text-gray-400">(optional)</span></label>
            <input id="staff_phone" name="staff_phone" type="tel"
                   value="{{ old('staff_phone') }}"
                   class="mt-1 block w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 transition"
                   placeholder="0801 234 5678" />
            <p class="text-xs text-gray-500 mt-1">
                Helps us identify the staff you're meeting. You can leave it blank.
            </p>
        </div>

        <!-- Reason -->
        <div>
            <label for="reason_for_visit_id" class="block text-sm font-medium text-gray-700">Reason for visit</label>
            <select id="reason_for_visit_id" name="reason_for_visit_id"
                    class="mt-1 block w-full rounded-lg border-gray-300 focus:border-amber-500 focus:ring-amber-500 transition">
                <option value="">Select a reason</option>
                @foreach ($reasonOptions as $id => $name)
                    <option value="{{ $id }}" @selected(old('reason_for_visit_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Submit -->
        <div class="pt-2">
            <button type="submit"
                    class="w-full flex justify-center items-center gap-2 px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg shadow transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 13l4 4L19 7" />
                </svg>
                Check In
            </button>
        </div>
    </form>

    <!-- Footer -->
    <div class="mt-6 text-center">
        <a href="{{ route('visitor.lookup') }}"
           class="text-amber-700 hover:text-amber-900 hover:underline text-sm transition">
            Not you? Go back
        </a>
    </div>

</div>

</body>
</html>
