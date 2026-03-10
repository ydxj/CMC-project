<?php

namespace App\Http\Controllers;

use App\Models\Formateur;
use App\Models\Groupe;
use App\Models\Module;
use App\Models\ModuleGroupeFormateur;
use App\Models\Pole;
use App\Models\Salle;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        $role = $user->role;

        if ($role === 'DIRECTEUR') {
            return $this->getDirecteurStats();
        }

        if ($role === 'CHEF_POLE') {
            return $this->getChefPoleStats($user->id);
        }

        if ($role === 'FORMATEUR') {
            return $this->getFormateurStats($user->id);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unknown role',
        ], 400);
    }

    private function getDirecteurStats()
    {
        $poles = Pole::count();
        $salles = Salle::count();
        $formateurs = User::where('role', 'FORMATEUR')->count();
        $modules = Module::count();

        return response()->json([
            'success' => true,
            'stats' => [
                'poles' => $poles,
                'salles' => $salles,
                'formateurs' => $formateurs,
                'modules' => $modules,
            ],
        ]);
    }

    private function getChefPoleStats($userId)
    {
        $poles = Pole::where('id_chef', $userId)->get();
        $poleIds = $poles->pluck('id')->toArray();

        $salles = Salle::whereIn('id_pole', $poleIds)->count();

        $formateurs = Formateur::whereHas('poles', function ($query) use ($poleIds) {
            $query->whereIn('poles.id', $poleIds);
        })->distinct()->count();

        $modules = Module::whereHas('filiere', function ($query) use ($poleIds) {
            $query->whereIn('id_pole', $poleIds);
        })->count();

        $poleNames = $poles->pluck('nom_pole')->join(' / ') ?: '--';

        return response()->json([
            'success' => true,
            'stats' => [
                'poles' => $poleNames,
                'salles' => $salles,
                'formateurs' => $formateurs,
                'modules' => $modules,
            ],
        ]);
    }

    private function getFormateurStats($userId)
    {
        $formateur = Formateur::where('id_user', $userId)->first();
        if (!$formateur) {
            return response()->json([
                'success' => false,
                'message' => 'No formateur profile found',
            ], 404);
        }

        $assignments = ModuleGroupeFormateur::where('id_formateur', $formateur->id)
            ->with(['module.filiere.pole', 'groupe'])
            ->get();

        $modules = $assignments->map(fn($a) => $a->module?->nom_module)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $groupes = $assignments->map(fn($a) => $a->groupe?->nom_groupe)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $poles = $assignments->map(fn($a) => $a->module?->filiere?->pole?->nom_pole)
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $assignments_count = $assignments->count();

        return response()->json([
            'success' => true,
            'stats' => [
                'modules' => count($modules),
                'groupes' => count($groupes),
                'poles' => !empty($poles) ? implode(' / ', $poles) : '--',
                'assignments' => $assignments_count,
            ],
            'overview' => [
                'modules' => $modules,
                'groupes' => $groupes,
                'poles' => $poles,
            ],
        ]);
    }
}
