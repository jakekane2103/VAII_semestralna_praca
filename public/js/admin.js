// Unified admin JS: handle delete modal, edit modal and add-modal interactions, series handling and header resize
(function () {
    'use strict';

    function qs(selector, ctx) { return (ctx || document).querySelector(selector); }
    function qsa(selector, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(selector)); }

    document.addEventListener('DOMContentLoaded', function () {
        var root = document.getElementById('admin-root') || document.body;
        var ADMIN_DELETE_BOOK_URL = root.dataset.deleteBookUrl || null;
        var ADMIN_DELETE_SERIES_URL = root.dataset.deleteSeriesUrl || null;

        // --- Delete modal (shared for books and series) ---
        var adminDeleteModalEl = qs('#adminDeleteModal');
        var adminDeleteModal = adminDeleteModalEl ? new bootstrap.Modal(adminDeleteModalEl, { focus: false }) : null;
        var adminDeleteForm = adminDeleteModalEl ? adminDeleteModalEl.querySelector('form') : null;
        var adminDeleteHidden = qs('#modal-delete-id');
        var adminDeleteNameEl = qs('#modal-delete-nazov');
        var adminDeleteTypeEl = qs('#modal-delete-type');

        function openDeleteModalForBook(btn) {
            if (!btn) return;
            var id = btn.getAttribute('data-id');
            var nazov = btn.getAttribute('data-nazov') || '';
            if (adminDeleteForm && ADMIN_DELETE_BOOK_URL) adminDeleteForm.action = ADMIN_DELETE_BOOK_URL;
            if (adminDeleteHidden) {
                try { adminDeleteHidden.setAttribute('name', 'id_kniha'); } catch (e) {}
                adminDeleteHidden.value = id || '';
            }
            if (adminDeleteNameEl) adminDeleteNameEl.textContent = nazov;
            if (adminDeleteTypeEl) adminDeleteTypeEl.textContent = 'knihu';
            if (adminDeleteModal) adminDeleteModal.show();
        }

        function openDeleteModalForSeries(btn) {
            if (!btn) return;
            var id = btn.getAttribute('data-id');
            var name = btn.getAttribute('data-name') || '';
            if (adminDeleteForm && ADMIN_DELETE_SERIES_URL) adminDeleteForm.action = ADMIN_DELETE_SERIES_URL;
            if (adminDeleteHidden) {
                try { adminDeleteHidden.setAttribute('name', 'id'); } catch (e) {}
                adminDeleteHidden.value = id || '';
            }
            if (adminDeleteNameEl) adminDeleteNameEl.textContent = name;
            if (adminDeleteTypeEl) adminDeleteTypeEl.textContent = 'sériu';
            if (adminDeleteModal) adminDeleteModal.show();
        }

        // Wire delete buttons
        qsa('.admin-delete-button').forEach(function (btn) {
            // prefer pointerdown to avoid other handlers interfering but keep click as final
            btn.addEventListener('pointerdown', function (e) { if (e && e.stopPropagation) e.stopPropagation(); }, { passive: true });
            btn.addEventListener('click', function (e) { if (e && e.preventDefault) e.preventDefault(); openDeleteModalForBook(btn); });
        });

        qsa('.admin-delete-series').forEach(function (btn) {
            btn.addEventListener('click', function (e) { if (e && e.preventDefault) e.preventDefault(); openDeleteModalForSeries(btn); });
        });

        // Reset shared delete modal to defaults when hidden
        if (adminDeleteModalEl) {
            adminDeleteModalEl.addEventListener('hidden.bs.modal', function () {
                if (adminDeleteForm && ADMIN_DELETE_BOOK_URL) adminDeleteForm.action = ADMIN_DELETE_BOOK_URL;
                if (adminDeleteHidden) {
                    try { adminDeleteHidden.setAttribute('name', 'id_kniha'); } catch (e) {}
                    adminDeleteHidden.value = '';
                }
                if (adminDeleteNameEl) adminDeleteNameEl.textContent = '';
                if (adminDeleteTypeEl) adminDeleteTypeEl.textContent = 'knihu';
            });
        }

        // --- Edit book modal (open by clicking book row) ---
        var adminEditModalEl = qs('#adminEditModal');
        var adminEditModal = adminEditModalEl ? new bootstrap.Modal(adminEditModalEl, { focus: false }) : null;

        function openEditBookFromRow(row) {
            if (!row) return;
            // ignore clicks originating from delete buttons
            var lastClicked = window._lastAdminClickTarget;
            if (lastClicked && typeof lastClicked.closest === 'function' && lastClicked.closest('.admin-delete-button')) return;

            var id = row.getAttribute('data-id') || '';
            var nazov = row.getAttribute('data-nazov') || '';
            var autor = row.getAttribute('data-autor') || '';
            var cena = row.getAttribute('data-cena') || '';
            var obrazok = row.getAttribute('data-obrazok') || '';
            var popis = row.getAttribute('data-popis') || '';
            var seriesId = row.getAttribute('data-series-id') || '';
            var seriesName = row.getAttribute('data-series-name') || '';

            var idEl = qs('#edit-id');
            var nazovEl = qs('#edit-nazov');
            var autorEl = qs('#edit-autor');
            var cenaEl = qs('#edit-cena');
            var obrazokEl = qs('#edit-obrazok');
            var popisEl = qs('#edit-popis');
            var seriesSelectEl = qs('#edit-series');
            var seriesNewEl = qs('#edit-series-new');

            if (idEl) idEl.value = id;
            if (nazovEl) nazovEl.value = nazov;
            if (autorEl) autorEl.value = autor;
            if (cenaEl) cenaEl.value = cena;
            if (obrazokEl) obrazokEl.value = obrazok;
            if (popisEl) popisEl.value = popis;

            if (seriesSelectEl) {
                var found = false;
                for (var i = 0; i < seriesSelectEl.options.length; i++) {
                    if (String(seriesSelectEl.options[i].value) === String(seriesId)) {
                        seriesSelectEl.value = String(seriesId);
                        found = true; break;
                    }
                }
                if (!found && (seriesId || seriesName)) {
                    seriesSelectEl.value = 'new';
                    if (seriesNewEl) { seriesNewEl.classList.remove('d-none'); seriesNewEl.value = seriesName || ''; }
                } else {
                    if (seriesNewEl) seriesNewEl.classList.add('d-none');
                }
            }

            if (adminEditModal) adminEditModal.show();
        }

        // Track last click target to help ignore delete button-originated row clicks
        document.addEventListener('pointerdown', function (e) { window._lastAdminClickTarget = e.target; }, { capture: true, passive: true });

        qsa('.admin-book-row').forEach(function (row) {
            row.addEventListener('click', function (e) { openEditBookFromRow(row); });
        });

        // --- Edit series modal (rows clickable) ---
        var editSeriesModalEl = qs('#adminEditSeriesModal');
        var editSeriesModal = editSeriesModalEl ? new bootstrap.Modal(editSeriesModalEl, { focus: false }) : null;

        qsa('.admin-series-row').forEach(function (row) {
            row.addEventListener('click', function (e) {
                // ignore clicks on delete button inside row
                if (e && e.target && typeof e.target.closest === 'function' && e.target.closest('.admin-delete-series')) return;
                var id = row.getAttribute('data-id') || '';
                var name = row.getAttribute('data-name') || '';
                var idEl = qs('#edit-series-id');
                var nameEl = qs('#edit-series-name');
                if (idEl) idEl.value = id;
                if (nameEl) nameEl.value = name;
                if (editSeriesModal) editSeriesModal.show();
            });

            // keyboard accessibility: Enter/Space opens edit (unless delete button focused)
            row.addEventListener('keydown', function (e) {
                var active = document.activeElement;
                if (active && typeof active.closest === 'function' && active.closest('.admin-delete-series')) return;
                var k = e.key || e.keyIdentifier || '';
                if (k === 'Enter' || k === ' ' || k === 'Spacebar' || k === 'Space') {
                    e.preventDefault(); row.click();
                }
            });
        });

        // Also provide handlers for elements explicitly marked as edit buttons (if present)
        qsa('.admin-edit-series').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = btn.getAttribute('data-id');
                var name = btn.getAttribute('data-name');
                var idEl = qs('#edit-series-id');
                var nameEl = qs('#edit-series-name');
                if (idEl) idEl.value = id;
                if (nameEl) nameEl.value = name;
                if (editSeriesModal) editSeriesModal.show();
            });
        });

        // --- Add modal wiring ---
        var addBookModalEl = qs('#adminAddModal');
        var addBookModal = addBookModalEl ? new bootstrap.Modal(addBookModalEl, { focus: false }) : null;
        var addBookBtn = qs('#admin-open-add-book');
        if (addBookBtn) addBookBtn.addEventListener('click', function () { if (addBookModal) addBookModal.show(); });

        var addSeriesBtn = qs('#admin-open-add-series');
        var adminAddSeriesModalEl = qs('#adminAddSeriesModal');
        var adminAddSeriesModal = adminAddSeriesModalEl ? new bootstrap.Modal(adminAddSeriesModalEl, { focus: false }) : null;
        if (addSeriesBtn) addSeriesBtn.addEventListener('click', function () { if (adminAddSeriesModal) adminAddSeriesModal.show(); });

        // --- Toggle new-series input for add/edit selects ---
        function toggleNewSeriesInput(selectEl, newInputEl) {
            if (!selectEl || !newInputEl) return;
            if (selectEl.value === 'new') { newInputEl.classList.remove('d-none'); try { newInputEl.focus(); } catch (e) {} }
            else { newInputEl.classList.add('d-none'); newInputEl.value = ''; }
        }

        var addSeriesSelect = qs('#add-series');
        var addSeriesNew = qs('#add-series-new');
        if (addSeriesSelect && addSeriesNew) addSeriesSelect.addEventListener('change', function () { toggleNewSeriesInput(addSeriesSelect, addSeriesNew); });

        var editSeriesSelect = qs('#edit-series');
        var editSeriesNew = qs('#edit-series-new');
        if (editSeriesSelect && editSeriesNew) editSeriesSelect.addEventListener('change', function () { toggleNewSeriesInput(editSeriesSelect, editSeriesNew); });

        // Reset add form when its modal hides
        var adminAddForm = qs('#admin-add-form-modal');
        if (addBookModalEl && adminAddForm) {
            addBookModalEl.addEventListener('hidden.bs.modal', function () {
                try { adminAddForm.reset(); if (addSeriesNew) addSeriesNew.classList.add('d-none'); } catch (e) {}
            });
        }

        // --- Overview toggle (books / series) and action button active state ---
        var rbBooks = qs('#overview-books');
        var rbSeries = qs('#overview-series');
        var booksSection = qsa('.admin-overview-books');
        var seriesSection = qsa('.admin-overview-series');

        function showBooks() { booksSection.forEach(function (el) { el.classList.remove('d-none'); }); seriesSection.forEach(function (el) { el.classList.add('d-none'); }); }
        function showSeries() { seriesSection.forEach(function (el) { el.classList.remove('d-none'); }); booksSection.forEach(function (el) { el.classList.add('d-none'); }); }

        function setActiveActionButtons(showBooksView) {
            var bookBtn = qs('#admin-open-add-book');
            var seriesBtn = qs('#admin-open-add-series');
            if (bookBtn) { if (showBooksView) bookBtn.classList.add('active'); else bookBtn.classList.remove('active'); }
            if (seriesBtn) { if (showBooksView) seriesBtn.classList.remove('active'); else seriesBtn.classList.add('active'); }
        }

        if (rbBooks) rbBooks.addEventListener('change', function () { if (this.checked) { showBooks(); setActiveActionButtons(true); } });
        if (rbSeries) rbSeries.addEventListener('change', function () { if (this.checked) { showSeries(); setActiveActionButtons(false); } });

        // Ensure initial state
        setActiveActionButtons(rbBooks && rbBooks.checked);

        // --- Header height CSS variable updater (for modal offset) ---
        function updateHeaderHeightVar() {
            try {
                var header = qs('.sticky-header');
                if (!header) return;
                var h = header.getBoundingClientRect().height || 0;
                document.documentElement.style.setProperty('--header-height', Math.ceil(h) + 'px');
            } catch (e) { /* ignore */ }
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', updateHeaderHeightVar); else updateHeaderHeightVar();
        var _resizeTimer = null;
        window.addEventListener('resize', function () { clearTimeout(_resizeTimer); _resizeTimer = setTimeout(updateHeaderHeightVar, 120); });
    });
})();
