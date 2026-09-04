<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StructureController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate(['region' => ['required', 'string', 'not_in:A definir'], 'zone' => ['required', 'in:ville,village,rural'], 'telephone' => ['nullable', 'string', 'min:7'], 'email' => ['nullable', 'email'], 'adresse' => ['nullable', 'string']]);
        DB::table('structures')->where('id', $request->user()->structure_id)->update($data);
        return back()->with('status', 'Fiche de structure enregistrée.');
    }

    public function location(Request $request)
    {
        $data = $request->validate(['latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180']]);
        DB::table('structures')->where('id', $request->user()->structure_id)->update($data);
        return back()->with('status', 'Localisation enregistrée.');
    }
}
