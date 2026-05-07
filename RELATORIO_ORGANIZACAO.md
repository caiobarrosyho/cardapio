# Relatório de organização do projeto PHP de cardápio digital

## Objetivo da alteração

Organizar a base sem alterar o modelo atual de dados e sem remover funcionalidades existentes. O sistema continua usando os arquivos JSON atuais:

- `nograuburger/data/produtos.json`
- `nograuburger/data/pedidos.json`

Não houve migração para banco de dados.

## O que foi organizado

### 1. Helper compartilhado

Foi criado o arquivo `nograuburger/includes/app.php` para centralizar funções comuns que estavam repetidas em telas públicas e administrativas:

- escape seguro de HTML com `h()`;
- limpeza simples de entradas com `limpar()`;
- resolução de caminhos da pasta `data/`;
- leitura e gravação padronizada de JSON;
- carregamento de `produtos.json` e `pedidos.json` com valores padrão seguros;
- verificação de produto ativo;
- busca de produto por índice de categoria/produto;
- localização do produto completo a partir dos itens salvos na sacola.

### 2. Telas públicas atualizadas para usar o helper

Foram ajustados os includes e removidas declarações repetidas nas telas públicas principais:

- `nograuburger/index.php`
- `nograuburger/carrinho.php`
- `nograuburger/checkout.php`
- `nograuburger/header_topo.php`
- `nograuburger/registrar_pedido.php`

A lógica atual de exibição do cardápio, sacola, checkout e registro de pedido foi mantida.

### 3. Painel administrativo atualizado para usar o helper

Foram ajustadas telas do painel para reutilizar a leitura/gravação dos JSONs e o helper `h()`:

- `nograuburger/admin/index.php`
- `nograuburger/admin/editar_produto.php`
- `nograuburger/admin/pedidos.php`
- `nograuburger/admin/pedido_ajuste.php`
- `nograuburger/admin/taxas_entrega.php`
- `nograuburger/admin/salvar.php`

As ações administrativas existentes foram preservadas.

### 4. CSS preservado

O CSS segue separado no arquivo existente:

- `nograuburger/assets/style.css`

Nenhum CSS foi movido para dentro dos arquivos PHP.

## O que não foi alterado

- Não houve remoção de funcionalidades.
- Não houve migração para banco de dados.
- Não houve alteração na estrutura dos arquivos JSON atuais.
- Os arquivos de backup existentes, como `carrinho-21-04.php` e `registrar_pedido-21-04.php`, foram mantidos.
- O fluxo de pedidos continua gravando em `data/pedidos.json`.
- O cardápio continua lendo produtos e configurações da loja de `data/produtos.json`.

## Validação realizada

Foi executada validação de sintaxe em todos os arquivos PHP do projeto com `php -l`, sem erros de sintaxe.
