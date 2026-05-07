<?php
require '../includes/conexao.php';
require '../includes/funcoes.php';

if (isset($_GET['check'])) {
    // Retorna quantidade de pedidos não vistos
    $stmt = $pdo->query("SELECT COUNT(*) AS c FROM pedidos WHERE visto = 0");
    $count = $stmt->fetch()['c'];
    echo json_encode(['novos' => $count]);
    exit;
}

if (!esta_logado()) {
    redirecionar('login.php');
}

session_start();
// Marca todos pedidos como vistos
$pdo->exec("UPDATE pedidos SET visto = 1 WHERE visto = 0");

// Busca pedidos da loja logada
$stmt   = $pdo->prepare(
    "SELECT id, cliente, itens, datahora FROM pedidos WHERE loja_id = ? ORDER BY datahora DESC"
);
$stmt->execute([$_SESSION['admin_id']]);
$pedidos = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/style.css">
    <script src="notificacao.js"></script>
    <title>Dashboard Admin</title>
</head>
<body>
    <h1>Pedidos Recebidos</h1>
    <a href="logout.php">Sair</a>
    <button onclick="window.print()">Imprimir Pedidos</button>
    <ul>
        <?php foreach ($pedidos as $p): ?>
        <li>
            <a href="detalhes_pedido.php?id=<?php echo $p['id']; ?>">
                #<?php echo $p['id']; ?> - <?php echo proteger($p['cliente']); ?> (<?php echo $p['datahora']; ?>)
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
