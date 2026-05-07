-- =========================================================
-- No Grau Burger - Estrutura inicial MariaDB/MySQL
-- Fase 2: preparação para banco de dados.
--
-- IMPORTANTE:
-- - O sistema ainda continua usando data/produtos.json e data/pedidos.json.
-- - Este script apenas cria a estrutura futura do banco.
-- - Charset padrão: utf8mb4 para suportar acentos, emojis e símbolos.
-- =========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------
-- Tabela: lojas
-- Guarda os dados principais de cada loja/cardápio.
-- Hoje essas informações ficam em data/produtos.json no bloco "loja".
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS lojas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    tipo VARCHAR(80) NULL,
    slogan VARCHAR(180) NULL,
    descricao TEXT NULL,
    cnpj VARCHAR(20) NULL,
    whatsapp VARCHAR(30) NULL,
    endereco VARCHAR(255) NULL,
    endereco_topo VARCHAR(255) NULL,
    endereco_curto VARCHAR(255) NULL,
    complemento VARCHAR(120) NULL,
    bairro VARCHAR(120) NULL,
    cidade VARCHAR(120) NULL,
    uf CHAR(2) NULL,
    cep VARCHAR(12) NULL,
    logo VARCHAR(255) NULL,
    banner VARCHAR(255) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Dados cadastrais das lojas/cardápios.';

-- ---------------------------------------------------------
-- Tabela: usuarios
-- Usuários do painel administrativo.
-- A senha deve ser armazenada como hash (ex.: password_hash do PHP).
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loja_id INT UNSIGNED NULL,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'operador') NOT NULL DEFAULT 'admin',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_login_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuarios_email (email),
    KEY idx_usuarios_loja (loja_id),
    CONSTRAINT fk_usuarios_loja FOREIGN KEY (loja_id) REFERENCES lojas(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Usuários autorizados a acessar o painel administrativo.';

-- ---------------------------------------------------------
-- Tabela: categorias
-- Categorias do cardápio, equivalentes aos itens de "categorias" no JSON.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loja_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_categorias_loja (loja_id),
    UNIQUE KEY uk_categorias_loja_titulo (loja_id, titulo),
    CONSTRAINT fk_categorias_loja FOREIGN KEY (loja_id) REFERENCES lojas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Categorias de produtos do cardápio.';

-- ---------------------------------------------------------
-- Tabela: produtos
-- Produtos do cardápio. A migração dos produtos ainda não foi feita.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS produtos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loja_id INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    nome VARCHAR(180) NOT NULL,
    descricao TEXT NULL,
    detalhe VARCHAR(255) NULL,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    preco_original DECIMAL(10,2) NULL,
    foto VARCHAR(255) NULL,
    imagem VARCHAR(255) NULL,
    destaque TINYINT(1) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_produtos_loja (loja_id),
    KEY idx_produtos_categoria (categoria_id),
    UNIQUE KEY uk_produtos_categoria_nome (categoria_id, nome),
    CONSTRAINT fk_produtos_loja FOREIGN KEY (loja_id) REFERENCES lojas(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Produtos do cardápio, preparados para futura migração do JSON.';

-- ---------------------------------------------------------
-- Tabela: pedidos
-- Cabeçalho dos pedidos. Hoje os pedidos continuam em data/pedidos.json.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedidos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loja_id INT UNSIGNED NOT NULL,
    codigo_publico VARCHAR(80) NULL,
    nome_cliente VARCHAR(150) NOT NULL,
    telefone VARCHAR(30) NULL,
    tipo_pedido ENUM('Entrega', 'Retirada no local') NOT NULL DEFAULT 'Entrega',
    bairro VARCHAR(120) NULL,
    endereco VARCHAR(255) NULL,
    referencia VARCHAR(255) NULL,
    pag_forma VARCHAR(60) NULL,
    troco VARCHAR(60) NULL,
    obs TEXT NULL,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    taxa_entrega DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ajuste_tipo ENUM('acrescimo', 'desconto') NULL,
    ajuste_valor DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ajuste_obs VARCHAR(255) NULL,
    total_final DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    entrega_consultar TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('novo', 'preparo', 'finalizado', 'cancelado', 'em_impressao') NOT NULL DEFAULT 'novo',
    impresso TINYINT(1) NOT NULL DEFAULT 0,
    recebido_em DATETIME NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_pedidos_codigo_publico (codigo_publico),
    KEY idx_pedidos_loja_status (loja_id, status),
    KEY idx_pedidos_criado_em (criado_em),
    CONSTRAINT fk_pedidos_loja FOREIGN KEY (loja_id) REFERENCES lojas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Pedidos recebidos pelo cardápio digital.';

-- ---------------------------------------------------------
-- Tabela: pedido_itens
-- Itens de cada pedido. produto_id é opcional para preservar histórico
-- mesmo se um produto for removido no futuro.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT UNSIGNED NOT NULL,
    produto_id INT UNSIGNED NULL,
    nome_produto VARCHAR(180) NOT NULL,
    quantidade INT UNSIGNED NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    observacao TEXT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_pedido_itens_pedido (pedido_id),
    KEY idx_pedido_itens_produto (produto_id),
    CONSTRAINT fk_pedido_itens_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_pedido_itens_produto FOREIGN KEY (produto_id) REFERENCES produtos(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Itens vinculados aos pedidos.';

-- ---------------------------------------------------------
-- Tabela: taxas_entrega
-- Taxas por bairro, equivalentes ao bloco taxas_entrega do JSON.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS taxas_entrega (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loja_id INT UNSIGNED NOT NULL,
    bairro VARCHAR(120) NOT NULL,
    taxa DECIMAL(10,2) NULL,
    consultar TINYINT(1) NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_taxas_entrega_loja_bairro (loja_id, bairro),
    CONSTRAINT fk_taxas_entrega_loja FOREIGN KEY (loja_id) REFERENCES lojas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Taxas de entrega por bairro.';

-- ---------------------------------------------------------
-- Tabela: configuracoes_loja
-- Configurações flexíveis da loja, como pedido mínimo, textos, horários,
-- referências de upsell e outros ajustes que hoje vivem no JSON.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracoes_loja (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loja_id INT UNSIGNED NOT NULL,
    chave VARCHAR(100) NOT NULL,
    valor TEXT NULL,
    tipo VARCHAR(40) NOT NULL DEFAULT 'texto',
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_configuracoes_loja_chave (loja_id, chave),
    KEY idx_configuracoes_chave_valor (chave, valor(100)),
    CONSTRAINT fk_configuracoes_loja FOREIGN KEY (loja_id) REFERENCES lojas(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configurações flexíveis por loja.';

-- ---------------------------------------------------------
-- Tabela: logs_sistema
-- Registro de eventos técnicos e administrativos para auditoria futura.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS logs_sistema (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    loja_id INT UNSIGNED NULL,
    usuario_id INT UNSIGNED NULL,
    nivel ENUM('info', 'aviso', 'erro') NOT NULL DEFAULT 'info',
    acao VARCHAR(120) NOT NULL,
    mensagem TEXT NULL,
    contexto JSON NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_logs_loja (loja_id),
    KEY idx_logs_usuario (usuario_id),
    KEY idx_logs_criado_em (criado_em),
    CONSTRAINT fk_logs_loja FOREIGN KEY (loja_id) REFERENCES lojas(id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_logs_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Logs de eventos do sistema e do painel administrativo.';

SET FOREIGN_KEY_CHECKS = 1;
