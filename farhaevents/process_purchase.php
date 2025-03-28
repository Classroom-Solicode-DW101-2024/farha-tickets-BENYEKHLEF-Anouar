<?php
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request method.");
}

$editionId = filter_var($_POST['editionId'] ?? '', FILTER_SANITIZE_NUMBER_INT);
$normalQty = (int)($_POST['normalQuantity'] ?? 0);
$reducedQty = (int)($_POST['reducedQuantity'] ?? 0);

if ($normalQty < 0 || $reducedQty < 0 || ($normalQty + $reducedQty) == 0) {
    die("Invalid ticket quantity.");
}

//
$sql = "SELECT ed.editionId, e.TariffNormal, e.TariffReduit, s.capSalle,
               IFNULL(SUM(r.qteBilletsNormal + r.qteBilletsReduit), 0) as total_billets,
               (SELECT IFNULL(MAX(b.placeNum), 0) FROM billet b 
                JOIN reservation res ON b.idReservation = res.idReservation 
                WHERE res.editionId = ed.editionId) as highest_seat
        FROM edition ed
        JOIN evenement e ON ed.eventId = e.eventId
        JOIN salle s ON ed.NumSalle = s.NumSalle
        LEFT JOIN reservation r ON ed.editionId = r.editionId
        WHERE ed.editionId = :editionId
        GROUP BY ed.editionId";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':editionId' => $editionId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        die("Event not found.");
    }

    $available_tickets = $event['capSalle'] - $event['total_billets'];
    $totalQty = $normalQty + $reducedQty;

    if ($totalQty > $available_tickets) {
        die("Not enough tickets available.");
    }

    // Check if the next seats would exceed capacity
    $highest_seat = (int)$event['highest_seat']; 
    $next_seat = $highest_seat + 1;
    $last_seat_needed = $highest_seat + $totalQty;

    if ($last_seat_needed > $event['capSalle']) {
        die("Not enough sequential seats available within room capacity.");
    }

    $pdo->beginTransaction();

    // 
    $stmt = $pdo->prepare("INSERT INTO reservation (qteBilletsNormal, qteBilletsReduit, editionId, idUser)
                           VALUES (:normal, :reduced, :editionId, :userId)");
    $stmt->execute([
        ':normal' => $normalQty,
        ':reduced' => $reducedQty,
        ':editionId' => $editionId,
        ':userId' => $_SESSION['user_id']
    ]);
    $reservationId = $pdo->lastInsertId();

    // 
    $current_seat = $next_seat;

    // Insert normal tickets
    for ($i = 0; $i < $normalQty; $i++) {
        $seatNum = $current_seat++;
        $billetId = "BLT" . $reservationId . "N" . $i;
        $stmt = $pdo->prepare("INSERT INTO billet (billetId, typeBillet, placeNum, idReservation) 
                               VALUES (:billetId, 'Normal', :placeNum, :reservationId)");
        $stmt->execute([
            ':billetId' => $billetId,
            ':placeNum' => $seatNum,
            ':reservationId' => $reservationId
        ]);
    }

    // Insert reduced tickets
    for ($i = 0; $i < $reducedQty; $i++) {
        $seatNum = $current_seat++;
        $billetId = "BLT" . $reservationId . "R" . $i;
        $stmt = $pdo->prepare("INSERT INTO billet (billetId, typeBillet, placeNum, idReservation) 
                               VALUES (:billetId, 'Reduit', :placeNum, :reservationId)");
        $stmt->execute([
            ':billetId' => $billetId,
            ':placeNum' => $seatNum,
            ':reservationId' => $reservationId
        ]);
    }

    $pdo->commit();
    header("Location: confirmation.php?id=" . urlencode($reservationId));
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    die("Purchase failed: " . $e->getMessage());
}
?>