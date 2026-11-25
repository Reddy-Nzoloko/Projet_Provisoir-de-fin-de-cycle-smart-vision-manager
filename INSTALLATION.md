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

3. Configurez l’envoi des liens de réinitialisation dans l’environnement PHP :

   - `APP_BASE_URL` (par exemple `http://localhost/Smart_vision_Manager` en local, ou l’URL HTTPS publique en production)
   - `MAIL_FROM` (adresse d’expédition valide, par exemple `noreply@example.com`)
   - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`
   - `SMTP_ENCRYPTION` (`tls` pour le port 587 ou `ssl` pour le port 465)

   L’envoi utilise PHPMailer via SMTP authentifié. Pour Gmail, utilisez `smtp.gmail.com`, le port `587`, le chiffrement `tls` et un mot de passe d’application Google (pas le mot de passe normal du compte). Configurez ces variables dans l’environnement Windows utilisé par Apache, puis redémarrez Apache. Ne mettez jamais le mot de passe SMTP dans les fichiers suivis par Git. Le compte utilisateur doit aussi avoir une adresse e-mail enregistrée.

4. Ouvrez l’application via Apache, par exemple `http://localhost/Smart_vision_Manager/`.

5. Pour ajouter la récupération de mot de passe à une base déjà installée, exécutez `database/migrations/001_password_resets.sql`. Ne relancez pas `script.sql` sur une base contenant des données : il supprime les tables existantes.

Le schéma initialise les rôles mais aucun compte. Lors de la première visite, ouvrez « Créer le compte administrateur » sur la page de connexion : cette configuration n’est possible que tant qu’aucun utilisateur n’existe. Le mot de passe (12 à 72 octets) est haché avec `password_hash()`. Les connexions sont réservées aux comptes actifs; les connexions, déconnexions et la création initiale sont inscrites au journal d’audit.

Le tableau de bord requiert une session authentifiée. Les formulaires de connexion, de configuration et de déconnexion utilisent une protection CSRF; la déconnexion accepte uniquement une requête POST. En production, servez impérativement l’application en HTTPS et configurez les identifiants MySQL avec un compte dédié, non avec l’utilisateur local `root`.

Le chargement Tailwind par CDN sert à démarrer rapidement l’interface ; pour un déploiement, compilez Tailwind localement et épinglez les versions des dépendances front-end.
