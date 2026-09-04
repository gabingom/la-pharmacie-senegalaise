<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion | La Pharmacie Sénégalaise</title>
    <style>
        :root { --green: #2e9450; --deep: #17301f; --cream: #e9e5da; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px; background: var(--cream); color: var(--deep); font-family: Arial, sans-serif; }
        main { width: min(100%, 440px); padding: 42px; background: #fff; border-radius: 20px; box-shadow: 0 20px 60px rgba(20, 81, 44, .16); }
        h1 { margin: 0 0 8px; font-size: 1.8rem; }
        p { margin: 0 0 28px; color: #6d7f72; }
        label { display: block; margin: 18px 0 8px; font-weight: 600; }
        input { width: 100%; padding: 14px; border: 1px solid #dce5df; border-radius: 10px; font: inherit; }
        input:focus { outline: 3px solid rgba(46, 148, 80, .16); border-color: var(--green); }
        button { width: 100%; margin-top: 24px; padding: 14px; border: 0; border-radius: 10px; background: var(--green); color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        .remember { display: flex; gap: 8px; align-items: center; font-weight: 400; }
        .remember input { width: auto; }
        .error { margin-top: 6px; color: #a32d2d; font-size: .9rem; }
    </style>
</head>
<body>
    <main>
        <h1>La Pharmacie Sénégalaise</h1>
        <p>Accédez à votre espace de gestion pharmaceutique.</p>
        <form method="POST" action="{{ route('login.store') }}">
            @csrf
            <label for="email">Adresse email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Mot de passe</label>
            <input id="password" name="password" type="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <label class="remember"><input type="checkbox" name="remember"> Se souvenir de moi</label>
            <button type="submit">Se connecter</button>
        </form>
    </main>
</body>
</html>
