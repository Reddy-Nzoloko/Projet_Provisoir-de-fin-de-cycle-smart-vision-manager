<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\Equipment;
use PDOException;

final class EquipmentController
{
    public function __construct(private readonly Equipment $equipment)
    {
    }

    public function index(): void
    {
        if (Auth::user() === null) {
            header('Location: index.php?action=login', true, 303);
            exit;
        }

        if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        $error = Session::consumeFlash('equipment_error');
        $success = Session::consumeFlash('equipment_success');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
                http_response_code(403);
                $error = 'Votre session a expiré. Rechargez la page et réessayez.';
            } else {
                $action = is_string($_POST['operation'] ?? null) ? $_POST['operation'] : 'create';

                if ($action === 'delete') {
                    try {
                        $this->delete($_POST['id_equipement'] ?? null);
                        $success = 'Équipement supprimé.';
                    } catch (\DomainException $exception) {
                        $error = $exception->getMessage();
                    }
                } else {
                    $errors = $this->validate();

                    if ($errors !== null) {
                        $error = $errors;
                    } else {
                        $data = $this->data();

                        try {
                            if ($action === 'update') {
                                $this->equipment->update((int) ($_POST['id_equipement'] ?? 0), $data);
                                $success = 'Équipement mis à jour.';
                            } else {
                                $this->equipment->create($data);
                                $success = 'Équipement enregistré.';
                            }
                        } catch (PDOException $exception) {
                            $error = ($exception->errorInfo[1] ?? null) === 1062
                                ? 'Ce code équipement est déjà utilisé.'
                                : 'Impossible d’enregistrer cet équipement.';
                        }
                    }
                }
            }
        }

        $equipments = $this->equipment->all();
        $stations = $this->equipment->stations();
        $editing = null;

        if (isset($_GET['edit']) && is_string($_GET['edit']) && ctype_digit($_GET['edit'])) {
            $editing = $this->equipment->find((int) $_GET['edit']);
        }

        require __DIR__ . '/../Views/equipements/index.php';
    }

    private function delete(mixed $id): void
    {
        if (!is_string($id) && !is_int($id)) {
            throw new \DomainException('Identifiant invalide');
        }

        $id = (int) $id;
        if ($id < 1) {
            throw new \DomainException('Identifiant invalide');
        }

        try {
            $this->equipment->delete($id);
        } catch (PDOException $exception) {
            throw new \DomainException('Cet équipement est utilisé ailleurs et ne peut pas être supprimé.');
        }
    }

    private function validate(): ?string
    {
        $name = trim($this->postString('nom_equipement'));
        $code = trim($this->postString('code_equipement'));

        if ($name === '' || strlen($name) > 150) {
            return 'Saisissez le nom de l’équipement (150 caractères maximum).';
        }

        if ($code === '' || strlen($code) > 60) {
            return 'Saisissez un code équipement (60 caractères maximum).';
        }

        $type = trim($this->postString('type_equipement'));
        if ($type === '' || strlen($type) > 100) {
            return 'Saisissez un type d’équipement (100 caractères maximum).';
        }

        $mac = trim($this->postString('adresse_mac'));
        if ($mac !== '' && !preg_match('/^(?:[0-9A-Fa-f]{2}:){5}[0-9A-Fa-f]{2}$/', $mac)) {
            return 'Saisissez une adresse MAC au format AA:BB:CC:DD:EE:FF.';
        }

        $ip = trim($this->postString('adresse_ip_gestion'));
        if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return 'Saisissez une adresse IP valide.';
        }

        $subnetMask = trim($this->postString('masque_sous_reseau'));
        if ($ip !== '' && $subnetMask === '') {
            return 'Saisissez le masque ou le préfixe du sous-réseau.';
        }

        if ($ip === '' && $subnetMask !== '') {
            return 'Saisissez une adresse IP avant son masque de sous-réseau.';
        }

        if ($subnetMask !== '' && !$this->isValidSubnetMask($ip, $subnetMask)) {
            return 'Saisissez un masque compatible avec l’adresse IP (ex. 255.255.255.0 ou /24).';
        }

        return null;
    }

    private function isValidSubnetMask(string $ip, string $mask): bool
    {
        if (preg_match('/^\/?\d{1,3}$/', $mask) === 1) {
            $prefixLength = (int) ltrim($mask, '/');
            $maximumPrefixLength = str_contains($ip, ':') ? 128 : 32;

            return $prefixLength <= $maximumPrefixLength;
        }

        if (str_contains($ip, ':')) {
            return false;
        }

        if (filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        $binaryMask = '';
        foreach (explode('.', $mask) as $octet) {
            $binaryMask .= str_pad(decbin((int) $octet), 8, '0', STR_PAD_LEFT);
        }

        return preg_match('/^1*0*$/', $binaryMask) === 1;
    }

    private function data(): array
    {
        $stationId = $this->postString('id_station');

        return [
            'id_station' => $stationId === '' ? null : (int) $stationId,
            'nom_equipement' => trim($this->postString('nom_equipement')),
            'code_equipement' => trim($this->postString('code_equipement')),
            'type_equipement' => trim($this->postString('type_equipement')),
            'fabricant' => $this->nullable('fabricant'),
            'modele' => $this->nullable('modele'),
            'numero_serie' => $this->nullable('numero_serie'),
            'adresse_ip_gestion' => $this->nullable('adresse_ip_gestion'),
            'masque_sous_reseau' => $this->nullable('masque_sous_reseau'),
            'adresse_mac' => $this->nullable('adresse_mac'),
            'role_equipement' => $this->postString('role_equipement'),
            'statut' => $this->postString('statut'),
            'date_installation' => $this->nullable('date_installation'),
            'notes' => $this->nullable('notes'),
        ];
    }

    private function nullable(string $key): ?string
    {
        $value = trim($this->postString($key));

        return $value === '' ? null : $value;
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
