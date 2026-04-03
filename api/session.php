<?php
// Общие параметры сессии для всех API
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                   || ($_SERVER['SERVER_PORT'] ?? 80) == 443;

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => $isHttps ? 'None' : 'Lax',
        ]);
        session_start();
    }
}

/**
 * Инициализирует сессию и проверяет роль пользователя.
 * При провале — выводит JSON-ошибку и завершает выполнение.
 *
 * @param int[] $allowedRoles  Разрешённые role id. Пустой массив = только проверка аутентификации.
 * @return int  Числовой role id текущего пользователя.
 */
function requireRole(int ...$allowedRoles): int {
    initSession();

    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $role = (int)($_SESSION['user']['role'] ?? 0);

    if (!empty($allowedRoles) && !in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    return $role;
}
