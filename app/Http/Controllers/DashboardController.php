<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('dashboard', [
            'user' => $user,
            'medicaments' => DB::table('medicaments')->count(),
            'stocks' => DB::table('stocks')->where('structure_id', $user->structure_id)->sum('quantite'),
            'reequilibrages' => DB::table('reequilibrages')->where('statut', 'en_attente')->count(),
            'alertes' => DB::table('alertes')->where('lue', false)->count(),
        ]);
    }
}
