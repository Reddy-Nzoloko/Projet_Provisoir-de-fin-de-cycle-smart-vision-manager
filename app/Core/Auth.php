<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function user(): ?array
    {
        $user = $_SESSION['user'] ?? null;

        return is_array($user) ? $user : null;
    }

    public static function login(array $user): void
    {
        if (!session_regenerate_id(true)) {
            throw new \RuntimeException('Unable to renew the authenticated session.');
        }

        $_SESSION['user'] = [
            'id' => (int) $user['id_utilisateur'],
            'name' => (string) $user['nom_complet'],
            'role' => (string) $user['nom_role'],
        ];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        if (!session_destroy()) {
            throw new \RuntimeException('Unable to destroy the authenticated session.');
        }
    }
}
