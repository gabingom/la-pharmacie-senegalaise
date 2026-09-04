<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SignalementController extends Controller
{
    public function reequilibrage(Request $request)
    {
        $data = $request->validate([
            'medicament_id' => ['required', 'integer', 'exists:medicaments,id'],
            'quantite' => ['required', 'integer', 'min:1'],
            'priorite' => ['required', 'in:moderee,critique'],
            'justification' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::table('reequilibrages')->insert($data + [
            'destination_id' => $request->user()->structure_id,
            'origine' => 'pra', 'signale_par' => $request->user()->id,
        ]);
        return back()->with('status', 'Besoin de rééquilibrage signalé.');
    }

    public function subvention(Request $request)
    {
        $data = $request->validate([
            'pharmacie_id' => ['required', 'integer', 'exists:structures,id'],
            'medicaments' => ['required', 'string', 'max:2000'],
            'montant_estime' => ['required', 'numeric', 'min:0'],
            'motif' => ['required', 'string', 'max:2000'],
        ]);
        abort_unless(DB::table('structures')->where('id', $data['pharmacie_id'])->where('type', 'pharmacie')->where('pra_parent', $request->user()->structure_id)->exists(), 403);
        DB::table('subventions')->insert($data + ['signale_par' => $request->user()->id]);
        return back()->with('status', 'Demande de subvention envoyée.');
    }
}
