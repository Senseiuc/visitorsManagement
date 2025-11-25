<?php

namespace App\Services;

use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Traits\HasImageUpload;
use Cloudinary\Exception\ConfigurationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Log;
use Throwable;

class CheckinService
{
    use HasImageUpload;

    /**
     * Find or create a visitor based on provided data
     */
    public function findOrCreateVisitor(array $data, ?UploadedFile $imageFile = null): Visitor
    {
        // Check if visitor already exists by email or mobile
        $existing = Visitor::query()
            ->when(!empty($data['email'] ?? null), fn($q) => $q->orWhere('email', $data['email']))
            ->when(!empty($data['mobile'] ?? null), fn($q) => $q->orWhere('mobile', $data['mobile']))
            ->first();

        if ($existing) {
            return $existing;
        }

        // Create new visitor
        $visitor = new Visitor();
        $visitor->first_name = (string) $data['first_name'];
        $visitor->last_name = (string) $data['last_name'];
        $visitor->email = $data['email'] ?? null;
        $visitor->mobile = $data['mobile'] ?? null;

        if ($imageFile) {
            try {
                $disk = 'cloudinary';
                $path = $imageFile->store('visitors', $disk);
            } catch (ConfigurationException $e) {
                Log::warning('Cloudinary misconfigured, falling back to public disk for visitor image', [
                    'exception' => $e->getMessage(),
                ]);
                $disk = 'public';
                $path = $imageFile->store('visitors', $disk);
            } catch (Throwable $e) {
                Log::warning('Image upload failed on cloudinary, falling back to public disk', [
                    'exception' => $e->getMessage(),
                ]);
                $disk = 'public';
                $path = $imageFile->store('visitors', $disk);
            }
            $visitor->image_url = Storage::disk($disk)->url($path);
        }

        $visitor->save();
        return $visitor;
    }

    /**
     * Validate that visitor doesn't have an active check-in at the location
     * 
     * @throws ValidationException
     */
    public function validateDuplicateCheckin(Visitor $visitor, ?int $locationId): void
    {
        $activeVisit = Visit::query()
            ->where('visitor_id', $visitor->id)
            ->where('status', 'approved')
            ->whereNotNull('checkin_time')
            ->whereNull('checkout_time')
            ->when($locationId, fn($q) => $q->where('location_id', $locationId))
            ->first();

        if ($activeVisit) {
            throw ValidationException::withMessages([
                'visitor' => 'This visitor is already checked in at this location. Please check them out first.',
            ]);
        }
    }

    /**
     * Create a pending visit
     */
    public function createVisit(Visitor $visitor, array $data): Visit
    {
        $visit = new Visit();
        $visit->visitor_id = $visitor->id;
        $visit->staff_visited_id = $data['staff_visited_id'] ?? null;
        $visit->location_id = $data['location_id'] ?? null;
        $visit->reason_for_visit_id = $data['reason_for_visit_id'] ?? null;
        $visit->status = 'pending';
        $visit->checkin_time = null; // Will be set by receptionist on approval
        $visit->save();

        return $visit;
    }

    /**
     * Validate that staff belongs to the location
     * 
     * @throws ValidationException
     */
    public function validateStaffLocation(?int $staffId, ?int $locationId): void
    {
        if (!$staffId || !$locationId) {
            return;
        }

        $staff = User::find($staffId);
        if (!$staff) {
            throw ValidationException::withMessages([
                'staff_visited_id' => 'Selected staff member not found.',
            ]);
        }

        $staffLocationIds = $staff->locationIds();
        if (!in_array($locationId, $staffLocationIds)) {
            throw ValidationException::withMessages([
                'staff_visited_id' => 'Selected staff member does not belong to this location.',
            ]);
        }
    }
}
