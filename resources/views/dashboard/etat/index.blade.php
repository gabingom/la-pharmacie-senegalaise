<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard État | La Pharmacie Sénégalaise</title>
    <style>
        :root { --green: #2e9450; --deep: #17301f; --bg: #f3f7f3; --line: #dfeae1; }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--deep); font-family: Arial, sans-serif; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 22px 6%; background: #fff; border-bottom: 1px solid var(--line); }
        h1 { margin: 0; font-size: 1.4rem; } main { width: min(1100px, 88%); margin: 34px auto; }
        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
        .card { padding: 22px; background: #fff; border: 1px solid var(--line); border-radius: 12px; }
        .label { color: #708274; font-size: .9rem; } .value { display: block; margin-top: 10px; font-size: 2rem; font-weight: 700; color: var(--green); }
        table { width: 100%; border-collapse: collapse; } th, td { padding: 14px 10px; text-align: left; border-bottom: 1px solid var(--line); } th { color: #708274; font-size: .8rem; text-transform: uppercase; }
        button { border: 0; padding: 10px 14px; border-radius: 8px; background: var(--green); color: #fff; font-weight: 700; cursor: pointer; }
        @media (max-width: 700px) { .grid { grid-template-columns: repeat(2, 1fr); } header { gap: 12px; } }
    </style>
</head>
<body>
    <header>
        <h1>La Pharmacie Sénégalaise · État</h1>
        <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Déconnexion</button></form>
    </header>
    <main>
        <div class="grid">
            <div class="card"><span class="label">Médicaments suivis</span><span class="value">{{ $medicaments }}</span></div>
            <div class="card"><span class="label">PRA actives</span><span class="value">{{ $pra }}</span></div>
            <div class="card"><span class="label">Alertes critiques</span><span class="value">{{ $alertes }}</span></div>
            <div class="card"><span class="label">Rééquilibrages en attente</span><span class="value">{{ $reequilibrages }}</span></div>
        </div>
        <section class="card">
            <h2>État des stocks par région</h2>
            <table>
                <thead><tr><th>Région</th><th>Stock</th><th>Seuil</th><th>Couverture</th></tr></thead>
                <tbody>
                    @forelse ($regions as $region)
                        <tr><td>{{ $region->region ?: $region->nom }}</td><td>{{ $region->stock }}</td><td>{{ $region->seuil }}</td><td>{{ $region->seuil ? round($region->stock / $region->seuil * 100) : 0 }}%</td></tr>
                    @empty
                        <tr><td colspan="4">Aucune région enregistrée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <section class="card">
            <h2>Subventions à valider</h2>
            <table>
                <thead><tr><th>Pharmacie</th><th>Médicaments</th><th>Montant estimé</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($demandesSubvention as $subvention)
                        <tr><td>{{ $subvention->pharmacie }}</td><td>{{ $subvention->medicaments }}</td><td>{{ $subvention->montant_estime }}</td><td><form method="POST" action="{{ route('subventions.update', $subvention->id) }}" style="display:inline">@csrf @method('PATCH')<input type="hidden" name="action" value="approuver"><button type="submit">Approuver</button></form> <form method="POST" action="{{ route('subventions.update', $subvention->id) }}" style="display:inline">@csrf @method('PATCH')<input type="hidden" name="action" value="rejeter"><button type="submit">Rejeter</button></form></td></tr>
                    @empty
                        <tr><td colspan="4">Aucune subvention en attente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
        <section class="card">
            <h2>Rééquilibrages à valider</h2>
            <table>
                <thead><tr><th>Médicament</th><th>Source</th><th>Destination</th><th>Quantité</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($transferts as $transfert)
                        <tr>
                            <td>{{ $transfert->medicament }} {{ $transfert->dosage }}</td>
                            <td>{{ $transfert->source ?: 'Non définie' }}</td>
                            <td>{{ $transfert->destination }}</td>
                            <td>{{ $transfert->quantite }}</td>
                            <td>
                                <form method="POST" action="{{ route('reequilibrages.update', $transfert->id) }}" style="display:inline">@csrf @method('PATCH')<input type="hidden" name="action" value="approuver"><button type="submit">Approuver</button></form>
                                <form method="POST" action="{{ route('reequilibrages.update', $transfert->id) }}" style="display:inline">@csrf @method('PATCH')<input type="hidden" name="action" value="rejeter"><button type="submit">Rejeter</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">Aucun rééquilibrage en attente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>
</body>
</html>
