<?php
require "config.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: home.php");
    exit;
}

$reservationId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

$sql = "SELECT r.idReservation, r.qteBilletsNormal, r.qteBilletsReduit, 
               e.eventTitle, ed.dateEvent, ed.timeEvent,
               (r.qteBilletsNormal * e.TariffNormal + r.qteBilletsReduit * e.TariffReduit) AS total_paid
        FROM reservation r
        JOIN edition ed ON r.editionId = ed.editionId
        JOIN evenement e ON ed.eventId = e.eventId
        WHERE r.idReservation = :id AND r.idUser = :userId";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $reservationId, ':userId' => $_SESSION['user_id']]);
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$reservation) {
        die("Reservation not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation - FarhaEvents</title>
    <link rel="icon" href="./assets/images/f-logo.avif" type="image/x-icon" />
    <link rel="stylesheet" href="./assets/styles/confirmation.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="main-content">
        <div class="container">
            <h1>Confirmation d'achat</h1>
            <div class="confirmation-box">
                <i class="fas fa-check-circle success-icon"></i>
                <p>Votre achat a été effectué avec succès !</p>
                <div class="reservation-details">
                    <p><strong>Événement :</strong> <?= htmlspecialchars($reservation['eventTitle']) ?></p>
                    <p><strong>Date :</strong> <?= htmlspecialchars($reservation['dateEvent']) ?> à <?= htmlspecialchars($reservation['timeEvent']) ?></p>
                    <p><strong>Billets normaux :</strong> <?= $reservation['qteBilletsNormal'] ?></p>
                    <p><strong>Billets réduits :</strong> <?= $reservation['qteBilletsReduit'] ?></p>
                    <p><strong>Total payé :</strong> <?= number_format($reservation['total_paid'], 2) ?>€</p>
                    <p><strong>Référence :</strong> <?= htmlspecialchars($reservation['idReservation']) ?></p>
                </div>
                <div class="action-buttons">
                    <a href="tickets.php?id=<?= urlencode($reservation['idReservation']) ?>" class="btn">Voir mes billets</a>
                    <a href="invoice.php?id=<?= urlencode($reservation['idReservation']) ?>" class="btn">Voir ma facture</a>
                    <a href="home.php" class="btn secondary">Retour à l'accueil</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>