<?php
// includes/auth.php
// Authentication, session, CSRF, and authorization helpers.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' .
            htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token = null): bool
    {
        $token = $token ?? ($_POST['csrf_token'] ?? '');
        $sessionToken = $_SESSION['_csrf_token'] ?? '';

        return is_string($token)
            && is_string($sessionToken)
            && $token !== ''
            && $sessionToken !== ''
            && hash_equals($sessionToken, $token);
    }
}

if (!function_exists('abort_csrf')) {
    function abort_csrf(): void
    {
        http_response_code(419);
        exit('Invalid or expired form token. Please go back and try again.');
    }
}

if (!function_exists('login_user')) {
    function login_user(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        // Rotate the CSRF token whenever the authentication state changes.
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool
    {
        return !empty($_SESSION['logged_in']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('current_user_id')) {
    function current_user_id(): ?int
    {
        return is_logged_in() ? (int) $_SESSION['user_id'] : null;
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array
    {
        if (!is_logged_in()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'student',
        ];
    }
}

if (!function_exists('require_login')) {
    function require_login(string $loginUrl = '/login.php'): void
    {
        if (!is_logged_in()) {
            $redirect = $_SERVER['REQUEST_URI'] ?? '/index.php';
            header('Location: ' . $loginUrl . '?redirect=' . rawurlencode($redirect));
            exit;
        }
    }
}

if (!function_exists('require_guest')) {
    function require_guest(string $homeUrl = '/index.php'): void
    {
        if (is_logged_in()) {
            header('Location: ' . $homeUrl);
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    function require_role(string|array $roles, string $forbiddenUrl = '/index.php'): void
    {
        require_login();

        $roles = (array) $roles;
        $currentRole = $_SESSION['user_role'] ?? null;

        if (!in_array($currentRole, $roles, true)) {
            http_response_code(403);
            exit('403 Forbidden');
        }
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
}
