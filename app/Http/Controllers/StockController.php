<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'medicament_id' => ['nullable', 'integer', 'exists:medicaments,id'],
            'nom' => ['required_without:medicament_id', 'nullable', 'string', 'max:255'],
            'forme' => ['nullable', 'string', 'max:50'], 'dosage' => ['nullable', 'string', 'max:100'],
            'categorie' => ['nullable', 'string', 'max:100'], 'seuil_alerte' => ['required', 'integer', 'min:0'],
            'quantite' => ['required', 'integer', 'min:1'], 'numero_lot' => ['nullable', 'string', 'max:100'],
            'date_peremption' => ['nullable', 'date'],
        ]);
        $structureId = $request->user()->structure_id;
        $medicamentId = $data['medicament_id'] ?? null;

        DB::transaction(function () use (&$medicamentId, $data, $structureId) {
            if (!$medicamentId) {
                $medicamentId = DB::table('medicaments')->insertGetId([
                    'nom' => trim($data['nom']), 'forme' => $data['forme'] ?? 'autre',
                    'dosage' => trim($data['dosage'] ?? ''), 'categorie' => trim($data['categorie'] ?? ''),
                    'seuil_alerte' => $data['seuil_alerte'],
                ]);
            }
            $stock = DB::table('stocks')->where('structure_id', $structureId)->where('medicament_id', $medicamentId)
                ->where('numero_lot', $data['numero_lot'] ?? null)->first();
            if ($stock) {
                DB::table('stocks')->where('id', $stock->id)->update(['quantite' => $stock->quantite + $data['quantite'], 'date_peremption' => $data['date_peremption'] ?? $stock->date_peremption]);
            } else {
                DB::table('stocks')->insert(['structure_id' => $structureId, 'medicament_id' => $medicamentId, 'quantite' => $data['quantite'], 'numero_lot' => $data['numero_lot'] ?? null, 'date_peremption' => $data['date_peremption'] ?? null]);
            }
        });
        return back()->with('status', 'Stock ajouté avec succès.');
    }

    public function sell(Request $request)
    {
        $data = $request->validate(['medicament_id' => ['required', 'integer', 'exists:medicaments,id'], 'quantite' => ['required', 'integer', 'min:1']]);
        $structureId = $request->user()->structure_id;
        DB::transaction(function () use ($data, $structureId) {
            $stock = DB::table('stocks')->where('structure_id', $structureId)->where('medicament_id', $data['medicament_id'])->where('quantite', '>=', $data['quantite'])->lockForUpdate()->first();
            abort_unless($stock, 422, 'Stock insuffisant.');
            DB::table('stocks')->where('id', $stock->id)->decrement('quantite', $data['quantite']);
            DB::table('ventes')->insert(['structure_id' => $structureId, 'medicament_id' => $data['medicament_id'], 'quantite' => $data['quantite']]);
        });
        return back()->with('status', 'Vente enregistrée.');
    }
}
