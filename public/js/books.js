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
      const originalBtnText = submitBtn ? submitBtn.innerHTML : null;
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Pridávam...';
      }

      try {
        const response = await fetch(form.action, {
          method: form.method || 'POST',
          body: formData,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!response.ok) {
          handleFallbackSubmit(form, submitHandler);
          return;
        }

        // Try to read JSON response with full cart total
        let json = null;
        try {
          json = await response.json();
        } catch (e) {
          json = null;
        }

        const title = form.dataset.bookTitle || '';
        const author = form.dataset.bookAuthor || '';
        const img = form.dataset.bookImage || '';
        const priceStr = form.dataset.bookPrice || '';
        const singlePrice = parseFloat(priceStr.replace(',', '.')) || 0;

        // Prefer cartTotal from server (full cart sum); fall back to single book price if missing
        let cartTotal = singlePrice;
        if (json && typeof json.cartTotal !== 'undefined') {
          const parsedTotal = parseFloat(json.cartTotal);
          if (!isNaN(parsedTotal)) {
            cartTotal = parsedTotal;
          }
        }

        if (modalImg) {
          modalImg.src = img;
          modalImg.alt = title;
        }
        if (modalTitle) modalTitle.textContent = title;
        if (modalAuthor) modalAuthor.textContent = author;
        // Keep this as the single book price the user just added
        if (modalPrice) modalPrice.textContent = singlePrice ? formatCurrency(singlePrice) : '';
        if (modalViewAll) modalViewAll.href = cartUrl;

        const goal = 49.00;
        const remainingRaw = goal - cartTotal;
        const remaining = Math.max(0, remainingRaw);
        const pct = Math.min(100, Math.round((cartTotal / goal) * 100));

        if (modalShippingText && modalRemaining) {
          if (remainingRaw <= 0) {
            // Replace whole sentence with free-shipping message
            modalShippingText.textContent = 'V tomto nákupe máte dopravu zadarmo.';
          } else {
            // Restore original sentence structure and update only the number
            modalShippingText.innerHTML = 'Nakúpte ešte za <strong id="addToCartModalRemaining"></strong> a dopravu do výdajných miest máte zadarmo.';
            const remainingNode = document.getElementById('addToCartModalRemaining');
            if (remainingNode) {
              remainingNode.textContent = formatCurrency(remaining);
            }
          }
        }
        if (modalProgress) modalProgress.style.width = pct + '%';
        if (modalTotal) modalTotal.textContent = formatCurrency(cartTotal);
        if (modalSavings) {
          const savings = cartTotal * 0.18;
          modalSavings.textContent = savings > 0 ? 'U nás ušetríte ' + formatCurrency(savings) : '';
        }

        if (bootstrapModal) {
          bootstrapModal.show();
        } else {
          const go = window.confirm(title + ' bol(a) pridaná do košíka. Chcete ísť do košíka?');
          if (go) window.location.href = cartUrl;
        }

        if (btnEdit) {
          btnEdit.onclick = function(){
            if (bootstrapModal) bootstrapModal.hide();
          };
        }
        if (btnCheckout) {
          btnCheckout.onclick = function(){ window.location.href = cartUrl; };
        }

      } catch (err) {
        handleFallbackSubmit(form, submitHandler);
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.innerHTML = originalBtnText;
        }
      }
    };

    forms.forEach(function(f) {
      f.addEventListener('submit', submitHandler);
    });
  });
})();
