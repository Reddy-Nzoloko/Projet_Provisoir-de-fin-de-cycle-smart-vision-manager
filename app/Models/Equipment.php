<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Equipment
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function all(): array
    {
        $statement = $this->connection->query(
            'SELECT e.*, s.nom_station
             FROM equipements e
             LEFT JOIN stations s ON s.id_station = e.id_station
             ORDER BY e.date_creation DESC'
        );

        return $statement->fetchAll();
    }

    public function stations(): array
    {
        return $this->connection->query(
            'SELECT id_station, nom_station FROM stations ORDER BY nom_station'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT * FROM equipements WHERE id_equipement = :id');
        $statement->execute(['id' => $id]);
        $equipment = $statement->fetch();

        return $equipment ?: null;
    }

    public function create(array $data): void
    {
        $statement = $this->connection->prepare(
            "INSERT INTO equipements
             (id_station, nom_equipement, code_equipement, type_equipement, fabricant, modele,
              numero_serie, adresse_ip_gestion, masque_sous_reseau, adresse_mac, role_equipement, statut, date_installation, notes)
             VALUES
             (:id_station, :nom_equipement, :code_equipement, :type_equipement, :fabricant, :modele,
              :numero_serie, :adresse_ip_gestion, :masque_sous_reseau, :adresse_mac, :role_equipement, :statut, :date_installation, :notes)"
        );
        $statement->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $statement = $this->connection->prepare(
            'UPDATE equipements SET
             id_station = :id_station,
             nom_equipement = :nom_equipement,
             code_equipement = :code_equipement,
             type_equipement = :type_equipement,
             fabricant = :fabricant,
             modele = :modele,
             numero_serie = :numero_serie,
             adresse_ip_gestion = :adresse_ip_gestion,
             masque_sous_reseau = :masque_sous_reseau,
             adresse_mac = :adresse_mac,
             role_equipement = :role_equipement,
             statut = :statut,
             date_installation = :date_installation,
             notes = :notes
             WHERE id_equipement = :id'
        );
        $statement->execute($data + ['id' => $id]);
    }

    public function delete(int $id): void
    {
        $statement = $this->connection->prepare('DELETE FROM equipements WHERE id_equipement = :id');
        $statement->execute(['id' => $id]);
    }
}
