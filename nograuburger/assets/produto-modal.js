// assets/produto-modal.js
(function () {
  function $(sel)   { return document.querySelector(sel); }
  function $all(sel){ return document.querySelectorAll(sel); }

  const modalProduto    = $('#modalProduto');
  if (!modalProduto) return; // segurança

  const mpImg           = $('#mpImg');
  const mpNome          = $('#mpNome');
  const mpDesc          = $('#mpDesc');
  const mpDetalhe       = $('#mpDetalhe');
  const mpPreco         = $('#mpPreco');
  const mpPrecoOriginal = $('#mpPrecoOriginal');
  const mpId            = $('#mpId');
  const mpNomeInput     = $('#mpNomeInput');
  const mpPrecoInput    = $('#mpPrecoInput');
  const mpQtdInput      = $('#mpQtdInput');
  const mpQtdSpan       = $('#mpQtd');
  const mpTotalBtn      = $('#mpTotalBtn');
  const mpFechar        = $('#mpFechar');
  const mpMais          = $('#mpMais');
  const mpMenos         = $('#mpMenos');

  let precoAtual = 0;
  let qtdAtual   = 1;

  function formatMoney(v) {
    return 'R$ ' + v.toFixed(2).replace('.', ',');
  }

  function atualizarTotal() {
    if (qtdAtual < 1) qtdAtual = 1;
    mpQtdSpan.textContent   = qtdAtual;
    mpQtdInput.value        = qtdAtual;
    mpTotalBtn.textContent  = formatMoney(precoAtual * qtdAtual);
  }

  // Clique no card do produto abre o modal
  $all('.card-produto').forEach(card => {
    card.addEventListener('click', () => {
      const id       = card.dataset.id || '';
      const nome     = card.dataset.nome || '';
      const desc     = card.dataset.desc || '';
      const detalhe  = card.dataset.detalhe || '';
      const preco    = parseFloat(card.dataset.preco || '0');
      const precoOri = parseFloat(card.dataset.precoOriginal || '0');
      const foto     = card.dataset.foto || '';

      precoAtual = preco;
      qtdAtual   = 1;

      mpId.value         = id;
      mpNome.textContent = nome;
      mpNomeInput.value  = nome;
      mpDesc.textContent = desc;
      mpDetalhe.textContent = detalhe;
      mpPreco.textContent = formatMoney(preco);

      if (!isNaN(precoOri) && precoOri > preco) {
        mpPrecoOriginal.style.display = 'inline';
        mpPrecoOriginal.textContent   = formatMoney(precoOri);
      } else {
        mpPrecoOriginal.style.display = 'none';
      }

      if (foto) {
        mpImg.src = foto;
        mpImg.style.display = 'block';
      } else {
        mpImg.style.display = 'none';
      }

      mpPrecoInput.value = preco.toFixed(2);
      atualizarTotal();

      modalProduto.style.display = 'flex';
    });
  });

  // + e − quantidade
  if (mpMais) {
    mpMais.addEventListener('click', () => {
      qtdAtual++;
      atualizarTotal();
    });
  }

  if (mpMenos) {
    mpMenos.addEventListener('click', () => {
      if (qtdAtual > 1) {
        qtdAtual--;
        atualizarTotal();
      }
    });
  }

  // Fechar modal
  if (mpFechar) {
    mpFechar.addEventListener('click', () => {
      modalProduto.style.display = 'none';
    });
  }

  modalProduto.addEventListener('click', (e) => {
    if (e.target === modalProduto) modalProduto.style.display = 'none';
  });
})();
