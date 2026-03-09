<?php

namespace App\Http\Controllers;

use App\Models\Salle;
use App\Models\Pole;
use Illuminate\Http\Request;

class SallesController extends Controller
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
        $query = Salle::with('pole');
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
            'nom_salle' => 'required|string|max:100',
            'type_salle' => 'required|in:SALLE,ATELIER',
            'id_pole' => 'required|integer|exists:poles,id',
        ]);

        $accessDenied = $this->validateChefPoleAccess($request, (int) $data['id_pole']);
        if ($accessDenied) {
            return $accessDenied;
        }

        Salle::create($data);

        return response()->json([
            'success' => true,
            'data' => [
                'nom_salle' => $data['nom_salle'],
                'type_salle' => $data['type_salle'],
                'id_pole' => $data['id_pole'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $salle = Salle::find($id);

        if (!$salle) {
            return response()->json([
                'success' => false,
                'message' => 'Salle not found',
            ], 404);
        }

        $existingAccessDenied = $this->validateChefPoleAccess($request, (int) $salle->id_pole);
        if ($existingAccessDenied) {
            return $existingAccessDenied;
        }

        $data = $request->validate([
            'nom_salle' => 'sometimes|required|string|max:100',
            'type_salle' => 'sometimes|required|in:SALLE,ATELIER',
            'id_pole' => 'sometimes|required|integer|exists:poles,id',
        ]);

        $targetPoleId = isset($data['id_pole']) ? (int) $data['id_pole'] : (int) $salle->id_pole;
        $targetAccessDenied = $this->validateChefPoleAccess($request, $targetPoleId);
        if ($targetAccessDenied) {
            return $targetAccessDenied;
        }

        $salle->update([
            'nom_salle' => $data['nom_salle'] ?? $salle->nom_salle,
            'type_salle' => $data['type_salle'] ?? $salle->type_salle,
            'id_pole' => $data['id_pole'] ?? $salle->id_pole,
        ]);

        return response()->json([
            'success' => true,
            'data' => $salle,
        ]);
    }

    public function destroy($id)
    {
        $request = request();
        $salle = Salle::find($id);

        if (!$salle) {
            return response()->json([
                'success' => false,
                'message' => 'Salle not found',
            ], 404);
        }

        $accessDenied = $this->validateChefPoleAccess($request, (int) $salle->id_pole);
        if ($accessDenied) {
            return $accessDenied;
        }

        $salle->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salle deleted successfully',
        ]);
    }
}
