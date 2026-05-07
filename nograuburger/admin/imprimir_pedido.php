<?php
require_once __DIR__ . '/../includes/app.php';

$id = $_GET['id'] ?? '';
if ($id === '') {
    echo 'Pedido não informado.';
    exit;
}

try {
    $pedido = cardapio_buscar_pedido_banco($id);
    if ($pedido) {
        cardapio_atualizar_status_pedido_banco($id, $pedido['status'] ?? 'preparo', 1);
        $pedido['impresso'] = 1;
    }
} catch (Throwable $e) {
    error_log('Erro ao imprimir pedido: ' . $e->getMessage());
    echo 'Não foi possível carregar o pedido.';
    exit;
}

if (!$pedido) {
    echo 'Pedido não encontrado.';
    exit;
}

$dataHora = $pedido['data_hora'] ?? '';
$dataBr = $dataHora;
if ($dataHora && ($ts = strtotime($dataHora))) {
    $dataBr = date('d/m/Y H:i:s', $ts);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Imprimir Pedido <?php echo h($pedido['id']); ?></title>
    <link rel="stylesheet" href="../assets/style.css?v=17">
</head>
<body onload="print()">
    <div class="pedido-impressao" style="max-width:380px;margin:16px auto;font-family:Arial,sans-serif;">
        <h2>Pedido <?php echo h($pedido['id']); ?></h2>
        <p><strong>Data/Hora:</strong> <?php echo h($dataBr); ?></p>
        <p><strong>Cliente:</strong> <?php echo h($pedido['nome'] ?? ''); ?></p>
        <p><strong>Telefone:</strong> <?php echo h($pedido['telefone'] ?? ''); ?></p>
        <p><strong>Tipo:</strong> <?php echo h($pedido['tipo_pedido'] ?? ''); ?></p>
        <p><strong>Bairro:</strong> <?php echo h($pedido['bairro'] ?? ''); ?></p>
        <p><strong>Endereço:</strong> <?php echo h(trim(($pedido['endereco'] ?? '') . ' ' . ($pedido['referencia'] ?? ''))); ?></p>
        <p><strong>Pagamento:</strong> <?php echo h($pedido['pag_forma'] ?? ''); ?><?php echo !empty($pedido['troco']) ? ' - Troco: ' . h($pedido['troco']) : ''; ?></p>

        <h3>Itens</h3>
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr>
                    <th style="text-align:left;">Qtd</th>
                    <th style="text-align:left;">Produto</th>
                    <th style="text-align:right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($pedido['itens'] ?? []) as $item): ?>
                    <?php $totalItem = (float)($item['preco'] ?? 0) * (int)($item['qtd'] ?? 1); ?>
                    <tr>
                        <td><?php echo (int)($item['qtd'] ?? 1); ?>x</td>
                        <td><?php echo h($item['nome'] ?? ''); ?></td>
                        <td style="text-align:right;">R$ <?php echo h(cardapio_formatar_dinheiro($totalItem)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if (!empty($pedido['obs'])): ?>
            <p><strong>Observações:</strong><br><?php echo nl2br(h($pedido['obs'])); ?></p>
        <?php endif; ?>

        <p><strong>Subtotal:</strong> R$ <?php echo h($pedido['subtotal'] ?? '0,00'); ?></p>
        <p><strong>Taxa:</strong> R$ <?php echo h($pedido['taxa_entrega'] ?? '0,00'); ?></p>
        <p><strong>Total:</strong> R$ <?php echo h($pedido['total_final'] ?? '0,00'); ?></p>
    </div>
</body>
</html>
