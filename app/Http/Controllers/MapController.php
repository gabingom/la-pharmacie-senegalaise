<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class MapController extends Controller
{
    public function points(): JsonResponse
    {
        $user = auth()->user();
        $structure = $user->structure_id ? DB::table('structures')->where('id', $user->structure_id)->first() : null;
        $points = DB::table('structures')->where('statut', 'active')->whereNotNull('latitude')->whereNotNull('longitude')->get()->filter(function ($point) use ($user, $structure) {
            if ($user->role === 'etat' || $point->id === $user->structure_id) return true;
            if ($user->role === 'pra') return $point->type === 'pra' || ($point->type === 'pharmacie' && $point->pra_parent === $user->structure_id);
            if ($user->role === 'pharmacie') return $point->type === 'pra' || ($structure && $point->id === $structure->pra_parent) || $point->type === 'pharmacie';
            return false;
        })->values()->map(fn ($point) => ['id' => $point->id, 'nom' => $point->nom, 'type' => $point->type, 'region' => $point->region, 'zone' => $point->zone, 'telephone' => $point->telephone ?: 'Non communiqué', 'lat' => (float) $point->latitude, 'lng' => (float) $point->longitude]);
        return response()->json(['points' => $points]);
    }
}
