<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReequilibrageController extends Controller
{
    public function update(Request $request, int $reequilibrage)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approuver,rejeter'],
        ]);

        $updated = DB::table('reequilibrages')
            ->where('id', $reequilibrage)
            ->where('statut', 'en_attente')
            ->update([
                'statut' => $data['action'] === 'approuver' ? 'validee' : 'rejetee',
                'valide_par' => $request->user()->id,
                'traite_at' => now(),
            ]);

        return back()->with($updated ? 'status' : 'error', $updated
            ? 'Le rééquilibrage a été traité.'
            : 'Ce rééquilibrage est introuvable ou déjà traité.');
    }
}
