<?php
require '../includes/conexao.php';
require '../includes/funcoes.php';

if (!isset($_GET['id'])) exit;
$id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Imprimir Pedido #<?php echo $p['id']; ?></title>
</head>
<body onload="print()">
    <h2>Pedido #<?php echo $p['id']; ?></h2>
    <p><strong>Cliente:</strong> <?php echo proteger($p['cliente']); ?></p>
    <p><strong>Itens:</strong><br><?php echo nl2br(proteger($p['itens'])); ?></p>
    <p><strong>Data/Hora:</strong> <?php echo $p['datahora']; ?></p>
</body>
</html>
