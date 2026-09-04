<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthorizationController extends Controller
{
    public function request(Request $request)
    {
        $data = $request->validate(['medicament_id' => ['required', 'exists:medicaments,id'], 'pra_cible_id' => ['required', 'exists:structures,id'], 'motif' => ['required', 'string', 'max:2000']]);
        $pharmacieId = $request->user()->structure_id;
        $origine = DB::table('structures')->where('id', $pharmacieId)->value('pra_parent');
        abort_unless($origine && $origine != $data['pra_cible_id'], 422, 'PRA cible invalide.');
        abort_if(DB::table('autorisations_pra')->where(['pharmacie_id' => $pharmacieId, 'medicament_id' => $data['medicament_id'], 'pra_cible_id' => $data['pra_cible_id']])->whereIn('statut', ['en_attente', 'accordee'])->exists(), 422, 'Une demande existe déjà.');
        DB::table('autorisations_pra')->insert($data + ['pharmacie_id' => $pharmacieId, 'pra_origine_id' => $origine, 'initiateur' => 'pharmacie']);
        return back()->with('status', 'Demande d’autorisation envoyée.');
    }

    public function decide(Request $request, int $autorisation)
    {
        $data = $request->validate(['action' => ['required', 'in:accorder,refuser,revoquer'], 'reponse' => ['nullable', 'string', 'max:2000']]);
        if ($data['action'] === 'refuser') abort_if(trim($data['reponse'] ?? '') === '', 422, 'Le motif du refus est obligatoire.');
        $authorization = DB::table('autorisations_pra')->where('id', $autorisation)->where('pra_origine_id', $request->user()->structure_id)->first();
        abort_unless($authorization, 404);
        $statut = ['accorder' => 'accordee', 'refuser' => 'refusee', 'revoquer' => 'revoquee'][$data['action']];
        DB::table('autorisations_pra')->where('id', $autorisation)->update(['statut' => $statut, 'reponse' => $data['reponse'] ?? null, 'traite_at' => now()]);
        return back()->with('status', 'Autorisation mise à jour.');
    }
}
