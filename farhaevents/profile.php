<?php
require "config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$sql = "SELECT * FROM utilisateur WHERE idUser = :idUser";
$stmt = $pdo->prepare($sql);
$stmt->execute([':idUser' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    // $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['motPasse'];
    
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    } else {
        $password = $user['motPasse'];
    }
    

    $stmt = $pdo->prepare("UPDATE utilisateur SET nomUser = :nom, prenomUser = :prenom, mailUser = :email, motPasse = :password WHERE idUser = :idUser");

    // 
    $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
    $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':password', $password, PDO::PARAM_STR);
    $stmt->bindParam(':idUser', $_SESSION['user_id'], PDO::PARAM_STR); 

    $stmt->execute();

    $_SESSION['user_name'] = "$prenom $nom";
    header("Location: profile.php");
    exit;
}

//
$sql = "SELECT r.idReservation, r.qteBilletsNormal, r.qteBilletsReduit, e.eventTitle, ed.dateEvent,
               (r.qteBilletsNormal * e.TariffNormal + r.qteBilletsReduit * e.TariffReduit) AS total_paid
        FROM reservation r
        JOIN edition ed ON r.editionId = ed.editionId
        JOIN evenement e ON ed.eventId = e.eventId
        WHERE r.idUser = :idUser";
$stmt = $pdo->prepare($sql);
$stmt->execute([':idUser' => $_SESSION['user_id']]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - FarhaEvents</title>
    <link rel="icon" href="./assets/images/f-logo.avif" type="image/x-icon" />
    <link rel="stylesheet" href="./assets/styles/profile.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    
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
            <h1>Profil utilisateur</h1>
            <div class="profile-container">
                <div class="user-info">
                    <h2><i class="fa-solid fa-circle-info"></i>&nbsp; Mes informations</h2>
                    <form action="profile.php" method="POST">
                        <div class="form-group">
                            <label for="nom">Nom :</label>
                            <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nomUser']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom :</label>
                            <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenomUser']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email :</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['mailUser']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Nouveau mot de passe (optionnel) :</label>
                            <input type="password" id="password" name="password">
                        </div>
                        <button type="submit" name="update" class="btn2"><i class="fa-solid fa-pen"></i>&nbsp; Mettre à jour</button>
                    </form>
                </div>
                <div class="purchase-history">
                    <h2><i class="fa-solid fa-clock-rotate-left"></i>&nbsp; Historique des achats</h2>
                    <?php if (empty($purchases)): ?>
                        <p>Aucun achat effectué pour le moment.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Référence</th>
                                    <th>Événement</th>
                                    <th>Date</th>
                                    <th>Total payé</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($purchases as $purchase): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($purchase['idReservation']) ?></td>
                                        <td><?= htmlspecialchars($purchase['eventTitle']) ?></td>
                                        <td><?= htmlspecialchars($purchase['dateEvent']) ?></td>
                                        <td><?= number_format($purchase['total_paid'], 2) ?>MAD</td>
                                        <td>
                                            <a href="tickets.php?id=<?= urlencode($purchase['idReservation']) ?>" class="btn small">Billets</a>
                                            <a href="invoice.php?id=<?= urlencode($purchase['idReservation']) ?>" class="btn small">Facture</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>

</html>