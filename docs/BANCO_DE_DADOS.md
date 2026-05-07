# Banco de dados — Fase 2 do No Grau Burger

## Status da integração

Esta fase apenas prepara o projeto para MariaDB/MySQL. O funcionamento atual **não foi migrado** e continua baseado nos arquivos JSON existentes:

- `nograuburger/data/produtos.json` para loja, categorias, produtos e taxas atuais.
- `nograuburger/data/pedidos.json` para pedidos atuais.

Portanto, importar o SQL e configurar o PDO não muda o visual, não altera o painel administrativo e não troca a origem dos dados das telas atuais.

## Arquivos criados

- `nograuburger/config/conexao.php`: conexão PDO preparada para MariaDB/MySQL usando variáveis de ambiente.
- `nograuburger/config/conexao.example.php`: exemplo de configuração sem senha real.
- `nograuburger/database.sql`: estrutura inicial das tabelas futuras.
- `docs/BANCO_DE_DADOS.md`: esta documentação.

## Tabelas previstas

O arquivo `nograuburger/database.sql` cria as seguintes tabelas:

| Tabela | Objetivo |
| --- | --- |
| `usuarios` | Usuários do painel administrativo, com senha em hash. |
| `lojas` | Dados cadastrais e visuais da loja/cardápio. |
| `categorias` | Categorias do cardápio. |
| `produtos` | Produtos do cardápio para uma migração futura. |
| `pedidos` | Cabeçalho dos pedidos. |
| `pedido_itens` | Itens vinculados a cada pedido. |
| `taxas_entrega` | Taxas por bairro. |
| `configuracoes_loja` | Configurações flexíveis da loja, como horários, pedido mínimo e upsell. |
| `logs_sistema` | Logs técnicos e administrativos para auditoria. |

## Requisitos atendidos

- Conexão via PDO.
- Charset `utf8mb4` no DSN e nas tabelas.
- Chaves primárias com `AUTO_INCREMENT`.
- Chaves estrangeiras onde há relacionamento claro entre tabelas.
- Campos `criado_em` e `atualizado_em` nas tabelas principais.
- Comentários explicativos no SQL.
- Arquivo de exemplo de conexão sem senha real.
- Nenhuma migração de produtos ou pedidos nesta fase.

## Configuração por variáveis de ambiente

O arquivo `nograuburger/config/conexao.php` lê estas variáveis:

```bash
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nograuburger
DB_USERNAME=nograuburger_user
DB_PASSWORD=sua_senha_local
```

Exemplo temporário no terminal Linux/macOS:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=nograuburger
export DB_USERNAME=nograuburger_user
export DB_PASSWORD='sua_senha_local'
```

Em hospedagens compartilhadas, configure esses valores no painel do provedor quando disponível, ou adapte uma cópia local baseada em `conexao.example.php` sem versionar credenciais reais.

## Como importar o `database.sql` no MariaDB/MySQL

### Opção 1 — Terminal

1. Crie o banco com charset `utf8mb4`:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS nograuburger CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

2. Importe o arquivo SQL:

```bash
mysql -u root -p nograuburger < nograuburger/database.sql
```

3. Opcionalmente, crie um usuário específico para a aplicação:

```bash
mysql -u root -p -e "CREATE USER IF NOT EXISTS 'nograuburger_user'@'localhost' IDENTIFIED BY 'troque_esta_senha'; GRANT ALL PRIVILEGES ON nograuburger.* TO 'nograuburger_user'@'localhost'; FLUSH PRIVILEGES;"
```

> Troque `troque_esta_senha` por uma senha segura no servidor. Não coloque essa senha no repositório.

### Opção 2 — phpMyAdmin

1. Acesse o phpMyAdmin.
2. Crie um banco chamado `nograuburger` com collation `utf8mb4_unicode_ci`.
3. Abra o banco criado.
4. Entre na aba **Importar**.
5. Selecione `nograuburger/database.sql`.
6. Execute a importação.

## Próxima fase sugerida

Na fase seguinte, o ideal é criar uma camada de repositório que consiga ler do JSON ou do banco de dados por configuração. Assim, a migração de produtos e pedidos pode ser feita gradualmente, sem interromper o cardápio em produção.

## Fase 3 — migração de produtos

A migração de categorias e produtos passa a ser feita pelo script:

```bash
php nograuburger/migrar_produtos.php
```

O script lê `data/produtos.json`, cria backup automático em `data/backups/` e importa/atualiza as tabelas `categorias` e `produtos` sem duplicar registros em execuções repetidas.

Mais detalhes estão em `docs/MIGRACAO_PRODUTOS.md`.

## Fase 4 — migração de pedidos

A migração de pedidos é feita pelo script:

```bash
php nograuburger/migrar_pedidos.php
```

O script lê `data/pedidos.json`, cria backup automático em `data/backups/` e importa/atualiza as tabelas `pedidos` e `pedido_itens` sem duplicar registros em execuções repetidas.

Mais detalhes estão em `docs/MIGRACAO_PEDIDOS.md`.
