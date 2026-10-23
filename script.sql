-- ============================================================
-- SMART VISION MANAGER
-- Schéma initial complet MySQL / MariaDB
-- Compatible MySQL 8.0+ recommandé (InnoDB, utf8mb4)
-- À exécuter dans une base dédiée. Sauvegardez avant remplacement.
-- Ce script crée les tables, contraintes, index et données de référence.
-- Il ne crée PAS de compte administrateur avec mot de passe prédéfini.
-- ============================================================

CREATE DATABASE IF NOT EXISTS smart_vision_manager
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smart_vision_manager;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Suppression dans l'ordre dépendant : permet de rejouer le script.
-- ATTENTION : cette section EFFACE les tables et leurs données existantes.
DROP TABLE IF EXISTS ia_appels_api;
DROP TABLE IF EXISTS ia_messages;
DROP TABLE IF EXISTS ia_conversations;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS journaux_audit;
DROP TABLE IF EXISTS commandes_reseau;
DROP TABLE IF EXISTS incidents;
DROP TABLE IF EXISTS consommations_journalieres;
DROP TABLE IF EXISTS mesures_compteurs;
DROP TABLE IF EXISTS mesures_reseau;
DROP TABLE IF EXISTS paiements;
DROP TABLE IF EXISTS factures;
DROP TABLE IF EXISTS abonnements;
DROP TABLE IF EXISTS forfaits;
DROP TABLE IF EXISTS adresses_ip;
DROP TABLE IF EXISTS acces_clients;
DROP TABLE IF EXISTS liaisons_reseau;
DROP TABLE IF EXISTS equipements;
DROP TABLE IF EXISTS stations;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS utilisateurs;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 1. RÔLES ET UTILISATEURS
-- ============================================================

CREATE TABLE roles (
  id_role BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom_role VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE utilisateurs (
  id_utilisateur BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_role BIGINT UNSIGNED NOT NULL,
  nom_complet VARCHAR(150) NOT NULL,
  nom_utilisateur VARCHAR(80) NOT NULL UNIQUE,
  email VARCHAR(190) NULL UNIQUE,
  telephone VARCHAR(30) NULL,
  mot_de_passe VARCHAR(255) NOT NULL COMMENT 'Hash créé avec PHP password_hash()',
  statut ENUM('actif','inactif','bloque') NOT NULL DEFAULT 'actif',
  derniere_connexion DATETIME NULL,
  derniere_adresse_ip VARCHAR(45) NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_utilisateurs_role FOREIGN KEY (id_role) REFERENCES roles(id_role)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_utilisateurs_role_statut (id_role, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. CLIENTS, STATIONS, ÉQUIPEMENTS ET LIAISONS
-- ============================================================

CREATE TABLE clients (
  id_client BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code_client VARCHAR(40) NOT NULL UNIQUE,
  nom VARCHAR(100) NOT NULL,
  postnom VARCHAR(100) NULL,
  prenom VARCHAR(100) NULL,
  telephone VARCHAR(30) NOT NULL,
  email VARCHAR(190) NULL,
  adresse VARCHAR(255) NULL,
  reference_piece VARCHAR(100) NULL,
  statut ENUM('actif','suspendu','archive') NOT NULL DEFAULT 'actif',
  notes TEXT NULL,
  cree_par BIGINT UNSIGNED NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_clients_createur FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_clients_nom (nom, postnom, prenom),
  INDEX idx_clients_telephone (telephone),
  INDEX idx_clients_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stations (
  id_station BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom_station VARCHAR(120) NOT NULL,
  code_station VARCHAR(40) NOT NULL UNIQUE,
  adresse VARCHAR(255) NULL,
  description TEXT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  statut ENUM('active','inactive','maintenance','hors_ligne') NOT NULL DEFAULT 'active',
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_stations_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE equipements (
  id_equipement BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_station BIGINT UNSIGNED NULL,
  nom_equipement VARCHAR(150) NOT NULL,
  code_equipement VARCHAR(60) NOT NULL UNIQUE,
  type_equipement ENUM('routeur_central','routeur_client','antenne_ptp','point_acces','switch','starlink','serveur','autre') NOT NULL DEFAULT 'autre',
  fabricant VARCHAR(100) NULL,
  modele VARCHAR(100) NULL,
  numero_serie VARCHAR(120) NULL,
  adresse_ip_gestion VARCHAR(45) NULL,
  adresse_mac VARCHAR(17) NULL,
  role_equipement ENUM('central','distribution','client','autre') NOT NULL DEFAULT 'autre',
  statut ENUM('actif','inactif','maintenance','hors_ligne','retire') NOT NULL DEFAULT 'actif',
  date_installation DATE NULL,
  derniere_verification DATETIME NULL,
  notes TEXT NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_equipements_station FOREIGN KEY (id_station) REFERENCES stations(id_station)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_equipements_station (id_station),
  INDEX idx_equipements_type_statut (type_equipement, statut),
  INDEX idx_equipements_ip (adresse_ip_gestion),
  INDEX idx_equipements_mac (adresse_mac)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE liaisons_reseau (
  id_liaison BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_station_source BIGINT UNSIGNED NULL,
  id_station_destination BIGINT UNSIGNED NULL,
  id_equipement_source BIGINT UNSIGNED NULL,
  id_equipement_destination BIGINT UNSIGNED NULL,
  nom_liaison VARCHAR(150) NOT NULL,
  type_liaison ENUM('fibre','radio_ptp','wifi','ethernet','starlink','autre') NOT NULL DEFAULT 'autre',
  capacite_mbps DECIMAL(10,2) NULL,
  statut ENUM('active','inactive','degradee','hors_ligne','maintenance') NOT NULL DEFAULT 'active',
  notes TEXT NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_liaisons_station_source FOREIGN KEY (id_station_source) REFERENCES stations(id_station)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_liaisons_station_destination FOREIGN KEY (id_station_destination) REFERENCES stations(id_station)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_liaisons_equipement_source FOREIGN KEY (id_equipement_source) REFERENCES equipements(id_equipement)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_liaisons_equipement_destination FOREIGN KEY (id_equipement_destination) REFERENCES equipements(id_equipement)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_liaisons_statut (statut),
  INDEX idx_liaisons_equipements (id_equipement_source, id_equipement_destination)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. ACCÈS CLIENTS ET HISTORIQUE DES ADRESSES IP
-- ============================================================

CREATE TABLE acces_clients (
  id_acces BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_client BIGINT UNSIGNED NOT NULL,
  id_station BIGINT UNSIGNED NULL,
  id_equipement_client BIGINT UNSIGNED NULL,
  nom_acces VARCHAR(120) NOT NULL,
  adresse_installation VARCHAR(255) NULL,
  identifiant_routeur VARCHAR(100) NULL,
  statut ENUM('en_attente','actif','suspendu','resilie') NOT NULL DEFAULT 'en_attente',
  date_activation DATETIME NULL,
  date_resiliation DATETIME NULL,
  notes TEXT NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_acces_client FOREIGN KEY (id_client) REFERENCES clients(id_client)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_acces_station FOREIGN KEY (id_station) REFERENCES stations(id_station)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_acces_equipement_client FOREIGN KEY (id_equipement_client) REFERENCES equipements(id_equipement)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_acces_client_statut (id_client, statut),
  INDEX idx_acces_station (id_station),
  INDEX idx_acces_equipement (id_equipement_client)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE adresses_ip (
  id_adresse_ip BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_acces BIGINT UNSIGNED NOT NULL,
  adresse_ip VARCHAR(45) NOT NULL,
  source_attribution ENUM('statique','dhcp','pppoe','autre') NOT NULL DEFAULT 'statique',
  date_debut DATETIME NOT NULL,
  date_fin DATETIME NULL,
  est_actuelle TINYINT(1) NOT NULL DEFAULT 1,
  notes VARCHAR(255) NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_adresses_ip_acces FOREIGN KEY (id_acces) REFERENCES acces_clients(id_acces)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_adresses_ip_dates CHECK (date_fin IS NULL OR date_fin >= date_debut),
  INDEX idx_adresses_ip_acces (id_acces, date_debut),
  INDEX idx_adresses_ip_actuelle (adresse_ip, est_actuelle),
  INDEX idx_adresses_ip_periode (date_debut, date_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- L'unicité d'une IP active est à contrôler transactionnellement par le service applicatif.
-- Cela évite une contrainte unique fragile sur un indicateur booléen d'historique.

-- ============================================================
-- 4. FORFAITS, ABONNEMENTS, FACTURES ET PAIEMENTS
-- ============================================================

CREATE TABLE forfaits (
  id_forfait BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nom_forfait VARCHAR(120) NOT NULL,
  code_forfait VARCHAR(40) NOT NULL UNIQUE,
  description TEXT NULL,
  duree_jours INT UNSIGNED NOT NULL,
  prix DECIMAL(12,2) NOT NULL,
  devise CHAR(3) NOT NULL DEFAULT 'USD',
  debit_telechargement_mbps DECIMAL(10,2) NULL,
  debit_envoi_mbps DECIMAL(10,2) NULL,
  volume_inclus_go DECIMAL(12,2) NULL,
  est_actif TINYINT(1) NOT NULL DEFAULT 1,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT chk_forfait_prix CHECK (prix >= 0),
  CONSTRAINT chk_forfait_duree CHECK (duree_jours > 0),
  INDEX idx_forfaits_actif (est_actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE abonnements (
  id_abonnement BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_acces BIGINT UNSIGNED NOT NULL,
  id_forfait BIGINT UNSIGNED NOT NULL,
  date_debut DATETIME NOT NULL,
  date_fin DATETIME NOT NULL,
  prix_applique DECIMAL(12,2) NOT NULL,
  devise CHAR(3) NOT NULL DEFAULT 'USD',
  statut ENUM('en_attente','actif','expire','suspendu','annule') NOT NULL DEFAULT 'en_attente',
  motif_annulation VARCHAR(255) NULL,
  cree_par BIGINT UNSIGNED NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_abonnements_acces FOREIGN KEY (id_acces) REFERENCES acces_clients(id_acces)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_abonnements_forfait FOREIGN KEY (id_forfait) REFERENCES forfaits(id_forfait)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_abonnements_createur FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_abonnements_dates CHECK (date_fin >= date_debut),
  CONSTRAINT chk_abonnements_prix CHECK (prix_applique >= 0),
  INDEX idx_abonnements_acces_statut (id_acces, statut),
  INDEX idx_abonnements_fin_statut (date_fin, statut),
  INDEX idx_abonnements_forfait (id_forfait)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE factures (
  id_facture BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_abonnement BIGINT UNSIGNED NOT NULL,
  numero_facture VARCHAR(60) NOT NULL UNIQUE,
  date_facture DATETIME NOT NULL,
  date_echeance DATETIME NULL,
  montant_total DECIMAL(12,2) NOT NULL,
  devise CHAR(3) NOT NULL DEFAULT 'USD',
  statut ENUM('brouillon','emise','partielle','payee','annulee') NOT NULL DEFAULT 'emise',
  notes TEXT NULL,
  cree_par BIGINT UNSIGNED NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_factures_abonnement FOREIGN KEY (id_abonnement) REFERENCES abonnements(id_abonnement)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_factures_createur FOREIGN KEY (cree_par) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_factures_montant CHECK (montant_total >= 0),
  INDEX idx_factures_abonnement (id_abonnement),
  INDEX idx_factures_statut_echeance (statut, date_echeance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE paiements (
  id_paiement BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_facture BIGINT UNSIGNED NOT NULL,
  reference_paiement VARCHAR(120) NULL UNIQUE,
  montant DECIMAL(12,2) NOT NULL,
  devise CHAR(3) NOT NULL DEFAULT 'USD',
  mode_paiement ENUM('especes','mobile_money','virement','carte','autre') NOT NULL DEFAULT 'especes',
  statut ENUM('en_attente','confirme','rejete','rembourse') NOT NULL DEFAULT 'en_attente',
  date_paiement DATETIME NULL,
  enregistre_par BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_paiements_facture FOREIGN KEY (id_facture) REFERENCES factures(id_facture)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_paiements_utilisateur FOREIGN KEY (enregistre_par) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_paiements_montant CHECK (montant > 0),
  INDEX idx_paiements_facture_statut (id_facture, statut),
  INDEX idx_paiements_date (date_paiement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Le statut des factures doit être recalculé par le service de facturation
-- à partir des paiements confirmés, dans une transaction.

-- ============================================================
-- 5. MESURES DE TRAFIC ET CONSOMMATION
-- ============================================================

CREATE TABLE mesures_compteurs (
  id_mesure BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_acces BIGINT UNSIGNED NOT NULL,
  id_equipement BIGINT UNSIGNED NULL,
  date_mesure DATETIME(3) NOT NULL,
  octets_recus_cumul BIGINT UNSIGNED NULL,
  octets_envoyes_cumul BIGINT UNSIGNED NULL,
  debit_reception_bps BIGINT UNSIGNED NULL,
  debit_envoi_bps BIGINT UNSIGNED NULL,
  source_mesure ENUM('mikrotik_api','snmp','agent','manuel','autre') NOT NULL DEFAULT 'mikrotik_api',
  compteur_reinitialise TINYINT(1) NOT NULL DEFAULT 0,
  statut_collecte ENUM('valide','incomplet','erreur') NOT NULL DEFAULT 'valide',
  details_erreur VARCHAR(500) NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mesures_compteurs_acces FOREIGN KEY (id_acces) REFERENCES acces_clients(id_acces)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mesures_compteurs_equipement FOREIGN KEY (id_equipement) REFERENCES equipements(id_equipement)
    ON UPDATE CASCADE ON DELETE SET NULL,
  UNIQUE KEY uq_mesure_acces_date (id_acces, date_mesure),
  INDEX idx_mesures_date (date_mesure),
  INDEX idx_mesures_acces_date (id_acces, date_mesure),
  INDEX idx_mesures_statut (statut_collecte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE consommations_journalieres (
  id_consommation BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_abonnement BIGINT UNSIGNED NOT NULL,
  date_consommation DATE NOT NULL,
  octets_recus BIGINT UNSIGNED NOT NULL DEFAULT 0,
  octets_envoyes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  nombre_mesures INT UNSIGNED NOT NULL DEFAULT 0,
  donnees_completes TINYINT(1) NOT NULL DEFAULT 0,
  date_calcul DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_consommations_abonnement FOREIGN KEY (id_abonnement) REFERENCES abonnements(id_abonnement)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  UNIQUE KEY uq_consommation_abonnement_jour (id_abonnement, date_consommation),
  INDEX idx_consommation_jour (date_consommation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mesures_reseau (
  id_mesure_reseau BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_equipement BIGINT UNSIGNED NOT NULL,
  date_mesure DATETIME(3) NOT NULL,
  est_accessible TINYINT(1) NOT NULL,
  latence_ms DECIMAL(10,2) NULL,
  perte_paquets_pct DECIMAL(5,2) NULL,
  cpu_pct DECIMAL(5,2) NULL,
  memoire_pct DECIMAL(5,2) NULL,
  debit_reception_bps BIGINT UNSIGNED NULL,
  debit_envoi_bps BIGINT UNSIGNED NULL,
  source_mesure ENUM('ping','routeros_api','snmp','agent','autre') NOT NULL DEFAULT 'ping',
  details_erreur VARCHAR(500) NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mesures_reseau_equipement FOREIGN KEY (id_equipement) REFERENCES equipements(id_equipement)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_mesures_reseau_equipement_date (id_equipement, date_mesure),
  INDEX idx_mesures_reseau_date_accessible (date_mesure, est_accessible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. INCIDENTS ET COMMANDES RÉSEAU
-- ============================================================

CREATE TABLE incidents (
  id_incident BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_station BIGINT UNSIGNED NULL,
  id_equipement BIGINT UNSIGNED NULL,
  id_acces BIGINT UNSIGNED NULL,
  assigne_a BIGINT UNSIGNED NULL,
  titre VARCHAR(180) NOT NULL,
  description TEXT NULL,
  severite ENUM('info','faible','moyenne','elevee','critique') NOT NULL DEFAULT 'moyenne',
  statut ENUM('ouvert','en_cours','resolu','ferme') NOT NULL DEFAULT 'ouvert',
  date_detection DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_resolution DATETIME NULL,
  resolution TEXT NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_incidents_station FOREIGN KEY (id_station) REFERENCES stations(id_station)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_incidents_equipement FOREIGN KEY (id_equipement) REFERENCES equipements(id_equipement)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_incidents_acces FOREIGN KEY (id_acces) REFERENCES acces_clients(id_acces)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_incidents_assigne FOREIGN KEY (assigne_a) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_incidents_statut_severite (statut, severite),
  INDEX idx_incidents_detection (date_detection),
  INDEX idx_incidents_acces (id_acces)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commandes_reseau (
  id_commande BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_equipement BIGINT UNSIGNED NOT NULL,
  id_acces BIGINT UNSIGNED NULL,
  demande_par BIGINT UNSIGNED NULL,
  type_commande ENUM('verification','suspension','reactivation','limitation_debit','autre') NOT NULL,
  statut ENUM('en_attente','en_cours','reussie','echec','annulee') NOT NULL DEFAULT 'en_attente',
  cle_idempotence CHAR(36) NOT NULL UNIQUE,
  motif VARCHAR(255) NOT NULL,
  demande_json JSON NULL,
  resultat TEXT NULL,
  code_erreur VARCHAR(100) NULL,
  date_demande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_debut DATETIME NULL,
  date_fin DATETIME NULL,
  CONSTRAINT fk_commandes_equipement FOREIGN KEY (id_equipement) REFERENCES equipements(id_equipement)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_commandes_acces FOREIGN KEY (id_acces) REFERENCES acces_clients(id_acces)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_commandes_demandeur FOREIGN KEY (demande_par) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_commandes_statut_date (statut, date_demande),
  INDEX idx_commandes_acces (id_acces),
  INDEX idx_commandes_equipement (id_equipement)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Les commandes sont des demandes auditables : l'exécution doit être faite
-- par un service réseau à privilèges limités, jamais par l'IA directement.

-- ============================================================
-- 7. NOTIFICATIONS INTERNES ET AUDIT
-- ============================================================

CREATE TABLE notifications (
  id_notification BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_utilisateur BIGINT UNSIGNED NULL,
  id_client BIGINT UNSIGNED NULL,
  type_notification ENUM('abonnement','paiement','reseau','consommation','securite','systeme','ia') NOT NULL DEFAULT 'systeme',
  titre VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  niveau ENUM('info','succes','avertissement','erreur','critique') NOT NULL DEFAULT 'info',
  est_lue TINYINT(1) NOT NULL DEFAULT 0,
  cle_deduplication VARCHAR(190) NULL UNIQUE,
  date_lecture DATETIME NULL,
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT fk_notifications_client FOREIGN KEY (id_client) REFERENCES clients(id_client)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_notifications_user_lue_date (id_utilisateur, est_lue, date_creation),
  INDEX idx_notifications_type_date (type_notification, date_creation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE journaux_audit (
  id_audit BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_utilisateur BIGINT UNSIGNED NULL,
  action VARCHAR(100) NOT NULL,
  entite VARCHAR(100) NOT NULL,
  id_entite BIGINT UNSIGNED NULL,
  adresse_ip_utilisateur VARCHAR(45) NULL,
  resultat ENUM('succes','echec','refuse') NOT NULL DEFAULT 'succes',
  details JSON NULL,
  date_action DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  CONSTRAINT fk_audit_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_audit_utilisateur_date (id_utilisateur, date_action),
  INDEX idx_audit_entite (entite, id_entite),
  INDEX idx_audit_action_date (action, date_action),
  INDEX idx_audit_resultat (resultat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. INTÉGRATION IA EXTERNE (OPENAI API)
-- ============================================================

CREATE TABLE ia_conversations (
  id_conversation BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_utilisateur BIGINT UNSIGNED NOT NULL,
  titre VARCHAR(180) NOT NULL DEFAULT 'Nouvelle conversation',
  statut ENUM('ouverte','archivee') NOT NULL DEFAULT 'ouverte',
  date_creation TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  date_modification TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ia_conversations_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE CASCADE,
  INDEX idx_ia_conversations_user_date (id_utilisateur, date_modification)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ia_appels_api (
  id_appel_api BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_utilisateur BIGINT UNSIGNED NULL,
  fournisseur VARCHAR(40) NOT NULL DEFAULT 'openai',
  modele VARCHAR(120) NOT NULL,
  statut ENUM('en_attente','reussi','echec','limite') NOT NULL DEFAULT 'en_attente',
  jetons_entree INT UNSIGNED NULL,
  jetons_sortie INT UNSIGNED NULL,
  cout_estime DECIMAL(12,8) NULL,
  devise_cout CHAR(3) NOT NULL DEFAULT 'USD',
  duree_ms INT UNSIGNED NULL,
  code_erreur VARCHAR(100) NULL,
  message_erreur VARCHAR(500) NULL,
  date_appel DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  CONSTRAINT fk_ia_appels_utilisateur FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
    ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT chk_ia_cout CHECK (cout_estime IS NULL OR cout_estime >= 0),
  INDEX idx_ia_appels_date_statut (date_appel, statut),
  INDEX idx_ia_appels_user_date (id_utilisateur, date_appel),
  INDEX idx_ia_appels_modele (modele)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ia_messages (
  id_message BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  id_conversation BIGINT UNSIGNED NOT NULL,
  id_appel_api BIGINT UNSIGNED NULL,
  role_message ENUM('utilisateur','assistant','systeme') NOT NULL,
  contenu MEDIUMTEXT NOT NULL,
  date_creation DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  CONSTRAINT fk_ia_messages_conversation FOREIGN KEY (id_conversation) REFERENCES ia_conversations(id_conversation)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ia_messages_appel FOREIGN KEY (id_appel_api) REFERENCES ia_appels_api(id_appel_api)
    ON UPDATE CASCADE ON DELETE SET NULL,
  INDEX idx_ia_messages_conversation_date (id_conversation, date_creation),
  INDEX idx_ia_messages_appel (id_appel_api)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. DONNÉES DE RÉFÉRENCE
-- ============================================================

INSERT INTO roles (nom_role, description) VALUES
('administrateur', 'Accès complet à la configuration et à la gestion'),
('technicien', 'Supervision du réseau, équipements et incidents'),
('caissier', 'Gestion des factures et paiements'),
('superviseur', 'Consultation des rapports et supervision générale');

-- ============================================================
-- FIN DU SCRIPT
-- ============================================================
-- Étape suivante recommandée :
-- 1) Créer le premier administrateur via un script PHP qui utilise
--    password_hash($motDePasse, PASSWORD_DEFAULT).
-- 2) Configurer OPENAI_API_KEY et OPENAI_MODEL dans .env, jamais ici.
-- 3) Vérifier la version MySQL/MariaDB avant déploiement.
-- 4) Mettre en place les transactions et les règles métier dans PHP.
-- ============================================================
