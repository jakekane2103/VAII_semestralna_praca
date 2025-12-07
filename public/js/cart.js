// File: public/js/cart.js
// Purpose: Combines cart-related (add-to-cart modal/UX) and wishlist behavior into one file.

// --------------------
// Cart / Add-to-cart module (from books.js)
// --------------------
(function(){
  document.addEventListener('DOMContentLoaded', function() {
    const cartUrl = window.BOOKS_CART_URL || null;
    const forms = document.querySelectorAll('form.js-add-to-cart');
    const modalEl = document.getElementById('addToCartModal');
    if (!forms.length || !modalEl || !cartUrl) {
      return;
    }

    const modalImg = document.getElementById('addToCartModalImg');
    const modalTitle = document.getElementById('addToCartModalTitle');
    const modalAuthor = document.getElementById('addToCartModalAuthor');
    const modalPrice = document.getElementById('addToCartModalPrice');
    const modalViewAll = document.getElementById('addToCartModalViewAll');
    const modalRemaining = document.getElementById('addToCartModalRemaining');
    const modalProgress = document.getElementById('addToCartModalProgress');
    const modalTotal = document.getElementById('addToCartModalTotal');
    const modalSavings = document.getElementById('addToCartModalSavings');
    const modalShippingText = document.getElementById('addToCartModalShippingText');
    const btnEdit = document.getElementById('addToCartModalEdit');
    const btnCheckout = document.getElementById('addToCartModalCheckout');

    let bootstrapModal = null;
    if (window.bootstrap && modalEl) {
      bootstrapModal = new window.bootstrap.Modal(modalEl);
    }

    // Helper: normalize string (remove diacritics, lower-case)
    function normalizeForMatch(s) {
      if (!s) return '';
      try {
        return s.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
      } catch (e) {
        return (s || '').toLowerCase();
      }
    }

    // Keep reference to last submit button and a safety timeout to ensure cleanup
    let lastSubmitBtn = null;
    let cleanupTimeout = null;

    function cleanupAfterModal() {
      if (cleanupTimeout) { clearTimeout(cleanupTimeout); cleanupTimeout = null; }
      try { if (bootstrapModal && typeof bootstrapModal.hide === 'function') bootstrapModal.hide(); } catch (e) {}
      document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.parentNode && el.parentNode.removeChild(el); });
      document.body && document.body.classList && document.body.classList.remove('modal-open');

      // Restore add-to-cart buttons
      var addBtns = document.querySelectorAll('form.js-add-to-cart button[type="submit"]');
      addBtns.forEach(function(b){
        try {
          b.disabled = false;
          var lbl = b.querySelector && b.querySelector('.btn-label') ? b.querySelector('.btn-label') : null;
          if (b.dataset && b.dataset.originalLabel) {
            if (lbl) lbl.textContent = b.dataset.originalLabel;
            else b.innerHTML = b.dataset.originalLabel;
            delete b.dataset.originalLabel;
          }
        } catch(e) { }
      });

      try {
        if (lastSubmitBtn) {
          if (document.body.contains(lastSubmitBtn)) {
            lastSubmitBtn.disabled = false;
            if (lastSubmitBtn.dataset && lastSubmitBtn.dataset.originalLabel) {
              lastSubmitBtn.innerHTML = lastSubmitBtn.dataset.originalLabel;
              delete lastSubmitBtn.dataset.originalLabel;
            } else {
              lastSubmitBtn.innerHTML = 'Do košíka';
            }
          }
          lastSubmitBtn = null;
        }
      } catch (e) { }

      // Fallback: detect stuck labels by normalized text
      try {
        addBtns.forEach(function(b){
          try {
            var lblEl = b.querySelector && b.querySelector('.btn-label') ? b.querySelector('.btn-label') : null;
            var txt = lblEl ? (lblEl.textContent || '') : (b.innerText || b.textContent || '');
            var n = normalizeForMatch(txt).trim();
            if (n.indexOf('prid') !== -1) {
              b.disabled = false;
              if (b.dataset && b.dataset.originalLabel) {
                if (lblEl) lblEl.textContent = b.dataset.originalLabel; else b.innerHTML = b.dataset.originalLabel;
                delete b.dataset.originalLabel;
              } else { if (lblEl) lblEl.textContent = 'Do košíka'; else b.innerHTML = 'Do košíka'; }
            }
          } catch(e) { }
        });
      } catch (e) { }

      // Broader fallback across all buttons
      try {
        var allButtons = document.querySelectorAll('button');
        allButtons.forEach(function(btn){
          try {
            var lblEl = btn.querySelector && btn.querySelector('.btn-label') ? btn.querySelector('.btn-label') : null;
            var t = lblEl ? (lblEl.textContent || '') : (btn.innerText || btn.textContent || '');
            var nn = normalizeForMatch(t).trim();
            if (nn.indexOf('prid') !== -1) {
              btn.disabled = false;
              if (btn.dataset && btn.dataset.originalLabel) {
                if (lblEl) lblEl.textContent = btn.dataset.originalLabel; else btn.innerHTML = btn.dataset.originalLabel;
                delete btn.dataset.originalLabel;
              } else { if (lblEl) lblEl.textContent = 'Do košíka'; else btn.innerHTML = 'Do košíka'; }
            }
          } catch (e) { }
        });
      } catch (e) { }

      if (btnCheckout) btnCheckout.disabled = false;
    }

    function formatCurrency(val) {
      if (isNaN(val)) return val + ' €';
      return Number(val).toFixed(2).replace('.', ',') + ' €';
    }

    function handleFallbackSubmit(form, submitHandler) {
      form.removeEventListener('submit', submitHandler);
      form.submit();
    }

    const submitHandler = async function(e) {
      e.preventDefault();
      const form = e.currentTarget;
      const formData = new FormData(form);

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalBtnHTML = submitBtn ? submitBtn.innerHTML : null;
      const labelSpan = submitBtn ? submitBtn.querySelector('.btn-label') : null;
      try { if (submitBtn && submitBtn.dataset) submitBtn.dataset.originalLabel = labelSpan ? (labelSpan.textContent || '') : (originalBtnHTML || 'Do košíka'); } catch(e) {}
      lastSubmitBtn = submitBtn;
      if (submitBtn) {
        submitBtn.disabled = true;
        if (labelSpan) labelSpan.textContent = 'Pridávam...'; else submitBtn.innerHTML = 'Pridávam...';
      }

      if (btnCheckout) btnCheckout.disabled = true;

      try {
        const response = await fetch(form.action, { method: form.method || 'POST', body: formData, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!response.ok) { handleFallbackSubmit(form, submitHandler); return; }

        let json;
        try { json = await response.json(); } catch (e) { json = null; }

        const title = form.dataset.bookTitle || '';
        const author = form.dataset.bookAuthor || '';
        const img = form.dataset.bookImage || '';
        const priceStr = form.dataset.bookPrice || '';
        const singlePrice = parseFloat(priceStr.replace(',', '.')) || 0;

        let cartTotal = singlePrice;
        if (json && typeof json.cartTotal !== 'undefined') {
          const parsedTotal = parseFloat(json.cartTotal);
          if (!isNaN(parsedTotal)) cartTotal = parsedTotal;
        }

        if (modalImg) { modalImg.src = img; modalImg.alt = title; }
        if (modalTitle) modalTitle.textContent = title;
        if (modalAuthor) modalAuthor.textContent = author;
        if (modalPrice) modalPrice.textContent = singlePrice ? formatCurrency(singlePrice) : '';
        if (modalViewAll) modalViewAll.href = cartUrl;

        const goal = 49.00;
        const remainingRaw = goal - cartTotal;
        const remaining = Math.max(0, remainingRaw);
        const pct = Math.min(100, Math.round((cartTotal / goal) * 100));

        if (modalShippingText && modalRemaining) {
          if (remainingRaw <= 0) { modalShippingText.textContent = 'V tomto nákupe máte dopravu zadarmo.'; }
          else { modalShippingText.innerHTML = 'Nakúpte ešte za <strong id="addToCartModalRemaining"></strong> a dopravu do výdajných miest máte zadarmo.'; const remainingNode = document.getElementById('addToCartModalRemaining'); if (remainingNode) remainingNode.textContent = formatCurrency(remaining); }
        }
        if (modalProgress) modalProgress.style.width = pct + '%';
        if (modalTotal) modalTotal.textContent = formatCurrency(cartTotal);
        if (modalSavings) { const savings = cartTotal * 0.18; modalSavings.textContent = savings > 0 ? 'U nás ušetríte ' + formatCurrency(savings) : ''; }

        if (bootstrapModal) {
          bootstrapModal.show();
          if (cleanupTimeout) clearTimeout(cleanupTimeout);
          cleanupTimeout = setTimeout(function(){ cleanupAfterModal(); }, 6000);
        } else {
          const go = window.confirm(title + ' bol(a) pridaná do košíka. Chcete ísť do košíka?'); if (go) window.location.href = cartUrl;
        }

        if (btnEdit) btnEdit.onclick = function(){ cleanupAfterModal(); };
        if (btnCheckout) btnCheckout.onclick = function(){ window.location.href = cartUrl; };

      } catch (err) {
        handleFallbackSubmit(form, submitHandler);
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          try {
            var originalLabel = submitBtn.dataset && submitBtn.dataset.originalLabel ? submitBtn.dataset.originalLabel : null;
            if (labelSpan) { if (originalLabel !== null) labelSpan.textContent = originalLabel; else labelSpan.textContent = 'Do košíka'; }
            else { if (originalLabel !== null) submitBtn.innerHTML = originalLabel; else if (originalBtnHTML !== null) submitBtn.innerHTML = originalBtnHTML; else submitBtn.innerHTML = 'Do košíka'; }
            try { if (submitBtn.dataset) delete submitBtn.dataset.originalLabel; } catch (e) {}
          } catch (e) {}
          if (lastSubmitBtn === submitBtn) lastSubmitBtn = null;
        }
        if (btnCheckout) btnCheckout.disabled = false;
      }
    };

    if (modalEl) {
      try {
        modalEl.addEventListener('hidden.bs.modal', function () { cleanupAfterModal(); });
        modalEl.addEventListener('hide.bs.modal', function () { cleanupAfterModal(); });
        modalEl.addEventListener('click', function(e){ try { if (e.target && e.target.closest && e.target.closest('[data-bs-dismiss]')) cleanupAfterModal(); } catch(err){} });
        var headerClose = modalEl.querySelector('.btn-close'); if (headerClose) headerClose.addEventListener('click', function(){ cleanupAfterModal(); });
      } catch (e) { }
    }

    forms.forEach(function(f) { f.addEventListener('submit', submitHandler); });
  });
})();

// --------------------
// Wishlist module (from wishlist.js)
// --------------------
(function () {
    if (!window.fetch) return;

    function postForm(url, fd) {
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error('Network response was not ok'); return r.json().catch(function () { return { success: true }; }); });
    }

    function postJson(url, obj) {
        return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(obj) }).then(function (r) { if (!r.ok) throw new Error('Network response was not ok'); return r.json().catch(function () { return { success: true }; }); });
    }

    function handleAction(e, url, onSuccess) {
        e.preventDefault();
        var form = e.currentTarget;
        var idInput = form.querySelector('input[name="id"]');
        var id = idInput ? idInput.value : null;
        var fd = new FormData(form);

        postForm(url, fd)
            .then(function (data) {
                if (data && data.success === false) throw new Error(data.message || 'Action failed');
                onSuccess && onSuccess(id, data);
            })
            .catch(function (err) { console.error(err); try { alert('Action failed. Please try again.'); } catch (e) {} });
    }

    function handleHeartClick(e) {
        e.preventDefault();
        var btn = e.currentTarget;
        var bookId = btn.getAttribute('data-book-id');
        if (!bookId) return;

        var url = window.WISHLIST_ADD_URL || (document.body && document.body.dataset && document.body.dataset.wishlistAddUrl) || '/wishlist/add';
        var fd = new FormData(); fd.append('id', bookId);

        var wasOutline = btn.classList.contains('btn-outline-danger');
        var wasPressed = btn.getAttribute('aria-pressed') === 'true';

        if (wasOutline) { btn.classList.remove('btn-outline-danger'); btn.classList.add('btn-danger'); btn.setAttribute('aria-pressed', 'true'); }
        else { btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-danger'); btn.setAttribute('aria-pressed', 'false'); }

        postForm(url, fd)
            .then(function (data) {
                if (data && data.success === false) throw new Error(data.message || 'Action failed');
                if (data && data.item && data.item.id) {
                    var resolved = String(data.item.id);
                    btn.setAttribute('data-book-id', resolved);
                    var form = btn.closest('form');
                    if (form) { var input = form.querySelector('input[name="id"]'); if (input) input.value = resolved; }
                }
            })
            .catch(function (err) {
                console.error(err);
                if (wasOutline) { btn.classList.remove('btn-danger'); btn.classList.add('btn-outline-danger'); btn.setAttribute('aria-pressed', wasPressed ? 'true' : 'false'); }
                else { btn.classList.remove('btn-outline-danger'); btn.classList.add('btn-danger'); btn.setAttribute('aria-pressed', wasPressed ? 'true' : 'false'); }
                try { alert('Neúspech pri pridávaní do wishlistu. Skúste znova.'); } catch (e) {}
            });
    }

    function enableDragToReorder(container, reorderUrl) {
        if (!container || !reorderUrl) return;
        var dragSrcEl = null;
        function handleDragStart(e) { this.classList.add('dragging'); dragSrcEl = this; e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', this.dataset.id); } catch (err) {} }
        function handleDragOver(e) { if (e.preventDefault) e.preventDefault(); e.dataTransfer.dropEffect = 'move'; var target = e.currentTarget; var bounding = target.getBoundingClientRect(); var offset = bounding.y + (bounding.height / 2); var after = (e.clientY > offset); var parent = target.parentNode; if (after) { if (target.nextSibling !== dragSrcEl) parent.insertBefore(dragSrcEl, target.nextSibling); } else { if (target !== dragSrcEl && target.previousSibling !== dragSrcEl) parent.insertBefore(dragSrcEl, target); } updateRanks(container); return false; }
        function handleDragEnd(e) { this.classList.remove('dragging'); sendOrderToServer(container, reorderUrl); }
        function addDnDHandlers(item) { item.setAttribute('draggable', 'true'); item.addEventListener('dragstart', handleDragStart, false); item.addEventListener('dragover', handleDragOver, false); item.addEventListener('dragend', handleDragEnd, false); }
        var items = Array.prototype.slice.call(container.querySelectorAll('.wishlist-row'));
        items.forEach(function (it) { addDnDHandlers(it); });
    }

    function updateRanks(container) { var rows = container.querySelectorAll('.wishlist-row'); rows.forEach(function (row, idx) { var rankEl = row.querySelector('.wishlist-rank'); if (rankEl) rankEl.textContent = (idx + 1) + '.'; }); }

    function sendOrderToServer(container, reorderUrl) { var rows = container.querySelectorAll('.wishlist-row'); var order = []; rows.forEach(function (row) { order.push(row.dataset.id); }); container.classList.add('saving'); return postJson(reorderUrl, { order: order }).then(function (data) { if (data && data.success === false) throw new Error(data.message || 'Reorder failed'); if (data && Array.isArray(data.order)) { var byId = {}; var current = Array.prototype.slice.call(container.querySelectorAll('.wishlist-row')); current.forEach(function (r) { byId[r.dataset.id] = r; }); while (container.firstChild) container.removeChild(container.firstChild); data.order.forEach(function (id) { if (byId[id]) container.appendChild(byId[id]); }); updateRanks(container); } }).catch(function (err) { console.error(err); try { alert('Nepodarilo sa uložiť poradie. Skúste to znova.'); } catch (e) {} }).finally(function () { container.classList.remove('saving'); }); }

    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('#wishlist-grid form');
        forms.forEach(function (form) {
            var action = form.getAttribute('action') || '';
            var isMove = action.endsWith('/moveToCart') || action.indexOf('moveToCart') !== -1;
            var isRemove = action.endsWith('/remove') || action.indexOf('remove') !== -1;

            if (isMove || isRemove) {
                form.addEventListener('submit', function (e) { handleAction(e, form.getAttribute('action'), function (id) { var el = document.querySelector('.book-card[data-id="' + id + '"]') || document.querySelector('.wishlist-row[data-id="' + id + '"]'); if (el) el.remove(); }); });
            }
        });

        var heartBtns = document.querySelectorAll('.btn-wishlist');
        heartBtns.forEach(function (btn) { btn.addEventListener('click', handleHeartClick); });

        var wishlistGrid = document.getElementById('wishlist-grid');
        var reorderUrl = window.WISHLIST_REORDER_URL || (document.body && document.body.dataset && document.body.dataset.wishlistReorderUrl) || '/wishlist/reorder';
        if (wishlistGrid) { enableDragToReorder(wishlistGrid, reorderUrl); updateRanks(wishlistGrid); }
    });
})();

