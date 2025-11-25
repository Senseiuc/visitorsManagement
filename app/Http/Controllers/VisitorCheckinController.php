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
    /**
     * Start check-in for a specific location via UUID.
     */
    public function startLocationCheckin(string $uuid): RedirectResponse
    {
        $location = \App\Models\Location::where('uuid', $uuid)->firstOrFail();
        
        // Store location in session
        session(['checkin_location_id' => $location->id]);
        session(['checkin_location_name' => $location->name]);

        return redirect()->route('visitor.lookup');
    }

    /**
     * Step 1: Show a lookup form for an existing/new visitor by email or phone.
     */
    public function showLookup(): View
    {
        if (! session()->has('checkin_location_id')) {
            $defaultLocation = \App\Models\Location::first();
            if ($defaultLocation) {
                session(['checkin_location_id' => $defaultLocation->id]);
                session(['checkin_location_name' => $defaultLocation->name]);
            }
        }

        return view('visitor.lookup', [
            'locationName' => session('checkin_location_name'),
        ]);
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
        $locationId = session('checkin_location_id');

        return view('visitor.existing', [
            'visitor' => $visitor,
            'reasonOptions' => $reasons,
            'locationName' => session('checkin_location_name'),
            'locationId' => $locationId,
        ]);
    }

    /**
     * Step 2B: A new visitor – fill personal info + upload photo, choose staff + reason.
     */
    public function showNew(): View
    {
        $reasons = ReasonForVisit::query()->orderBy('name')->pluck('name', 'id');
        $prefill = session('prefill', []);
        $locationId = session('checkin_location_id');

        return view('visitor.new', [
            'reasonOptions' => $reasons,
            'prefill' => $prefill,
            'locationName' => session('checkin_location_name'),
            'locationId' => $locationId,
        ]);
    }

    /**
     * AJAX Endpoint: Lookup staff by ID or Email.
     */
    public function lookupStaff(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->query('query');
        $locationId = session('checkin_location_id');

        if (blank($query)) {
            return response()->json(['error' => 'Query required'], 400);
        }

        $staff = User::query()
            ->where(function ($q) use ($query) {
                $q->where('email', $query)
                  ->orWhere('staff_id', $query);
            })
            ->when($locationId, function ($q) use ($locationId) {
                // Scope to location if set
                $q->where(function ($sub) use ($locationId) {
                    $sub->whereHas('locations', fn ($l) => $l->where('locations.id', $locationId))
                        ->orWhere('assigned_location_id', $locationId);
                });
            })
            ->first(['id', 'name', 'email', 'staff_id']);

        if (! $staff) {
            return response()->json(['error' => 'Staff not found'], 404);
        }

        return response()->json($staff);
    }

    /**
     * Step 3: Create (or reuse) Visitor then create Visit as pending.
     */
    public function postCheckin(Request $request): RedirectResponse
    {
        $mode = $request->input('mode'); // 'existing' or 'new'
        $locationId = session('checkin_location_id');

        $rules = [
            'staff_visited_id' => ['nullable', 'exists:users,id'],
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

        $checkinService = new \App\Services\CheckinService();

        if ($mode === 'existing') {
            $visitor = Visitor::findOrFail((int) $data['visitor_id']);
        } else {
            $visitor = $checkinService->findOrCreateVisitor($data, $request->file('image'));
        }

        // Validate no duplicate check-in
        try {
            $checkinService->validateDuplicateCheckin($visitor, $locationId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        // Validate staff belongs to location
        $staffId = $data['staff_visited_id'] ? (int) $data['staff_visited_id'] : null;
        try {
            $checkinService->validateStaffLocation($staffId, $locationId);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        // Create visit
        $visit = $checkinService->createVisit($visitor, [
            'staff_visited_id' => $staffId,
            'location_id' => $locationId,
            'reason_for_visit_id' => $data['reason_for_visit_id'] ?? null,
        ]);

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
