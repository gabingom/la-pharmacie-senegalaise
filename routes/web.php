<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardEtatController;
use App\Http\Controllers\DashboardPraController;
use App\Http\Controllers\DashboardPharmacieController;
use App\Http\Controllers\ReequilibrageController;
use App\Http\Controllers\SubventionController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SignalementController;
use App\Http\Controllers\AuthorizationController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\StateAdministrationController;
use App\Http\Controllers\AccountGovernanceController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\PublicAccessController;
use App\Http\Controllers\PasswordController;

Route::get('/', [AuthController::class, 'create'])->name('login');
Route::get('/connexion', [AuthController::class, 'create']);
Route::post('/connexion', [AuthController::class, 'store'])->name('login.store');
Route::get('/demande-acces', [PublicAccessController::class, 'create'])->name('access.request');
Route::post('/demande-acces', [PublicAccessController::class, 'store'])->name('access.request.store');
Route::post('/deconnexion', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/sales', [StockController::class, 'sell'])->middleware('role:pharmacie')->name('sales.store');
    Route::post('/orders', [OrderController::class, 'store'])->middleware('role:pharmacie')->name('orders.store');
    Route::post('/orders/{commande}/decision', [OrderController::class, 'decide'])->middleware('role:pra')->name('orders.decide');
    Route::post('/stocks', [StockController::class, 'store'])->middleware('role:pra')->name('stocks.store');
    Route::post('/reports/reequilibrage', [SignalementController::class, 'reequilibrage'])->middleware('role:pra')->name('reports.reequilibrage');
    Route::post('/reports/subvention', [SignalementController::class, 'subvention'])->middleware('role:pra')->name('reports.subvention');
    Route::get('/map/points', [MapController::class, 'points'])->name('map.points');
    Route::post('/assistant', [AssistantController::class, 'ask'])->name('assistant.ask');
    Route::get('/mot-de-passe', [PasswordController::class, 'edit'])->name('password.change');
    Route::put('/mot-de-passe', [PasswordController::class, 'update'])->name('password.update');
    Route::post('/authorizations/request', [AuthorizationController::class, 'request'])->middleware('role:pharmacie')->name('authorizations.request');
    Route::post('/authorizations/{autorisation}/decision', [AuthorizationController::class, 'decide'])->middleware('role:pra')->name('authorizations.decide');
    Route::put('/my-structure', [StructureController::class, 'update'])->name('structure.update');
    Route::put('/my-structure/location', [StructureController::class, 'location'])->name('structure.location');
});

Route::middleware(['auth', 'role:etat'])->prefix('dashboard/etat')->group(function () {
    Route::get('/', [DashboardEtatController::class, 'index'])->name('dashboard.etat');
    Route::patch('/reequilibrages/{reequilibrage}', [ReequilibrageController::class, 'update'])->name('reequilibrages.update');
    Route::patch('/subventions/{subvention}', [SubventionController::class, 'update'])->name('subventions.update');
    Route::post('/structures/{structure}/pra', [StateAdministrationController::class, 'assignPra'])->name('structures.assign-pra');
    Route::put('/settings', [StateAdministrationController::class, 'settings'])->name('settings.update');
    Route::post('/access-requests/{demande}/decision', [AccountGovernanceController::class, 'accessDecision'])->name('access-requests.decision');
    Route::post('/reset-requests/{demande}/decision', [AccountGovernanceController::class, 'resetDecision'])->name('reset-requests.decision');
    Route::post('/accounts/{user}/decision', [AccountGovernanceController::class, 'accountDecision'])->name('accounts.decision');
});

Route::middleware(['auth', 'role:pra'])->prefix('dashboard/pra')->group(function () {
    Route::get('/', [DashboardPraController::class, 'index'])->name('dashboard.pra');
});

Route::middleware(['auth', 'role:pharmacie,fournisseur'])->prefix('dashboard/pharmacie')->group(function () {
    Route::get('/', [DashboardPharmacieController::class, 'index'])->name('dashboard.pharmacie');
});
