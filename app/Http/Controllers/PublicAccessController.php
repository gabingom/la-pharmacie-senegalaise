<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicAccessController extends Controller
{
    public function create()
    {
        return view('access.request');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['nom' => ['required', 'string', 'max:100'], 'prenom' => ['required', 'string', 'max:100'], 'structure_nom' => ['required', 'string', 'max:255'], 'role_demande' => ['required', 'in:pra,pharmacie,fournisseur'], 'email' => ['required', 'email', 'max:255'], 'telephone' => ['nullable', 'string', 'max:30']]);
        abort_if(DB::table('utilisateurs')->where('email', $data['email'])->exists(), 422, 'Un compte existe déjà avec cet email.');
        DB::table('demandes_acces')->insert($data);
        return back()->with('status', 'Votre demande a été envoyée.');
    }
}
