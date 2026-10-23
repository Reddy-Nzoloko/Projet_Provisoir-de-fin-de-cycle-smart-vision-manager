<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f5f4">
    <title>Connexion indisponible · Smart Vision</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-stone-100 px-5 text-stone-900 antialiased">
    <main class="mx-auto flex min-h-screen max-w-xl items-center">
        <section class="w-full rounded-3xl border border-stone-200 bg-white p-8 shadow-sm sm:p-10">
            <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-2xl bg-stone-950 text-sm font-bold text-white">SV</div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-stone-500">Smart Vision Manager</p>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight">Base de données indisponible</h1>
            <p class="mt-3 leading-7 text-stone-600">
                La connexion à MySQL n’a pas pu être établie. Vérifiez que le service MySQL fonctionne,
                que le schéma <code class="rounded bg-stone-100 px-1.5 py-0.5 text-sm">smart_vision_manager</code>
                est importé et que vos paramètres de connexion sont corrects.
            </p>
            <p class="mt-5 rounded-xl bg-stone-50 p-4 text-sm leading-6 text-stone-500">
                Vous pouvez définir <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_DATABASE</code>,
                <code>DB_USERNAME</code> et <code>DB_PASSWORD</code> dans l’environnement PHP.
                Le détail technique est consigné dans le journal d’erreurs PHP.
            </p>
        </section>
    </main>
</body>
</html>
