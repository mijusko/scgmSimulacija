<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

require_once 'db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // U stvarnoj aplikaciji koristili bismo password_verify za provere heša
        if ($user && $user['password'] === $password) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: dashboard.php');
            }
            exit;
        } else {
            $error = 'Pogrešno korisničko ime ili lozinka.';
        }
    } else {
        $error = 'Molimo unesite podatke.';
    }
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prijava - Simulacija Sistema</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-body">
    <div class="background-anim"></div>
    <div class="login-container glass-panel fade-in">
        <div class="logo-area">
            <h1>Simulacija Sistema</h1>
            <p>Sistem za evidenciju štampe</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Korisničko Ume</label>
                <input type="text" id="username" name="username" required placeholder="Unesite username (npr. radnik1)">
            </div>
            <div class="form-group">
                <label for="password">Lozinka</label>
                <input type="password" id="password" name="password" required placeholder="Unesite lozinku (npr. sifra123)">
            </div>
            <button type="submit" class="btn-primary">Prijavi se</button>
        </form>
    </div>
</body>
</html>
