# API do No Grau Burger — Fase 5

## Visão geral

A API fica isolada em `nograuburger/api/` para que o bot de atendimento consulte cardápio e envie pedidos sem misturar regras dentro das telas públicas ou do painel administrativo.

Todas as respostas são JSON e usam:

```http
Content-Type: application/json; charset=utf-8
```

## Autenticação e identificação da loja

Todos os endpoints exigem:

- `loja_id` **ou** `slug` da loja;
- `token` de segurança.

O token é lido da tabela `configuracoes_loja`, nas chaves `api_token` ou `token_api`.

Exemplo para configurar token e slug:

```sql
INSERT INTO configuracoes_loja (loja_id, chave, valor, tipo)
VALUES (1, 'api_token', 'troque-por-um-token-seguro', 'texto')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);

INSERT INTO configuracoes_loja (loja_id, chave, valor, tipo)
VALUES (1, 'slug', 'nograu', 'texto')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
```

O token pode ser enviado como parâmetro `token` ou no header:

```http
Authorization: Bearer troque-por-um-token-seguro
```

## Formato de erro

Exemplo padrão de erro:

```json
{
  "ok": false,
  "erro": {
    "codigo": "token_invalido",
    "mensagem": "Token de acesso inválido."
  }
}
```

Erros internos são registrados em `nograuburger/logs/api-AAAA-MM-DD.log` e não exibem senha, DSN ou stack trace ao cliente. A pasta `logs/` inclui bloqueio básico para Apache via `.htaccess`.

## Endpoints

### GET `/api/categorias.php`

Lista categorias ativas da loja.

**Parâmetros:**

| Nome | Obrigatório | Descrição |
| --- | --- | --- |
| `loja_id` ou `slug` | Sim | Identificador da loja. |
| `token` | Sim, se não usar Bearer | Token configurado em `configuracoes_loja`. |

**Exemplo:**

```text
/api/categorias.php?slug=nograu&token=troque-por-um-token-seguro
```

**Resposta:**

```json
{
  "ok": true,
  "loja": { "id": 1, "nome": "No Grau Burger" },
  "categorias": [
    { "id": 1, "titulo": "Lanches", "descricao": "", "ordem": 0, "ativo": 1 }
  ]
}
```

### GET `/api/produtos.php`

Lista produtos ativos da loja. Pode filtrar por categoria.

**Parâmetros:**

| Nome | Obrigatório | Descrição |
| --- | --- | --- |
| `loja_id` ou `slug` | Sim | Identificador da loja. |
| `token` | Sim, se não usar Bearer | Token configurado. |
| `categoria_id` | Não | Filtra produtos de uma categoria. |

**Exemplo:**

```text
/api/produtos.php?slug=nograu&categoria_id=1&token=troque-por-um-token-seguro
```

**Resposta:**

```json
{
  "ok": true,
  "produtos": [
    {
      "id": 10,
      "categoria_id": 1,
      "categoria": "Lanches",
      "nome": "Smash Burger",
      "preco": 24.9,
      "foto": "uploads/prod_smash.jpg",
      "ativo": 1
    }
  ]
}
```

### GET `/api/config_loja.php`

Retorna dados básicos da loja, configurações públicas e taxas de entrega. O token da API nunca é retornado.

**Parâmetros:** `loja_id` ou `slug`, `token`.

**Resposta:**

```json
{
  "ok": true,
  "loja": {
    "id": 1,
    "nome": "No Grau Burger",
    "whatsapp": "5519999999999"
  },
  "configuracoes": {
    "slug": { "valor": "nograu", "tipo": "texto" }
  },
  "taxas_entrega": [
    { "bairro": "Centro", "taxa": 5.0, "consultar": 0 }
  ]
}
```

### POST `/api/criar_pedido.php`

Cria pedido no banco para uso pelo bot.

**Corpo JSON:**

```json
{
  "slug": "nograu",
  "token": "troque-por-um-token-seguro",
  "cliente": {
    "nome": "Maria Silva",
    "telefone": "5519999999999"
  },
  "tipo_pedido": "Entrega",
  "bairro": "Centro",
  "endereco": "Rua Exemplo, 123",
  "referencia": "Casa azul",
  "pag_forma": "PIX",
  "troco": "",
  "obs": "Sem cebola",
  "taxa_entrega": "5,00",
  "itens": [
    { "produto_id": 10, "qtd": 2 },
    { "nome": "Produto manual", "qtd": 1, "preco": "12,00" }
  ]
}
```

**Resposta:**

```json
{
  "ok": true,
  "pedido": {
    "id": "api_663b1d9f2b1a0.12345678",
    "db_id": 45,
    "status": "novo",
    "subtotal": "61,80",
    "taxa_entrega": "5,00",
    "total_final": "66,80"
  }
}
```

### GET `/api/pedidos_novos.php`

Lista pedidos com status `novo` para o bot ou integrador.

**Parâmetros:**

| Nome | Obrigatório | Descrição |
| --- | --- | --- |
| `loja_id` ou `slug` | Sim | Identificador da loja. |
| `token` | Sim | Token da API. |
| `limite` | Não | Quantidade máxima, de 1 a 50. Padrão: 20. |

**Resposta:**

```json
{
  "ok": true,
  "pedidos": [
    {
      "id": "api_663b1d9f2b1a0.12345678",
      "status": "novo",
      "nome": "Maria Silva",
      "telefone": "5519999999999",
      "total_final": "66,80",
      "itens": [
        { "nome": "Smash Burger", "qtd": 2, "preco": 24.9 }
      ]
    }
  ]
}
```

### GET `/api/status_pedido.php`

Consulta status e dados de um pedido.

**Parâmetros:** `loja_id` ou `slug`, `token`, `pedido_id`.

**Exemplo:**

```text
/api/status_pedido.php?slug=nograu&token=troque-por-um-token-seguro&pedido_id=api_663b1d9f2b1a0.12345678
```

**Resposta:**

```json
{
  "ok": true,
  "pedido": {
    "id": "api_663b1d9f2b1a0.12345678",
    "status": "preparo",
    "impresso": 0,
    "total_final": "66,80"
  }
}
```

### POST `/api/atualizar_status.php`

Atualiza status de um pedido.

**Corpo JSON:**

```json
{
  "slug": "nograu",
  "token": "troque-por-um-token-seguro",
  "pedido_id": "api_663b1d9f2b1a0.12345678",
  "status": "finalizado",
  "impresso": 1
}
```

Status aceitos:

- `novo`
- `preparo`
- `finalizado`
- `cancelado`
- `em_impressao`

**Resposta:**

```json
{
  "ok": true,
  "pedido": {
    "id": "api_663b1d9f2b1a0.12345678",
    "db_id": 45,
    "status": "finalizado",
    "impresso": 1
  }
}
```

### Erro de JSON inválido

Quando um endpoint `POST` recebe um corpo que não é JSON válido, a API responde:

```json
{
  "ok": false,
  "erro": {
    "codigo": "json_invalido",
    "mensagem": "O corpo da requisição não é um JSON válido."
  }
}
```

## Exemplo de uso pelo bot

Fluxo sugerido:

1. Bot chama `GET /api/config_loja.php` para obter dados da loja e taxas.
2. Bot chama `GET /api/categorias.php` e `GET /api/produtos.php` para montar o cardápio na conversa.
3. Cliente escolhe itens.
4. Bot chama `POST /api/criar_pedido.php`.
5. Bot consulta `GET /api/status_pedido.php` quando o cliente pergunta pelo andamento.
6. Integrações internas podem chamar `POST /api/atualizar_status.php` para sincronizar status.

Exemplo com `curl`:

```bash
curl -X POST "https://seu-dominio/nograuburger/api/criar_pedido.php" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer troque-por-um-token-seguro" \
  -d '{
    "slug":"nograu",
    "cliente":{"nome":"Maria Silva","telefone":"5519999999999"},
    "tipo_pedido":"Entrega",
    "bairro":"Centro",
    "endereco":"Rua Exemplo, 123",
    "pag_forma":"PIX",
    "taxa_entrega":"5,00",
    "itens":[{"produto_id":10,"qtd":2}]
  }'
```

## Como testar no navegador ou Postman

### Navegador

Use endpoints `GET`, por exemplo:

```text
http://localhost:8080/api/produtos.php?slug=nograu&token=troque-por-um-token-seguro
```

### Postman/Insomnia

1. Selecione o método `POST`.
2. Informe a URL, por exemplo `http://localhost:8080/api/criar_pedido.php`.
3. Em Headers, use `Content-Type: application/json`.
4. Em Authorization, use Bearer Token com o token configurado.
5. Em Body, escolha raw JSON e envie o payload do endpoint.
