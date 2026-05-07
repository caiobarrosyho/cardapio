# Migração de produtos — Fase 3

## Escopo

A Fase 3 migra apenas categorias e produtos de `nograuburger/data/produtos.json` para MariaDB/MySQL.

Ainda **não** são migrados:

- pedidos de `nograuburger/data/pedidos.json`;
- regras de checkout/pedidos;
- histórico de pedidos;
- visual do cardápio.

O arquivo `produtos.json` não é removido. Ele permanece como backup/fonte histórica e também continua guardando dados de loja que ainda não foram migrados completamente.

## Pré-requisitos

1. Importar `nograuburger/database.sql` no MariaDB/MySQL.
2. Configurar `nograuburger/config/conexao.php` por variáveis de ambiente ou com base em `conexao.example.php`.
3. Garantir que a extensão PDO MySQL esteja habilitada no PHP.

## Como rodar a migração

Pelo terminal, na raiz do repositório:

```bash
php nograuburger/migrar_produtos.php
```

Também é possível acessar pelo navegador em ambiente administrativo/controlado:

```text
https://seu-dominio/nograuburger/migrar_produtos.php
```

Ao executar, o script:

1. lê `nograuburger/data/produtos.json`;
2. cria um backup automático em `nograuburger/data/backups/`;
3. cria/usa a primeira loja do banco;
4. importa ou atualiza categorias pelo título;
5. importa ou atualiza produtos pelo nome dentro da categoria;
6. evita duplicação quando executado mais de uma vez.

## Como testar se o cardápio vem do banco

1. Execute a migração.
2. Abra o painel administrativo.
3. Edite o nome ou preço de um produto pelo painel.
4. Abra `nograuburger/index.php` no navegador.
5. Confirme que a alteração aparece no cardápio público.

Outra forma é alterar temporariamente um produto direto no banco, por exemplo:

```sql
UPDATE produtos SET nome = CONCAT(nome, ' DB') WHERE id = 1;
```

Depois recarregue `index.php`. Se o nome atualizado aparecer, os produtos estão sendo carregados do banco.

## Segurança e erros

- O sistema usa PDO.
- As credenciais não devem ser exibidas nem versionadas.
- Em caso de banco indisponível, as telas exibem mensagem amigável sem mostrar senha, DSN ou stack trace.
- As entradas básicas de produto são sanitizadas.
- Nome de produto é obrigatório.
- Preços são validados como decimal.
- Produtos têm status ativo/inativo no banco.
