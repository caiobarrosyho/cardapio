<?php
// header_topo.php – barra fixa do topo (logo, busca, endereço, sacola)
require_once __DIR__ . '/funcoes_carrinho.php';

$dataFile = __DIR__ . '/data/produtos.json';
$dados = json_decode(@file_get_contents($dataFile), true) ?: [];

$loja = $dados['loja'] ?? [];

// só cria a função h() se ainda não existir
if (!function_exists('h')) {
    function h($s){
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// endereço curto mostrado no topo
$endTopo = $loja['endereco_topo']
  ?? ($loja['endereco_curto']
  ?? ($loja['endereco'] ?? 'Endereço não configurado'));

// totais do carrinho
$totaisCarrinho = carrinho_totais();
$temItens       = $totaisCarrinho['itens'] > 0;

$labelSacola = 'R$ ' . number_format($totaisCarrinho['subtotal'], 2, ',', '.') .
               ' • ' . $totaisCarrinho['itens'] . ' itens';
?>
<header class="top-nav">
  <div class="top-nav-inner">
    <div class="top-left">
      <div class="top-logo">CardaFood</div>

      <!-- Menu simplificado: só Início levando para o cardápio -->
      <div class="top-menu">
        <a href="index.php" class="top-menu-link active">Início</a>
      </div>
    </div>

    <div class="top-searchbox">
      <input type="text" id="buscaTopo" placeholder="Busque por item ou loja">
    </div>

    <div class="top-right">
      <span><?php echo h($endTopo); ?></span>
      <a href="carrinho.php" class="top-cart <?php echo $temItens ? 'top-cart-full' : ''; ?>">
        <?php echo $labelSacola; ?>
      </a>
    </div>
  </div>
</header>
