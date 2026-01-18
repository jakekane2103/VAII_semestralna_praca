// File: public/js/auth.js
// Purpose: Authentication-related UI helpers (password toggle, login modal show & autofocus).

(function(){
  // Small internal logger (no-op when console isn't available)
  function log() { if (typeof console !== 'undefined' && console.log) console.log.apply(console, arguments); }

  document.addEventListener('DOMContentLoaded', function () {
    // Helper to toggle a password input and icon
    function togglePasswordForButton(btn) {
      if (!btn) return;
      var inputId = btn.getAttribute('data-target') || '';
      if (!inputId) return;
      if (inputId.charAt(0) === '#') inputId = inputId.slice(1);
      var input = document.getElementById(inputId);
      var icon = btn.querySelector('i');
      if (!input) {
        // Fallback: look for an input inside the same input-group as the button
        var group = btn.closest('.input-group');
        if (group) {
          input = group.querySelector('input[type="password"], input[type="text"], input[type="email"]');
        }
        if (!input) { log('auth.js: target input not found for', inputId, btn); return; }
      }
      try {
        if (input.type === 'password') {
          input.type = 'text';
          if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
          btn.setAttribute('aria-pressed', 'true');
        } else {
          input.type = 'password';
          if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
          btn.setAttribute('aria-pressed', 'false');
        }
      } catch (e) {
        log('auth.js: error toggling password visibility', e);
      }
    }

    // 1) Attach direct handlers to any toggle buttons (improves reliability)
    var toggleButtons = document.querySelectorAll("[data-toggle='password']");
    toggleButtons.forEach(function(btn) {
      // click
      btn.addEventListener('click', function(ev) {
        ev.preventDefault();
        togglePasswordForButton(btn);
      });

      // initialize aria-pressed for accessibility
      if (!btn.hasAttribute('aria-pressed')) btn.setAttribute('aria-pressed', 'false');
    });

    // Also keep a delegated listener as a fallback for dynamically injected controls
    document.addEventListener('click', function (ev) {
      var btn = ev.target.closest && ev.target.closest("[data-toggle='password']");
      if (!btn) return;
      ev.preventDefault();
      togglePasswordForButton(btn);
    });

    // Login modal handling
    var loginModalEl = document.getElementById('loginModal');
    if (loginModalEl) {
      var loginEmailInput = document.getElementById('email');

      // autofocus when modal is shown
      try {
        loginModalEl.addEventListener('shown.bs.modal', function () {
          if (loginEmailInput) {
            try { loginEmailInput.focus(); } catch(e) { /* ignore */ }
          }
        });
      } catch (e) {
        // ignore if bootstrap event system isn't available
      }

      // Auto-open when server indicated so via data-open="1"
      try {
        var shouldOpen = loginModalEl.getAttribute('data-open') === '1';
        if (shouldOpen) {
          if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try {
              var modal = new bootstrap.Modal(loginModalEl);
              modal.show();
            } catch (e) {
              // fallback
              loginModalEl.classList.add('show');
              loginModalEl.style.display = 'block';
            }
          } else {
            loginModalEl.classList.add('show');
            loginModalEl.style.display = 'block';
          }
        }
      } catch (e) {
        log('auth.js: error auto-opening modal', e);
      }
    }
  });
})();
