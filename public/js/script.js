// File: public/js/script.js
// Purpose: Small site-wide UI helpers (password toggle and login modal autofocus).

// Password toggle handler
// Purpose: Toggle visibility of password inputs when a button with data-toggle='password' is clicked.
// - Buttons should have data-target attribute with the input id and contain an <i> icon to switch classes.
// Used: site-wide on auth forms (login/register) where toggling password visibility is desired.
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-toggle='password']").forEach(btn => {
        btn.addEventListener("click", () => {
            const inputId = btn.getAttribute("data-target");
            const input = document.getElementById(inputId);
            const icon = btn.querySelector("i");

            if (!input) return;

            if (input.type === "password") {
                input.type = "text";
                if (icon) { icon.classList.remove("bi-eye-slash"); icon.classList.add("bi-eye"); }
            } else {
                input.type = "password";
                if (icon) { icon.classList.remove("bi-eye"); icon.classList.add("bi-eye-slash"); }
            }
        });
    });
});

// Login modal autofocus
// Purpose: When the Bootstrap login modal is shown, autofocus the email input for convenience.
// Used: bound to #loginModal shown.bs.modal event if both the modal and input (#email) exist on the page.
document.addEventListener("DOMContentLoaded", () => {
    // Autofocus on username in login modal
    const loginModal = document.getElementById("loginModal");
    const loginEmailInput = document.getElementById("email");

    if (loginModal && loginEmailInput) {
        loginModal.addEventListener("shown.bs.modal", () => {
            loginEmailInput.focus();
        });
    }

});

// Header height helper: measure the fixed header and expose it via CSS variable
function updateHeaderHeight() {
    try {
        const header = document.querySelector('.sticky-header');
        if (!header) return;
        const height = Math.ceil(header.getBoundingClientRect().height);
        document.documentElement.style.setProperty('--header-height', height + 'px');
    } catch (e) {
        // fail silently
        console.error('updateHeaderHeight error', e);
    }
}

// Run on DOM ready and react to changes
document.addEventListener('DOMContentLoaded', () => {
    updateHeaderHeight();
    // Recalculate on window load as well (images/fonts might change layout)
    window.addEventListener('load', updateHeaderHeight);
    window.addEventListener('resize', () => {
        // throttle with requestAnimationFrame for smoother updates
        window.requestAnimationFrame(updateHeaderHeight);
    });

    const header = document.querySelector('.sticky-header');
    if (header) {
        // Observe DOM changes inside header that might affect its height
        const mo = new MutationObserver(() => window.requestAnimationFrame(updateHeaderHeight));
        mo.observe(header, { attributes: true, childList: true, subtree: true });

        // Ensure we update when header images load
        header.querySelectorAll('img').forEach(img => {
            if (!img.complete) img.addEventListener('load', updateHeaderHeight);
        });
    }
});

// Responsive carousel builder: build slides from flat .carousel-card list
function buildResponsiveCarousels() {
    const carousels = document.querySelectorAll('.carousel');
    carousels.forEach(carousel => {
        const cardsContainer = carousel.querySelector('.carousel-cards');
        if (!cardsContainer) return;

        // store original HTML once
        if (!cardsContainer.dataset.original) {
            cardsContainer.dataset.original = cardsContainer.innerHTML;
        }

        // get cards (from DOM or from original if DOM was cleared)
        let cards = Array.from(cardsContainer.querySelectorAll('.carousel-card'));
        if (cards.length === 0 && cardsContainer.dataset.original) {
            const tmp = document.createElement('div');
            tmp.innerHTML = cardsContainer.dataset.original;
            cards = Array.from(tmp.querySelectorAll('.carousel-card'));
        }

        // decide group size
        const groupSize = window.innerWidth >= 768 ? 4 : 2;

        // find the carousel-inner container (we'll replace its content)
        const inner = carousel.querySelector('.carousel-inner');
        if (!inner) return;

        // clear existing slides
        inner.innerHTML = '';

        // build slides
        for (let i = 0; i < cards.length; i += groupSize) {
            const item = document.createElement('div');
            item.className = 'carousel-item';

            const wrapper = document.createElement('div');
            wrapper.className = 'books-wrapper';

            const row = document.createElement('div');
            row.className = 'row mt-4';

            for (let j = i; j < i + groupSize && j < cards.length; j++) {
                // clone the card node so we don't move the original source
                const card = cards[j].cloneNode(true);
                row.appendChild(card);
            }

            wrapper.appendChild(row);
            item.appendChild(wrapper);
            inner.appendChild(item);
        }

        // mark first slide as active
        const first = inner.querySelector('.carousel-item');
        if (first) first.classList.add('active');

        // ensure controls are positioned on sides: Bootstrap handles it, but we nudge z-index
        const prev = carousel.querySelector('.carousel-control-prev');
        const next = carousel.querySelector('.carousel-control-next');
        if (prev) prev.style.zIndex = '5';
        if (next) next.style.zIndex = '5';

        // Reinitialize Bootstrap carousel so it picks up the new slides
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
                const existing = bootstrap.Carousel.getInstance(carousel);
                if (existing) existing.dispose();
                // preserve interval if specified on element
                const intervalAttr = carousel.getAttribute('data-bs-interval');
                const options = {};
                if (intervalAttr !== null) options.interval = parseInt(intervalAttr, 10) || false;
                new bootstrap.Carousel(carousel, options);
            }
        } catch (e) {
            // fail silently
            console.error('Carousel init error', e);
        }
    });
}

// Debounce utility
let _resizeTimeout = null;
function onResizeRebuild() {
    if (_resizeTimeout) clearTimeout(_resizeTimeout);
    _resizeTimeout = setTimeout(() => {
        buildResponsiveCarousels();
    }, 150);
}

// Initialize on DOM ready and on load/resize
document.addEventListener('DOMContentLoaded', () => {
    buildResponsiveCarousels();
    window.addEventListener('load', buildResponsiveCarousels);
    window.addEventListener('resize', onResizeRebuild);
    window.addEventListener('orientationchange', buildResponsiveCarousels);

    // Observe any changes to body that may affect carousels (e.g., AJAX inserts)
    const mo = new MutationObserver(() => buildResponsiveCarousels());
    mo.observe(document.body, { childList: true, subtree: true });
});
