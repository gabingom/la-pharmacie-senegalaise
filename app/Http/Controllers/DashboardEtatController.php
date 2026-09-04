<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardEtatController extends Controller
{
    public function index()
    {
        $regions = DB::table('structures as st')
            ->leftJoin('stocks as s', 's.structure_id', '=', 'st.id')
            ->leftJoin('medicaments as m', 's.medicament_id', '=', 'm.id')
            ->where('st.type', 'pra')
            ->select('st.id', 'st.nom', 'st.region')
            ->selectRaw('COALESCE(SUM(s.quantite), 0) as stock')
            ->selectRaw('COALESCE(SUM(m.seuil_alerte), 1) as seuil')
            ->groupBy('st.id', 'st.nom', 'st.region')
            ->orderBy('stock')
            ->get();

        return view('dashboard.etat.index', [
            'medicaments' => DB::table('medicaments')->count(),
            'pra' => DB::table('structures')->where('type', 'pra')->where('statut', 'active')->count(),
            'alertes' => DB::table('alertes')->where('priorite', 'critique')->where('lue', false)->count(),
            'reequilibrages' => DB::table('reequilibrages')->where('statut', 'en_attente')->count(),
            'transferts' => DB::table('reequilibrages as r')
                ->join('medicaments as m', 'm.id', '=', 'r.medicament_id')
                ->leftJoin('structures as src', 'src.id', '=', 'r.source_id')
                ->join('structures as dst', 'dst.id', '=', 'r.destination_id')
                ->select('r.*', 'm.nom as medicament', 'm.dosage', 'src.nom as source', 'dst.nom as destination')
                ->where('r.statut', 'en_attente')
                ->orderByDesc('r.created_at')
                ->get(),
            'demandesSubvention' => DB::table('subventions as sub')
                ->join('structures as ph', 'ph.id', '=', 'sub.pharmacie_id')
                ->select('sub.*', 'ph.nom as pharmacie')
                ->where('sub.statut', 'en_attente')
                ->orderByDesc('sub.created_at')
                ->get(),
            'regions' => $regions,
            'commandes' => DB::table('commandes')->where('statut', 'en_attente')->count(),
            'subventions' => DB::table('subventions')->where('statut', 'en_attente')->count(),
        ]);
    }
}
