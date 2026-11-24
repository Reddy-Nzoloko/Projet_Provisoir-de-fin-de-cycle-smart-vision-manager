<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f5f4">
    <title>Nouveau mot de passe · Smart Vision Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-stone-100 text-stone-950 antialiased">
    <main class="mx-auto flex min-h-screen max-w-xl items-center justify-center px-4 py-10 sm:px-6">
        <section class="w-full rounded-3xl border border-stone-200 bg-white p-6 shadow-xl shadow-stone-900/5 sm:p-10">
            <a href="index.php?action=login" class="text-sm font-semibold text-stone-600 hover:text-stone-950">← Retour à la connexion</a>
            <h1 class="mt-8 text-3xl font-semibold tracking-tight">Choisir un nouveau mot de passe</h1>

            <?php if ($error !== null): ?>
                <div class="mt-6 rounded-xl border border-stone-300 bg-stone-100 px-4 py-3 text-sm text-stone-800" role="alert"><?= $escape($error) ?></div>
            <?php endif; ?>

            <?php if ($tokenValid): ?>
                <form action="index.php?action=reset-password" method="post" class="mt-8 space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= $escape(\App\Core\Session::csrfToken()) ?>">
                    <input type="hidden" name="token" value="<?= $escape($token) ?>">
                    <div>
                        <label for="password" class="block text-sm font-medium">Nouveau mot de passe</label>
                        <input id="password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password" class="mt-2 block w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-stone-500 focus:ring-4 focus:ring-stone-100">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium">Confirmer le mot de passe</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" maxlength="72" autocomplete="new-password" class="mt-2 block w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none focus:border-stone-500 focus:ring-4 focus:ring-stone-100">
                    </div>
                    <button type="submit" class="w-full rounded-xl bg-stone-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-stone-800 focus:outline-none focus:ring-4 focus:ring-stone-300">Enregistrer le mot de passe</button>
                </form>
            <?php else: ?>
                <p class="mt-4 text-sm text-stone-600">Demandez un nouveau lien pour continuer.</p>
                <a href="index.php?action=forgot-password" class="mt-6 inline-flex rounded-xl bg-stone-950 px-4 py-3 text-sm font-medium text-white hover:bg-stone-800">Demander un nouveau lien</a>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>