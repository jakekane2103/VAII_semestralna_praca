// File: public/js/books.js
// Purpose: Handles "add to cart" interactions on book listing/detail pages.
// - Attaches submit handlers to forms with class `js-add-to-cart`.
// - Shows a Bootstrap modal with cart totals and shipping progress after successful adds.
// - Functions below are referenced from the event listeners in this file and are necessary.
(function(){
  // DOM ready handler: initializes variables and event listeners.
  // Used: runs on DOMContentLoaded to wire up add-to-cart forms and modal.
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

    // Keep reference to last submit button and a safety timeout to ensure cleanup
    let lastSubmitBtn = null;
    let cleanupTimeout = null;

    // cleanupAfterModal
    // Purpose: Ensure the modal/backdrop are fully removed and any disabled add-to-cart buttons are restored.
    // - This defends against cases where the backdrop remains or a button stays disabled (user-reported freeze).
    // Used: bound to the "continue shopping" button and modal hidden event.
    function cleanupAfterModal() {
      // Clear safety timeout if set
      if (cleanupTimeout) {
        clearTimeout(cleanupTimeout);
        cleanupTimeout = null;
      }
      // Use bootstrap API to hide if available
      try {
        if (bootstrapModal && typeof bootstrapModal.hide === 'function') {
          bootstrapModal.hide();
        }
      } catch (err) {
        // ignore
      }

      // Remove any stray modal-backdrop elements and ensure body no longer blocks scroll
      document.querySelectorAll('.modal-backdrop').forEach(function(el){ el.parentNode && el.parentNode.removeChild(el); });
      document.body && document.body.classList && document.body.classList.remove('modal-open');

      // Re-enable all add-to-cart submit buttons and restore original label if stored
      var addBtns = document.querySelectorAll('form.js-add-to-cart button[type="submit"]');
      addBtns.forEach(function(b){
        try {
          b.disabled = false;
          var lbl = b.querySelector && b.querySelector('.btn-label') ? b.querySelector('.btn-label') : null;
          if (b.dataset && b.dataset.originalLabel) {
            if (lbl) lbl.textContent = b.dataset.originalLabel;
            else b.innerHTML = b.dataset.originalLabel;
            delete b.dataset.originalLabel;
            console.debug('Restored add-to-cart button label from dataset for', b);
          }
        } catch(e) { /* ignore */ }
      });

      // Specific restore for last submit button if it still exists
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
      } catch (e) { /* ignore */ }

      // Fallback: if any add-to-cart buttons are still stuck showing 'Pridávam' (ellipsis variants), force-restore them
      try {
        addBtns.forEach(function(b){
          try {
            var lblEl = b.querySelector && b.querySelector('.btn-label') ? b.querySelector('.btn-label') : null;
            var txt = lblEl ? (lblEl.textContent || '') : (b.innerText || b.textContent || '');
            var n = normalizeForMatch(txt).trim();
            if (n.indexOf('prid') !== -1) {
              console.debug('Fallback restore: found stuck button text', txt, b);
              b.disabled = false;
              if (b.dataset && b.dataset.originalLabel) {
                if (lblEl) lblEl.textContent = b.dataset.originalLabel;
                else b.innerHTML = b.dataset.originalLabel;
                delete b.dataset.originalLabel;
              } else {
                if (lblEl) lblEl.textContent = 'Do košíka';
                else b.innerHTML = 'Do košíka';
              }
            }
          } catch(e) { /* ignore per-button */ }
        });
       } catch (e) { /* ignore */ }

       // Broader fallback: scan all buttons on page and restore any that still show 'Pridáv' (covers edge cases)
       try {
         var allButtons = document.querySelectorAll('button');
         allButtons.forEach(function(btn){
           try {
            var lblEl = btn.querySelector && btn.querySelector('.btn-label') ? btn.querySelector('.btn-label') : null;
            var t = lblEl ? (lblEl.textContent || '') : (btn.innerText || btn.textContent || '');
            var nn = normalizeForMatch(t).trim();
            if (nn.indexOf('prid') !== -1) {
               console.debug('Global fallback restore for button', t, btn);
               btn.disabled = false;
               if (btn.dataset && btn.dataset.originalLabel) {
                 if (lblEl) lblEl.textContent = btn.dataset.originalLabel;
                 else btn.innerHTML = btn.dataset.originalLabel;
                 delete btn.dataset.originalLabel;
               } else {
                 if (lblEl) lblEl.textContent = 'Do košíka';
                 else btn.innerHTML = 'Do košíka';
               }
             }
           } catch (e) { /* ignore per-button errors */ }
         });
       } catch (e) { /* ignore */ }

      // Re-enable checkout button if present
      if (btnCheckout) {
        btnCheckout.disabled = false;
      }
    }

    // formatCurrency
    // Purpose: Format a numeric value as a Euro currency string (X,XX €).
    // Input: val - number or numeric string. Output: localized string.
    // Used: by the modal rendering code below to show prices.
    function formatCurrency(val) {
      if (isNaN(val)) return val + ' €';
      return Number(val).toFixed(2).replace('.', ',') + ' €';
    }

    // handleFallbackSubmit
    // Purpose: If the AJAX add-to-cart fails, remove the JS submit handler and fall back
    // to normal form submission so the server can handle the request with a full page load.
    // Input: form - the <form> element, submitHandler - the function to remove.
    // Used: called from submitHandler on network errors.
    function handleFallbackSubmit(form, submitHandler) {
      form.removeEventListener('submit', submitHandler);
      form.submit();
    }

    // submitHandler
    // Purpose: Main async handler for "add to cart" form submission.
    // - Prevents default submit, sends AJAX request, updates modal with results,
    //   shows Bootstrap modal, and restores button state afterwards.
    // Inputs (via closure / DOM): form element (e.currentTarget), dataset attributes for title/price/image.
    // Outputs: updates modal DOM and may navigate to cart when user clicks checkout.
    // Used: attached to each form.js-add-to-cart in the forms.forEach below.
    const submitHandler = async function(e) {
      e.preventDefault();
      const form = e.currentTarget;
      const formData = new FormData(form);

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalBtnHTML = submitBtn ? submitBtn.innerHTML : null;
      // Prefer a nested .btn-label span so we only change the visible label and preserve icons/markup
      const labelSpan = submitBtn ? submitBtn.querySelector('.btn-label') : null;
      // Persist original label text in dataset.originalLabel on the button (so cleanup can restore it)
      try { if (submitBtn && submitBtn.dataset) submitBtn.dataset.originalLabel = labelSpan ? (labelSpan.textContent || '') : (originalBtnHTML || 'Do košíka'); } catch(e) {}
      // Remember the last button used so cleanup can target it if the button reference is lost elsewhere
      lastSubmitBtn = submitBtn;
      if (submitBtn) {
        submitBtn.disabled = true;
        // Update only the visible label if present, otherwise fallback to replacing innerHTML (last resort)
        if (labelSpan) {
          labelSpan.textContent = 'Pridávam...';
        } else {
          submitBtn.innerHTML = 'Pridávam...';
        }
      }

      // Prevent navigating to cart while add request is ongoing
      if (btnCheckout) {
        btnCheckout.disabled = true;
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
        let json;
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
          // Safety: ensure cleanup runs after X ms in case modal lifecycle or other code blocks it.
          if (cleanupTimeout) clearTimeout(cleanupTimeout);
          cleanupTimeout = setTimeout(function(){
            console.debug('cleanupAfterModal triggered by safety timeout');
            cleanupAfterModal();
          }, 6000);
        } else {
          const go = window.confirm(title + ' bol(a) pridaná do košíka. Chcete ísť do košíka?');
          if (go) window.location.href = cartUrl;
        }

        // Ensure the "continue shopping" button closes the modal and runs cleanup actions.
        if (btnEdit) {
          btnEdit.onclick = function(){
            cleanupAfterModal();
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
          // Restore label from dataset.originalLabel if present
          try {
            var originalLabel = submitBtn.dataset && submitBtn.dataset.originalLabel ? submitBtn.dataset.originalLabel : null;
            if (labelSpan) {
              if (originalLabel !== null) labelSpan.textContent = originalLabel;
              else labelSpan.textContent = 'Do košíka';
            } else {
              // last-resort: restore stored HTML or fallback text
              if (originalLabel !== null) submitBtn.innerHTML = originalLabel;
              else if (originalBtnHTML !== null) submitBtn.innerHTML = originalBtnHTML;
              else submitBtn.innerHTML = 'Do košíka';
            }
            // remove stored original label
            try { if (submitBtn.dataset) delete submitBtn.dataset.originalLabel; } catch (e) {}
          } catch (e) { /* ignore restore errors */ }
          // Clear lastSubmitBtn since we've restored it here
          if (lastSubmitBtn === submitBtn) lastSubmitBtn = null;
        }
        // Re-enable the checkout button after the add request completes
        if (btnCheckout) {
          btnCheckout.disabled = false;
        }
      }
    };

    // Bind the cleanup function to Bootstrap modal hidden event (fires when the modal is fully hidden)
    if (modalEl) {
      try {
        modalEl.addEventListener('hidden.bs.modal', function () { cleanupAfterModal(); });
        // Also bind on hide (start of hiding) to be more resilient
        modalEl.addEventListener('hide.bs.modal', function () { cleanupAfterModal(); });
        // If user clicks any element inside the modal that has data-bs-dismiss, run cleanup as well
        modalEl.addEventListener('click', function(e){
          try {
            if (e.target && e.target.closest && e.target.closest('[data-bs-dismiss]')) {
              cleanupAfterModal();
            }
          } catch (err) { /* ignore */ }
        });
        // Ensure the modal header close button triggers cleanup (defensive)
        var headerClose = modalEl.querySelector('.btn-close');
        if (headerClose) headerClose.addEventListener('click', function(){ cleanupAfterModal(); });
      } catch (e) {
        // ignore if event cannot be bound
      }
    }

    // Attach submitHandler to each add-to-cart form
    // Used: iteration attaches event listeners to forms collected above.
    forms.forEach(function(f) {
      f.addEventListener('submit', submitHandler);
    });
  });
 })();
