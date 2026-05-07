# Migração de pedidos — Fase 4

## Escopo

A Fase 4 migra os pedidos de `nograuburger/data/pedidos.json` para as tabelas `pedidos` e `pedido_itens` no MariaDB/MySQL.

O arquivo `pedidos.json` **não é removido**. Ele permanece como backup/fonte histórica, e o script de migração cria uma cópia automática antes de importar os dados.

## Pré-requisitos

1. Importar ou atualizar `nograuburger/database.sql` no MariaDB/MySQL.
2. Garantir que `nograuburger/config/conexao.php` esteja configurado.
3. Ter executado a Fase 3 para produtos/categorias, quando possível.
4. Garantir que a extensão PDO MySQL esteja habilitada no PHP.

> A tabela `pedidos` agora prevê os campos `status` com `em_impressao` e `impresso` para controle de impressão.

## Como rodar a migração

Na raiz do repositório:

```bash
php nograuburger/migrar_pedidos.php
```

Ao executar, o script:

1. lê `nograuburger/data/pedidos.json`;
2. cria backup automático em `nograuburger/data/backups/`;
3. importa ou atualiza cada pedido usando o identificador público antigo (`codigo_publico`);
4. importa os itens na tabela `pedido_itens`;
5. evita duplicação quando executado mais de uma vez.

## O que passou a usar o banco

- `nograuburger/registrar_pedido.php` grava novos pedidos no banco.
- `nograuburger/admin/pedidos.php` lista e atualiza status pelo banco.
- `nograuburger/admin/pedido_ajuste.php` salva ajustes no banco.
- `nograuburger/admin/imprimir_pedido.php` imprime pedidos do banco.
- `nograuburger/admin/pedido_proximo_json.php` lê o próximo pedido do banco.
- `nograuburger/admin/pedido_proximo_html.php` gera cupom do próximo pedido pelo banco.

## Como testar um pedido novo

1. Abra o cardápio público.
2. Adicione produtos à sacola.
3. Finalize o pedido normalmente.
4. Confirme que a resposta do envio retorna `ok: true`.
5. Abra `nograuburger/admin/pedidos.php` e verifique se o pedido aparece.
6. Abra o detalhe do pedido e teste a impressão.
7. Opcionalmente, consulte direto no banco:

```sql
SELECT id, codigo_publico, nome_cliente, status, impresso, total_final
FROM pedidos
ORDER BY id DESC
LIMIT 5;
```

## Segurança e erros

- O registro de pedidos usa PDO.
- Erros de banco são registrados com `error_log`.
- O cliente recebe apenas mensagem genérica quando há falha.
- Dados sensíveis como senha, DSN e stack trace não são exibidos ao cliente.
