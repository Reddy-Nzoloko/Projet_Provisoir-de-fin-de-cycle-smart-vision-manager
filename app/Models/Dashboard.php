<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use PDO;

final class Dashboard
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function summary(): array
    {
        $clients = $this->connection->query(
            "SELECT COUNT(*) AS total, SUM(statut = 'actif') AS actifs FROM clients"
        )->fetch();

        $equipment = $this->connection->query(
            "SELECT COUNT(*) AS total,
                    SUM(statut = 'actif') AS actifs,
                    SUM(statut = 'hors_ligne') AS hors_ligne
             FROM equipements"
        )->fetch();

        $incidents = $this->connection->query(
            "SELECT COUNT(*) AS ouverts,
                    SUM(severite IN ('elevee', 'critique') AND statut IN ('ouvert', 'en_cours')) AS urgents
             FROM incidents
             WHERE statut IN ('ouvert', 'en_cours')"
        )->fetch();

        $monthStart = new DateTimeImmutable('first day of this month');
        $nextMonthStart = $monthStart->modify('first day of next month');
        $revenueStatement = $this->connection->prepare(
            "SELECT COALESCE(SUM(montant), 0) AS total
             FROM paiements
             WHERE statut = 'confirme'
               AND date_paiement >= :month_start
               AND date_paiement < :next_month_start"
        );
        $revenueStatement->execute([
            'month_start' => $monthStart->format('Y-m-d H:i:s'),
            'next_month_start' => $nextMonthStart->format('Y-m-d H:i:s'),
        ]);

        return [
            'clients' => $clients,
            'equipment' => $equipment,
            'incidents' => $incidents,
            'revenue_month' => $revenueStatement->fetchColumn(),
            'currency' => 'USD',
        ];
    }

    public function paymentChart(): array
    {
        $firstMonth = new DateTimeImmutable('first day of -5 months');
        $monthNames = [
            1 => 'Jan', 2 => 'Fév', 3 => 'Mar', 4 => 'Avr',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
        ];
        $statement = $this->connection->prepare(
            "SELECT DATE_FORMAT(date_paiement, '%Y-%m') AS month_key,
                    SUM(montant) AS total
             FROM paiements
             WHERE statut = 'confirme'
               AND date_paiement >= :start_date
               AND date_paiement < :end_date
             GROUP BY month_key
             ORDER BY month_key"
        );
        $statement->execute([
            'start_date' => $firstMonth->format('Y-m-d 00:00:00'),
            'end_date' => (new DateTimeImmutable('first day of next month'))->format('Y-m-d 00:00:00'),
        ]);

        $payments = [];
        foreach ($statement->fetchAll() as $row) {
            $payments[$row['month_key']] = (float) $row['total'];
        }

        $labels = [];
        $values = [];
        for ($offset = 0; $offset < 6; $offset++) {
            $month = $firstMonth->modify("+{$offset} months");
            $key = $month->format('Y-m');
            $labels[] = $monthNames[(int) $month->format('n')] . ' ' . $month->format('Y');
            $values[] = $payments[$key] ?? 0;
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function trafficChart(): array
    {
        $today = new DateTimeImmutable('today');
        $firstDay = $today->modify('-6 days');
        $statement = $this->connection->prepare(
            "SELECT date_consommation AS day_key,
                    SUM(octets_recus + octets_envoyes) AS bytes_total
             FROM consommations_journalieres
             WHERE date_consommation >= :start_date
               AND date_consommation <= :end_date
             GROUP BY day_key
             ORDER BY day_key"
        );
        $statement->execute([
            'start_date' => $firstDay->format('Y-m-d'),
            'end_date' => $today->format('Y-m-d'),
        ]);

        $traffic = [];
        foreach ($statement->fetchAll() as $row) {
            $traffic[$row['day_key']] = (float) $row['bytes_total'] / 1073741824;
        }

        $labels = [];
        $values = [];
        $dayNames = [1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam', 7 => 'Dim'];
        for ($offset = 0; $offset < 7; $offset++) {
            $day = $firstDay->modify("+{$offset} days");
            $key = $day->format('Y-m-d');
            $labels[] = $dayNames[(int) $day->format('N')] . ' ' . $day->format('d');
            $values[] = round($traffic[$key] ?? 0, 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    public function recentIncidents(): array
    {
        $statement = $this->connection->query(
            "SELECT i.titre, i.severite, i.statut, i.date_detection,
                    COALESCE(s.nom_station, e.nom_equipement, 'Réseau') AS cible
             FROM incidents i
             LEFT JOIN stations s ON s.id_station = i.id_station
             LEFT JOIN equipements e ON e.id_equipement = i.id_equipement
             ORDER BY i.date_detection DESC
             LIMIT 5"
        );

        return $statement->fetchAll();
    }
}
