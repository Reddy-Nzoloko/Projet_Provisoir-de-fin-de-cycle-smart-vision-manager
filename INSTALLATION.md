# Démarrage local

## Prérequis

- PHP 8.1 ou supérieur avec `pdo_mysql`
- MySQL ou MariaDB démarré (XAMPP convient)
- Apache avec `mod_rewrite` et les fichiers `.htaccess` activés
- Accès Internet pour Tailwind CSS, Chart.js et la police distante pendant ce prototype

## Installation

1. Créez la base de données en important `script.sql` dans MySQL. **Ce script supprime les tables existantes de cette base avant de les recréer.**
2. Vérifiez les paramètres de connexion dans l’environnement PHP :

   - `DB_HOST` (par défaut `127.0.0.1`)
   - `DB_PORT` (par défaut `3306`)
   - `DB_DATABASE` (par défaut `smart_vision_manager`)
   - `DB_USERNAME` (par défaut `root`)
   - `DB_PASSWORD` (par défaut vide, configuration locale XAMPP uniquement)

3. Ouvrez l’application via Apache, par exemple `http://localhost/Smart_vision_Manager/`.

Le schéma initialise les rôles mais aucun compte. Lors de la première visite, ouvrez « Créer le compte administrateur » sur la page de connexion : cette configuration n’est possible que tant qu’aucun utilisateur n’existe. Le mot de passe (12 à 72 octets) est haché avec `password_hash()`. Les connexions sont réservées aux comptes actifs; les connexions, déconnexions et la création initiale sont inscrites au journal d’audit.

Le tableau de bord requiert une session authentifiée. Les formulaires de connexion, de configuration et de déconnexion utilisent une protection CSRF; la déconnexion accepte uniquement une requête POST. En production, servez impérativement l’application en HTTPS et configurez les identifiants MySQL avec un compte dédié, non avec l’utilisateur local `root`.

Le chargement Tailwind par CDN sert à démarrer rapidement l’interface ; pour un déploiement, compilez Tailwind localement et épinglez les versions des dépendances front-end.
