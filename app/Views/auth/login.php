<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$success = \App\Core\Session::consumeFlash('success');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f5f4">
    <title>Connexion · Smart Vision Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-stone-100 text-stone-950 antialiased">
    <main class="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-4 py-10 sm:px-6">
        <section class="grid w-full overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-2xl shadow-stone-900/5 lg:grid-cols-[1fr_1fr]">
            <div class="hidden flex-col justify-between bg-stone-950 p-10 text-white lg:flex xl:p-14">
                <a href="index.php?action=login" class="flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white text-xs font-bold text-stone-950">SV</span>
                    <span class="text-sm font-semibold tracking-tight">Smart Vision <span class="font-normal text-stone-400">Manager</span></span>
                </a>
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-stone-400">Supervision réseau</p>
                    <h1 class="mt-5 text-4xl font-semibold leading-tight tracking-tight xl:text-5xl">Toutes vos opérations.<br>Une vision claire.</h1>
                    <p class="mt-5 max-w-sm text-sm leading-7 text-stone-400">Gérez votre réseau, vos équipements et vos clients depuis un espace unique.</p>
                </div>
                <p class="text-xs text-stone-500">Smart Vision Manager · Accès sécurisé</p>
            </div>

            <div class="flex items-center justify-center px-6 py-10 sm:px-12 lg:px-10 xl:px-16">
                <div class="w-full max-w-md">
                    <a href="index.php?action=login" class="flex items-center gap-3 lg:hidden">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-stone-950 text-xs font-bold text-white">SV</span>
                        <span class="text-sm font-semibold">Smart Vision Manager</span>
                    </a>
                    <p class="mt-10 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500 lg:mt-0">Votre espace de travail</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight">Bon retour.</h2>
                    <p class="mt-2 text-sm text-stone-500">Connectez-vous pour accéder à votre tableau de bord.</p>

                    <?php if ($success !== null): ?>
                        <div class="mt-6 rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 text-sm text-stone-700" role="status"><?= $escape($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error !== null): ?>
                        <div class="mt-6 rounded-xl border border-stone-300 bg-stone-100 px-4 py-3 text-sm text-stone-800" role="alert"><?= $escape($error) ?></div>
                    <?php endif; ?>

                    <form action="index.php?action=login" method="post" class="mt-8 space-y-5">
                        <input type="hidden" name="csrf_token" value="<?= $escape(\App\Core\Session::csrfToken()) ?>">
                        <div>
                            <label for="identifier" class="block text-sm font-medium">Nom d’utilisateur ou e-mail</label>
                            <input id="identifier" name="identifier" type="text" required autocomplete="username" maxlength="190" value="<?= $escape($_POST['identifier'] ?? '') ?>" class="mt-2 block w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-stone-400 focus:border-stone-500 focus:ring-4 focus:ring-stone-100" placeholder="Votre identifiant">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium">Mot de passe</label>
                            <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-2 block w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm outline-none transition placeholder:text-stone-400 focus:border-stone-500 focus:ring-4 focus:ring-stone-100" placeholder="Votre mot de passe">
                        </div>
                        <button type="submit" class="flex w-full items-center justify-center rounded-xl bg-stone-950 px-4 py-3 text-sm font-medium text-white transition hover:bg-stone-800 focus:outline-none focus:ring-4 focus:ring-stone-300">Se connecter <span class="ml-2" aria-hidden="true">→</span></button>
                    </form>

                    <?php if (!$hasUsers): ?>
                        <p class="mt-6 text-center text-sm text-stone-500">Première utilisation ?
                            <a href="index.php?action=setup" class="font-semibold text-stone-900 underline decoration-stone-300 underline-offset-4 hover:decoration-stone-900">Créer le compte administrateur</a>
                        </p>
                    <?php endif; ?>
                    <p class="mt-10 text-center text-xs text-stone-400">Accès réservé aux utilisateurs autorisés.</p>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
