<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index()
    {
        return response()->json(
            User::select('id', 'nom', 'prenom', 'email', 'role')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:DIRECTEUR,CHEF_POLE,FORMATEUR',
            'matricule' => 'nullable|string|max:50',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
        ]);
    }
}
