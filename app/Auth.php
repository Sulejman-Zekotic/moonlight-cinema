<?php

declare(strict_types=1);

final class Auth
{
    public function __construct(private Database $db)
    {
    }

    public function userId(): ?int
    {
        $userId = $_SESSION['user_id'] ?? null;

        if ($userId) {
            return (int) $userId;
        }

        $cookieUserId = $this->userIdFromAuthCookie();
        if ($cookieUserId !== null) {
            $_SESSION['user_id'] = $cookieUserId;
            return $cookieUserId;
        }

        return null;
    }

    public function user(): ?array
    {
        $userId = $this->userId();

        if (!$userId) {
            return null;
        }

        return $this->db->fetch('SELECT id, full_name, email, role FROM users WHERE id = :id', [
            'id' => $userId,
        ]);
    }

    public function check(): bool
    {
        return $this->userId() !== null;
    }

    public function isAdmin(): bool
    {
        return (($this->user()['role'] ?? null) === 'admin');
    }

    public function login(string $email, string $password): array
    {
        $user = $this->db->fetch('SELECT * FROM users WHERE email = :email LIMIT 1', [
            'email' => $this->normalizeEmail($email),
        ]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Pogrešan email ili lozinka.'];
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_role'] = (string) $user['role'];
        $_SESSION['user_name'] = (string) $user['full_name'];

        $this->rememberAuthCookie((int) $user['id']);
        session_write_close();

        return [
            'success' => true,
            'message' => 'Prijava uspješna.',
            'role' => $user['role'],
            'name' => $user['full_name'],
        ];
    }

    public function register(string $fullName, string $email, string $password): array
    {
        $fullName = trim($fullName);
        $email = $this->normalizeEmail($email);

        if (mb_strlen($fullName) < 2) {
            return ['success' => false, 'message' => 'Ime mora imati najmanje 2 slova.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Neispravan email.'];
        }

        if (!$this->isStrongPassword($password)) {
            return ['success' => false, 'message' => 'Lozinka mora imati malo i veliko slovo, broj, specijalni znak i najmanje 8 karaktera.'];
        }

        $exists = $this->db->fetchColumn('SELECT COUNT(*) FROM users WHERE email = :email', ['email' => $email]);
        if ((int) $exists > 0) {
            return ['success' => false, 'message' => 'Email je već registrovan.'];
        }

        $this->db->execute(
            'INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (:full_name, :email, :password_hash, :role, :created_at)',
            [
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user',
                'created_at' => $this->now(),
            ]
        );

        return ['success' => true, 'message' => 'Registracija uspješna.'];
    }

    public function requestPasswordReset(string $email, Mailer $mailer): array
    {
        $email = $this->normalizeEmail($email);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Unesite ispravan email.'];
        }

        $user = $this->db->fetch('SELECT id, full_name, email FROM users WHERE email = :email LIMIT 1', [
            'email' => $email,
        ]);

        if (!$user) {
            return [
                'success' => true,
                'message' => 'Ako nalog postoji, link za reset lozinke je poslan na email adresu.',
            ];
        }

        $this->cleanupResetTokens((int) $user['id']);

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+60 minutes'));

        $this->db->execute(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, used_at, created_at)
             VALUES (:user_id, :token_hash, :expires_at, :used_at, :created_at)',
            [
                'user_id' => (int) $user['id'],
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'used_at' => null,
                'created_at' => $this->now(),
            ]
        );

        $resetLink = current_full_url('reset-lozinke', ['token' => $token]);
        $mailResult = $mailer->sendPasswordResetLink([
            'to' => $user['email'],
            'name' => $user['full_name'],
            'reset_url' => $resetLink,
            'expires_at' => $expiresAt,
        ]);

        if (!($mailResult['sent'] ?? false)) {
            return [
                'success' => false,
                'message' => 'Reset link nije poslan. Provjerite mail postavke i pokušajte ponovo.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Link za reset lozinke je poslan na email adresu.',
        ];
    }

    public function resetPassword(string $token, string $password): array
    {
        $token = trim($token);

        if ($token === '') {
            return ['success' => false, 'message' => 'Reset link nije ispravan.'];
        }

        if (!$this->isStrongPassword($password)) {
            return ['success' => false, 'message' => 'Lozinka mora imati malo i veliko slovo, broj, specijalni znak i najmanje 8 karaktera.'];
        }

        $record = $this->validResetRecord($token);
        if (!$record) {
            return ['success' => false, 'message' => 'Reset link je istekao ili više nije važeći.'];
        }

        $userId = (int) $record['user_id'];

        $this->db->begin();

        try {
            $this->db->execute(
                'UPDATE users SET password_hash = :password_hash WHERE id = :id',
                [
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => $userId,
                ]
            );

            $this->db->execute(
                'UPDATE password_resets SET used_at = :used_at WHERE id = :id',
                [
                    'used_at' => $this->now(),
                    'id' => (int) $record['id'],
                ]
            );

            $this->db->execute(
                'DELETE FROM password_resets WHERE user_id = :user_id AND id != :id',
                [
                    'user_id' => $userId,
                    'id' => (int) $record['id'],
                ]
            );

            $this->db->commit();
        } catch (Throwable $throwable) {
            $this->db->rollBack();
            throw $throwable;
        }

        return ['success' => true, 'message' => 'Lozinka je uspješno promijenjena.'];
    }

    public function resetTokenState(string $token): array
    {
        $token = trim($token);
        if ($token === '') {
            return ['valid' => false, 'message' => 'Nedostaje reset token.'];
        }

        $record = $this->validResetRecord($token);
        if (!$record) {
            return ['valid' => false, 'message' => 'Reset link je istekao ili više nije važeći.'];
        }

        return ['valid' => true, 'message' => null];
    }

    public function logout(): void
    {
        $_SESSION = [];
        $this->clearAuthCookie();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?: '/',
                $params['domain'] ?? '',
                (bool) ($params['secure'] ?? false),
                (bool) ($params['httponly'] ?? true)
            );
        }

        session_unset();
        session_destroy();
    }

    private function rememberAuthCookie(int $userId): void
    {
        $value = $userId . '|' . $this->authCookieSignature($userId);

        setcookie('moonlight_auth', $value, [
            'expires' => time() + 60 * 60 * 24 * 14,
            'path' => '/',
            'secure' => $this->isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function clearAuthCookie(): void
    {
        setcookie('moonlight_auth', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $this->isHttpsRequest(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function userIdFromAuthCookie(): ?int
    {
        $cookie = (string) ($_COOKIE['moonlight_auth'] ?? '');
        if ($cookie === '' || !str_contains($cookie, '|')) {
            return null;
        }

        [$rawUserId, $signature] = explode('|', $cookie, 2);
        $userId = (int) $rawUserId;

        if ($userId <= 0) {
            return null;
        }

        if (!hash_equals($this->authCookieSignature($userId), $signature)) {
            $this->clearAuthCookie();
            return null;
        }

        return $userId;
    }

    private function authCookieSignature(int $userId): string
    {
        $secret = getenv('MC_APP_KEY') ?: 'moonlight-cinema-local-secret-change-later';
        return hash_hmac('sha256', (string) $userId, $secret);
    }

    private function isHttpsRequest(): bool
    {
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));

        return str_contains($forwardedProto, 'https')
            || !empty($_SERVER['HTTP_X_ARR_SSL'])
            || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443');
    }

    private function validResetRecord(string $token): ?array
    {
        $this->cleanupResetTokens();

        return $this->db->fetch(
            'SELECT id, user_id, expires_at
             FROM password_resets
             WHERE token_hash = :token_hash
               AND used_at IS NULL
               AND expires_at >= :now
             LIMIT 1',
            [
                'token_hash' => hash('sha256', $token),
                'now' => $this->now(),
            ]
        );
    }

    private function cleanupResetTokens(?int $userId = null): void
    {
        $sql = 'DELETE FROM password_resets WHERE (used_at IS NOT NULL OR expires_at < :now)';
        $params = ['now' => $this->now()];

        if ($userId !== null) {
            $sql .= ' OR user_id = :user_id';
            $params['user_id'] = $userId;
        }

        $this->db->execute($sql, $params);
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[a-z]/', $password)
            && preg_match('/[A-Z]/', $password)
            && preg_match('/\d/', $password)
            && preg_match('/[!@#$%^&*]/', $password)
            && !preg_match('/\s/', $password);
    }
}
