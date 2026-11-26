<?php

declare(strict_types=1);

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$currentUser = \App\Core\Auth::user() ?? ['name' => 'Utilisateur', 'role' => ''];
$initials = 'SV';
$parts = preg_split('/\s+/u', trim((string) $currentUser['name'])) ?: [];
$letters = '';
foreach ($parts as $part) {
    if ($part !== '' && preg_match('/^\X/u', $part, $match) === 1) {
        $letters .= strtoupper($match[0]);
    }
    if (strlen($letters) >= 4) {
        break;
    }
}
if ($letters !== '') {
    $initials = substr($letters, 0, 2);
}

$form = $editing ?? [
    'id_equipement' => '', 'id_station' => '', 'nom_equipement' => '', 'code_equipement' => '',
    'type_equipement' => 'autre', 'fabricant' => '', 'modele' => '', 'numero_serie' => '',
    'adresse_ip_gestion' => '', 'masque_sous_reseau' => '', 'adresse_mac' => '', 'role_equipement' => 'autre',
    'statut' => 'actif', 'date_installation' => '', 'notes' => '',
];
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Équipements · Smart Vision Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-stone-100 text-stone-950">
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="index.php" class="text-sm text-stone-500 hover:text-stone-900">← Tableau de bord</a>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">Équipements</h1>
        </div>
        <div class="flex h-10 items-center gap-2 rounded-xl border border-stone-200 bg-white px-3 text-xs text-stone-600">
            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-stone-200 text-[10px] font-semibold"><?= $escape($initials) ?></span>
            <?= $escape($currentUser['name']) ?>
        </div>
    </header>

    <?php if ($success !== null): ?>
        <div class="mt-6 rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm text-stone-700" role="status"><?= $escape($success) ?></div>
    <?php endif; ?>
    <?php if ($error !== null): ?>
        <div class="mt-6 rounded-xl border border-stone-300 bg-stone-100 px-4 py-3 text-sm text-stone-800" role="alert"><?= $escape($error) ?></div>
    <?php endif; ?>

    <section class="mt-7 grid gap-6 lg:grid-cols-[380px_minmax(0,1fr)]">
        <form action="index.php?action=equipements" method="post" data-equipment-form class="h-fit rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <input type="hidden" name="csrf_token" value="<?= $escape(\App\Core\Session::csrfToken()) ?>">
            <input type="hidden" name="operation" value="<?= $editing ? 'update' : 'create' ?>">
            <input type="hidden" name="id_equipement" value="<?= $escape($form['id_equipement']) ?>">

            <h2 class="text-sm font-semibold"><?= $editing ? 'Modifier l’équipement' : 'Nouvel équipement' ?></h2>
            <p data-submit-feedback class="mt-3 hidden rounded-lg px-3 py-2 text-sm" role="status" aria-live="polite"></p>

            <label class="mt-5 block text-sm font-medium" for="nom_equipement">Nom *</label>
            <input id="nom_equipement" name="nom_equipement" required maxlength="150" value="<?= $escape($form['nom_equipement']) ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm">

            <label class="mt-4 block text-sm font-medium" for="code_equipement">Code *</label>
            <input id="code_equipement" name="code_equipement" required maxlength="60" value="<?= $escape($form['code_equipement']) ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm" placeholder="EQ-001">

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium" for="type_equipement">Type</label>
                    <input id="type_equipement" name="type_equipement" list="types_equipement" maxlength="100" value="<?= $escape($form['type_equipement']) ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm" placeholder="Choisir ou saisir un type">
                    <datalist id="types_equipement">
                        <?php foreach (['routeur_central', 'routeur_client', 'antenne_ptp', 'point_acces', 'switch', 'starlink', 'serveur', 'autre'] as $value): ?>
                            <option value="<?= $escape($value) ?>"><?= $escape(str_replace('_', ' ', ucfirst($value))) ?></option>
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium" for="statut">Statut</label>
                    <select id="statut" name="statut" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm">
                        <?php foreach (['actif', 'inactif', 'maintenance', 'hors_ligne', 'retire'] as $value): ?>
                            <option value="<?= $escape($value) ?>" <?= $form['statut'] === $value ? 'selected' : '' ?>><?= $escape(str_replace('_', ' ', ucfirst($value))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label class="mt-4 block text-sm font-medium" for="id_station">Station</label>
            <select id="id_station" name="id_station" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm">
                <option value="">Aucune station</option>
                <?php foreach ($stations as $station): ?>
                    <option value="<?= (int) $station['id_station'] ?>" <?= (int) $form['id_station'] === (int) $station['id_station'] ? 'selected' : '' ?>><?= $escape($station['nom_station']) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div><label class="block text-sm font-medium" for="fabricant">Fabricant</label><input id="fabricant" name="fabricant" maxlength="100" value="<?= $escape($form['fabricant']) ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm"></div>
                <div><label class="block text-sm font-medium" for="modele">Modèle</label><input id="modele" name="modele" maxlength="100" value="<?= $escape($form['modele']) ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm"></div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div><label class="block text-sm font-medium" for="numero_serie">N° série</label><input id="numero_serie" name="numero_serie" maxlength="120" value="<?= $escape($form['numero_serie']) ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm"></div>
                <div><label class="block text-sm font-medium" for="date_installation">Installation</label><input id="date_installation" name="date_installation" type="date" value="<?= $escape($form['date_installation']) ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm"></div>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <div><label class="block text-sm font-medium" for="adresse_ip_gestion">IP gestion</label><input id="adresse_ip_gestion" name="adresse_ip_gestion" maxlength="45" value="<?= $escape($form['adresse_ip_gestion']) ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm"></div>
                <div><label class="block text-sm font-medium" for="masque_sous_reseau">Masque / préfixe</label><input id="masque_sous_reseau" name="masque_sous_reseau" maxlength="45" value="<?= $escape($form['masque_sous_reseau'] ?? '') ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm" placeholder="255.255.255.0 ou /24"></div>
                <div><label class="block text-sm font-medium" for="adresse_mac">Adresse MAC</label><input id="adresse_mac" name="adresse_mac" maxlength="17" value="<?= $escape($form['adresse_mac']) ?>" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm" placeholder="AA:BB:CC:DD:EE:FF"></div>
            </div>

            <label class="mt-4 block text-sm font-medium" for="role_equipement">Rôle</label>
            <select id="role_equipement" name="role_equipement" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm">
                <?php foreach (['central', 'distribution', 'client', 'autre'] as $value): ?>
                    <option value="<?= $escape($value) ?>" <?= $form['role_equipement'] === $value ? 'selected' : '' ?>><?= $escape(ucfirst($value)) ?></option>
                <?php endforeach; ?>
            </select>

            <label class="mt-4 block text-sm font-medium" for="notes">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="mt-2 w-full rounded-xl border border-stone-200 px-3 py-2.5 text-sm"><?= $escape($form['notes']) ?></textarea>

            <button type="submit" class="mt-5 w-full rounded-xl bg-stone-950 px-4 py-3 text-sm font-medium text-white"><?= $editing ? 'Mettre à jour' : 'Enregistrer' ?></button>
            <?php if ($editing): ?><a href="index.php?action=equipements" class="mt-3 block text-center text-sm text-stone-500">Annuler</a><?php endif; ?>
        </form>

        <section class="overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm">
            <div class="border-b border-stone-100 px-5 py-4"><h2 class="text-sm font-semibold"><?= count($equipments) ?> équipement<?= count($equipments) > 1 ? 's' : '' ?></h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-stone-100 text-xs uppercase text-stone-400"><tr><th class="px-5 py-3">Équipement</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Adresse IP</th><th class="px-5 py-3">Date d’entrée</th><th class="px-5 py-3">Statut</th><th class="px-5 py-3">Station</th><th class="px-5 py-3 text-right">Actions</th></tr></thead>
                    <tbody>
                    <?php if ($equipments === []): ?>
                        <tr><td colspan="7" class="px-5 py-12 text-center text-stone-500">Aucun équipement enregistré.</td></tr>
                    <?php else: foreach ($equipments as $item): ?>
                        <tr class="border-b border-stone-100 last:border-0">
                            <td class="px-5 py-4"><div class="font-medium"><?= $escape($item['nom_equipement']) ?></div><div class="mt-1 text-xs text-stone-400"><?= $escape($item['code_equipement']) ?></div></td>
                            <td class="px-5 py-4 text-stone-600"><?= $escape(str_replace('_', ' ', ucfirst($item['type_equipement']))) ?></td>
                            <td class="px-5 py-4 font-mono text-sm text-stone-700">
                                <?= $escape($item['adresse_ip_gestion'] ?: 'Non renseignée') ?>
                                <?php if ($item['adresse_ip_gestion'] && $item['masque_sous_reseau']): ?>
                                    <div class="mt-1 text-xs text-stone-500"><?= $escape($item['masque_sous_reseau']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-5 py-4 text-stone-600"><?= $item['date_installation'] ? $escape(date('d/m/Y', strtotime($item['date_installation']))) : 'Non renseignée' ?></td>
                            <td class="px-5 py-4"><?= $escape(str_replace('_', ' ', ucfirst($item['statut']))) ?></td>
                            <td class="px-5 py-4 text-stone-600"><?= $escape($item['nom_station'] ?: 'Non affecté') ?></td>
                            <td class="px-5 py-4">
                                <div class="flex justify-end gap-2">
                                    <a href="index.php?action=equipements&amp;edit=<?= (int) $item['id_equipement'] ?>" class="rounded-lg border border-stone-200 px-3 py-2 text-xs hover:bg-stone-50">Modifier</a>
                                    <form action="index.php?action=equipements" method="post" onsubmit="return confirm('Supprimer cet équipement ?')">
                                        <input type="hidden" name="csrf_token" value="<?= $escape(\App\Core\Session::csrfToken()) ?>">
                                        <input type="hidden" name="operation" value="delete">
                                        <input type="hidden" name="id_equipement" value="<?= (int) $item['id_equipement'] ?>">
                                        <button type="submit" class="rounded-lg border border-stone-200 px-3 py-2 text-xs text-red-700 hover:bg-red-50">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </section>
</main>
<script>
    const equipmentForm = document.querySelector('[data-equipment-form]');
    const submitFeedback = equipmentForm.querySelector('[data-submit-feedback]');
    const submitButton = equipmentForm.querySelector('button[type="submit"]');

    equipmentForm.addEventListener('invalid', () => {
        submitFeedback.textContent = 'Veuillez corriger les champs signalés, notamment le nom et le code, avant d’enregistrer.';
        submitFeedback.classList.remove('hidden', 'bg-stone-100', 'text-stone-700');
        submitFeedback.classList.add('bg-amber-50', 'text-amber-900');
    }, true);

    equipmentForm.addEventListener('input', () => {
        submitFeedback.classList.add('hidden');
    });

    equipmentForm.addEventListener('submit', () => {
        submitFeedback.textContent = 'Enregistrement en cours…';
        submitFeedback.classList.remove('hidden', 'bg-amber-50', 'text-amber-900');
        submitFeedback.classList.add('bg-stone-100', 'text-stone-700');
        submitButton.disabled = true;
        submitButton.textContent = 'Enregistrement…';
    });
</script>
</body>
</html>
