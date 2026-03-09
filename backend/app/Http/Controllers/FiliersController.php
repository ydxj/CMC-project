<?php

namespace App\Http\Controllers;

use App\Models\Filiere;
use App\Models\Pole;
use Illuminate\Http\Request;

class FiliersController extends Controller
{
    private function getChefPoleIds($user)
    {
        if (!$user || $user->role !== 'CHEF_POLE') {
            return null;
        }

        return Pole::where('id_chef', $user->id)->pluck('id');
    }

    private function validateChefPoleAccess(Request $request, int $poleId)
    {
        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds !== null && !$chefPoleIds->contains($poleId)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: this pole does not belong to you',
            ], 403);
        }

        return null;
    }

    public function index(Request $request)
    {
        $query = Filiere::with('pole');
        $chefPoleIds = $this->getChefPoleIds($request->user());
        if ($chefPoleIds !== null) {
            $query->whereIn('id_pole', $chefPoleIds);
        }

        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json($query->get());
        }

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
            'nom_filiere' => 'required|string|max:150',
            'id_pole' => 'required|integer|exists:poles,id',
        ]);

        $accessDenied = $this->validateChefPoleAccess($request, (int) $data['id_pole']);
        if ($accessDenied) {
            return $accessDenied;
        }

        Filiere::create([
            'nom_filiere' => $data['nom_filiere'],
            'id_pole' => $data['id_pole'],
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_filiere' => $data['nom_filiere'],
                'id_pole' => $data['id_pole'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $filiere = Filiere::find($id);

        if (!$filiere) {
            return response()->json([
                'success' => false,
                'message' => 'Filiere not found',
            ], 404);
        }

        $existingAccessDenied = $this->validateChefPoleAccess($request, (int) $filiere->id_pole);
        if ($existingAccessDenied) {
            return $existingAccessDenied;
        }

        $data = $request->validate([
            'nom_filiere' => 'sometimes|required|string|max:150',
            'id_pole' => 'sometimes|required|integer|exists:poles,id',
        ]);

        $targetPoleId = isset($data['id_pole']) ? (int) $data['id_pole'] : (int) $filiere->id_pole;
        $targetAccessDenied = $this->validateChefPoleAccess($request, $targetPoleId);
        if ($targetAccessDenied) {
            return $targetAccessDenied;
        }

        $filiere->update([
            'nom_filiere' => $data['nom_filiere'] ?? $filiere->nom_filiere,
            'id_pole' => $data['id_pole'] ?? $filiere->id_pole,
        ]);

        return response()->json([
            'success' => true,
            'data' => $filiere,
        ]);
    }

    public function destroy($id)
    {
        $request = request();
        $filiere = Filiere::find($id);

        if (!$filiere) {
            return response()->json([
                'success' => false,
                'message' => 'Filiere not found',
            ], 404);
        }

        $accessDenied = $this->validateChefPoleAccess($request, (int) $filiere->id_pole);
        if ($accessDenied) {
            return $accessDenied;
        }

        $filiere->delete();

        return response()->json([
            'success' => true,
            'message' => 'Filiere deleted successfully',
        ]);
    }
}
