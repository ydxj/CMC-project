<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use App\Models\FiliereModulePhase;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FiliereModulePhasesController extends Controller
{
    public function index(int $filiereId)
    {
        $filiere = Filiere::find($filiereId);

        if (!$filiere) {
            return response()->json([
                'success' => false,
                'message' => 'Filiere not found',
            ], 404);
        }

        return response()->json($this->buildPayload($filiere));
    }

    public function update(Request $request, int $filiereId)
    {
        $filiere = Filiere::find($filiereId);

        if (!$filiere) {
            return response()->json([
                'success' => false,
                'message' => 'Filiere not found',
            ], 404);
        }

        $data = $request->validate([
            'phases' => 'required|array',
            'phases.*.phase_number' => 'required|integer|min:1',
            'phases.*.module_ids' => 'required|array',
            'phases.*.module_ids.*' => 'required|integer',
            'previous_module_by_id' => 'sometimes|array',
            'previous_module_by_id.*' => 'nullable|integer',
        ]);

        $moduleIds = [];
        foreach ($data['phases'] as $phaseRow) {
            foreach ($phaseRow['module_ids'] as $moduleId) {
                $moduleId = (int) $moduleId;
                if (in_array($moduleId, $moduleIds, true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Each module can only exist once in phases.',
                    ], 422);
                }

                $moduleIds[] = $moduleId;
            }
        }

        $validModuleIds = Module::query()
            ->where('id_filiere', $filiereId)
            ->whereIn('id', $moduleIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($moduleIds);
        sort($validModuleIds);
        if ($moduleIds !== $validModuleIds) {
            return response()->json([
                'success' => false,
                'message' => 'Some modules do not belong to the selected filiere.',
            ], 422);
        }

        $previousByModule = collect($data['previous_module_by_id'] ?? [])
            ->mapWithKeys(fn ($prev, $moduleId) => [(int) $moduleId => $prev === null ? null : (int) $prev])
            ->all();

        foreach ($previousByModule as $moduleId => $previousModuleId) {
            if (!in_array((int) $moduleId, $moduleIds, true)) {
                continue;
            }

            if ($previousModuleId === null) {
                continue;
            }

            if ($previousModuleId === (int) $moduleId || !in_array($previousModuleId, $moduleIds, true)) {
                $previousByModule[$moduleId] = null;
            }
        }

        if ($this->hasCycle($moduleIds, $previousByModule)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid dependencies: cycle detected between modules.',
            ], 422);
        }

        DB::transaction(function () use ($filiereId, $moduleIds, $previousByModule) {
            FiliereModulePhase::query()->where('id_filiere', $filiereId)->delete();

            foreach ($moduleIds as $moduleId) {
                FiliereModulePhase::create([
                    'id_filiere' => $filiereId,
                    'id_module' => (int) $moduleId,
                    'id_previous_module' => $previousByModule[(int) $moduleId] ?? null,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'data' => $this->buildPayload($filiere),
        ]);
    }

    private function buildPayload(Filiere $filiere): array
    {
        $modules = Module::query()
            ->where('id_filiere', $filiere->id)
            ->orderBy('id')
            ->get(['id', 'nom_module', 'volume_horaire', 'id_filiere']);

        $modulesById = $modules->keyBy('id');

        $rows = FiliereModulePhase::query()
            ->where('id_filiere', $filiere->id)
            ->get(['id_module', 'id_previous_module']);

        $previousByModule = $rows
            ->mapWithKeys(fn ($row) => [(int) $row->id_module => $row->id_previous_module !== null ? (int) $row->id_previous_module : null])
            ->all();

        $phasesByModule = [];
        foreach ($modules as $module) {
            $this->resolvePhaseNumber((int) $module->id, $previousByModule, $phasesByModule, []);
        }

        $phaseRows = $modules
            ->map(function ($module) use ($previousByModule, $phasesByModule) {
                $moduleId = (int) $module->id;
                return [
                    'phase_number' => $phasesByModule[$moduleId] ?? 1,
                    'module' => [
                        'id' => $moduleId,
                        'nom_module' => $module->nom_module,
                        'volume_horaire' => (int) $module->volume_horaire,
                        'id_previous_module' => $previousByModule[$moduleId] ?? null,
                    ],
                ];
            })
            ->sortBy([
                ['phase_number', 'asc'],
                ['module.id', 'asc'],
            ])
            ->groupBy('phase_number')
            ->map(function ($items, $phaseNumber) {
                return [
                    'phase_number' => (int) $phaseNumber,
                    'modules' => $items->pluck('module')->values()->all(),
                ];
            })
            ->values()
            ->all();

        return [
            'filiere' => [
                'id' => (int) $filiere->id,
                'nom_filiere' => $filiere->nom_filiere,
            ],
            'modules' => $modules,
            'phases' => $phaseRows,
        ];
    }

    private function hasCycle(array $moduleIds, array $previousByModule): bool
    {
        $visiting = [];
        $visited = [];

        foreach ($moduleIds as $moduleId) {
            if ($this->visitCycle((int) $moduleId, $previousByModule, $visiting, $visited)) {
                return true;
            }
        }

        return false;
    }

    private function visitCycle(int $moduleId, array $previousByModule, array &$visiting, array &$visited): bool
    {
        if (isset($visited[$moduleId])) {
            return false;
        }

        if (isset($visiting[$moduleId])) {
            return true;
        }

        $visiting[$moduleId] = true;
        $previous = $previousByModule[$moduleId] ?? null;

        if ($previous !== null && $this->visitCycle((int) $previous, $previousByModule, $visiting, $visited)) {
            return true;
        }

        unset($visiting[$moduleId]);
        $visited[$moduleId] = true;

        return false;
    }

    private function resolvePhaseNumber(int $moduleId, array $previousByModule, array &$cache, array $path): int
    {
        if (isset($cache[$moduleId])) {
            return $cache[$moduleId];
        }

        if (in_array($moduleId, $path, true)) {
            $cache[$moduleId] = 1;
            return 1;
        }

        $previous = $previousByModule[$moduleId] ?? null;
        if ($previous === null) {
            $cache[$moduleId] = 1;
            return 1;
        }

        $path[] = $moduleId;
        $cache[$moduleId] = $this->resolvePhaseNumber((int) $previous, $previousByModule, $cache, $path) + 1;

        return $cache[$moduleId];
    }
}
