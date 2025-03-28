<?php
require 'config.php';

$error = '';
$success = '';
$erreurs = [];
$email = $_GET['email'] ?? ''; // Pre-fill from GET if redirected from login

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($nom)) {
        $erreurs['nom'] = "Veuillez renseigner le nom.";
    }
    if (empty($prenom)) {
        $erreurs['prenom'] = "Veuillez renseigner le prénom.";
    }
    if (empty($email)) {
        $erreurs['email'] = "Veuillez renseigner l'email.";
    }
    if (empty($password)) {
        $erreurs['password'] = "Veuillez renseigner le mot de passe.";
    }

    if (empty($erreurs)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utilisateur WHERE mailUser = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->fetchColumn() > 0) {
            $error = "Cet email est déjà utilisé.";
        } else {
            $idUser = getLastIdClient() + 1;
            $stmt = $pdo->prepare("INSERT INTO utilisateur (idUser, nomUser, prenomUser, mailUser, motPasse) VALUES (:idUser, :nom, :prenom, :email, :password)");
            $stmt->bindParam(':idUser', $idUser, PDO::PARAM_INT);
            $stmt->bindParam(':nom', $nom, PDO::PARAM_STR);
            $stmt->bindParam(':prenom', $prenom, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->execute();

            $success = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
            header("Refresh: 2; url=login.php?email=" . urlencode($email));
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - FarhaEvents</title>
    <link rel="stylesheet" href="./assets/styles/register.css?v=<?php echo time(); ?>">
    <link rel="icon" href="./assets/images/f-logo.avif" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="login-form">
            <h2>Inscription</h2>
            <?php if ($success): ?>
                <p class="success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>

            <form action="register.php" method="post">
                <label for="nom">Nom :</label>
                <input type="text" id="nom" name="nom" required value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                <?php if (isset($erreurs['nom'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($erreurs['nom']); ?></span>
                <?php endif; ?>

                <label for="prenom">Prénom :</label>
                <input type="text" id="prenom" name="prenom" required value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                <?php if (isset($erreurs['prenom'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($erreurs['prenom']); ?></span>
                <?php endif; ?>

                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
                <?php if (isset($erreurs['email'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($erreurs['email']); ?></span>
                <?php endif; ?>
                <?php if ($error): ?>
                    <p class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
                <?php if (isset($erreurs['password'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($erreurs['password']); ?></span>
                <?php endif; ?>

                <input type="submit" value="S'inscrire">
            </form>
            <p class="switch-form">Déjà un compte ? <a href="login.php">&nbsp;Connectez-vous ici</a>.</p>
        </div>
    </div>
</body>

</html>