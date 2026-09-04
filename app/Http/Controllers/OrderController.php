<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['medicament_id' => ['required', 'integer', 'exists:medicaments,id'], 'quantite' => ['required', 'integer', 'min:1'], 'pra_cible_id' => ['nullable', 'integer'], 'urgence' => ['nullable', 'in:normale,urgente'], 'notes' => ['nullable', 'string']]);
        $pharmacieId = $request->user()->structure_id;
        $praParent = DB::table('structures')->where('id', $pharmacieId)->value('pra_parent');
        abort_unless($praParent, 422, 'Votre pharmacie n’est rattachée à aucun PRA.');
        $praId = $data['pra_cible_id'] ?: $praParent;
        if ((int) $praId !== (int) $praParent) {
            abort_unless(DB::table('autorisations_pra')->where(['pharmacie_id' => $pharmacieId, 'medicament_id' => $data['medicament_id'], 'pra_cible_id' => $praId, 'statut' => 'accordee'])->exists(), 403, 'Autorisation requise pour ce PRA.');
        }
        abort_unless(DB::table('stocks')->where(['structure_id' => $praId, 'medicament_id' => $data['medicament_id']])->where('quantite', '>', 0)->exists(), 422, 'Médicament indisponible chez ce PRA.');
        DB::transaction(function () use ($data, $praId, $request) {
            $id = DB::table('commandes')->insertGetId(['reference' => 'CMD-' . date('Y') . '-' . strtoupper(substr(uniqid(), -6)), 'demandeur_id' => $request->user()->id, 'pra_cible_id' => $praId, 'urgence' => $data['urgence'] ?? 'normale', 'notes' => $data['notes'] ?? null]);
            DB::table('lignes_commande')->insert(['commande_id' => $id, 'medicament_id' => $data['medicament_id'], 'quantite_demandee' => $data['quantite']]);
        });
        return back()->with('status', 'Commande créée.');
    }

    public function decide(Request $request, int $commande)
    {
        $data = $request->validate(['action' => ['required', 'in:valider,rejeter']]);
        $praId = $request->user()->structure_id;
        $order = DB::table('commandes as c')->join('utilisateurs as u', 'u.id', '=', 'c.demandeur_id')->join('structures as s', 's.id', '=', 'u.structure_id')->where('c.id', $commande)->select('c.*', 's.id as pharmacie_id', 's.pra_parent')->first();
        abort_unless($order && (int) ($order->pra_cible_id ?: $order->pra_parent) === (int) $praId, 404);
        if ($data['action'] === 'rejeter') {
            DB::table('commandes')->where('id', $commande)->update(['statut' => 'rejetee', 'validateur_id' => $request->user()->id, 'date_validation' => now()]);
            return back()->with('status', 'Commande rejetée.');
        }
        $line = DB::table('lignes_commande')->where('commande_id', $commande)->first();
        DB::transaction(function () use ($line, $order, $commande, $praId, $request) {
            $stock = DB::table('stocks')->where(['structure_id' => $praId, 'medicament_id' => $line->medicament_id])->where('quantite', '>=', $line->quantite_demandee)->lockForUpdate()->first();
            if (!$stock) { DB::table('commandes')->where('id', $commande)->update(['statut' => 'validee', 'validateur_id' => $request->user()->id, 'date_validation' => now()]); return; }
            DB::table('stocks')->where('id', $stock->id)->decrement('quantite', $line->quantite_demandee);
            $target = DB::table('stocks')->where(['structure_id' => $order->pharmacie_id, 'medicament_id' => $line->medicament_id])->first();
            if ($target) DB::table('stocks')->where('id', $target->id)->increment('quantite', $line->quantite_demandee); else DB::table('stocks')->insert(['structure_id' => $order->pharmacie_id, 'medicament_id' => $line->medicament_id, 'quantite' => $line->quantite_demandee]);
            DB::table('lignes_commande')->where('id', $line->id)->update(['quantite_livree' => $line->quantite_demandee]);
            DB::table('commandes')->where('id', $commande)->update(['statut' => 'livree', 'validateur_id' => $request->user()->id, 'date_validation' => now()]);
        });
        return back()->with('status', 'Commande traitée.');
    }
}
