<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Session;
use App\Models\User;
use DomainException;

final class AuthController
{
    public function __construct(private readonly User $users)
    {
    }

    public function login(): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        if (Auth::user() !== null) {
            $this->redirect('index.php');
        }

        $error = Session::consumeFlash('error');
        $hasUsers = $this->users->hasAnyUsers();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
                http_response_code(403);
                $error = 'Votre session a expiré. Rechargez la page et réessayez.';
            } else {
                $identifier = trim($this->postString('identifier'));
                $password = $this->postString('password');

                if ($identifier === '' || strlen($identifier) > 190 || $password === '' || strlen($password) > 1024) {
                    $error = 'Saisissez votre identifiant et votre mot de passe.';
                } else {
                    $user = $this->users->authenticate($identifier, $password);

                    if ($user === null) {
                        $error = 'Identifiants incorrects ou compte inactif.';
                    } else {
                        Auth::login($user);
                        $this->redirect('index.php');
                    }
                }
            }
        }

        require __DIR__ . '/../Views/auth/login.php';
    }

    public function setup(): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        if ($this->users->hasAnyUsers()) {
            http_response_code(404);
            require __DIR__ . '/../Views/errors/not-found.php';
            return;
        }

        $error = Session::consumeFlash('error');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
                http_response_code(403);
                $error = 'Votre session a expiré. Rechargez la page et réessayez.';
            } else {
                $fullName = trim($this->postString('full_name'));
                $username = trim($this->postString('username'));
                $emailInput = trim($this->postString('email'));
                $password = $this->postString('password');
                $passwordConfirmation = $this->postString('password_confirmation');
                $email = $emailInput === '' ? null : $emailInput;
                $validFullName = preg_match_all('/./us', $fullName, $fullNameCharacters) !== false;
                $validUsername = preg_match_all('/./us', $username, $usernameCharacters) !== false;

                if (!$validFullName || $fullName === '' || count($fullNameCharacters[0] ?? []) > 150) {
                    $error = 'Saisissez un nom complet (150 caractères maximum).';
                } elseif (!$validUsername || count($usernameCharacters[0] ?? []) < 3 || count($usernameCharacters[0] ?? []) > 80
                    || !preg_match('/^[\pL\pN._-]+$/u', $username)) {
                    $error = 'Le nom d’utilisateur doit contenir entre 3 et 80 caractères (lettres, chiffres, point, tiret ou souligné).';
                } elseif ($email !== null && (strlen($email) > 190 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
                    $error = 'Saisissez une adresse e-mail valide.';
                } elseif (strlen($password) < 12 || strlen($password) > 72) {
                    $error = 'Choisissez un mot de passe d’au moins 12 caractères (72 octets maximum).';
                } elseif (!hash_equals($password, $passwordConfirmation)) {
                    $error = 'Les deux mots de passe ne correspondent pas.';
                } else {
                    try {
                        $user = $this->users->createInitialAdministrator($fullName, $username, $email, $password);
                        Auth::login($user);
                        $this->redirect('index.php');
                    } catch (DomainException $exception) {
                        http_response_code(409);
                        $error = 'Un compte existe déjà. Connectez-vous avec votre compte.';
                    } catch (\PDOException $exception) {
                        if (($exception->errorInfo[1] ?? null) !== 1062) {
                            throw $exception;
                        }

                        $error = 'Ce nom d’utilisateur ou cette adresse e-mail est déjà utilisé.';
                    }
                }
            }
        }

        require __DIR__ . '/../Views/auth/setup.php';
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            return;
        }

        if (!Session::hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(403);
            $error = 'Votre session a expiré. Rechargez la page et réessayez.';
            require __DIR__ . '/../Views/errors/forbidden.php';
            return;
        }

        $user = Auth::user();
        if ($user !== null) {
            $this->users->recordLogout((int) $user['id']);
        }

        Auth::logout();
        Session::start();
        Session::flash('success', 'Vous êtes déconnecté.');
        $this->redirect('index.php?action=login');
    }

    private function redirect(string $location): never
    {
        header('Location: ' . $location, true, 303);
        exit;
    }

    private function postString(string $key): string
    {
        $value = $_POST[$key] ?? '';

        return is_string($value) ? $value : '';
    }
}
