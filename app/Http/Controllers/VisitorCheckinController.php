<?php

namespace App\Http\Controllers;

use App\Models\ReasonForVisit;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Cloudinary\Exception\ConfigurationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Log;
use Throwable;

class VisitorCheckinController extends Controller
{
    /**
     * Step 1: Show a lookup form for an existing/new visitor by email or phone.
     */
    public function showLookup(): View
    {
        return view('visitor.lookup');
    }

    /**
     * Step 1 POST: Determine if visitor exists, then redirect.
     */
    public function postLookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email'],
            'mobile' => ['nullable', 'string', 'max:50'],
        ]);

        if (blank($data['email'] ?? null) && blank($data['mobile'] ?? null)) {
            return back()->withErrors(['email' => 'Enter your email or phone number.'])->withInput();
        }

        $query = Visitor::query();
        if (! blank($data['email'] ?? null)) {
            $query->orWhere('email', $data['email']);
        }
        if (! blank($data['mobile'] ?? null)) {
            $query->orWhere('mobile', $data['mobile']);
        }

        $visitor = $query->first();

        if ($visitor) {
            return redirect()->route('visitor.existing', ['visitor' => $visitor->id]);
        }

        // Pre-fill new form with provided values
        return redirect()->route('visitor.new')->with([
            'prefill' => [
                'email' => $data['email'] ?? '',
                'mobile' => $data['mobile'] ?? '',
            ],
        ]);
    }

    /**
     * Step 2A: Existing visitor – just choose staff + reason and check-in.
     */
    public function showExisting(Request $request, Visitor $visitor): View
    {
        $reasons = ReasonForVisit::query()->orderBy('name')->pluck('name', 'id');

        return view('visitor.existing', [
            'visitor' => $visitor,
            'reasonOptions' => $reasons,
        ]);
    }

    /**
     * Step 2B: A new visitor – fill personal info + upload photo, choose staff + reason.
     */
    public function showNew(): View
    {
        $reasons = ReasonForVisit::query()->orderBy('name')->pluck('name', 'id');
        $prefill = session('prefill', []);

        return view('visitor.new', [
            'reasonOptions' => $reasons,
            'prefill' => $prefill,
        ]);
    }

    /**
     * Step 3: Create (or reuse) Visitor then create Visit as pending.
     */
    public function postCheckin(Request $request): RedirectResponse
    {
        $mode = $request->input('mode'); // 'existing' or 'new'

        $rules = [
            'staff_visited_id' => ['nullable', 'exists:users,id'],
            'staff_phone' => ['nullable', 'string', 'max:50'],
            'reason_for_visit_id' => ['nullable', 'exists:reasons_for_visit,id'],
        ];

        if ($mode === 'existing') {
            $rules['visitor_id'] = ['required', 'exists:visitors,id'];
        } else {
            $rules = array_merge($rules, [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'mobile' => ['nullable', 'string', 'max:50'],
                'image' => ['nullable', 'image', 'max:5120'], // 5MB
            ]);
        }

        $data = $request->validate($rules);

        if ($mode === 'existing') {
            $visitor = Visitor::findOrFail((int) $data['visitor_id']);
        } else {
            // If email/phone indicates existing, reuse it rather than duplicate
            $existing = Visitor::query()
                ->when(! empty($data['email'] ?? null), fn ($q) => $q->orWhere('email', $data['email']))
                ->when(! empty($data['mobile'] ?? null), fn ($q) => $q->orWhere('mobile', $data['mobile']))
                ->first();

            if ($existing) {
                $visitor = $existing;
            } else {
                $visitor = new Visitor();
                $visitor->first_name = (string) $data['first_name'];
                $visitor->last_name = (string) $data['last_name'];
                $visitor->email = $data['email'] ?? null;
                $visitor->mobile = $data['mobile'] ?? null;

                if ($request->hasFile('image')) {
                    // Try Cloudinary first; gracefully fall back to public disk if Cloudinary is not configured
                    try {
                        $disk = 'cloudinary';
                        $path = $request->file('image')->store('visitors', $disk);
                    } catch (ConfigurationException $e) {
                        Log::warning('Cloudinary misconfigured, falling back to public disk for visitor image', [
                            'exception' => $e->getMessage(),
                        ]);
                        $disk = 'public';
                        $path = $request->file('image')->store('visitors', $disk);
                    } catch (Throwable $e) {
                        Log::warning('Image upload failed on cloudinary, falling back to public disk', [
                            'exception' => $e->getMessage(),
                        ]);
                        $disk = 'public';
                        $path = $request->file('image')->store('visitors', $disk);
                    }
                    // Store the complete publicly accessible URL, not just the storage path
                    $visitor->image_url = Storage::disk($disk)->url($path);
                }
                $visitor->save();
            }
        }

        // Resolve staff by explicit id or by provided phone number (optional)
        $staffId = $data['staff_visited_id'] ?? null;
        if (empty($staffId) && ! empty($data['staff_phone'] ?? null)) {
            $phone = preg_replace('/\D+/', '', (string) $data['staff_phone']);
            $candidates = User::query()
                ->where('phone_number', $data['staff_phone'])
                ->orWhere('phone_number', $phone)
                ->pluck('id')
                ->all();
            if (count($candidates) === 1) {
                $staffId = (int) $candidates[0];
            }
        }

        // Create a pending visit (approved by receptionist later)
        $visit = new Visit();
        $visit->visitor_id = $visitor->id;
        $visit->staff_visited_id = $staffId ? (int) $staffId : null;
        $visit->reason_for_visit_id = $data['reason_for_visit_id'] ?? null;
        $visit->status = 'pending';
        $visit->checkin_time = null; // receptionist will approve and set check-in time
        $visit->save();

        return redirect()->route('visitor.success')->with('visitor_name', $visitor->first_name . ' ' . $visitor->last_name);
    }

    /**
     * Final confirmation page.
     */
    public function success(): View
    {
        $name = session('visitor_name');
        return view('visitor.success', ['name' => $name]);
    }
}
