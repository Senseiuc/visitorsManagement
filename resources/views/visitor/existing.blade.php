<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Your Visit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

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
        <!-- Logo -->
        <div class="flex justify-center mb-4">
            <img src="{{ asset('images/image.png') }}" alt="VMS Logo" class="h-20 w-20">
        </div>
        
        <h1 class="text-3xl font-bold text-gray-800 flex items-center justify-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full animate-pulse" style="background-color: #642d86;"></span>
            Welcome back, {{ $visitor->full_name }}
        </h1>
        @if(session('checkin_location_name'))
            <div class="mt-2 inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium border" style="background-color: rgba(100, 45, 134, 0.1); color: #642d86; border-color: rgba(100, 45, 134, 0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                {{ session('checkin_location_name') }}
            </div>
        @endif
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

        <!-- Staff Lookup -->
        <div x-data="{ 
            query: '', 
            name: '', 
            error: '', 
            staffId: '',
            async lookup() {
                if (!this.query) return;
                this.error = '';
                this.name = '';
                try {
                    const res = await fetch(`{{ route('visitor.staff-lookup') }}?query=${encodeURIComponent(this.query)}`);
                    if (!res.ok) throw new Error('Not found');
                    const data = await res.json();
                    this.name = data.name;
                    this.staffId = data.id;
                } catch (e) {
                    this.error = 'Staff not found';
                    this.staffId = '';
                }
            }
        }">
            <label class="block text-sm font-medium text-gray-700">Staff ID or Email <span class="text-gray-400">(optional)</span></label>
            <div class="flex gap-2 mt-1">
                <input x-model="query" @keydown.enter.prevent="lookup" type="text"
                       class="block w-full rounded-lg border-gray-300 transition" style="--tw-ring-color: #642d86;" onfocus="this.style.borderColor='#642d86'" onblur="this.style.borderColor=''"
                       placeholder="Enter ID or Email" />
                <button type="button" @click="lookup"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                    Verify
                </button>
            </div>
            <p x-show="name" class="mt-2 text-sm text-green-600 font-medium">
                Verified: <span x-text="name"></span>
            </p>
            <p x-show="error" class="mt-2 text-sm text-red-600" x-text="error"></p>
            <input type="hidden" name="staff_visited_id" x-model="staffId">
        </div>

        <!-- Reason -->
        <div>
            <label for="reason_for_visit_id" class="block text-sm font-medium text-gray-700">Reason for visit</label>
            <select id="reason_for_visit_id" name="reason_for_visit_id"
                    class="mt-1 block w-full rounded-lg border-gray-300 transition" style="--tw-ring-color: #642d86;" onfocus="this.style.borderColor='#642d86'" onblur="this.style.borderColor=''">
                <option value="">Select a reason</option>
                @foreach ($reasonOptions as $id => $name)
                    <option value="{{ $id }}" @selected(old('reason_for_visit_id') == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Submit -->
        <div class="pt-2">
            <button type="submit"
                    class="w-full flex justify-center items-center gap-2 px-4 py-3 text-white rounded-lg shadow transition" style="background-color: #642d86;" onmouseover="this.style.backgroundColor='#7d3aa3'" onmouseout="this.style.backgroundColor='#642d86'">
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
           class="text-sm transition" style="color: #642d86;" onmouseover="this.style.textDecoration='underline'; this.style.color='#7d3aa3'" onmouseout="this.style.textDecoration='none'; this.style.color='#642d86'">
            Not you? Go back
        </a>
    </div>

</div>

</body>
</html>
