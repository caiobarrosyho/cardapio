<?php
session_start();
if (empty($_SESSION['logado_cardapio'])) {
    header('Location: login.php');
    exit;
}

$dataFile = __DIR__ . '/../data/produtos.json';
$dados = json_decode(@file_get_contents($dataFile), true);
$categorias = $dados['categorias'] ?? [];

$catIdx  = (int)($_GET['cat'] ?? -1);
$prodIdx = (int)($_GET['prod'] ?? -1);

if (!isset($categorias[$catIdx]['produtos'][$prodIdx])) {
    echo "Produto não encontrado.";
    exit;
}

$cat  = $categorias[$catIdx];
$prod = $cat['produtos'][$prodIdx];

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Editar produto – <?php echo h($prod['nome']); ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="../assets/style.css?v=6">
</head>
<body>

<div class="edit-wrapper admin-box">
  <h2>Editar produto</h2>
  <p style="font-size:12px;color:#777;margin-bottom:10px;">
    Categoria: <?php echo h($cat['titulo']); ?>
  </p>

  <form action="salvar.php" method="post" enctype="multipart/form-data">
    <!-- IMPORTANTE: acao precisa bater com o case do salvar.php -->
    <input type="hidden" name="acao" value="salvar_produto">
    <input type="hidden" name="cat" value="<?php echo $catIdx; ?>">
    <input type="hidden" name="prod" value="<?php echo $prodIdx; ?>">
    <input type="hidden" name="foto_atual" value="<?php echo h($prod['foto'] ?? ''); ?>">

    <div class="admin-form-row">
      <div>
        <label>Nome</label>
        <input type="text" name="nome" value="<?php echo h($prod['nome']); ?>" required>
      </div>
      <div>
        <label>Preço (ponto como separador)</label>
        <input type="number" step="0.01" name="preco"
               value="<?php echo h($prod['preco']); ?>" required>
      </div>
    </div>

    <div class="admin-form-row">
      <div>
        <label>Descrição</label>
        <textarea name="descricao" required><?php echo h($prod['descricao']); ?></textarea>
      </div>
      <div>
        <label>Detalhe (ex: Serve 1 pessoa (500g))</label>
        <textarea name="detalhe"><?php echo h($prod['detalhe'] ?? ''); ?></textarea>
      </div>
    </div>

    <div class="admin-form-row">
      <div>
        <label>Preço original (opcional – aparece riscado)</label>
        <input type="number" step="0.01" name="preco_original"
               value="<?php echo h($prod['preco_original'] ?? ''); ?>">
      </div>
      <div>
        <label>Foto do produto (deixe em branco para manter)</label>
        <input type="file" name="foto">
        <?php if (!empty($prod['foto'])): ?>
          <small>Atual: <?php echo h($prod['foto']); ?></small>
        <?php endif; ?>
      </div>
    </div>

    <div class="admin-form-row">
      <div>
        <label>
          <input type="checkbox" name="ativo" value="1"
                 <?php echo (!isset($prod['ativo']) || $prod['ativo']) ? 'checked' : ''; ?>>
          Produto ativo (aparece no cardápio)
        </label>
      </div>
    </div>

    <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
      <button class="btn btn-red" type="submit">Salvar alterações</button>
      <a class="btn btn-outline" href="index.php">Voltar</a>
    </div>
  </form>
</div>

</body>
</html>
