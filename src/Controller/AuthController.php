<?php

namespace App\Controller;

use App\Model\User;

class AuthController extends Controller
{
    // ── Login ────────────────────────────────────────────────────────────────

    public function loginForm(): void
    {
        if ($this->userId()) {
            $this->redirect('/');
        }
        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();
        $this->render('auth/login', compact('flash', 'csrf'));
    }

    public function login(): void
    {
        $this->validateCsrf();

        $username = $this->input('username');
        $password = $this->input('password');

        $user = User::findByUsername($username);
        if (!$user || !User::verifyPassword($user, $password)) {
            $this->flash('danger', 'Identifiants incorrects.');
            $this->redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (bool)$user['is_admin'];

        $this->redirect('/');
    }

    // ── Register ─────────────────────────────────────────────────────────────

    public function registerForm(): void
    {
        if ($this->userId()) {
            $this->redirect('/');
        }
        $flash = $this->getFlash();
        $csrf  = $this->csrfToken();
        $this->render('auth/register', compact('flash', 'csrf'));
    }

    public function register(): void
    {
        $this->validateCsrf();

        $username  = $this->input('username');
        $email     = $this->input('email');
        $password  = $this->input('password');
        $password2 = $this->input('password_confirm');

        // Validation
        if (strlen($username) < 3 || strlen($username) > 100) {
            $this->flash('danger', 'Le nom d\'utilisateur doit avoir entre 3 et 100 caractères.');
            $this->redirect('/register');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('danger', 'Adresse e-mail invalide.');
            $this->redirect('/register');
        }
        if (strlen($password) < 8) {
            $this->flash('danger', 'Le mot de passe doit avoir au moins 8 caractères.');
            $this->redirect('/register');
        }
        if ($password !== $password2) {
            $this->flash('danger', 'Les mots de passe ne correspondent pas.');
            $this->redirect('/register');
        }
        if (User::findByUsername($username)) {
            $this->flash('danger', 'Ce nom d\'utilisateur est déjà pris.');
            $this->redirect('/register');
        }
        if (User::findByEmail($email)) {
            $this->flash('danger', 'Cette adresse e-mail est déjà utilisée.');
            $this->redirect('/register');
        }

        // First user ever becomes admin
        $isAdmin = !User::existsAny();

        $userId = User::create($username, $email, $password, $isAdmin);

        session_regenerate_id(true);
        $_SESSION['user_id']  = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = $isAdmin;

        $this->flash('success', 'Bienvenue ' . htmlspecialchars($username) . ' !');
        $this->redirect('/');
    }

    // ── Logout ───────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $this->validateCsrf();
        session_destroy();
        $this->redirect('/login');
    }
}
