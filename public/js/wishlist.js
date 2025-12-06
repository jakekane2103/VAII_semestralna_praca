(function () {
    // Keep this file lightweight and defensive for older browsers
    if (!window.fetch) return;

    function postForm(url, fd) {
        return fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function (r) {
                if (!r.ok) throw new Error('Network response was not ok');
                return r.json().catch(function () { return { success: true }; });
            });
    }

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

    function handleHeartClick(e) {
        e.preventDefault();
        var btn = e.currentTarget;
        var bookId = btn.getAttribute('data-book-id');
        if (!bookId) return;

        var url = window.WISHLIST_ADD_URL || (document.body && document.body.dataset && document.body.dataset.wishlistAddUrl) || '/wishlist/add';
        var fd = new FormData();
        fd.append('id', bookId);

        // Optimistic UI: toggle immediately, revert on error
        var wasOutline = btn.classList.contains('btn-outline-danger');
        var wasPressed = btn.getAttribute('aria-pressed') === 'true';

        // Toggle visual state
        if (wasOutline) {
            btn.classList.remove('btn-outline-danger');
            btn.classList.add('btn-danger');
            btn.setAttribute('aria-pressed', 'true');
        } else {
            btn.classList.remove('btn-danger');
            btn.classList.add('btn-outline-danger');
            btn.setAttribute('aria-pressed', 'false');
        }

        postForm(url, fd)
            .then(function (data) {
                if (data && data.success === false) throw new Error(data.message || 'Action failed');

                // If server returned resolved item, update button/form to use numeric DB id
                if (data && data.item && data.item.id) {
                    var resolved = String(data.item.id);
                    // update data-book-id and hidden input if present
                    btn.setAttribute('data-book-id', resolved);
                    var form = btn.closest('form');
                    if (form) {
                        var input = form.querySelector('input[name="id"]');
                        if (input) input.value = resolved;
                    }
                }
                // success - keep toggled state
            })
            .catch(function (err) {
                console.error(err);
                // revert UI change on error
                if (wasOutline) {
                    btn.classList.remove('btn-danger');
                    btn.classList.add('btn-outline-danger');
                    btn.setAttribute('aria-pressed', wasPressed ? 'true' : 'false');
                } else {
                    btn.classList.remove('btn-outline-danger');
                    btn.classList.add('btn-danger');
                    btn.setAttribute('aria-pressed', wasPressed ? 'true' : 'false');
                }
                try { alert('Neúspech pri pridávaní do wishlistu. Skúste znova.'); } catch (e) {}
            });
    }

    // Drag & Drop reorder helpers
    function enableDragToReorder(container, reorderUrl) {
        if (!container || !reorderUrl) return;
        var dragSrcEl = null;

        function handleDragStart(e) {
            this.classList.add('dragging');
            dragSrcEl = this;
            e.dataTransfer.effectAllowed = 'move';
            try { e.dataTransfer.setData('text/plain', this.dataset.id); } catch (err) { /* IE fallback ignore */ }
        }

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

        function handleDragEnd(e) {
            this.classList.remove('dragging');
            // After drag end, push order to server
            sendOrderToServer(container, reorderUrl);
        }

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

    function updateRanks(container) {
        var rows = container.querySelectorAll('.wishlist-row');
        rows.forEach(function (row, idx) {
            var rankEl = row.querySelector('.wishlist-rank');
            if (rankEl) rankEl.textContent = (idx + 1) + '.';
        });
    }

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

    document.addEventListener('DOMContentLoaded', function () {
        // Existing wishlist page forms: move/remove
        var forms = document.querySelectorAll('#wishlist-grid form');
        forms.forEach(function (form) {
            var action = form.getAttribute('action') || '';
            var isMove = action.endsWith('/moveToCart') || action.indexOf('moveToCart') !== -1;
            var isRemove = action.endsWith('/remove') || action.indexOf('remove') !== -1;

            if (isMove || isRemove) {
                form.addEventListener('submit', function (e) {
                    handleAction(e, form.getAttribute('action'), function (id) {
                        var el = document.querySelector('.book-card[data-id="' + id + '"]') || document.querySelector('.wishlist-row[data-id="' + id + '"]');
                        if (el) el.remove();
                    });
                });
            }
        });

        // Heart buttons on book lists
        var heartBtns = document.querySelectorAll('.btn-wishlist');
        heartBtns.forEach(function (btn) {
            btn.addEventListener('click', handleHeartClick);
        });

        // Enable drag & drop on wishlist rows if present
        var wishlistGrid = document.getElementById('wishlist-grid');
        var reorderUrl = window.WISHLIST_REORDER_URL || (document.body && document.body.dataset && document.body.dataset.wishlistReorderUrl) || '/wishlist/reorder';
        if (wishlistGrid) {
            enableDragToReorder(wishlistGrid, reorderUrl);
            updateRanks(wishlistGrid);
        }
    });
})();
