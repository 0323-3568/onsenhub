<?php
require_once '../includes/config.php';
// No direct user authentication is strictly necessary for this endpoint if room availability
// is considered public information. If it's sensitive, add auth checks.

header('Content-Type: application/json');

$roomId = isset($_GET['room_id']) ? intval($_GET['room_id']) : 0;
$response = ['disabledDates' => []];

if ($roomId > 0) {
    try {
        $stmtBlocked = $pdo->prepare("SELECT check_in, check_out FROM reservations WHERE room_id = ? AND status IN ('pending', 'confirmed', 'modification approved', 'modification requested', 'cancellation requested')");
        $stmtBlocked->execute([$roomId]);
        $reservations = $stmtBlocked->fetchAll(PDO::FETCH_ASSOC);

        $datesToDisable = [];
        foreach ($reservations as $res) {
            $currentDate = new DateTime($res['check_in']);
            $checkOutDate = new DateTime($res['check_out']);
            while ($currentDate < $checkOutDate) {
                $datesToDisable[] = $currentDate->format('Y-m-d');
                $currentDate->modify('+1 day');
            }
        }
        $response['disabledDates'] = array_values(array_unique($datesToDisable)); // Ensure unique dates and re-index
    } catch (PDOException $e) {
        error_log("Error fetching room availability for room ID $roomId: " . $e->getMessage());
        // In case of error, send empty disabledDates to prevent incorrect blocking
    }
}

echo json_encode($response);
exit();