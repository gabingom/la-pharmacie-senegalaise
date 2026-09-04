<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StateAdministrationController extends Controller
{
    public function assignPra(Request $request, int $structure)
    {
        $data = $request->validate(['pra_id' => ['required', 'integer']]);
        abort_unless(DB::table('structures')->where('id', $structure)->where('type', 'pharmacie')->exists(), 404);
        abort_unless(DB::table('structures')->where('id', $data['pra_id'])->where('type', 'pra')->where('statut', 'active')->exists(), 422, 'PRA inactif ou introuvable.');
        DB::table('structures')->where('id', $structure)->update(['pra_parent' => $data['pra_id']]);
        return back()->with('status', 'PRA de rattachement mis à jour.');
    }

    public function settings(Request $request)
    {
        $data = $request->validate(['params' => ['required', 'array']]);
        foreach ($data['params'] as $cle => $valeur) {
            DB::table('parametres')->where('cle', $cle)->update(['valeur' => $valeur]);
        }
        return back()->with('status', 'Paramètres enregistrés.');
    }
}
