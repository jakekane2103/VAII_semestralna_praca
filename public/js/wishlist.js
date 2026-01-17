// File: public/js/wishlist.js
// Purpose: Provide client-side wishlist interactions: add/remove via AJAX, optimistic UI for hearts,
// drag & drop reordering, and sending new order to the server.
// All functions below are used by event handlers in this file.
(function () {
    // Avoid double-init if this script was already loaded or if cart.js included the same module
    if (window.__wishlist_loaded) return;
    window.__wishlist_loaded = true;

    // Defensive guard: if fetch is unavailable, bail out (older browsers)
    // Used: run immediately to avoid errors when fetch not present.
    if (!window.fetch) return;

    // postForm
    // Purpose: Send multipart/form-data POST (FormData) and return parsed JSON or a success object.
    // Input: url string, fd FormData
    // Output: Promise resolving to JSON object or { success: true }
    // Used: by handleAction and handleHeartClick to perform form-like POSTs.
    function postForm(url, fd) {
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.json().catch(function () { return { success: true }; });
            });
    }

    // small transient toast helper: creates a non-obscuring popup in the bottom-right corner
    function showTransientToast(message, timeout) {
        timeout = typeof timeout === 'number' ? timeout : 1800;
        try {
            var containerId = '__wv_toast_container__';
            var container = document.getElementById(containerId);
            if (!container) {
                container = document.createElement('div');
                container.id = containerId;
                container.style.position = 'fixed';
                container.style.right = '12px';
                container.style.bottom = '12px';
                container.style.zIndex = 1060; // above most elements but below modals
                container.style.display = 'flex';
                container.style.flexDirection = 'column';
                container.style.gap = '8px';
                document.body.appendChild(container);
            }

            var toast = document.createElement('div');
            toast.className = 'wv-toast';
            toast.style.background = 'rgba(0,0,0,0.75)';
            toast.style.color = '#fff';
            toast.style.padding = '8px 12px';
            toast.style.borderRadius = '6px';
            toast.style.fontSize = '13px';
            toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.2)';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(6px)';
            toast.style.transition = 'opacity 220ms ease, transform 220ms ease';
            toast.textContent = message;

            container.appendChild(toast);
            // force reflow then show
            void toast.offsetWidth;
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';

            setTimeout(function () {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(6px)';
                setTimeout(function () { try { container.removeChild(toast); } catch (e) {} }, 260);
            }, timeout);
        } catch (e) {
            try { console.log(message); } catch (e2) {}
        }
    }

    // postJson
    // Purpose: Send JSON POST to server and parse JSON response.
    // Input: url string, obj - object to send
    // Output: Promise resolving to parsed JSON
    // Used: by sendOrderToServer to post reorder payload.
    function postJson(url, obj) {
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(obj)
        }).then(function (r) {
            if (!r.ok) throw new Error('Network response was not ok');
            return r.json().catch(function () { return { success: true }; });
        });
    }

    // handleAction
    // Purpose: Generic handler for forms on the wishlist grid (move/remove actions).
    // - Prevents default, builds FormData, posts via postForm and on success calls onSuccess callback.
    // Input: event, url string, onSuccess callback(id, data)
    // Used: attached to remove/move forms inside DOMContentLoaded block below.
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
            .catch(function (err) {
                console.error(err);
                try { alert('Action failed. Please try again.'); } catch (e) { /* ignore */ }
            });
    }

    // handleHeartClick
    // Purpose: Toggle wishlist "heart" button optimistically and call server to persist.
    // - Updates classes and aria-pressed immediately, sends POST via postForm, and reverts UI on error.
    // Input: click event on button with data-book-id
    // Used: bound via delegated listener below.
    function handleHeartClick(e) {
        try {
            // Determine the button element robustly (support delegation)
            var btn = (e && e.currentTarget && e.currentTarget.matches && e.currentTarget.matches('.btn-wishlist')) ? e.currentTarget : (e && e.target && e.target.closest ? e.target.closest('.btn-wishlist') : null);
            if (!btn) return;

            // Prevent duplicate activations (pointerdown + click) on the same button within short time
            try {
                // If another request for this button is in-flight, ignore
                if (btn.getAttribute('data-wv-inflight') === '1') return;
                btn.setAttribute('data-wv-inflight', '1');

                var last = parseInt(btn.getAttribute('data-wv-last') || '0', 10) || 0;
                var now = Date.now();
                if (now - last < 700) {
                    // ignore duplicate
                    btn.removeAttribute('data-wv-inflight');
                    return;
                }
                btn.setAttribute('data-wv-last', String(now));
            } catch (e) { /* ignore */ }

            // Prevent further handlers and default submission
            if (e && typeof e.preventDefault === 'function') e.preventDefault();
            try { if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation(); } catch (x) {}
            try { if (typeof e.stopPropagation === 'function') e.stopPropagation(); } catch (x) {}

            var bookId = btn.getAttribute('data-book-id');
            // DEBUG: log that handler was reached
            try { console.debug && console.debug('wishlist: click handler reached', { bookId: bookId, btn: btn }); } catch (e) {}
            if (!bookId) return;

            // Determine whether this click is an add (newPressed true) or remove (newPressed false)
            var wasPressed = (btn.getAttribute('aria-pressed') === 'true');
            var newPressed = !wasPressed;

            // Choose endpoint based on desired state (add vs remove). Keep existing global fallbacks.
            var addUrl = window.WISHLIST_ADD_URL || (document.body && document.body.dataset && document.body.dataset.wishlistAddUrl) || '/wishlist/add';
            var removeUrl = window.WISHLIST_REMOVE_URL || (document.body && document.body.dataset && document.body.dataset.wishlistRemoveUrl) || '/wishlist/remove';
            var url = newPressed ? addUrl : removeUrl;

            var fd = new FormData();
            fd.append('id', bookId);

            // Optimistic UI: toggle visual state immediately
            var wasPressed = (btn.getAttribute('aria-pressed') === 'true');
            var newPressed = !wasPressed;

            // Helper to apply visual state (keeps image swap by filename)
            function applyState(pressed) {
                try {
                    // update classes
                    if (pressed) {
                        btn.classList.remove('btn-outline-danger');
                        btn.classList.add('btn', 'btn-danger');
                        btn.setAttribute('aria-pressed', 'true');
                    } else {
                        btn.classList.remove('btn-danger');
                        btn.classList.add('btn', 'btn-outline-danger');
                        btn.setAttribute('aria-pressed', 'false');
                    }

                    // swap image by filename to avoid relying on absolute/public paths
                    var img = btn.querySelector('img');
                    if (img) {
                        try {
                            var iconOn = btn.getAttribute('data-icon-on') || (btn.dataset && btn.dataset.iconOn) || null;
                            var iconOff = btn.getAttribute('data-icon-off') || (btn.dataset && btn.dataset.iconOff) || null;
                            if (pressed) {
                                if (iconOn) img.setAttribute('src', iconOn);
                                else if (img.getAttribute('src').indexOf('wishlistIconRed-outlineWhite') === -1) {
                                    img.setAttribute('src', img.getAttribute('src').replace('wishlistIconWhite', 'wishlistIconRed-outlineWhite'));
                                }
                            } else {
                                if (iconOff) img.setAttribute('src', iconOff);
                                else if (img.getAttribute('src').indexOf('wishlistIconRed') !== -1) {
                                    img.setAttribute('src', img.getAttribute('src').replace('wishlistIconRed-outlineWhite', 'wishlistIconWhite'));
                                }
                            }
                        } catch (e) { /* ignore image swap errors */ }
                    }
                } catch (e) { /* ignore UI set errors */ }
            }

            // Apply optimistic state
            applyState(newPressed);
            // Show small confirmation for add, unobtrusive
            try {
                // toast dedupe: prevent immediate opposite toast for same book
                var lastToast = (window.__wv_last_wishlist_toast && window.__wv_last_wishlist_toast[bookId]) || null;
                var nowt = Date.now();
                var thisType = newPressed ? 'added' : 'removed';
                if (!lastToast || (nowt - lastToast.t > 800) || lastToast.type === thisType) {
                    showTransientToast(newPressed ? 'Pridané do wishlistu' : 'Odstránené z wishlistu');
                    // store last toast info
                    window.__wv_last_wishlist_toast = window.__wv_last_wishlist_toast || {};
                    window.__wv_last_wishlist_toast[bookId] = { type: thisType, t: nowt };
                } else {
                    // suppress the opposite toast that happens too soon
                    try { console.debug && console.debug('wishlist: suppressed duplicate toast', { bookId: bookId, lastToast: lastToast, thisType: thisType }); } catch (e) {}
                }
            } catch (e) {
                try { showTransientToast(newPressed ? 'Pridané do wishlistu' : 'Odstránené z wishlistu'); } catch (ee) {}
            }

            // Immediately blur to avoid sticky :focus styles after click.
            try { btn.blur(); } catch (e) {}

            postForm(url, fd)
                .then(function (data) {
                    if (data && data.success === false) throw new Error(data.message || 'Action failed');

                    // If server returned resolved item, update button/form to use numeric DB id
                    if (data && data.item && data.item.id) {
                        var resolved = String(data.item.id);
                        btn.setAttribute('data-book-id', resolved);
                        var form = btn.closest('form');
                        if (form) {
                            var input = form.querySelector('input[name="id"]');
                            if (input) input.value = resolved;
                        }
                    }

                    // Optionally, server can return authoritative state; if so, ensure UI matches it
                    if (data && typeof data.inWishlist !== 'undefined') {
                        applyState(!!data.inWishlist);
                    }
                })
                .catch(function (err) {
                    console.error(err);
                    // Revert optimistic UI on error
                    applyState(wasPressed);
                    try { showTransientToast('Neúspech pri wishlist operácii'); } catch (e) {}
                })
                .finally(function(){ try { btn.blur(); } catch (e) {}
                    try { btn.removeAttribute('data-wv-inflight'); } catch (e) {}
                    // safety: clear inflight after 3s in case finally wasn't reached
                    setTimeout(function(){ try { btn.removeAttribute('data-wv-inflight'); } catch (e) {} }, 3000);
                });
        } catch (err) {
            console.error(err);
        }
    }

    // enableDragToReorder
    // Purpose: Add drag & drop support to a wishlist container so users can reorder rows.
    // - Attaches dragstart/dragover/dragend handlers to child .wishlist-row elements.
    // - On drag end it calls sendOrderToServer to persist the new order.
    // Input: container DOM element, reorderUrl string
    // Used: called in DOMContentLoaded block when wishlist grid exists.
    function enableDragToReorder(container, reorderUrl) {
        if (!container || !reorderUrl) return;
        var dragSrcEl = null;

        // handleDragStart
        // Purpose: Store the element being dragged, add dragging class, set dataTransfer.
        // Used by browser drag lifecycle.
        function handleDragStart(e) {
            this.classList.add('dragging');
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', this.dataset.id); } catch (err) { /* IE fallback ignore */ }
        }

        // handleDragOver
        // Purpose: Manage DOM reorder as the dragged item moves over targets.
        // - Inserts dragged element before/after target based on mouse Y position.
        // - Calls updateRanks to refresh display order numbers.
        // Used by browser drag lifecycle.
        function handleDragOver(e) {
            if (e.preventDefault) e.preventDefault(); // Allows drop
            e.dataTransfer.dropEffect = 'move';
            var target = e.currentTarget;
            var bounding = target.getBoundingClientRect();
            var offset = bounding.y + (bounding.height / 2);
            var after = (e.clientY > offset);
            var parent = target.parentNode;
            if (after) {
                if (target.nextSibling !== dragSrcEl) parent.insertBefore(dragSrcEl, target.nextSibling);
            } else {
                if (target !== dragSrcEl && target.previousSibling !== dragSrcEl) parent.insertBefore(dragSrcEl, target);
            }
            updateRanks(container);
            return false;
        }

        // handleDragEnd
        // Purpose: Clean up dragging class and trigger server update of order.
        // Used by browser drag lifecycle.
        function handleDragEnd() {
            this.classList.remove('dragging');
            // After drag end, push order to server
            sendOrderToServer(container, reorderUrl);
        }

        // addDnDHandlers
        // Purpose: Attach drag event listeners to a single item.
        // Used by enableDragToReorder to initialize existing rows.
        function addDnDHandlers(item) {
            item.setAttribute('draggable', 'true');
            item.addEventListener('dragstart', handleDragStart, false);
            item.addEventListener('dragover', handleDragOver, false);
            item.addEventListener('dragend', handleDragEnd, false);
        }

        // Attach handlers to child items
        var items = Array.prototype.slice.call(container.querySelectorAll('.wishlist-row'));
        items.forEach(function (it) { addDnDHandlers(it); });
    }

    // updateRanks
    // Purpose: Update displayed rank numbers (1., 2., ...) inside .wishlist-row elements.
    // Input: container DOM element
    // Used: called after reorder operations and on initial load to display indices.
    function updateRanks(container) {
        var rows = container.querySelectorAll('.wishlist-row');
        rows.forEach(function (row, idx) {
            var rankEl = row.querySelector('.wishlist-rank');
            if (rankEl) rankEl.textContent = (idx + 1) + '.';
        });
    }

    // sendOrderToServer
    // Purpose: Collect current row order and POST to server via postJson.
    // - Shows a saving state on the container while the request is in-flight.
    // - On success, optionally reorders DOM to server-authoritative order.
    // Input: container DOM element, reorderUrl string
    // Used: called by handleDragEnd inside enableDragToReorder.
    function sendOrderToServer(container, reorderUrl) {
        var rows = container.querySelectorAll('.wishlist-row');
        var order = [];
        rows.forEach(function (row) { order.push(row.dataset.id); });

        // optimistic UI: show saving state
        container.classList.add('saving');

        return postJson(reorderUrl, { order: order })
            .then(function (data) {
                if (data && data.success === false) throw new Error(data.message || 'Reorder failed');
                // success: update ranks (server returns authoritative order too)
                if (data && Array.isArray(data.order)) {
                    // reorder DOM to match server order if different
                    var byId = {};
                    var current = Array.prototype.slice.call(container.querySelectorAll('.wishlist-row'));
                    current.forEach(function (r) { byId[r.dataset.id] = r; });
                    // clear
                    while (container.firstChild) container.removeChild(container.firstChild);
                    data.order.forEach(function (id) { if (byId[id]) container.appendChild(byId[id]); });
                    updateRanks(container);
                }
            })
            .catch(function (err) {
                console.error(err);
                try { alert('Nepodarilo sa uložiť poradie. Skúste to znova.'); } catch (e) {}
            })
            .finally(function () {
                container.classList.remove('saving');
            });
    }

    // DOM-level delegated click binding (attach immediately so timing isn't an issue)
    if (!window.__wv_wishlist_click_bound) {
        window.__wv_wishlist_click_bound = true;
        document.addEventListener('click', function (e) {
            try {
                var target = e.target || null;
                var btn = target && target.closest ? target.closest('.btn-wishlist') : null;
                if (!btn) return;
                handleHeartClick(e);
            } catch (ex) { console.error(ex); }
        }, true);

        // NOTE: pointerdown listener removed to avoid duplicate handling on some devices/browsers
    }

    // DOMContentLoaded: wire up drag & drop
    document.addEventListener('DOMContentLoaded', function () {

        // Enable drag & drop on wishlist rows if present
        var wishlistGrid = document.getElementById('wishlist-grid');
        var reorderUrl = window.WISHLIST_REORDER_URL || (document.body && document.body.dataset && document.body.dataset.wishlistReorderUrl) || '/wishlist/reorder';
        if (wishlistGrid) {
            enableDragToReorder(wishlistGrid, reorderUrl);
            updateRanks(wishlistGrid);
        }
    });
})();
