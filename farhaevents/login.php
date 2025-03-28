<?php
require 'config.php';

$error = '';
$erreurs = [];
$email = $_GET['email'] ?? ''; // Pre-fill from GET if redirected from register

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $erreurs['email'] = "Veuillez renseigner l'email.";
    }
    if (empty($password)) {
        $erreurs['password'] = "Veuillez renseigner le mot de passe.";
    }

    if (empty($erreurs)) {
        $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE mailUser = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($password === $user['motPasse']) {
                $_SESSION['user_id'] = $user['idUser'];
                $_SESSION['user_name'] = $user['prenomUser'] . ' ' . $user['nomUser'];
                header("Location: home.php");
                exit;
            } else {
                $error = "Mot de passe incorrect.";
            }
        } else {
            header("Location: register.php?email=" . urlencode($email));
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
    <title>Connexion - FarhaEvents</title>
    <link rel="stylesheet" href="./assets/styles/login.css?v=<?php echo time(); ?>">
    <link rel="icon" href="./assets/images/f-logo.avif" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Quicksand:wght@300..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="container">
        <div class="login-form">
            <h2>Connexion</h2>

            <form action="login.php" method="post">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? $email); ?>">
                <?php if (isset($erreurs['email'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($erreurs['email']); ?></span>
                <?php endif; ?>

                <label for="password">Mot de passe :</label>
                <input type="password" id="password" name="password" required>
                <?php if (isset($erreurs['password'])): ?>
                    <span class="error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($erreurs['password']); ?></span>
                <?php endif; ?>
                <?php if ($error): ?>
                    <p class="error"><i class="fa-solid fa-circle-exclamation"></i>&nbsp; <?php echo htmlspecialchars($error); ?></p>
                <?php endif; ?>

                <input type="submit" name="submit" value="Se connecter">
            </form>
            <p class="switch-form">Pas de compte ? <a href="register.php">&nbsp;Inscrivez-vous ici</a>.</p>
        </div>
    </div>

</body>

</html>