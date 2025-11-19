<!doctype html>
<html lang="en" x-data="visitorForm()">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center py-10">
<div class="w-full max-w-2xl p-6">

    <!-- Header -->
    <div class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-800">New Visitor</h1>
        <p class="text-gray-600 mt-1">Please complete all steps to check in.</p>
    </div>

    <!-- STEPPER -->
    <!-- IMPROVED STEPPER -->
    <div class="relative mb-10">
        <!-- LINE -->
        <div class="absolute inset-x-0 top-5 h-1 bg-gray-200 rounded-full">
            <div class="h-1 bg-amber-600 rounded-full transition-all duration-500"
                 :style="`width: ${(step - 1) / 2 * 100}%`">
            </div>
        </div>

        <!-- STEPS -->
        <div class="flex justify-between relative z-10">

            <!-- Step 1 -->
            <div class="flex flex-col items-center w-1/3 text-center">
                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2
                        transition-all duration-300"
                     :class="step >= 1 ? 'bg-amber-600 text-white border-amber-600'
                                   : 'bg-white text-gray-600 border-gray-300'">
                    <span>1</span>
                </div>
                <p class="mt-2 text-sm font-medium"
                   :class="step >= 1 ? 'text-amber-600' : 'text-gray-500'">
                    Personal
                </p>
            </div>

            <!-- Step 2 -->
            <div class="flex flex-col items-center w-1/3 text-center">
                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2
                        transition-all duration-300"
                     :class="step >= 2 ? 'bg-amber-600 text-white border-amber-600'
                                   : 'bg-white text-gray-600 border-gray-300'">
                    <span>2</span>
                </div>
                <p class="mt-2 text-sm font-medium"
                   :class="step >= 2 ? 'text-amber-600' : 'text-gray-500'">
                    Contact
                </p>
            </div>

            <!-- Step 3 -->
            <div class="flex flex-col items-center w-1/3 text-center">
                <div class="w-10 h-10 flex items-center justify-center rounded-full border-2
                        transition-all duration-300"
                     :class="step >= 3 ? 'bg-amber-600 text-white border-amber-600'
                                   : 'bg-white text-gray-600 border-gray-300'">
                    <span>3</span>
                </div>
                <p class="mt-2 text-sm font-medium"
                   :class="step >= 3 ? 'text-amber-600' : 'text-gray-500'">
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
                           type="text" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-amber-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Last name</label>
                    <input x-model="form.last_name" name="last_name"
                           type="text" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-amber-500" required>
                </div>
            </div>
        </div>

        <!-- STEP 2: CONTACT -->
        <div x-show="step === 2" x-transition.opacity x-transition.scale.origin.top>
            <h2 class="text-lg font-semibold text-gray-700 mb-4">Contact Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input x-model="form.email" name="email" type="email"
                           class="mt-1 w-full rounded-lg border-gray-300 focus:ring-amber-500"
                           placeholder="you@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Phone</label>
                    <input x-model="form.mobile" name="mobile"
                           type="text" class="mt-1 w-full rounded-lg border-gray-300 focus:ring-amber-500"
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
                <label class="block text-sm font-medium text-gray-700">Staff Phone (optional)</label>
                <input x-model="form.staff_phone" name="staff_phone" type="tel"
                       placeholder="0801 234 5678"
                       class="mt-1 w-full rounded-lg border-gray-300 focus:ring-amber-500">
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700">Reason for visit</label>
                <select x-model="form.reason_for_visit_id" name="reason_for_visit_id"
                        class="mt-1 w-full rounded-lg border-gray-300 focus:ring-amber-500">
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
                    class="ml-auto px-6 py-2 rounded-lg bg-amber-600 text-white shadow hover:bg-amber-700">
                Continue
            </button>

            <button type="submit" x-show="step === 3"
                    class="ml-auto px-6 py-2 rounded-lg bg-green-600 text-white shadow hover:bg-green-700">
                Check In
            </button>
        </div>
    </form>

    <div class="mt-6 text-center">
        <a href="{{ route('visitor.lookup') }}" class="text-amber-700 hover:underline text-sm">Back to Lookup</a>
    </div>
</div>

<script>
    function visitorForm() {
        return {
            step: 1,
            form: {
                first_name: '',
                last_name: '',
                email: '',
                mobile: '',
                staff_phone: '',
                reason_for_visit_id: '',
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
