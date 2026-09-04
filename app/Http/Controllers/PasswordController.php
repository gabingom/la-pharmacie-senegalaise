<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.password');
    }

    public function update(Request $request)
    {
        $data = $request->validate(['actuel' => ['required'], 'nouveau' => ['required', 'string', 'min:6', 'different:actuel'], 'confirm' => ['required', 'same:nouveau']]);
        abort_unless(Hash::check($data['actuel'], $request->user()->mot_de_passe), 422, 'Le mot de passe actuel est incorrect.');
        $request->user()->update(['mot_de_passe' => Hash::make($data['nouveau']), 'doit_changer_mdp' => false]);
        $route = match ($request->user()->role) {
            'etat' => 'dashboard.etat',
            'pra' => 'dashboard.pra',
            default => 'dashboard.pharmacie',
        };
        return redirect()->route($route)->with('status', 'Mot de passe modifié.');
    }
}
