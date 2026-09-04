<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubventionController extends Controller
{
    public function update(Request $request, int $subvention)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approuver,rejeter'],
        ]);

        $updated = DB::table('subventions')
            ->where('id', $subvention)
            ->where('statut', 'en_attente')
            ->update([
                'statut' => $data['action'] === 'approuver' ? 'approuvee' : 'rejetee',
                'valide_par' => $request->user()->id,
                'traite_at' => now(),
            ]);

        return back()->with($updated ? 'status' : 'error', $updated
            ? 'La subvention a été traitée.'
            : 'Cette subvention est introuvable ou déjà traitée.');
    }
}
