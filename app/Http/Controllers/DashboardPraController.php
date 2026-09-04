<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardPraController extends Controller
{
    public function index()
    {
        $structureId = auth()->user()->structure_id;

        return view('dashboard.pra.index', [
            'user' => auth()->user(),
            'stocks' => DB::table('stocks as s')
                ->join('medicaments as m', 'm.id', '=', 's.medicament_id')
                ->where('s.structure_id', $structureId)
                ->select('m.nom', 'm.dosage', 's.quantite', 'm.seuil_alerte')
                ->orderBy('s.quantite')
                ->get(),
            'commandes' => DB::table('commandes as c')
                ->join('utilisateurs as u', 'u.id', '=', 'c.demandeur_id')
                ->join('structures as s', 's.id', '=', 'u.structure_id')
                ->where('s.pra_parent', $structureId)
                ->where('c.statut', 'en_attente')
                ->count(),
            'reequilibrages' => DB::table('reequilibrages')
                ->where('destination_id', $structureId)
                ->where('statut', 'en_attente')
                ->count(),
        ]);
    }
}
