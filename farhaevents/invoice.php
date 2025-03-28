<?php
require "config.php";

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: login.php");
    exit;
}

$reservationId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);

// 
$sql = "SELECT r.idReservation, r.qteBilletsNormal, r.qteBilletsReduit, 
               e.eventTitle, ed.dateEvent, ed.timeEvent, ed.NumSalle,
               e.TariffNormal, e.TariffReduit,
               u.nomUser, u.prenomUser, u.mailUser,
               (r.qteBilletsNormal * e.TariffNormal + r.qteBilletsReduit * e.TariffReduit) AS total_paid
        FROM reservation r
        JOIN edition ed ON r.editionId = ed.editionId
        JOIN evenement e ON ed.eventId = e.eventId
        JOIN utilisateur u ON r.idUser = u.idUser
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

// 
$dateTime = new DateTime($reservation['dateEvent'] . ' ' . $reservation['timeEvent']);
$formattedDateTime = $dateTime->format('d/m/Y à H\H\00');
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - FarhaEvents</title>
    <link rel="icon" href="./assets/images/f-logo.avif" type="image/x-icon" />
    <link rel="stylesheet" href="./assets/styles/invoice.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="association-info">
                <h1>ASSOCIATION FARHA</h1>
                <p>CENTRE CULTUREL FARHA, TANGER</p>
            </div>
            <div class="client-info">
                <p><strong>Client :</strong> <?= htmlspecialchars($reservation['prenomUser'] . ' ' . $reservation['nomUser']) ?></p>
                <p><strong>Adresse email :</strong> <?= htmlspecialchars($reservation['mailUser']) ?></p>
            </div>
        </div>

        <div class="event-info">
            <p><?= htmlspecialchars($reservation['eventTitle']) ?></p>
            <p><?= htmlspecialchars($formattedDateTime) ?></p>
        </div>

        <h2>FACTURE #<?= htmlspecialchars($reservation['idReservation']) ?></h2>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Tarif</th>
                    <th>Prix</th>
                    <th>Qté</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($reservation['qteBilletsNormal'] > 0): ?>
                    <tr>
                        <td>Normal</td>
                        <td><?= number_format($reservation['TariffNormal'], 2) ?></td>
                        <td><?= htmlspecialchars($reservation['qteBilletsNormal']) ?></td>
                        <td><?= number_format($reservation['qteBilletsNormal'] * $reservation['TariffNormal'], 2) ?> MAD</td>
                    </tr>
                <?php endif; ?>
                <?php if ($reservation['qteBilletsReduit'] > 0): ?>
                    <tr>
                        <td>Réduit</td>
                        <td><?= number_format($reservation['TariffReduit'], 2) ?></td>
                        <td><?= htmlspecialchars($reservation['qteBilletsReduit']) ?></td>
                        <td><?= number_format($reservation['qteBilletsReduit'] * $reservation['TariffReduit'], 2) ?> MAD</td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td></td>
                    <td></td>
                    <td><?= htmlspecialchars($reservation['qteBilletsNormal'] + $reservation['qteBilletsReduit']) ?></td>
                    <td><?= number_format($reservation['total_paid'], 2) ?> MAD</td>
                </tr>
            </tbody>
        </table>

        <div class="total">
            <p><strong>Total à payer :</strong> <?= number_format($reservation['total_paid'], 2) ?> MAD</p>
        </div>

        <div class="footer">
            <p>MERCI !</p>
        </div>
    </div>

    <div class="download-btn" style="text-align: center; margin-top: 20px;">
        <button id="downloadPdf"><i class="fa-solid fa-file-arrow-down"></i>&nbsp; Télécharger la facture en PDF</button>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        document.getElementById('downloadPdf').addEventListener('click', function() {
            const invoice = document.querySelector('.invoice-container');

            html2canvas(invoice, {
                scale: 2
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jspdf.jsPDF('p', 'mm', 'a4');

                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();

                // Calculate image size to fit A4
                const imgWidth = pageWidth;
                const imgHeight = canvas.height * imgWidth / canvas.width;

                pdf.addImage(imgData, 'PNG', 0, 0, imgWidth, imgHeight);
                pdf.save('Facture_FarhaEvents.pdf');
            });
        });
    </script>



</body>

</html>