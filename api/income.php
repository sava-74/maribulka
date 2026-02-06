<?php
/**
 * API для работы с приходом (платежами)
 *
 * GET /api/income.php - получить все платежи
 * GET /api/income.php?month=2026-02 - платежи за месяц
 * GET /api/income.php?booking_id=1 - платежи по записи
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

require_once 'database.php';

$db = Database::getInstance()->getConnection();

try {
    if (isset($_GET['booking_id'])) {
        // Платежи по конкретной записи
        $stmt = $db->prepare("
            SELECT
                i.*,
                c.name as client_name,
                c.phone,
                b.shooting_date,
                st.name as shooting_type_name
            FROM income i
            LEFT JOIN clients c ON i.client_id = c.id
            LEFT JOIN bookings b ON i.booking_id = b.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            WHERE i.booking_id = ?
            ORDER BY i.date DESC
        ");
        $stmt->execute([$_GET['booking_id']]);
        $result = $stmt->fetchAll();
        echo json_encode($result);
    } elseif (isset($_GET['month'])) {
        // Платежи за месяц
        $stmt = $db->prepare("
            SELECT
                i.*,
                c.name as client_name,
                c.phone,
                st.name as shooting_type_name,
                b.quantity
            FROM income i
            LEFT JOIN clients c ON i.client_id = c.id
            LEFT JOIN bookings b ON i.booking_id = b.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            WHERE DATE_FORMAT(i.date, '%Y-%m') = ?
            ORDER BY i.date DESC
        ");
        $stmt->execute([$_GET['month']]);
        $result = $stmt->fetchAll();
        echo json_encode($result);
    } else {
        // Все платежи (последние 100)
        $stmt = $db->query("
            SELECT
                i.*,
                c.name as client_name,
                c.phone,
                st.name as shooting_type_name
            FROM income i
            LEFT JOIN clients c ON i.client_id = c.id
            LEFT JOIN bookings b ON i.booking_id = b.id
            LEFT JOIN shooting_types st ON b.shooting_type_id = st.id
            ORDER BY i.date DESC, i.created_at DESC
            LIMIT 100
        ");
        $result = $stmt->fetchAll();
        echo json_encode($result);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
}
?>
