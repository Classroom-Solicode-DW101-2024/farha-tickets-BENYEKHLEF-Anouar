<?php
require 'config.php';

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

$query = 'SELECT e.eventId, ed.editionId, e.eventTitle AS event_title, e.eventDescription AS event_description, 
                 e.eventType AS typeEvent, e.TariffNormal, e.TariffReduit, ed.image AS event_image, 
                 ed.dateEvent AS edition_date, ed.timeEvent AS edition_time, ed.NumSalle, s.capSalle,
                 (SELECT IFNULL(SUM(r.qteBilletsNormal) + SUM(r.qteBilletsReduit), 0) FROM reservation r WHERE r.editionId = ed.editionId) AS total_reserved
          FROM evenement e
          JOIN edition ed ON e.eventId = ed.eventId
          JOIN salle s ON ed.NumSalle = s.NumSalle
          WHERE ed.dateEvent >= CURDATE()';

// 
if ($searchTerm) {
    $query .= ' AND e.eventTitle LIKE "%" :searchTerm "%"';
}

// 
if ($startDate && $endDate) {
    $query .= ' AND ed.dateEvent BETWEEN :startDate AND :endDate';
} else {
    if ($startDate) {
        $query .= ' AND ed.dateEvent >= :startDate';
    }
    if ($endDate) {
        $query .= ' AND ed.dateEvent <= :endDate';
    }
}

// 
if ($category) {
    $query .= ' AND e.eventType = :category';
}

// 
$query .= ' ORDER BY ed.dateEvent ASC';

    $stmt = $pdo->prepare($query);

    if ($searchTerm) {
        $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%', PDO::PARAM_STR);
    }

    if ($startDate) {
        $stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
    }

    if ($endDate) {
        $stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);
    }

    if ($category) {
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    }

    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarhaEvents - Événements culturels</title>
    <link rel="icon" href="./assets/images/f-logo.avif" type="image/x-icon" />
    <link rel="stylesheet" href="./assets/styles/home.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
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
                <li><a href="home.php" class="nav-link active"><i class="fa-solid fa-house"></i>  Accueil</a></li>
                <li><a href="about.php" class="nav-link"><i class="fa-solid fa-circle-info"></i>  À propos</a></li>
                <li><a href="contact.php" class="nav-link"><i class="fa-solid fa-id-badge"></i>  Contact</a></li>
                <li>
                    <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_name'])): ?>
                        <div class="user-dropdown">
                            <span class="nav-link user-name"> <i class="fa-solid fa-user-tie" style="color: #E86C60;"></i>  <?php echo htmlspecialchars($_SESSION['user_name']); ?>   <i class="fas fa-caret-down"></i> </span>
                            <div class="dropdown-content">
                                <a href="profile.php"><i class="fa-solid fa-user" style="color: #E86C60;"></i>  Profil</a>
                                <a href="logout.php" class="logout-link"><i class="fa-solid fa-arrow-right-from-bracket" style="color: #E86C60;"></i>  Déconnexion</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="nav-link"><i class="fa-solid fa-right-to-bracket"></i>  Connexion</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Découvrez les meilleurs événements culturels</h1>
            <p>Films, concerts, théâtre, rencontres - vivez des moments inoubliables avec FarhaEvents</p>
            <a href="#events" class="hero-btn"><i class="fa-solid fa-down-long"></i>  Explorer les événements</a>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <div class="section-title">
            <h1>Événements à venir</h1>
            <p>Découvrez notre sélection d'événements culturels à ne pas manquer</p>
        </div>

        <!-- Search form -->
        <form action="home.php" method="get" id="events">
            <input type="text" name="search" placeholder="Rechercher par titre" value="<?php echo htmlspecialchars($searchTerm); ?>">
            <input type="date" name="startDate" value="<?php echo htmlspecialchars($startDate); ?>" placeholder="Date de début">
            <input type="date" name="endDate" value="<?php echo htmlspecialchars($endDate); ?>" placeholder="Date de fin">
            <select name="category">
                <option value="">Toutes les catégories</option>
                <option value="Cinéma" <?php if ($category === 'Cinéma') echo 'selected'; ?>>Cinéma</option>
                <option value="Musique" <?php if ($category === 'Musique') echo 'selected'; ?>>Musique</option>
                <option value="Théatre" <?php if ($category === 'Théatre') echo 'selected'; ?>>Théatre</option>
                <option value="Rencontres" <?php if ($category === 'Rencontres') echo 'selected'; ?>>Rencontres</option>
            </select>
            <button type="submit" id="searchBtn"><i class="fa-solid fa-magnifying-glass"></i> Rechercher</button>
            <button type="button" id="clearBtn" onclick="window.location.href='home.php'"><i class="fa-regular fa-circle-xmark"></i></button>
        </form>
    </div>

    <?php if ($events): ?>
        <div class="event-container">
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <?php if ($event['total_reserved'] >= $event['capSalle']): ?>
                        <img class="sold-out" src="assets/images/sold-out.png" alt="Sold Out">
                    <?php endif; ?>
                    <img src="<?php echo htmlspecialchars($event['event_image']); ?>" alt="<?php echo htmlspecialchars($event['event_title']); ?>">
                    <div class="event-info">
                        <span class="event-type"><i class="fas fa-theater-masks"></i>  <?php echo htmlspecialchars($event['typeEvent']); ?></span>
                        <h2><?php echo htmlspecialchars($event['event_title']); ?></h2>
                        <div class="event-date">
                            <i class="far fa-calendar-alt"></i> <?php echo htmlspecialchars($event['edition_date']); ?> à <?php echo htmlspecialchars($event['edition_time']); ?>
                        </div>
                        <?php if ($event['total_reserved'] < $event['capSalle']): ?>
                            <a href="details.php?editionId=<?php echo $event['editionId']; ?>"><i class="fa-solid fa-credit-card"></i>  J'achète</a>
                        <?php else: ?>
                            <a style="background-color: grey;" href="details.php?editionId=<?php echo $event['editionId']; ?>">Guichet fermé</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-events">
            <p>Aucun événement trouvé.</p>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-column" id="footer-column1">
                <h3>À propos de FarhaEvents</h3>
                <p>FarhaEvents est votre partenaire privilégié pour découvrir et réserver les meilleurs événements culturels. Nous vous proposons une sélection variée de films, concerts, pièces de théâtre et rencontres.</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-link"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-column" id="footer-column2">
                <h3>Liens rapides</h3>
                <ul class="footer-links">
                    <li><a href="home.php">Accueil</a></li>
                    <li><a href="about.php">À propos</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="profile.php">Profil</a></li>
                </ul>
            </div>
            <div class="footer-column" id="footer-column3">
                <h3>Catégories</h3>
                <ul class="footer-links">
                    <li><a href="">Cinéma</a></li>
                    <li><a href="">Musique</a></li>
                    <li><a href="">Théâtre</a></li>
                    <li><a href="">Rencontres</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3>Contact</h3>
                <p><i class="fas fa-map-marker-alt"></i>  123 Rue du Commerce, 75001 Tanger</p>
                <p><i class="fas fa-phone"></i>  +33 1 23 45 67 89</p>
                <p><i class="fas fa-envelope"></i>  contact@farha-events.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo date('Y'); ?> FarhaEvents. Tous droits réservés.</p>
        </div>
    </footer>
</body>

</html>