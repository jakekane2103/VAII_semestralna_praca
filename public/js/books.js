// File: public/js/books.js
// Purpose: Lightweight page protections for Books pages.
// - Exposes dataset URL/icons as globals for other scripts.
// - Keeps author-scroll fallback.
// - Saves/restores wishlist button markup so other scripts (wishlist/cart) can safely toggle them.
(function(){
    document.addEventListener('DOMContentLoaded', function() {
        // Expose dataset values as globals if not already present
        try {
            var pageRoot = document.querySelector('.books-index') || document.querySelector('.page-book-detail');
            if (pageRoot && pageRoot.dataset) {
                if (!window.BOOKS_CART_URL && pageRoot.dataset.booksCartUrl) window.BOOKS_CART_URL = pageRoot.dataset.booksCartUrl;
                if (!window.WISHLIST_ADD_URL && pageRoot.dataset.wishlistAddUrl) window.WISHLIST_ADD_URL = pageRoot.dataset.wishlistAddUrl;
                if (!window.WISHLIST_REMOVE_URL && pageRoot.dataset.wishlistRemoveUrl) window.WISHLIST_REMOVE_URL = pageRoot.dataset.wishlistRemoveUrl;
                if (!window.WISH_ICON_ON && pageRoot.dataset.wishIconOn) window.WISH_ICON_ON = pageRoot.dataset.wishIconOn;
                if (!window.WISH_ICON_OFF && pageRoot.dataset.wishIconOff) window.WISH_ICON_OFF = pageRoot.dataset.wishIconOff;
                if (!window.CART_ICON && pageRoot.dataset.cartIcon) window.CART_ICON = pageRoot.dataset.cartIcon;
            }
        } catch (e) { /* ignore */ }

        // Author-scroll fallback: when an author filter anchors to a result, ensure it's visible below the sticky header
        try {
            var root = document.querySelector('.books-index') || document.querySelector('.page-book-detail');
            var authorFilter = root && root.dataset ? (root.dataset.authorFilter || '') : '';
            if (authorFilter && document.querySelector('.authorFoundBooks')) {
                var el = document.querySelector('.authorFoundBooks');
                var headerHeight = 0;
                var computed = getComputedStyle(document.documentElement).getPropertyValue('--header-height');
                if (computed) {
                    var parsed = parseInt(computed.trim());
                    if (!isNaN(parsed)) headerHeight = parsed;
                }
                if (!headerHeight) {
                    var header = document.querySelector('.sticky-header');
                    if (header) headerHeight = Math.ceil(header.getBoundingClientRect().height || 0);
                }
                var extra = 8;
                var top = el.getBoundingClientRect().top + window.scrollY - headerHeight - extra;
                window.scrollTo({ top: Math.max(0, top), behavior: 'instant' in document.documentElement ? 'instant' : 'auto' });
            }
        } catch (e) { /* ignore */ }

        // Save original state of wishlist buttons so other scripts can't permanently overwrite them
        try {
            document.querySelectorAll('.btn-wishlist').forEach(function (btn) {
                try {
                    if (!btn.dataset) return;
                    btn.dataset.wishlistInner = btn.innerHTML;
                    btn.dataset.wishlistClass = btn.className;
                    btn.dataset.wishlistAria = btn.getAttribute('aria-pressed') || '';
                    btn.dataset.wishlistStored = '1';
                } catch (e) { /* ignore per-button errors */ }
            });
        } catch (e) { /* ignore */ }

        // If the add-to-cart modal is present, ensure wishlist buttons are restored when the modal hides
        try {
            var modal = document.getElementById('addToCartModal');
            if (modal) {
                modal.addEventListener('hidden.bs.modal', function () {
                    try {
                        document.querySelectorAll('.btn-wishlist').forEach(function (btn) {
                            try {
                                if (!btn.dataset || btn.dataset.wishlistStored !== '1') return;
                                if (typeof btn.dataset.wishlistInner !== 'undefined') btn.innerHTML = btn.dataset.wishlistInner;
                                if (typeof btn.dataset.wishlistClass !== 'undefined') btn.className = btn.dataset.wishlistClass;
                                if (typeof btn.dataset.wishlistAria !== 'undefined' && btn.dataset.wishlistAria !== '') {
                                    btn.setAttribute('aria-pressed', btn.dataset.wishlistAria);
                                } else {
                                    btn.removeAttribute('aria-pressed');
                                }
                            } catch (e) { /* ignore per-button restore errors */ }
                        });
                    } catch (e) { /* ignore */ }
                });

                // Also dispatch hidden event on hide start (defensive)
                modal.addEventListener('hide.bs.modal', function () {
                    try { var ev = new Event('hidden.bs.modal'); modal.dispatchEvent(ev); } catch (e) { /* ignore */ }
                });
            }
        } catch (e) { /* ignore */ }
    });
})();

// End of file: public/js/books.js
