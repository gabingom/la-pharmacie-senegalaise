<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function create()
    {
        if (Auth::check()) {
            return redirect()->route($this->dashboardRoute(Auth::user()));
        }

        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])
            ->where('statut', 'actif')
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->mot_de_passe)) {
            return back()->withErrors(['email' => 'Identifiant ou mot de passe incorrect.'])
                ->onlyInput('email');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login' => now()])->save();

        if ($user->doit_changer_mdp) {
            return redirect()->route('dashboard')->with('warning', 'Veuillez modifier votre mot de passe temporaire.');
        }

        return redirect()->intended(route($this->dashboardRoute($user)));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function dashboardRoute(User $user): string
    {
        return match ($user->role) {
            'etat' => 'dashboard.etat',
            'pra' => 'dashboard.pra',
            'pharmacie', 'fournisseur' => 'dashboard.pharmacie',
            default => 'login',
        };
    }
}
