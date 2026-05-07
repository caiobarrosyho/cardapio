<?php
session_start();

$senhaCorreta = 'nograuburger'; // TROCAR AQUI

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    if ($senha === $senhaCorreta) {
        $_SESSION['logado_cardapio'] = true;
        header('Location: index.php');
        exit;
    } else {
        $erro = 'Senha incorreta.';
    }
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Login – Painel Cardápio</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon-32.png">
<link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon-16.png">
<link rel="shortcut icon" href="assets/img/favicon.ico">
<link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
<link rel="stylesheet" href="../assets/style.css?v=1">
</head>
<body>
<div class="login-wrapper">
  <h1>Painel do Cardápio</h1>
  <?php if ($erro): ?>
    <p style="color:#ea1d2c;font-size:12px;margin-bottom:8px;"><?php echo htmlspecialchars($erro); ?></p>
  <?php endif; ?>
  <form method="post">
    <label>Senha</label>
    <input type="password" name="senha" required>
    <button class="btn btn-red" type="submit">Entrar</button>
  </form>
</div>
</body>
</html>
