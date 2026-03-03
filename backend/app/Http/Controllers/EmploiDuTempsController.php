<?php

namespace App\Http\Controllers;

use App\Models\EmploiDuTemps;
use App\Models\ModuleGroupeFormateur;
use Illuminate\Http\Request;

class EmploiDuTempsController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(EmploiDuTemps::all());
        }

        $query = EmploiDuTemps::query();

        if ($offset !== null) {
            $query->skip(max(0, (int) $offset));
        }

        if ($limit !== null) {
            $query->take(max(0, (int) $limit));
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date_seance' => 'required|date',
            'heure_debut' => 'required|date_format:H:i',
            'heure_fin' => 'required|date_format:H:i|after:heure_debut',
            'id_groupe' => 'required|integer|exists:groupes,id',
            'id_module' => 'required|integer|exists:modules,id',
            'id_formateur' => 'required|integer|exists:formateurs,id',
            'id_salle' => 'required|integer|exists:salles,id',
        ]);

        $assignment = ModuleGroupeFormateur::firstOrCreate([
            'id_module' => $data['id_module'],
            'id_groupe' => $data['id_groupe'],
            'id_formateur' => $data['id_formateur'],
        ]);

        EmploiDuTemps::create([
            'date_seance' => $data['date_seance'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'id_module_groupe_formateur' => $assignment->id,
            'id_salle' => $data['id_salle'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'date_seance' => $data['date_seance'],
                'heure_debut' => $data['heure_debut'],
                'heure_fin' => $data['heure_fin'],
                'id_groupe' => $data['id_groupe'],
                'id_module' => $data['id_module'],
                'id_formateur' => $data['id_formateur'],
                'id_salle' => $data['id_salle'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $emploi = EmploiDuTemps::find($id);

        if (!$emploi) {
            return response()->json([
                'success' => false,
                'message' => 'Emploi du temps not found',
            ], 404);
        }

        $data = $request->validate([
            'date_seance' => 'sometimes|required|date',
            'heure_debut' => 'sometimes|required|date_format:H:i',
            'heure_fin' => 'sometimes|required|date_format:H:i|after:heure_debut',
            'id_groupe' => 'sometimes|required|integer|exists:groupes,id',
            'id_module' => 'sometimes|required|integer|exists:modules,id',
            'id_formateur' => 'sometimes|required|integer|exists:formateurs,id',
            'id_salle' => 'sometimes|required|integer|exists:salles,id',
        ]);

        // Handle module-groupe-formateur update
        if (isset($data['id_module']) || isset($data['id_groupe']) || isset($data['id_formateur'])) {
            $assignment = ModuleGroupeFormateur::firstOrCreate([
                'id_module' => $data['id_module'] ?? $emploi->moduleGroupeFormateur->id_module,
                'id_groupe' => $data['id_groupe'] ?? $emploi->moduleGroupeFormateur->id_groupe,
                'id_formateur' => $data['id_formateur'] ?? $emploi->moduleGroupeFormateur->id_formateur,
            ]);

            $emploi->id_module_groupe_formateur = $assignment->id;
        }

        $emploi->update([
            'date_seance' => $data['date_seance'] ?? $emploi->date_seance,
            'heure_debut' => $data['heure_debut'] ?? $emploi->heure_debut,
            'heure_fin' => $data['heure_fin'] ?? $emploi->heure_fin,
            'id_salle' => $data['id_salle'] ?? $emploi->id_salle,
            'id_module_groupe_formateur' => $emploi->id_module_groupe_formateur,
        ]);

        return response()->json([
            'success' => true,
            'data' => $emploi,
        ]);
    }

    public function destroy($id)
    {
        $emploi = EmploiDuTemps::find($id);

        if (!$emploi) {
            return response()->json([
                'success' => false,
                'message' => 'Emploi du temps not found',
            ], 404);
        }

        $emploi->delete();

        return response()->json([
            'success' => true,
            'message' => 'Emploi du temps deleted successfully',
        ]);
    }

    public function getWeeklySchedule(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'id_groupe' => 'nullable|integer|exists:groupes,id',
            'id_formateur' => 'nullable|integer|exists:formateurs,id',
            'id_salle' => 'nullable|integer|exists:salles,id',
            'id_module' => 'nullable|integer|exists:modules,id',
        ]);

        $date = \Carbon\Carbon::parse($data['start_date']);
        // Get Monday of the week
        $monday = $date->copy()->startOfWeek();
        $sunday = $date->copy()->endOfWeek();

        $query = EmploiDuTemps::whereBetween('date_seance', [$monday, $sunday])
            ->with(['moduleGroupeFormateur.groupe', 'moduleGroupeFormateur.module', 'moduleGroupeFormateur.formateur', 'salle']);

        if (isset($data['id_groupe'])) {
            $query->whereHas('moduleGroupeFormateur', function ($q) use ($data) {
                $q->where('id_groupe', $data['id_groupe']);
            });
        }

        if (isset($data['id_formateur'])) {
            $query->whereHas('moduleGroupeFormateur', function ($q) use ($data) {
                $q->where('id_formateur', $data['id_formateur']);
            });
        }

        if (isset($data['id_salle'])) {
            $query->where('id_salle', $data['id_salle']);
        }

        if (isset($data['id_module'])) {
            $query->whereHas('moduleGroupeFormateur', function ($q) use ($data) {
                $q->where('id_module', $data['id_module']);
            });
        }

        $schedules = $query->orderBy('date_seance')->orderBy('heure_debut')->get();

        return response()->json([
            'success' => true,
            'week' => [
                'start' => $monday->format('Y-m-d'),
                'end' => $sunday->format('Y-m-d'),
            ],
            'data' => $schedules,
        ]);
    }
}
