<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Checkpoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CheckpointController extends Controller
{
    private const INPUT_TYPES = ['radio', 'text', 'number', 'dropdown', 'date'];

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'equipment_category' => ['sometimes', 'string', 'max:150'],
            'input_type' => ['sometimes', Rule::in(self::INPUT_TYPES)],
            'is_mandatory' => ['sometimes', 'boolean'],
        ]);

        $checkpoints = $this->filteredQuery($request)
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $checkpoints]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'equipment_category' => ['sometimes', 'string', 'max:150'],
            'input_type' => ['sometimes', Rule::in(self::INPUT_TYPES)],
            'is_mandatory' => ['sometimes', 'boolean'],
        ]);
        $term = $validated['q'];

        $checkpoints = $this->filteredQuery($request)
            ->where(function (Builder $query) use ($term): void {
                $query->where('checkpoint_code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('equipment_category', 'like', "%{$term}%");
            })
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json(['success' => true, 'data' => $checkpoints]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCheckpoint($request);
        $validated['admin_id'] = $request->user()->id;

        $checkpoint = DB::transaction(function () use ($validated): Checkpoint {
            $checkpoint = Checkpoint::create($validated);
            $checkpoint->update(['checkpoint_code' => sprintf('CHK-%03d', $checkpoint->id)]);

            return $checkpoint->refresh();
        });

        return response()->json([
            'success' => true,
            'message' => 'Checkpoint created successfully.',
            'data' => $checkpoint,
        ], 201);
    }

    public function show(Request $request, int $checkpoint): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->baseQuery($request)->findOrFail($checkpoint),
        ]);
    }

    public function update(Request $request, int $checkpoint): JsonResponse
    {
        $record = $this->baseQuery($request)->findOrFail($checkpoint);
        $record->update($this->validateCheckpoint($request, true));

        return response()->json([
            'success' => true,
            'message' => 'Checkpoint updated successfully.',
            'data' => $record->refresh(),
        ]);
    }

    public function destroy(Request $request, int $checkpoint): JsonResponse
    {
        $this->baseQuery($request)->findOrFail($checkpoint)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Checkpoint deleted successfully.',
        ]);
    }

    private function baseQuery(Request $request): Builder
    {
        return Checkpoint::query()->where('admin_id', $request->user()->id);
    }

    private function filteredQuery(Request $request): Builder
    {
        return $this->baseQuery($request)
            ->when($request->filled('equipment_category'), fn (Builder $query) => $query->where('equipment_category', $request->input('equipment_category')))
            ->when($request->filled('input_type'), fn (Builder $query) => $query->where('input_type', $request->input('input_type')))
            ->when($request->has('is_mandatory'), fn (Builder $query) => $query->where('is_mandatory', $request->boolean('is_mandatory')));
    }

    private function validateCheckpoint(Request $request, bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'name' => [$presence, 'string', 'max:255'],
            'equipment_category' => [$presence, 'string', 'max:150'],
            'input_type' => [$presence, Rule::in(self::INPUT_TYPES)],
            'is_mandatory' => ['sometimes', 'boolean'],
            'requires_failure_photo' => ['sometimes', 'boolean'],
        ]);
    }
}
