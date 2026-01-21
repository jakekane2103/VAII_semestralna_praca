// File: public/js/wishlist.js
(function () {
    if (window.__wishlist_loaded) return;
    window.__wishlist_loaded = true;

    if (!window.fetch) return;

    // Lightweight JSON POST fallback (use existing global if provided)
    const postJson = window.postJson || function (url, obj) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(obj)
        }).then(r => {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.json().catch(() => ({ success: true }));
        });
    };

    const postForm = function (url, fd) {
        return fetch(url, {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.json().catch(() => ({ success: true }));
        });
    };

    // transient toast helper
    function showTransientToast(message, timeout = 1800) {
        let container = document.getElementById('__wv_toast_container__');
        if (!container) {
            container = document.createElement('div');
            container.id = '__wv_toast_container__';
            Object.assign(container.style, {
                position: 'fixed',
                right: '12px',
                bottom: '12px',
                zIndex: '1060',
                display: 'flex',
                flexDirection: 'column',
                gap: '8px'
            });
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = 'wv-toast';
        Object.assign(toast.style, {
            background: 'rgba(0,0,0,0.75)',
            color: '#fff',
            padding: '8px 12px',
            borderRadius: '6px',
            fontSize: '13px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.2)',
            opacity: '0',
            transform: 'translateY(6px)',
            transition: 'opacity 220ms ease, transform 220ms ease'
        });
        toast.textContent = message;
        container.appendChild(toast);
        // force reflow
        void toast.offsetWidth;
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(6px)';
            setTimeout(() => { try { container.removeChild(toast); } catch (e) {} }, 260);
        }, timeout);
    }

    // Resolve URLs with fallbacks
    function resolveUrl(name, gridEl) {
        switch (name) {
            case 'add':
                return window.WISHLIST_ADD_URL || (document.body && document.body.dataset && document.body.dataset.wishlistAddUrl) || '/wishlist/add';
            case 'remove':
                return window.WISHLIST_REMOVE_URL || (document.body && document.body.dataset && document.body.dataset.wishlistRemoveUrl) || '/wishlist/remove';
            case 'reorder':
                if (gridEl && gridEl.dataset && gridEl.dataset.wishlistReorderUrl) {
                    if (!window.WISHLIST_REORDER_URL) window.WISHLIST_REORDER_URL = gridEl.dataset.wishlistReorderUrl;
                    return gridEl.dataset.wishlistReorderUrl;
                }
                return window.WISHLIST_REORDER_URL || (document.body && document.body.dataset && document.body.dataset.wishlistReorderUrl) || '/wishlist/reorder';
            default:
                return '';
        }
    }

    // Toggle heart button optimistically and persist
    function handleHeartClick(e) {
        const btn = e.target && e.target.closest ? e.target.closest('.btn-wishlist') : null;
        if (!btn) return;

        // prevent duplicate activations
        if (btn.getAttribute('data-wv-inflight') === '1') return;
        btn.setAttribute('data-wv-inflight', '1');

        const last = parseInt(btn.getAttribute('data-wv-last') || '0', 10) || 0;
        const now = Date.now();
        if (now - last < 700) {
            btn.removeAttribute('data-wv-inflight');
            return;
        }
        btn.setAttribute('data-wv-last', String(now));

        if (e && typeof e.preventDefault === 'function') e.preventDefault();
        try { if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation(); } catch (x) {}
        try { if (typeof e.stopPropagation === 'function') e.stopPropagation(); } catch (x) {}

        const bookId = btn.getAttribute('data-book-id');
        if (!bookId) {
            btn.removeAttribute('data-wv-inflight');
            return;
        }

        const wasPressed = btn.getAttribute('aria-pressed') === 'true';
        const newPressed = !wasPressed;
        const url = newPressed ? resolveUrl('add') : resolveUrl('remove');

        const fd = new FormData();
        fd.append('id', bookId);

        function applyState(pressed) {
            try {
                if (pressed) {
                    btn.classList.remove('btn-outline-danger');
                    btn.classList.add('btn', 'btn-danger');
                    btn.setAttribute('aria-pressed', 'true');
                } else {
                    btn.classList.remove('btn-danger');
                    btn.classList.add('btn-outline-danger');
                    btn.setAttribute('aria-pressed', 'false');
                }

                const img = btn.querySelector('img');
                if (img) {
                    const iconOn = btn.getAttribute('data-icon-on') || (btn.dataset && btn.dataset.iconOn) || null;
                    const iconOff = btn.getAttribute('data-icon-off') || (btn.dataset && btn.dataset.iconOff) || null;
                    try {
                        const src = img.getAttribute('src') || '';
                        if (pressed) {
                            if (iconOn) img.setAttribute('src', iconOn);
                            else if (src.indexOf('wishlistIconRed-outlineWhite') === -1) {
                                img.setAttribute('src', src.replace('wishlistIconWhite', 'wishlistIconRed-outlineWhite'));
                            }
                        } else {
                            if (iconOff) img.setAttribute('src', iconOff);
                            else if (src.indexOf('wishlistIconRed') !== -1) {
                                img.setAttribute('src', src.replace('wishlistIconRed-outlineWhite', 'wishlistIconWhite'));
                            }
                        }
                    } catch (e) { /* ignore image swap errors */ }
                }
            } catch (e) { /* ignore UI set errors */ }
        }

        applyState(newPressed);

        // toast dedupe
        try {
            window.__wv_last_wishlist_toast = window.__wv_last_wishlist_toast || {};
            const lastToast = window.__wv_last_wishlist_toast[bookId] || null;
            const nowt = Date.now();
            const thisType = newPressed ? 'added' : 'removed';
            if (!lastToast || (nowt - lastToast.t > 800) || lastToast.type === thisType) {
                showTransientToast(newPressed ? 'Pridané do wishlistu' : 'Odstránené z wishlistu');
                window.__wv_last_wishlist_toast[bookId] = { type: thisType, t: nowt };
            }
        } catch (e) {
            showTransientToast(newPressed ? 'Pridané do wishlistu' : 'Odstránené z wishlistu');
        }

        try { btn.blur(); } catch (e) {}

        postForm(url, fd)
            .then(function (data) {
                if (data && data.success === false) throw new Error(data.message || 'Action failed');

                if (data && data.item && data.item.id) {
                    const resolved = String(data.item.id);
                    btn.setAttribute('data-book-id', resolved);
                    const form = btn.closest('form');
                    if (form) {
                        const input = form.querySelector('input[name="id"]');
                        if (input) input.value = resolved;
                    }
                }

                if (data && typeof data.inWishlist !== 'undefined') {
                    applyState(!!data.inWishlist);
                }
            })
            .catch(function (err) {
                console.error(err);
                applyState(wasPressed);
                try { showTransientToast('Neúspech pri wishlist operácii'); } catch (e) {}
            })
            .finally(function () {
                try { btn.blur(); } catch (e) {}
                try { btn.removeAttribute('data-wv-inflight'); } catch (e) {}
                setTimeout(function () { try { btn.removeAttribute('data-wv-inflight'); } catch (e) {} }, 3000);
            });
    }

    // Update displayed ranks
    function updateRanks(container) {
        const rows = container.querySelectorAll('.wishlist-row');
        rows.forEach((row, idx) => {
            const rankEl = row.querySelector('.wishlist-rank');
            if (rankEl) rankEl.textContent = (idx + 1) + '.';
        });
    }

    // Send order to server
    function sendOrderToServer(container, reorderUrl) {
        const rows = container.querySelectorAll('.wishlist-row');
        const order = Array.prototype.map.call(rows, r => r.dataset.id);

        container.classList.add('saving');

        return postJson(reorderUrl, { order })
            .then((data) => {
                if (data && data.success === false) throw new Error(data.message || 'Reorder failed');

                if (data && Array.isArray(data.order)) {
                    const byId = {};
                    Array.prototype.slice.call(container.querySelectorAll('.wishlist-row')).forEach(r => byId[r.dataset.id] = r);
                    while (container.firstChild) container.removeChild(container.firstChild);
                    data.order.forEach(id => { if (byId[id]) container.appendChild(byId[id]); });
                    updateRanks(container);
                } else {
                    updateRanks(container);
                }
            })
            .catch((err) => {
                console.error(err);
                try { alert('Nepodarilo sa uložiť poradie. Skúste to znova.'); } catch (e) {}
            })
            .finally(() => {
                container.classList.remove('saving');
            });
    }

    // Drag & drop
    function enableDragToReorder(container, reorderUrl) {
        if (!container || !reorderUrl) return;
        let dragSrcEl = null;

        function handleDragStart(e) {
            this.classList.add('dragging');
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', this.dataset.id); } catch (err) {}
        }

        function handleDragOver(e) {
            if (e.preventDefault) e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            const target = e.currentTarget;
            const rect = target.getBoundingClientRect();
            const after = e.clientY > rect.top + rect.height / 2;
            const parent = target.parentNode;
            if (after) {
                if (target.nextSibling !== dragSrcEl) parent.insertBefore(dragSrcEl, target.nextSibling);
            } else {
                if (target !== dragSrcEl && target.previousSibling !== dragSrcEl) parent.insertBefore(dragSrcEl, target);
            }
            updateRanks(container);
            return false;
        }

        function handleDragEnd() {
            this.classList.remove('dragging');
            sendOrderToServer(container, reorderUrl);
        }

        function addDnDHandlers(item) {
            item.setAttribute('draggable', 'true');
            item.addEventListener('dragstart', handleDragStart, false);
            item.addEventListener('dragover', handleDragOver, false);
            item.addEventListener('dragend', handleDragEnd, false);
        }

        Array.prototype.slice.call(container.querySelectorAll('.wishlist-row')).forEach(addDnDHandlers);
    }

    // Delegated click handler
    if (!window.__wv_wishlist_click_bound) {
        window.__wv_wishlist_click_bound = true;
        document.addEventListener('click', function (e) {
            const btn = e.target && e.target.closest ? e.target.closest('.btn-wishlist') : null;
            if (!btn) return;
            handleHeartClick(e);
        }, true);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const wishlistGrid = document.getElementById('wishlist-grid');
        const reorderUrl = resolveUrl('reorder', wishlistGrid);
        if (wishlistGrid) {
            enableDragToReorder(wishlistGrid, reorderUrl);
            updateRanks(wishlistGrid);
        }

        // wire up remove forms (uses external handleAction if present)
        try {
            const removeForms = document.querySelectorAll('#wishlist-grid form[action$="Wishlist.remove"]');
            removeForms.forEach(f => {
                f.addEventListener('submit', function (e) {
                    if (typeof handleAction === 'function') {
                        handleAction(e, f.action, function (id) {
                            const row = document.querySelector('.wishlist-row[data-id="' + id + '"]');
                            if (row) row.parentNode.removeChild(row);
                        });
                    }
                });
            });
        } catch (e) { /* ignore */ }
    });
})();
