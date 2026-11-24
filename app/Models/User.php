<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class User
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function hasAnyUsers(): bool
    {
        return (int) $this->connection->query('SELECT COUNT(*) FROM utilisateurs')->fetchColumn() > 0;
    }

    public function authenticate(string $identifier, string $password): ?array
    {
        $statement = $this->connection->prepare(
            "SELECT u.id_utilisateur, u.nom_complet, u.mot_de_passe, r.nom_role
             FROM utilisateurs u
             INNER JOIN roles r ON r.id_role = u.id_role
             WHERE (u.nom_utilisateur = :username OR u.email = :email)
               AND u.statut = 'actif'
             LIMIT 1"
        );
        $statement->execute(['username' => $identifier, 'email' => $identifier]);
        $user = $statement->fetch();

        if (!$user || !password_verify($password, $user['mot_de_passe'])) {
            return null;
        }

        $this->connection->beginTransaction();

        try {
            $update = $this->connection->prepare(
                'UPDATE utilisateurs
                 SET derniere_connexion = CURRENT_TIMESTAMP, derniere_adresse_ip = :ip
                 WHERE id_utilisateur = :id'
            );
            $update->execute([
                'ip' => $this->clientIp(),
                'id' => $user['id_utilisateur'],
            ]);
            $this->writeAudit(
                (int) $user['id_utilisateur'],
                'connexion',
                'utilisateurs',
                (int) $user['id_utilisateur']
            );
            $this->connection->commit();
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }

        unset($user['mot_de_passe']);

        return $user;
    }

    public function createInitialAdministrator(
        string $fullName,
        string $username,
        ?string $email,
        string $password
    ): array {
        $this->connection->beginTransaction();

        try {
            $roleStatement = $this->connection->prepare(
                "SELECT id_role FROM roles WHERE nom_role = 'administrateur' FOR UPDATE"
            );
            $roleStatement->execute();
            $roleId = $roleStatement->fetchColumn();

            if ($roleId === false) {
                throw new \RuntimeException('Le rôle administrateur est absent de la base de données.');
            }

            if ($this->hasAnyUsers()) {
                throw new \DomainException('Un compte existe déjà. La création initiale est désactivée.');
            }

            $statement = $this->connection->prepare(
                "INSERT INTO utilisateurs (id_role, nom_complet, nom_utilisateur, email, mot_de_passe, statut)
                 VALUES (:role_id, :full_name, :username, :email, :password_hash, 'actif')"
            );
            $statement->execute([
                'role_id' => $roleId,
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $userId = (int) $this->connection->lastInsertId();
            $this->writeAudit($userId, 'creation_initiale', 'utilisateurs', $userId);
            $this->connection->commit();

            return [
                'id_utilisateur' => $userId,
                'nom_complet' => $fullName,
                'nom_role' => 'administrateur',
            ];
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    public function recordLogout(int $userId): void
    {
        $this->writeAudit($userId, 'deconnexion', 'utilisateurs', $userId);
    }

    public function createPasswordReset(string $username, string $email, string $tokenHash, string $expiresAt): ?string
    {
        $statement = $this->connection->prepare(
            "SELECT id_utilisateur, email FROM utilisateurs
             WHERE nom_utilisateur = :username AND email = :email AND statut = 'actif'
             LIMIT 1"
        );
        $statement->execute(['username' => $username, 'email' => $email]);
        $user = $statement->fetch();

        if (!$user) {
            return null;
        }

        $this->connection->beginTransaction();

        try {
            $delete = $this->connection->prepare(
                'DELETE FROM reinitialisations_mot_de_passe WHERE id_utilisateur = :user_id'
            );
            $delete->execute(['user_id' => $user['id_utilisateur']]);

            $insert = $this->connection->prepare(
                'INSERT INTO reinitialisations_mot_de_passe (id_utilisateur, jeton_hash, expire_le)
                 VALUES (:user_id, :token_hash, :expires_at)'
            );
            $insert->execute([
                'user_id' => $user['id_utilisateur'],
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
            ]);
            $this->connection->commit();

            return (string) $user['email'];
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    public function deletePasswordReset(string $tokenHash): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM reinitialisations_mot_de_passe WHERE jeton_hash = :token_hash'
        );
        $statement->execute(['token_hash' => $tokenHash]);
    }

    public function isPasswordResetValid(string $tokenHash): bool
    {
        $statement = $this->connection->prepare(
            'SELECT 1 FROM reinitialisations_mot_de_passe
             WHERE jeton_hash = :token_hash AND expire_le > UTC_TIMESTAMP()
             LIMIT 1'
        );
        $statement->execute(['token_hash' => $tokenHash]);

        return $statement->fetchColumn() !== false;
    }

    public function resetPassword(string $tokenHash, string $password): bool
    {
        $this->connection->beginTransaction();

        try {
            $statement = $this->connection->prepare(
                'SELECT id_utilisateur FROM reinitialisations_mot_de_passe
                 WHERE jeton_hash = :token_hash AND expire_le > UTC_TIMESTAMP()
                 FOR UPDATE'
            );
            $statement->execute(['token_hash' => $tokenHash]);
            $userId = $statement->fetchColumn();

            if ($userId === false) {
                $this->connection->rollBack();
                return false;
            }

            $update = $this->connection->prepare(
                'UPDATE utilisateurs SET mot_de_passe = :password_hash WHERE id_utilisateur = :user_id'
            );
            $update->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'user_id' => $userId,
            ]);

            $delete = $this->connection->prepare(
                'DELETE FROM reinitialisations_mot_de_passe WHERE id_utilisateur = :user_id'
            );
            $delete->execute(['user_id' => $userId]);
            $this->writeAudit((int) $userId, 'reinitialisation_mot_de_passe', 'utilisateurs', (int) $userId);
            $this->connection->commit();

            return true;
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    private function writeAudit(int $userId, string $action, string $entity, int $entityId): void
    {
        $statement = $this->connection->prepare(
            'INSERT INTO journaux_audit (id_utilisateur, action, entite, id_entite, adresse_ip_utilisateur, resultat)
             VALUES (:user_id, :action, :entity, :entity_id, :ip, :result)'
        );
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'ip' => $this->clientIp(),
            'result' => 'succes',
        ]);
    }

    private function clientIp(): ?string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;

        return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
    }
}
