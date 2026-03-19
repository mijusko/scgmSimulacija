<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once 'db.php';

// Dodavanje novog proizvoda
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $order_number = trim($_POST['order_number'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);

    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO products (name, order_number, available_stock) VALUES (?, ?, ?)");
        $stmt->execute([$name, $order_number, $stock]);
        $new_id = $pdo->lastInsertId();

        $stmt2 = $pdo->prepare("INSERT INTO printed_quantities (product_id, total_quantity) VALUES (?, 0)");
        $stmt2->execute([$new_id]);

        header("Location: admin.php?msg=added");
        exit;
    }
}

// Uređivanje zaliha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_stock') {
    $product_id = (int)$_POST['product_id'];
    $new_stock = (int)$_POST['new_stock'];

    $stmt = $pdo->prepare("UPDATE products SET available_stock = ? WHERE id = ?");
    $stmt->execute([$new_stock, $product_id]);
    header("Location: admin.php?msg=updated");
    exit;
}

// Brisanje proizvoda
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    header("Location: admin.php?msg=deleted");
    exit;
}

// Dohvati sve proizvode
$stmt = $pdo->query('
    SELECT p.id, p.name, p.order_number, p.available_stock, COALESCE(pq.total_quantity, 0) as printed 
    FROM products p 
    LEFT JOIN printed_quantities pq ON p.id = pq.product_id 
    ORDER BY p.id DESC
');
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Šef</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .admin-table th, .admin-table td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.2); }
        .admin-table th { background: rgba(0,0,0,0.2); font-weight: 600; }
        .admin-container { max-width: 900px; margin: 0 auto; flex-direction: column; align-items: stretch; width: 100%; }
        .action-flex { display: flex; gap: 10px; align-items: center; }
        .inline-form { display: flex; gap: 10px; }
        .inline-form input { width: 80px; padding: 5px; }
        .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body class="dashboard-body">
    <nav class="navbar glass-panel">
        <div class="nav-brand">Admin Panel - Upravljanje Proizvodima</div>
        <div class="nav-user">
            Šef: <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            <a href="dashboard.php" class="btn-secondary btn-sm" style="margin-right: 10px;">🛠️ Probaj Štampu</a>
            <a href="logout.php" class="btn-secondary btn-sm">Odjava</a>
        </div>
    </nav>

    <main class="container admin-container fade-in">
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="glass-panel" style="background: rgba(46, 204, 113, 0.2); margin-bottom: 20px; padding: 15px;">
                Akcija je uspešno izvršena!
            </div>
        <?php
endif; ?>

        <div class="glass-panel" style="text-align: center;">
            <h2 style="margin-bottom: 20px;">Dodaj Novi Proizvod</h2>
            <form action="admin.php" method="POST" style="display: flex; flex-direction: column; gap: 15px; max-width: 400px; margin: 0 auto;">
                <input type="hidden" name="action" value="add">
                <input type="text" name="name" placeholder="Naziv proizvoda" required>
                <input type="text" name="order_number" placeholder="Broj narudžbine">
                <input type="number" name="stock" placeholder="Početno stanje komada" required>
                <button type="submit" class="btn-primary" style="margin-top: 10px;">➕ Dodaj Proizvod</button>
            </form>
        </div>

        <div class="glass-panel" style="margin-top: 30px; width: 100%; box-sizing: border-box;">
            <h2>Spisak Proizvoda i Statistika</h2>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th style="min-width: 250px;">Naziv</th>
                            <th>Stanje (Zalihe)</th>
                            <th>Ukupno Odštampano</th>
                            <th>Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($p['name']); ?></strong><br><small><?php echo htmlspecialchars($p['order_number']); ?></small></td>
                            <td>
                                <form action="admin.php" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                                    <input type="hidden" name="action" value="edit_stock">
                                    <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                    <input type="number" name="new_stock" value="<?php echo $p['available_stock']; ?>" style="width: 100px; padding: 6px;">
                                    <button type="submit" class="btn-secondary btn-sm" style="width: 100px;">Sačuvaj</button>
                                </form>
                            </td>
                            <td>
                                <span style="font-size: 1.2em; font-weight: bold; color: #4facfe;">
                                    <?php echo $p['printed']; ?> kom
                                </span>
                            </td>
                            <td>
                                <a href="admin.php?action=delete&id=<?php echo $p['id']; ?>" class="btn-secondary btn-sm" onclick="return confirm('Da li ste sigurni? Obrisaće se i sva istorija štampe za ovo!');" style="background: rgba(231, 76, 60, 0.2); border-color: rgba(231, 76, 60, 0.5); color: #ffcccc;">🗑️ Obriši</a>
                            </td>
                        </tr>
                        <?php
endforeach; ?>
                        <?php if (empty($products)): ?>
                            <tr><td colspan="5" style="text-align:center;">Nema proizvoda u bazi.</td></tr>
                        <?php
endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
