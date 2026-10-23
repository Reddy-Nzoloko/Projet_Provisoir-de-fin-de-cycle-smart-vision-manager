<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$currentUser = \App\Core\Auth::user() ?? ['name' => 'Utilisateur', 'role' => ''];
$userInitials = '';
$initialCount = 0;
foreach (preg_split('/\s+/u', trim($currentUser['name'])) ?: [] as $namePart) {
    if ($namePart !== '' && preg_match('/^\X/u', $namePart, $initial) === 1) {
        $userInitials .= strtoupper($initial[0]);
        $initialCount++;
    }
    if ($initialCount >= 2) {
        break;
    }
}
$userInitials = $userInitials !== '' ? $userInitials : 'SV';
$chartData = json_encode(
    ['payments' => $paymentChart, 'traffic' => $trafficChart],
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
);
$monthlyRevenue = number_format((float) $summary['revenue_month'], 2, ',', ' ') . ' $US';
$monthName = (new DateTimeImmutable())->format('F Y');
$frenchMonths = [
    'January' => 'janvier', 'February' => 'février', 'March' => 'mars', 'April' => 'avril',
    'May' => 'mai', 'June' => 'juin', 'July' => 'juillet', 'August' => 'août',
    'September' => 'septembre', 'October' => 'octobre', 'November' => 'novembre', 'December' => 'décembre',
];
$monthName = strtr($monthName, $frenchMonths);

$navigation = [
    ['label' => 'Vue d’ensemble', 'icon' => 'grid', 'active' => true],
    ['label' => 'Clients', 'icon' => 'users'],
    ['label' => 'Réseau', 'icon' => 'network'],
    ['label' => 'Équipements', 'icon' => 'server'],
    ['label' => 'Abonnements', 'icon' => 'layers'],
    ['label' => 'Facturation', 'icon' => 'receipt'],
    ['label' => 'Incidents', 'icon' => 'alert'],
];

$incidentLabels = [
    'critique' => 'Critique',
    'elevee' => 'Élevée',
    'moyenne' => 'Moyenne',
    'faible' => 'Faible',
    'info' => 'Info',
];
$incidentStyles = [
    'critique' => 'bg-stone-950 text-white',
    'elevee' => 'bg-stone-200 text-stone-900',
    'moyenne' => 'bg-stone-100 text-stone-700',
    'faible' => 'bg-stone-100 text-stone-500',
    'info' => 'bg-stone-100 text-stone-500',
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f5f4">
    <title>Vue d’ensemble · Smart Vision Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    boxShadow: { panel: '0 1px 2px rgba(0, 0, 0, .04), 0 8px 28px rgba(0, 0, 0, .035)' }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif; }
        .chart-wrap { position: relative; height: 255px; }
        @media (max-width: 640px) { .chart-wrap { height: 220px; } }
    </style>
</head>
<body class="min-h-screen bg-stone-100 text-stone-950 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[250px_minmax(0,1fr)]">
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-[270px] -translate-x-full flex-col border-r border-stone-200 bg-white px-5 py-6 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:w-auto lg:translate-x-0">
            <a href="index.php" class="flex items-center gap-3 px-1" aria-label="Smart Vision Manager, accueil">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-stone-950 text-xs font-bold tracking-tight text-white">SV</span>
                <span>
                    <span class="block text-sm font-bold tracking-tight">Smart Vision</span>
                    <span class="mt-0.5 block text-[11px] text-stone-500">MANAGER</span>
                </span>
            </a>

            <div class="mt-10 px-2 text-[10px] font-semibold uppercase tracking-[0.18em] text-stone-400">Espace de travail</div>
            <nav class="mt-3 space-y-1" aria-label="Navigation principale">
                <?php foreach ($navigation as $item): ?>
                    <?php if ($item['active']): ?>
                        <a href="index.php" aria-current="page" class="flex items-center gap-3 rounded-xl bg-stone-950 px-3 py-2.5 text-sm font-medium text-white">
                            <span class="w-5 text-center" aria-hidden="true">◈</span>
                            <?= $escape($item['label']) ?>
                        </a>
                    <?php else: ?>
                        <span class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-stone-400" aria-disabled="true" title="Module à venir">
                            <span class="w-5 text-center" aria-hidden="true">·</span>
                            <?= $escape($item['label']) ?>
                            <span class="ml-auto text-[10px] text-stone-300">BIENTÔT</span>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="mt-auto rounded-2xl border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-center gap-2 text-xs font-semibold">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-stone-400 opacity-50"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-stone-700"></span>
                    </span>
                    Supervision prête
                </div>
                <p class="mt-2 text-xs leading-5 text-stone-500">Les indicateurs reflètent les données enregistrées dans votre base.</p>
            </div>
            <div class="mt-5 flex items-center gap-3 border-t border-stone-100 px-1 pt-5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-200 text-xs font-semibold"><?= $escape($userInitials) ?></div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold"><?= $escape($currentUser['name']) ?></p>
                    <p class="mt-0.5 truncate text-[11px] text-stone-500"><?= $escape(ucfirst($currentUser['role'])) ?></p>
                </div>
            </div>
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-stone-950/30 lg:hidden"></div>

        <main class="min-w-0">
            <header class="sticky top-0 z-20 border-b border-stone-200/80 bg-stone-100/90 backdrop-blur">
                <div class="flex h-[68px] items-center justify-between px-4 sm:px-7 lg:px-10">
                    <div class="flex items-center gap-3">
                        <button id="menu-toggle" class="flex h-10 w-10 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-700 lg:hidden" type="button" aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="sidebar">
                            <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                        </button>
                        <div>
                            <p class="text-xs text-stone-500">Tableau de bord <span class="mx-1 text-stone-300">/</span> <span class="text-stone-800">Vue d’ensemble</span></p>
                            <p class="mt-0.5 hidden text-[11px] text-stone-400 sm:block">Opérations réseau &amp; clients</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-3">
                        <span class="hidden rounded-full border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-500 sm:inline-flex"><?= $escape((new DateTimeImmutable())->format('d/m/Y')) ?></span>
                        <form action="index.php?action=logout" method="post">
                            <input type="hidden" name="csrf_token" value="<?= $escape(\App\Core\Session::csrfToken()) ?>">
                            <button type="submit" class="flex items-center gap-2 rounded-xl border border-stone-200 bg-white px-3 py-2 text-xs font-medium text-stone-600 transition hover:bg-stone-50" title="Se déconnecter">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-stone-100 text-[10px] font-semibold text-stone-800"><?= $escape($userInitials) ?></span>
                                <span class="hidden sm:inline"><?= $escape($currentUser['name']) ?></span>
                                <span aria-hidden="true">↗</span>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <div class="mx-auto max-w-[1500px] px-4 py-7 sm:px-7 sm:py-9 lg:px-10">
                <section class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-[0.16em] text-stone-500"><?= $escape(ucfirst($monthName)) ?></p>
                        <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-[30px]">Vue d’ensemble</h1>
                        <p class="mt-2 text-sm text-stone-500">Les informations essentielles de votre activité, en un seul endroit.</p>
                    </div>
                    <div class="flex items-center gap-2 self-start rounded-full border border-stone-200 bg-white px-3 py-2 text-xs font-medium text-stone-600 sm:self-auto">
                        <span class="h-2 w-2 rounded-full bg-stone-900"></span>
                        Données en direct
                    </div>
                </section>

                <section class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Indicateurs principaux">
                    <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-panel">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-stone-500">Clients actifs</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-stone-100 text-sm" aria-hidden="true">◎</span>
                        </div>
                        <p class="mt-5 text-3xl font-semibold tracking-tight"><?= number_format((int) ($summary['clients']['actifs'] ?? 0), 0, ',', ' ') ?></p>
                        <p class="mt-2 text-xs text-stone-500">sur <?= number_format((int) ($summary['clients']['total'] ?? 0), 0, ',', ' ') ?> clients enregistrés</p>
                    </article>
                    <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-panel">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-stone-500">Équipements actifs</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-stone-100 text-sm" aria-hidden="true">⌁</span>
                        </div>
                        <p class="mt-5 text-3xl font-semibold tracking-tight"><?= number_format((int) ($summary['equipment']['actifs'] ?? 0), 0, ',', ' ') ?></p>
                        <p class="mt-2 text-xs text-stone-500"><?= number_format((int) ($summary['equipment']['hors_ligne'] ?? 0), 0, ',', ' ') ?> hors ligne sur <?= number_format((int) ($summary['equipment']['total'] ?? 0), 0, ',', ' ') ?> équipements</p>
                    </article>
                    <article class="rounded-2xl border border-stone-200 bg-white p-5 shadow-panel">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-stone-500">Incidents ouverts</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-stone-100 text-sm" aria-hidden="true">!</span>
                        </div>
                        <p class="mt-5 text-3xl font-semibold tracking-tight"><?= number_format((int) ($summary['incidents']['ouverts'] ?? 0), 0, ',', ' ') ?></p>
                        <p class="mt-2 text-xs text-stone-500"><?= number_format((int) ($summary['incidents']['urgents'] ?? 0), 0, ',', ' ') ?> priorité élevée ou critique</p>
                    </article>
                    <article class="rounded-2xl border border-stone-200 bg-stone-950 p-5 text-white shadow-panel">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-stone-300">Paiements confirmés</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-sm" aria-hidden="true">$</span>
                        </div>
                        <p class="mt-5 text-3xl font-semibold tracking-tight"><?= $escape($monthlyRevenue ?: '0,00 $US') ?></p>
                        <p class="mt-2 text-xs text-stone-400">total encaissé ce mois-ci</p>
                    </article>
                </section>

                <section class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1.55fr)_minmax(320px,1fr)]" aria-label="Graphiques d’activité">
                    <article class="min-w-0 rounded-2xl border border-stone-200 bg-white p-5 shadow-panel sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold">Évolution des paiements</h2>
                                <p class="mt-1 text-xs text-stone-500">Encaissements confirmés · 6 derniers mois</p>
                            </div>
                            <span class="rounded-lg bg-stone-100 px-2.5 py-1.5 text-[11px] font-medium text-stone-600">USD</span>
                        </div>
                        <div class="chart-wrap mt-6"><canvas id="payments-chart" aria-label="Graphique des paiements des six derniers mois"></canvas></div>
                    </article>
                    <article class="min-w-0 rounded-2xl border border-stone-200 bg-white p-5 shadow-panel sm:p-6">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-semibold">Trafic consommé</h2>
                                <p class="mt-1 text-xs text-stone-500">Volume quotidien · 7 derniers jours</p>
                            </div>
                            <span class="rounded-lg bg-stone-100 px-2.5 py-1.5 text-[11px] font-medium text-stone-600">Go</span>
                        </div>
                        <div class="chart-wrap mt-6"><canvas id="traffic-chart" aria-label="Graphique du trafic réseau des sept derniers jours"></canvas></div>
                    </article>
                </section>

                <section class="mt-5 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-panel" aria-labelledby="incidents-title">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-stone-100 px-5 py-5 sm:px-6">
                        <div>
                            <h2 id="incidents-title" class="text-sm font-semibold">Activité récente</h2>
                            <p class="mt-1 text-xs text-stone-500">Derniers incidents signalés sur votre réseau</p>
                        </div>
                        <span class="rounded-full border border-stone-200 px-2.5 py-1 text-[11px] font-medium text-stone-500"><?= count($recentIncidents) ?> événement<?= count($recentIncidents) > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-left text-sm">
                            <thead class="bg-stone-50/80 text-[11px] font-medium uppercase tracking-wide text-stone-500">
                                <tr>
                                    <th class="px-6 py-3">Incident</th>
                                    <th class="px-6 py-3">Équipement / station</th>
                                    <th class="px-6 py-3">Priorité</th>
                                    <th class="px-6 py-3">Statut</th>
                                    <th class="px-6 py-3">Détecté le</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                <?php if ($recentIncidents === []): ?>
                                    <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-stone-500">Aucun incident enregistré pour le moment.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($recentIncidents as $incident): ?>
                                        <tr class="transition-colors hover:bg-stone-50/70">
                                            <td class="max-w-[240px] truncate px-6 py-4 font-medium text-stone-800"><?= $escape($incident['titre']) ?></td>
                                            <td class="px-6 py-4 text-stone-600"><?= $escape($incident['cible']) ?></td>
                                            <td class="px-6 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-medium <?= $escape($incidentStyles[$incident['severite']] ?? $incidentStyles['info']) ?>"><?= $escape($incidentLabels[$incident['severite']] ?? 'Info') ?></span></td>
                                            <td class="px-6 py-4 text-stone-600"><?= $escape(str_replace('_', ' ', ucfirst($incident['statut']))) ?></td>
                                            <td class="whitespace-nowrap px-6 py-4 text-xs text-stone-500"><?= $escape((new DateTimeImmutable($incident['date_detection']))->format('d/m/Y · H:i')) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <footer class="py-7 text-center text-[11px] text-stone-400">Smart Vision Manager <span class="mx-1">·</span> Supervision intelligente de votre réseau</footer>
            </div>
        </main>
    </div>
    <script>
        window.smartVisionDashboard = <?= $chartData ?>;
    </script>
    <script src="public/assets/dashboard.js" defer></script>
</body>
</html>
