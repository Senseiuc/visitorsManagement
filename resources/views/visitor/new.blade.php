<!doctype html>
<html lang="en" x-data="visitorForm()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .purple-ring:focus {
            --tw-ring-color: #642d86;
            border-color: #642d86;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center py-10">
<div class="w-full max-w-2xl p-6">

    <!-- Header -->
    <div class="mb-8 text-center">
        <!-- Logo -->
        <div class="flex justify-center mb-4">
            <img src="{{ asset('images/image.png') }}" alt="VMS Logo" class="h-20 w-20">
        </div>

        <h1 class="text-3xl font-bold text-gray-800">New Visitor</h1>
        @if(session('checkin_location_name'))
            <div class="mt-2 inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium border" style="background-color: rgba(100, 45, 134, 0.1); color: #642d86; border-color: rgba(100, 45, 134, 0.3);">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                </svg>
                {{ session('checkin_location_name') }}
            </div>
        @endif
        <p class="text-gray-600 mt-1">Please complete all steps to check in.</p>
    </div>

    <!-- STEPPER -->
    <div class="relative mb-10">
        <!-- LINE -->
        <div class="absolute inset-x-0 top-5 h-1 bg-gray-200 rounded-full">
            <div class="h-1 rounded-full transition-all duration-500" style="background-color: #642d86;"
                 :style="`width: ${(step - 1) / 2 * 100}%`">
            </div>
        </div>

        <!-- STEPS -->
        <div class="flex justify-between relative z-10">

            <!-- Step 1 -->
            <div class="flex flex-col items-center w-1/3 text-center">
                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2 transition-all duration-300"
                     :style="step >= 1 ? 'background-color: #642d86; color: white; border-color: #642d86' : 'background-color: white; color: #6b7280; border-color: #d1d5db'">
                    <span>1</span>
                </div>
                <p class="mt-2 text-sm font-medium"
                   :style="step >= 1 ? 'color: #642d86' : 'color: #6b7280'">
                    Personal
                </p>
            </div>

            <!-- Step 2 -->
            <div class="flex flex-col items-center w-1/3 text-center">
                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2 transition-all duration-300"
                     :style="step >= 2 ? 'background-color: #642d86; color: white; border-color: #642d86' : 'background-color: white; color: #6b7280; border-color: #d1d5db'">
                    <span>2</span>
                </div>
                <p class="mt-2 text-sm font-medium"
                   :style="step >= 2 ? 'color: #642d86' : 'color: #6b7280'">
                    Contact
                </p>
            </div>

            <!-- Step 3 -->
            <div class="flex flex-col items-center w-1/3 text-center">
                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2 transition-all duration-300"
                     :style="step >= 3 ? 'background-color: #642d86; color: white; border-color: #642d86' : 'background-color: white; color: #6b7280; border-color: #d1d5db'">
                    <span>3</span>
                </div>
                <p class="mt-2 text-sm font-medium"
                   :style="step >= 3 ? 'color: #642d86' : 'color: #6b7280'">
                    Visit Details
                </p>
            </div>

        </div>
    </div>

    <!-- FORM -->
    <form action="{{ route('visitor.checkin') }}" method="post" enctype="multipart/form-data"
          class="bg-white shadow-lg rounded-xl p-6 space-y-6 border border-gray-100">
        @csrf
        <input type="hidden" name="mode" value="new">

        <!-- STEP 1: PERSONAL INFO -->
        <div x-show="step === 1" x-transition.opacity x-transition.scale.origin.top>
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Personal Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First name</label>
                    <input x-model="form.first_name" name="first_name"
                           type="text" class="mt-1 w-full rounded-lg border-gray-300 purple-ring" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Last name</label>
                    <input x-model="form.last_name" name="last_name"
                           type="text" class="mt-1 w-full rounded-lg border-gray-300 purple-ring" required>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Organization <span class="text-gray-400">(optional)</span></label>
                <input x-model="form.organization" name="organization" type="text"
                       class="mt-1 w-full rounded-lg border-gray-300 purple-ring"
                       placeholder="Company / Organization name">
            </div>
        </div>

        <!-- STEP 2: CONTACT -->
        <div x-show="step === 2" x-transition.opacity x-transition.scale.origin.top>
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Contact Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input x-model="form.email" name="email" type="email"
                           class="mt-1 w-full rounded-lg border-gray-300 purple-ring"
                           placeholder="you@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input x-model="form.mobile" name="mobile"
                           type="text" class="mt-1 w-full rounded-lg border-gray-300 purple-ring"
                           placeholder="0801 234 5678">
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Photo (optional)</label>
                <input name="image" type="file" accept="image/*" capture="environment"
                       class="mt-1 block w-full text-sm text-gray-700">
                <p class="text-xs text-gray-500">Max 5MB</p>
            </div>
        </div>

        <!-- STEP 3: VISIT DETAILS -->
        <div x-show="step === 3" x-transition.opacity x-transition.scale.origin.top>
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Visit Details</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700">Staff ID or Email <span class="text-gray-400">(optional)</span></label>
                <div class="flex gap-2">
                    <input x-model="staffQuery" @keydown.enter.prevent="lookupStaff" type="text"
                           placeholder="Enter Staff ID or Email"
                           class="mt-1 w-full rounded-lg border-gray-300 purple-ring">
                    <button type="button" @click="lookupStaff"
                            class="mt-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                        Verify
                    </button>
                </div>
                <p x-show="staffName" class="mt-2 text-sm text-green-600 font-medium">
                    Verified: <span x-text="staffName"></span>
                </p>
                <p x-show="staffError" class="mt-2 text-sm text-red-600" x-text="staffError"></p>
                <input type="hidden" name="staff_visited_id" x-model="form.staff_visited_id">
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Reason for visit</label>
                <select x-model="form.reason_for_visit_id" name="reason_for_visit_id"
                        class="mt-1 w-full rounded-lg border-gray-300 purple-ring">
                    <option value="">Select a reason</option>
                    @foreach ($reasonOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- NAVIGATION BUTTONS -->
        <div class="flex justify-between pt-2">
            <button type="button" x-show="step > 1" @click="prevStep"
                    class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">
                Back
            </button>

            <button type="button" x-show="step < 3" @click="nextStep"
                    class="ml-auto px-6 py-2 rounded-lg text-white shadow transition"
                    style="background-color: #642d86;"
                    onmouseover="this.style.backgroundColor='#7d3aa3'"
                    onmouseout="this.style.backgroundColor='#642d86'">
                Continue
            </button>

            <button type="submit" x-show="step === 3"
                    class="ml-auto px-6 py-2 rounded-lg bg-green-600 text-white shadow hover:bg-green-700">
                Check In
            </button>
        </div>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('visitor.lookup') }}" class="text-sm transition" style="color: #642d86;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Back to Lookup</a>
    </div>
</div>

<script>
    function visitorForm() {
        return {
            step: 1,
            staffQuery: '',
            staffName: '',
            staffError: '',
            form: {
                first_name: '',
                last_name: '',
                email: '',
                mobile: '',
                organization: '',
                staff_visited_id: '',
                reason_for_visit_id: '',
            },

            async lookupStaff() {
                if (!this.staffQuery) return;
                this.staffError = '';
                this.staffName = '';

                try {
                    const res = await fetch(`{{ route('visitor.staff-lookup') }}?query=${encodeURIComponent(this.staffQuery)}`);
                    if (!res.ok) throw new Error('Staff not found');
                    const data = await res.json();
                    this.staffName = data.name;
                    this.form.staff_visited_id = data.id;
                } catch (e) {
                    this.staffError = 'Staff member not found. Please check ID or Email.';
                    this.form.staff_visited_id = '';
                }
            },

            nextStep() {
                // Basic validation
                if (this.step === 1 && (!this.form.first_name || !this.form.last_name)) {
                    alert("Please fill in your first and last name.");
                    return;
                }
                this.step++;
            },

            prevStep() {
                this.step--;
            },
        }
    }
</script>

</body>
</html>
