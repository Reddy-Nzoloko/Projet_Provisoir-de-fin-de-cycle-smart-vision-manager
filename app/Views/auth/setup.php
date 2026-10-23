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
    <title>Configuration initiale · Smart Vision Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-stone-100 px-4 py-10 text-stone-950 antialiased">
    <main class="mx-auto flex min-h-[calc(100vh-5rem)] max-w-lg items-center">
        <section class="w-full rounded-3xl border border-stone-200 bg-white p-6 shadow-xl shadow-stone-900/5 sm:p-10">
            <a href="index.php?action=login" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-stone-950 text-xs font-bold text-white">SV</span>
                <span class="text-sm font-semibold">Smart Vision Manager</span>
            </a>
            <p class="mt-8 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">Configuration initiale</p>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight">Créer l’administrateur</h1>
            <p class="mt-2 text-sm leading-6 text-stone-500">Ce formulaire n’est disponible qu’avant la création du premier utilisateur.</p>

            <?php if ($error !== null): ?>
                <div class="mt-6 rounded-xl border border-stone-300 bg-stone-100 px-4 py-3 text-sm text-stone-800" role="alert"><?= $escape($error) ?></div>
            <?php endif; ?>

            <form action="index.php?action=setup" method="post" class="mt-7 space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $escape(\App\Core\Session::csrfToken()) ?>">
                <div>
                    <label for="full_name" class="block text-sm font-medium">Nom complet</label>
                    <input id="full_name" name="full_name" type="text" required maxlength="150" autocomplete="name" value="<?= $escape($_POST['full_name'] ?? '') ?>" class="mt-2 block w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-stone-500 focus:ring-4 focus:ring-stone-100">
                </div>
                <div>
                    <label for="username" class="block text-sm font-medium">Nom d’utilisateur</label>
                    <input id="username" name="username" type="text" required minlength="3" maxlength="80" autocomplete="username" value="<?= $escape($_POST['username'] ?? '') ?>" class="mt-2 block w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-stone-500 focus:ring-4 focus:ring-stone-100">
                    <p class="mt-1.5 text-xs text-stone-400">3 à 80 caractères : lettres, chiffres, point, tiret ou souligné.</p>
                </div>
                <div>
                    <label for="email" class="block text-sm font-medium">E-mail <span class="font-normal text-stone-400">(facultatif)</span></label>
                    <input id="email" name="email" type="email" maxlength="190" autocomplete="email" value="<?= $escape($_POST['email'] ?? '') ?>" class="mt-2 block w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-stone-500 focus:ring-4 focus:ring-stone-100">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium">Mot de passe</label>
                    <input id="password" name="password" type="password" required minlength="12" maxlength="72" autocomplete="new-password" class="mt-2 block w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-stone-500 focus:ring-4 focus:ring-stone-100">
                    <p class="mt-1.5 text-xs text-stone-400">Au moins 12 caractères, 72 octets maximum.</p>
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium">Confirmer le mot de passe</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" maxlength="72" autocomplete="new-password" class="mt-2 block w-full rounded-xl border border-stone-200 px-4 py-3 text-sm outline-none focus:border-stone-500 focus:ring-4 focus:ring-stone-100">
                </div>
                <button type="submit" class="mt-2 flex w-full items-center justify-center rounded-xl bg-stone-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-stone-800 focus:outline-none focus:ring-4 focus:ring-stone-300">Créer le compte administrateur</button>
            </form>
            <p class="mt-6 text-center text-xs text-stone-400">Le mot de passe est stocké sous forme de hash sécurisé.</p>
        </section>
    </main>
</body>
</html>
