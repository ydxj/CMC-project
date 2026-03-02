<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit');
        $offset = $request->query('offset');

        if ($limit === null && $offset === null) {
            return response()->json(
                User::select('id', 'nom', 'prenom', 'email', 'role')->get()
            );
        }

        $query = User::select('id', 'nom', 'prenom', 'email', 'role');

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
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:DIRECTEUR,CHEF_POLE,FORMATEUR',
            'matricule' => 'required|string|max:50',
        ]);

        $data['password'] = Hash::make($data['password']);
        User::create($data);
        // var_dump($data);
        // die();

        return response()->json([
            'success' => true,
            'data' => [
                'nom' => $data['nom'],
                'prenom' => $data['prenom'],
                'email' => $data['email'],
                'role' => $data['role'],
                'matricule' => $data['matricule'],
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $data = $request->validate([
            'nom' => 'sometimes|required|string|max:100',
            'prenom' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6',
            'role' => 'sometimes|required|in:DIRECTEUR,CHEF_POLE,FORMATEUR',
            'matricule' => 'sometimes|required|string|max:50',
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update([
            'nom' => $data['nom'] ?? $user->nom,
            'prenom' => $data['prenom'] ?? $user->prenom,
            'email' => $data['email'] ?? $user->email,
            'role' => $data['role'] ?? $user->role,
            'matricule' => $data['matricule'] ?? $user->matricule,
            'password' => $data['password'] ?? $user->password,
        ]);

        return response()->json([
            'success' => true,
            'data' => $user->only('id', 'nom', 'prenom', 'email', 'role', 'matricule'),
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }
}
