<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    die("Niste prijavljeni.");
}

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Neispravan zahtev.");
}

$product_id = $_POST['product_id'] ?? null;
$quantity = $_POST['quantity'] ?? null;
$worker_id = $_SESSION['user_id'];

if (!$product_id || !$quantity || $quantity < 1) {
    die("Nevažeći podaci za štampu.");
}

try {
    $stmt = $pdo->prepare("SELECT name, order_number, available_stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if (!$product) {
        die("Proizvod nije pronađen.");
    }

    $insertStmt = $pdo->prepare("INSERT INTO print_logs (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $insertStmt->execute([$worker_id, $product_id, $quantity]);

    if ($product['available_stock'] >= $quantity) {
        $updateStmt = $pdo->prepare("UPDATE products SET available_stock = available_stock - ? WHERE id = ?");
        $updateStmt->execute([$quantity, $product_id]);
    }

    // Dodajemo u novu tabelu za praćenje ukupnih količina (UPDATE ili INSERT ako ne postoji)
    $aggStmt = $pdo->prepare("INSERT INTO printed_quantities (product_id, total_quantity) VALUES (?, ?) ON DUPLICATE KEY UPDATE total_quantity = total_quantity + ?");
    $aggStmt->execute([$product_id, $quantity, $quantity]);

    $print_id = $pdo->lastInsertId();

}
catch (\Exception $e) {
    die("Došlo je do greške: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <title>Deklaracija - <?php echo htmlspecialchars($product['name']); ?></title>
    <style>
        @page { size: auto; margin: 0mm; }
        body { font-family: 'Inter', Arial, sans-serif; padding: 40px; text-align: center; color: #000; background: #fff;}
        .label-container { border: 3px solid #000; padding: 30px; width: 100%; max-width: 600px; margin: 0 auto; box-sizing: border-box;}
        h1 { margin-top: 0; font-size: 28px; text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 15px;}
        h2 { font-size: 24px; margin-top: 20px;}
        .details { font-size: 18px; margin: 20px 0; line-height: 1.6;}
        .barcode-container { margin-top: 30px; padding-top: 20px; border-top: 1px dashed #000;}
        .barcode { letter-spacing: 4px; font-weight: bold; font-size: 28px; font-family: 'Courier New', Courier, monospace;}
        .footer { margin-top: 40px; font-size: 14px; color: #333; }
        
        @media print {
            body { padding: 0; }
            .label-container { border: none; width: 100%; max-width: none; }
        }
    </style>
</head>
<body onload="setTimeout(function(){ window.print(); window.close(); }, 500);">
    <div class="label-container">
        <h1>Deklaracija Proizvoda</h1>
        <h2><?php echo htmlspecialchars($product['name']); ?></h2>
        
        <div class="details">
            <p><strong>Opis:</strong> <?php echo htmlspecialchars($product['order_number']); ?></p>
            <p><strong>Količina za pakovanje:</strong> <?php echo htmlspecialchars($quantity); ?> kom</p>
            <p><strong>Datum proizvodnje / štampe:</strong> <?php echo date('d.m.Y H:i'); ?></p>
        </div>
        
        <div class="barcode-container">
             <div class="barcode">
                || |||| | ||| | || ||
                <br>
                <?php echo str_pad($product_id, 4, '0', STR_PAD_LEFT) . '-' . str_pad($print_id, 6, '0', STR_PAD_LEFT); ?>
            </div>
        </div>
        
        <div class="footer">
             Radnik: <?php echo htmlspecialchars($_SESSION['username']); ?> | Nalog štampe: #<?php echo str_pad($print_id, 6, '0', STR_PAD_LEFT); ?>
             <br>
             <i>Interni barkod generisan automatski</i>
        </div>
    </div>
</body>
</html>
