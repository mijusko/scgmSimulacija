<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// Dohvatanje proizvoda iz baze
$stmt = $pdo->query('SELECT id, name, available_stock FROM products ORDER BY name ASC');
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Štampa</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">
    <nav class="navbar glass-panel">
        <div class="nav-brand">Sistem Simulacije</div>
        <div class="nav-user">
            Ulogovan kao: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="admin.php" class="btn-secondary btn-sm" style="margin-right: 10px;">👑 Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="btn-secondary btn-sm">Odjava</a>
        </div>
    </nav>

    <main class="container fade-in">
        <div class="glass-panel main-panel form-panel">
            <h2>Kreiranje Naloga za Štampu</h2>
            <p class="subtitle">Izaberite proizvod sa liste i unesite količinu za štampu deklaracije.</p>
            
            <form action="print.php" method="POST" target="_blank">
                <div class="form-group">
                    <label for="product_id">Proizvod</label>
                    <select name="product_id" id="product_id" required>
                        <option value="" disabled selected>-- Izaberite proizvod --</option>
                        <?php foreach($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?> 
                                (Stanje: <?php echo $product['available_stock']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Količina (kom)</label>
                    <input type="number" id="quantity" name="quantity" min="1" max="1000" value="1" required>
                </div>

                <button type="submit" class="btn-primary animate-btn">
                    <span class="icon">🖨️</span> Odštampaj Deklaraciju
                </button>
            </form>
        </div>
    </main>
</body>
</html>
