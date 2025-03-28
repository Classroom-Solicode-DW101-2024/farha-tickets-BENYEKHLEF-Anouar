<?php
require "config.php";

if (!isset($_GET['editionId'])) {
    die("Edition ID is missing.");
}

$editionId = filter_var($_GET['editionId'], FILTER_SANITIZE_NUMBER_INT);

$sql = "SELECT ed.editionId, e.eventId, e.eventTitle, e.eventType, e.eventDescription, 
               e.TariffNormal, e.TariffReduit, ed.dateEvent, ed.timeEvent, ed.image,
               s.capSalle, IFNULL(SUM(r.qteBilletsNormal + r.qteBilletsReduit), 0) as total_billets
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
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if (!$event) {
    die("Event not found.");
}

$available_seats = $event['capSalle'] - $event['total_billets'];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event['eventTitle']) ?> - FarhaEvents</title>
    <link rel="icon" href="./assets/images/f-logo.avif" type="image/x-icon" />
    <link rel="stylesheet" href="./assets/styles/details.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="home.php" class="logo">
                <img src="./assets/images/f-logo.avif" id="logo-img" alt="">
                <span class="logo-text">| FarhaEvents</span>
            </a>


            <ul class="nav-links" id="navLinks">
                <li><a href="home.php" class="nav-link"><i class="fa-solid fa-house"></i>&nbsp; Accueil</a></li>
                <li><a href="about.php" class="nav-link"><i class="fa-solid fa-circle-info"></i>&nbsp; À propos</a></li>
                <li><a href="contact.php" class="nav-link"><i class="fa-solid fa-id-badge"></i>&nbsp; Contact</a></li>
                <li>
                    <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_name'])): ?>
                        <div class="user-dropdown">
                            <span class="nav-link user-name"> <i class="fa-solid fa-user-tie" style="color:  #E86C60;"></i>&nbsp; <?php echo htmlspecialchars($_SESSION['user_name']); ?>&nbsp; &nbsp;<i class="fas fa-caret-down"></i> </span>
                            <div class="dropdown-content">
                                <a href="profile.php"><i class="fa-solid fa-user" style="color:  #E86C60;"></i>&nbsp; Profil</a>
                                <a href="logout.php" class="logout-link"><i class="fa-solid fa-arrow-right-from-bracket" style="color:  #E86C60;"></i>&nbsp; Déconnexion</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="nav-link"><i class="fa-solid fa-right-to-bracket"></i> Connexion</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <div class="main-content">
        <div class="container">
            <div class="event-header">
                <h1><?= htmlspecialchars($event['eventTitle']) ?></h1>
                <p class="event-subtitle"><?= htmlspecialchars($event['dateEvent']) ?> à <?= htmlspecialchars($event['timeEvent']) ?> - <?= htmlspecialchars($event['eventType']) ?></p>
            </div>

            <div class="event-container">
                <div class="event-info">
                    <img src="<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['eventTitle']) ?>">
                    <div class="event-meta">
                        <div class="event-meta-item">
                            <i class="far fa-calendar-alt"></i>
                            <span><?= htmlspecialchars($event['dateEvent']) ?></span>
                        </div>
                        <div class="event-meta-item">
                            <i class="fas fa-clock"></i>
                            <span><?= htmlspecialchars($event['timeEvent']) ?></span>
                        </div>
                        <div class="event-meta-item">
                            <i class="fas fa-theater-masks"></i>
                            <span><?= htmlspecialchars($event['eventType']) ?></span>
                        </div>
                        <div class="event-meta-item">
                            <i class="fas fa-chair"></i>
                            <span><?= $available_seats ?> places disponibles (sur <?= $event['capSalle'] ?>)</span>
                        </div>
                    </div>
                    <h3>Description de l'événement</h3>
                    <p><?= htmlspecialchars($event['eventDescription']) ?></p>
                </div>

                <div class="ticket-form-container">
                    <h2>Acheter des billets</h2>
                    <?php if ($available_seats <= 0): ?>
                        <p class="sold-out">Guichet fermé - Pas de places disponibles.</p>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                        <p class="login-required">Veuillez <a href="login.php">vous connecter</a> pour acheter des billets.</p>
                    <?php else: ?>
                        <form action="process_purchase.php" method="POST" class="ticket-form">
                            <input type="hidden" name="editionId" value="<?= htmlspecialchars($event['editionId']) ?>">

                            <div class="ticket-option">
                                <h3>Billets normaux <span class="ticket-price"><?= htmlspecialchars($event['TariffNormal']) ?>MAD</span></h3>
                                <p>Admission standard pour tous les participants.</p>
                                <div class="ticket-input-group">
                                    <div>
                                        <label for="normalQuantity">Quantité :</label>
                                        <input type="number" id="normalQuantity" name="normalQuantity" min="0" max="<?= $available_seats ?>" value="0" required>
                                    </div>
                                    <span class="subtotal" id="normalSubtotal">0.00 MAD</span>
                                </div>
                            </div>

                            <div class="ticket-option">
                                <h3>Billets réduits <span class="ticket-price"><?= htmlspecialchars($event['TariffReduit']) ?>€</span></h3>
                                <p>Billets à prix réduit pour étudiants et mineurs.</p>
                                <div class="ticket-input-group">
                                    <div>
                                        <label for="reducedQuantity">Quantité :</label>
                                        <input type="number" id="reducedQuantity" name="reducedQuantity" min="0" max="<?= $available_seats ?>" value="0" required>
                                    </div>
                                    <span class="subtotal" id="reducedSubtotal">0.00 MAD</span>
                                </div>
                            </div>

                            <div class="order-summary">
                                <div class="summary-row">
                                    <span>Billets normaux :</span>
                                    <span id="normalTotal">0.00 MAD</span>
                                </div>
                                <div class="summary-row">
                                    <span>Billets réduits :</span>
                                    <span id="reducedTotal">0.00 MAD</span>
                                </div>
                                <div class="summary-row">
                                    <span>Total :</span>
                                    <span id="orderTotal">0.00 MAD</span>
                                </div>
                            </div>

                            <button type="submit" class="submit-button">Valider l'achat</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php if (isset($_SESSION['user_id']) && $available_seats > 0): ?>
            document.addEventListener('DOMContentLoaded', function() {
                const normalPrice = <?= floatval($event['TariffNormal']) ?>;
                const reducedPrice = <?= floatval($event['TariffReduit']) ?>;
                const maxSeats = <?= $available_seats ?>; // Use available seats instead of tickets

                const normalInput = document.getElementById('normalQuantity');
                const reducedInput = document.getElementById('reducedQuantity');
                const normalSubtotal = document.getElementById('normalSubtotal');
                const reducedSubtotal = document.getElementById('reducedSubtotal');
                const normalTotal = document.getElementById('normalTotal');
                const reducedTotal = document.getElementById('reducedTotal');
                const orderTotal = document.getElementById('orderTotal');

                function updateTotals() {
                    const normalQty = parseInt(normalInput.value) || 0;
                    const reducedQty = parseInt(reducedInput.value) || 0;
                    const totalQty = normalQty + reducedQty;

                    if (totalQty > maxSeats) {
                        alert(`Vous ne pouvez pas acheter plus de ${maxSeats} places restantes.`);
                        normalInput.value = maxSeats - reducedQty;
                        updateTotals();
                        return;
                    }

                    const normalAmount = normalQty * normalPrice;
                    const reducedAmount = reducedQty * reducedPrice;
                    const totalAmount = normalAmount + reducedAmount;

                    normalSubtotal.textContent = normalAmount.toFixed(2) + '€';
                    reducedSubtotal.textContent = reducedAmount.toFixed(2) + '€';
                    normalTotal.textContent = normalAmount.toFixed(2) + '€';
                    reducedTotal.textContent = reducedAmount.toFixed(2) + '€';
                    orderTotal.textContent = totalAmount.toFixed(2) + '€';
                }

                normalInput.addEventListener('input', updateTotals);
                reducedInput.addEventListener('input', updateTotals);
                updateTotals();
            });
        <?php endif; ?>
    </script>
</body>

</html>