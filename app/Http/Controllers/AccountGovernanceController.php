<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountGovernanceController extends Controller
{
    public function accessDecision(Request $request, int $demande)
    {
        $data = $request->validate(['action' => ['required', 'in:approuver,rejeter']]);
        $access = DB::table('demandes_acces')->where('id', $demande)->where('statut', 'en_attente')->first();
        abort_unless($access, 404);
        if ($data['action'] === 'rejeter') {
            DB::table('demandes_acces')->where('id', $demande)->update(['statut' => 'rejetee', 'traite_par' => $request->user()->id, 'traite_at' => now()]);
            return back()->with('status', 'Demande rejetée.');
        }
        abort_if(DB::table('utilisateurs')->where('email', $access->email)->exists(), 422, 'Un compte existe déjà avec cet email.');
        DB::transaction(function () use ($access, $demande, $request) {
            $structure = DB::table('structures')->insertGetId(['nom' => $access->structure_nom, 'type' => $access->role_demande, 'region' => 'A definir']);
            DB::table('utilisateurs')->insert(['nom' => $access->nom, 'prenom' => $access->prenom, 'email' => $access->email, 'mot_de_passe' => Hash::make('LPS' . random_int(1000, 9999) . '!'), 'doit_changer_mdp' => true, 'role' => $access->role_demande, 'structure_id' => $structure, 'statut' => 'actif']);
            DB::table('demandes_acces')->where('id', $demande)->update(['statut' => 'approuvee', 'traite_par' => $request->user()->id, 'traite_at' => now()]);
        });
        return back()->with('status', 'Demande approuvée et compte créé.');
    }

    public function resetDecision(Request $request, int $demande)
    {
        $data = $request->validate(['action' => ['required', 'in:autoriser,refuser']]);
        $reset = DB::table('demandes_reset')->where('id', $demande)->where('statut', 'en_attente')->first();
        abort_unless($reset, 404);
        DB::table('demandes_reset')->where('id', $demande)->update(['statut' => $data['action'] === 'autoriser' ? 'autorisee' : 'refusee', 'traite_par' => $request->user()->id, 'traite_at' => now()]);
        if ($data['action'] === 'autoriser') DB::table('utilisateurs')->where('id', $reset->utilisateur_id)->update(['reset_autorise' => true]);
        return back()->with('status', 'Demande de réinitialisation traitée.');
    }

    public function accountDecision(Request $request, int $user)
    {
        $data = $request->validate(['action' => ['required', 'in:suspendre,reactiver,supprimer']]);
        abort_if($user === $request->user()->id, 422, 'Vous ne pouvez pas modifier votre propre compte.');
        $target = DB::table('utilisateurs')->where('id', $user)->first();
        abort_unless($target, 404); abort_if($target->role === 'etat', 403, 'Les comptes État ne peuvent pas être modifiés.');
        if ($data['action'] === 'reactiver') {
            DB::table('utilisateurs')->where('id', $user)->update(['statut' => 'actif']);
            return back()->with('status', 'Compte réactivé.');
        }
        abort_if($data['action'] === 'supprimer' && DB::table('commandes')->where('demandeur_id', $user)->exists(), 422, 'Ce compte possède un historique de commandes et doit être suspendu.');
        if ($data['action'] === 'supprimer') DB::table('utilisateurs')->where('id', $user)->delete(); else DB::table('utilisateurs')->where('id', $user)->update(['statut' => 'suspendu']);
        return back()->with('status', 'Compte traité.');
    }
}
