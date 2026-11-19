<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ActionMail;
use App\Models\Equipment;
use App\Models\Site;
use App\Services\AdminNotificationService;
use App\Services\CertificatePdfService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateEquipment($request);
        $site = $this->ownedSite($request, (int) $validated['site_id']);
        $validated['is_active'] = $this->activeValue($request);

        [$equipment, $certificate] = DB::transaction(function () use ($request, $validated): array {
            $equipment = Equipment::create($validated)->load('site.customer');
            $number = $request->input('certificate_number') ?: 'ASTA-'.now()->format('Ym').'-'.str_pad((string) $equipment->id, 6, '0', STR_PAD_LEFT);
            $issuedAt = $request->input('certificate_issued_at', now()->toDateString());
            $path = $request->hasFile('certificate_file')
                ? $request->file('certificate_file')->store('equipment-certificates/'.now()->format('Y/m'), 'public')
                : CertificatePdfService::generate($equipment, $number, $issuedAt);

            $certificate = $equipment->certificates()->create([
                'certificate_number' => $number,
                'title' => $request->input('certificate_title', 'Equipment Certificate'),
                'file_path' => $path,
                'issued_at' => $issuedAt,
                'expires_at' => $request->input('certificate_expires_at'),
            ]);

            return [$equipment, $certificate];
        });

        $recipient = $site->email ?: $site->customer->email;
        Mail::to($recipient)->send(new ActionMail(
            'Equipment Certificate Generated',
            "Hello {$site->customer->contact_person_name},\n\nEquipment {$equipment->name} ({$equipment->equipment_code}) was added at {$site->site_name}.\n\nCertificate: {$certificate->file_url}",
            $certificate->file_path,
            $certificate->certificate_number.'.'.pathinfo($certificate->file_path, PATHINFO_EXTENSION),
        ));

        AdminNotificationService::create(
            $request->user()->id, 'certificate_generated', 'Certificate Generated',
            "Certificate for equipment {$equipment->equipment_code} was generated and sent to {$recipient}.",
            'equipment', $equipment->id, ['certificate_id' => $certificate->id, 'certificate_url' => $certificate->file_url],
        );

        return response()->json([
            'success' => true,
            'message' => 'Equipment and certificate created successfully. Certificate email sent.',
            'data' => $equipment->fresh()->load(['site.customer', 'certificates']),
        ], 201);
    }

    public function show(Request $request, int $equipment): JsonResponse
    {
        $record = $this->baseQuery($request)->with(['site.customer', 'certificates'])->findOrFail($equipment);

        return response()->json(['success' => true, 'data' => $record]);
    }

    public function update(Request $request, int $equipment): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($equipment);
        $validated = $this->validateEquipment($request, $record, true);
        if (isset($validated['site_id'])) {
            $this->ownedSite($request, (int) $validated['site_id']);
        }
        if ($request->hasAny(['is_active', 'status'])) {
            $validated['is_active'] = $this->activeValue($request);
        }
        $record->update($validated);

        AdminNotificationService::create(
            $request->user()->id, 'equipment_updated', 'Equipment Updated',
            "Equipment {$record->name} was updated.", 'equipment', $record->id,
        );

        return response()->json(['success' => true, 'message' => 'Equipment updated successfully.', 'data' => $record->fresh()->load(['site.customer', 'certificates'])]);
    }

    public function destroy(Request $request, int $equipment): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($equipment);
        $name = $record->name;
        $id = $record->id;
        $record->delete();
        AdminNotificationService::create($request->user()->id, 'equipment_deleted', 'Equipment Deleted', "Equipment {$name} was deleted.", 'equipment', $id);

        return response()->json(['success' => true, 'message' => 'Equipment deleted successfully.']);
    }

    public function certificates(Request $request, int $equipment): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($equipment);
        $request->validate(['per_page' => ['sometimes', 'integer', 'min:1', 'max:100']]);
        $certificates = $record->certificates()->latest('issued_at')->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $certificates]);
    }

    private function baseQuery(Request $request): Builder
    {
        return Equipment::query()->whereHas('site.customer', fn (Builder $query) => $query->where('admin_id', $request->user()->id));
    }

    private function ownedSite(Request $request, int $siteId): Site
    {
        return Site::with('customer')->whereHas('customer', fn (Builder $query) => $query->where('admin_id', $request->user()->id))->findOrFail($siteId);
    }

    private function validateEquipment(Request $request, ?Equipment $equipment = null, bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';
        $siteId = (int) $request->input('site_id', $equipment?->site_id);

        return $request->validate([
            'site_id' => [$presence, 'integer', 'exists:sites,id'],
            'name' => [$presence, 'string', 'max:150'],
            'short_name' => [$presence, 'string', 'max:50'],
            'manufacturer' => [$presence, 'string', 'max:150'],
            'model' => [$presence, 'string', 'max:100'],
            'serial_number' => [$presence, 'string', 'max:100'],
            'equipment_code' => [$presence, 'string', 'max:100', Rule::unique('equipments')->where('site_id', $siteId)->ignore($equipment?->id)],
            'rated_capacity' => [$presence, 'string', 'max:100'],
            'swl_capacity' => [$presence, 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'next_inspection_at' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['active', 'inactive'])],
            'certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'certificate_number' => ['nullable', 'string', 'max:100'],
            'certificate_title' => ['nullable', 'string', 'max:150'],
            'certificate_issued_at' => ['nullable', 'date'],
            'certificate_expires_at' => ['nullable', 'date', 'after_or_equal:certificate_issued_at'],
        ]);
    }

    private function activeValue(Request $request): bool
    {
        return $request->has('is_active') ? $request->boolean('is_active') : $request->input('status', 'active') === 'active';
    }
}
