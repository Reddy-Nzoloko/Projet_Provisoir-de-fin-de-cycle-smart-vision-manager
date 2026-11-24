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

    public function requestPasswordReset(): void
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
        $success = Session::consumeFlash('success');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
                http_response_code(403);
                $error = 'Votre session a expiré. Rechargez la page et réessayez.';
            } else {
                $username = trim($this->postString('username'));
                $email = trim($this->postString('email'));

                if ($username === '' || strlen($username) > 80 || strlen($email) > 190
                    || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                    $error = 'Saisissez un nom d’utilisateur et une adresse e-mail valides.';
                } else {
                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $accountEmail = $this->users->createPasswordReset(
                        $username,
                        $email,
                        $tokenHash,
                        gmdate('Y-m-d H:i:s', time() + 3600)
                    );

                    if ($accountEmail !== null && !$this->sendPasswordResetEmail($accountEmail, $token)) {
                        $this->users->deletePasswordReset($tokenHash);
                        error_log('Smart Vision Manager: échec de l’envoi du courriel de réinitialisation.');
                    }

                    Session::flash(
                        'success',
                        'Si les informations correspondent à un compte actif, un lien de réinitialisation sera envoyé à son adresse e-mail.'
                    );
                    $this->redirect('index.php?action=forgot-password');
                }
            }
        }

        require __DIR__ . '/../Views/auth/forgot-password.php';
    }

    public function resetPassword(): void
    {
        if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
            http_response_code(405);
            header('Allow: GET, POST');
            return;
        }

        $error = Session::consumeFlash('error');
        $token = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $this->postString('token')
            : (is_string($_GET['token'] ?? null) ? $_GET['token'] : '');
        $tokenHash = preg_match('/^[a-f0-9]{64}$/', $token) === 1 ? hash('sha256', $token) : '';
        $tokenValid = $tokenHash !== '' && $this->users->isPasswordResetValid($tokenHash);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Session::hasValidCsrfToken($_POST['csrf_token'] ?? null)) {
                http_response_code(403);
                $error = 'Votre session a expiré. Rechargez la page et réessayez.';
            } elseif (!$tokenValid) {
                $error = 'Ce lien est invalide ou a expiré. Demandez un nouveau lien.';
            } else {
                $password = $this->postString('password');
                $confirmation = $this->postString('password_confirmation');

                if (strlen($password) < 12 || strlen($password) > 72) {
                    $error = 'Choisissez un mot de passe d’au moins 12 caractères (72 octets maximum).';
                } elseif (!hash_equals($password, $confirmation)) {
                    $error = 'Les deux mots de passe ne correspondent pas.';
                } elseif ($this->users->resetPassword($tokenHash, $password)) {
                    Session::flash('success', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
                    $this->redirect('index.php?action=login');
                } else {
                    $tokenValid = false;
                    $error = 'Ce lien est invalide ou a expiré. Demandez un nouveau lien.';
                }
            }
        }

        require __DIR__ . '/../Views/auth/reset-password.php';
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

    private function sendPasswordResetEmail(string $email, string $token): bool
    {
        $baseUrl = getenv('APP_BASE_URL');
        $sender = getenv('MAIL_FROM');
        $parts = is_string($baseUrl) ? parse_url($baseUrl) : false;

        if (!is_string($baseUrl) || !is_array($parts)
            || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || empty($parts['host']) || isset($parts['query']) || isset($parts['fragment'])
            || !is_string($sender) || filter_var($sender, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $url = rtrim($baseUrl, '/') . '/index.php?action=reset-password&token=' . rawurlencode($token);
        $subject = 'Réinitialisation de votre mot de passe';
        $message = "Bonjour,\n\n"
            . "Une demande de réinitialisation du mot de passe de votre compte Smart Vision Manager a été faite.\n"
            . "Pour choisir un nouveau mot de passe dans l’heure, ouvrez ce lien :\n{$url}\n\n"
            . "Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.\n";
        $headers = "From: {$sender}\r\nContent-Type: text/plain; charset=UTF-8";

        return mail($email, $subject, $message, $headers);
    }
}
