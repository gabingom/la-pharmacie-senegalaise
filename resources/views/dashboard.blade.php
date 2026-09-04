<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord | La Pharmacie Sénégalaise</title>
    <style>
        :root { --green: #2e9450; --deep: #17301f; --bg: #f3f7f3; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--bg); color: var(--deep); font-family: Arial, sans-serif; }
        header { display: flex; justify-content: space-between; align-items: center; padding: 22px 6%; background: #fff; border-bottom: 1px solid #e1ebe3; }
        h1 { margin: 0; font-size: 1.4rem; }
        button { border: 0; padding: 10px 14px; border-radius: 8px; background: var(--green); color: #fff; font-weight: 700; cursor: pointer; }
        main { width: min(1100px, 88%); margin: 42px auto; }
        .welcome { padding: 28px; background: #fff; border-radius: 14px; box-shadow: 0 8px 30px rgba(23, 48, 31, .06); }
        .role { color: var(--green); font-weight: 700; text-transform: uppercase; letter-spacing: .06em; font-size: .8rem; }
    </style>
</head>
<body>
    <header>
        <h1>La Pharmacie Sénégalaise</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Déconnexion</button>
        </form>
    </header>
    <main>
        <section class="welcome">
            <div class="role">{{ $user->role }}</div>
            <h2>Bienvenue, {{ $user->name }}</h2>
            <p>Votre espace Laravel est connecté.</p>
            @if (session('warning')) <p>{{ session('warning') }}</p> @endif
            <div>
                <strong>{{ $medicaments }}</strong> médicaments ·
                <strong>{{ $stocks }}</strong> unités en stock ·
                <strong>{{ $reequilibrages }}</strong> rééquilibrages en attente ·
                <strong>{{ $alertes }}</strong> alertes non lues
            </div>
        </section>
    </main>
</body>
</html>
