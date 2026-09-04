<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardPharmacieController extends Controller
{
    public function index()
    {
        $structureId = auth()->user()->structure_id;

        return view('dashboard.pharmacie.index', [
            'user' => auth()->user(),
            'stocks' => DB::table('stocks as s')
                ->join('medicaments as m', 'm.id', '=', 's.medicament_id')
                ->where('s.structure_id', $structureId)
                ->select('m.nom', 'm.dosage', 'm.forme', 's.quantite', 's.date_peremption')
                ->orderBy('s.quantite')
                ->get(),
            'commandes' => DB::table('commandes')
                ->where('demandeur_id', auth()->id())
                ->count(),
            'commandesAttente' => DB::table('commandes')
                ->where('demandeur_id', auth()->id())
                ->where('statut', 'en_attente')
                ->count(),
        ]);
    }
}
