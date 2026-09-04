<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssistantController extends Controller
{
    public function ask(Request $request): JsonResponse
    {
        $data = $request->validate(['question' => ['required', 'string', 'max:1000']]);
        $question = mb_strtolower($data['question']);
        if (str_contains($question, 'rupture') || str_contains($question, 'stock critique')) {
            $count = DB::table('stocks as s')->join('medicaments as m', 'm.id', '=', 's.medicament_id')->whereColumn('s.quantite', '<', 'm.seuil_alerte')->count();
            return response()->json(['answer' => "J'ai détecté {$count} stock(s) sous le seuil d'alerte."]);
        }
        if (str_contains($question, 'reequilibr') || str_contains($question, 'transfert')) {
            $count = DB::table('reequilibrages')->where('statut', 'en_attente')->count();
            return response()->json(['answer' => "{$count} rééquilibrage(s) sont actuellement en attente de validation."]);
        }
        if (str_contains($question, 'commande')) {
            $count = DB::table('commandes')->where('statut', 'en_attente')->count();
            return response()->json(['answer' => "{$count} commande(s) sont actuellement en attente."]);
        }
        return response()->json(['answer' => 'Je peux vous renseigner sur les stocks critiques, les commandes et les rééquilibrages.']);
    }
}
