// Unified admin JS: handle delete modal, edit modal and add-modal interactions
document.addEventListener('DOMContentLoaded', function () {
    // Delete modal
    var deleteModalEl = document.getElementById('adminDeleteModal');
    var deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl, { focus: false }) : null;

    function onDeleteClick(e) {
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();
        try { this.blur(); } catch (err) { /* ignore */ }

        var id = this.getAttribute('data-id');
        var nazov = this.getAttribute('data-nazov') || '';
        var input = document.getElementById('modal-delete-id');
        var nameEl = document.getElementById('modal-delete-nazov');
        if (input) input.value = id;
        if (nameEl) nameEl.textContent = nazov;
        if (deleteModal) deleteModal.show();
    }

    var deleteButtons = document.querySelectorAll('.admin-delete-button');
    deleteButtons.forEach(function (btn) {
        // ensure other handlers don't run first
        btn.addEventListener('pointerdown', function (e) {
            if (e.stopPropagation) e.stopPropagation();
            // do not prevent default on pointerdown so clicks still work
        }, { passive: true });
        btn.addEventListener('click', onDeleteClick);
    });

    // Edit modal
    var editModalEl = document.getElementById('adminEditModal');
    var editModal = editModalEl ? new bootstrap.Modal(editModalEl, { focus: false }) : null;

    function toggleNewSeriesInput(selectEl, newInputEl) {
        if (!selectEl || !newInputEl) return;
        if (selectEl.value === 'new') {
            newInputEl.classList.remove('d-none');
            newInputEl.focus();
        } else {
            newInputEl.classList.add('d-none');
            newInputEl.value = '';
        }
    }

    function onRowClick(e) {
        // If the click originated from inside a delete button, ignore (delete handler will run)
        if (e && e.target && typeof e.target.closest === 'function' && e.target.closest('.admin-delete-button')) {
            return;
        }

        var row = this;
        var id = row.getAttribute('data-id') || '';
        var nazov = row.getAttribute('data-nazov') || '';
        var autor = row.getAttribute('data-autor') || '';
        var cena = row.getAttribute('data-cena') || '';
        var obrazok = row.getAttribute('data-obrazok') || '';
        var popis = row.getAttribute('data-popis') || '';
        var seriesId = row.getAttribute('data-series-id') || '';
        var seriesName = row.getAttribute('data-series-name') || '';

        var idEl = document.getElementById('edit-id');
        var nazovEl = document.getElementById('edit-nazov');
        var autorEl = document.getElementById('edit-autor');
        var cenaEl = document.getElementById('edit-cena');
        var obrazokEl = document.getElementById('edit-obrazok');
        var popisEl = document.getElementById('edit-popis');
        var seriesSelectEl = document.getElementById('edit-series');
        var seriesNewEl = document.getElementById('edit-series-new');

        if (idEl) idEl.value = id;
        if (nazovEl) nazovEl.value = nazov;
        if (autorEl) autorEl.value = autor;
        if (cenaEl) cenaEl.value = cena;
        if (obrazokEl) obrazokEl.value = obrazok;
        if (popisEl) popisEl.value = popis;

        if (seriesSelectEl) {
            if (seriesId && seriesId !== '') {
                // Try to set the select to the existing series id; if not present, fall back to 'new'
                var found = false;
                for (var i = 0; i < seriesSelectEl.options.length; i++) {
                    if (String(seriesSelectEl.options[i].value) === String(seriesId)) {
                        seriesSelectEl.value = String(seriesId);
                        found = true;
                        break;
                    }
                }
                if (!found) {
                    seriesSelectEl.value = 'new';
                    if (seriesNewEl) {
                        seriesNewEl.classList.remove('d-none');
                        seriesNewEl.value = seriesName || '';
                    }
                } else {
                    if (seriesNewEl) seriesNewEl.classList.add('d-none');
                }
            } else if (seriesName && seriesName !== '') {
                // No series_id but a name exists (edge case) -> treat as new
                seriesSelectEl.value = 'new';
                if (seriesNewEl) {
                    seriesNewEl.classList.remove('d-none');
                    seriesNewEl.value = seriesName;
                }
            } else {
                seriesSelectEl.value = '';
                if (seriesNewEl) seriesNewEl.classList.add('d-none');
            }
        }

        if (editModal) editModal.show();
    }

    document.querySelectorAll('.admin-book-row').forEach(function (row) {
        row.removeEventListener('click', onRowClick); // safe no-op
        row.addEventListener('click', onRowClick);
    });

    // Wire up change handlers for edit-series select
    var editSeriesSelect = document.getElementById('edit-series');
    var editSeriesNew = document.getElementById('edit-series-new');
    if (editSeriesSelect && editSeriesNew) {
        editSeriesSelect.addEventListener('change', function () {
            toggleNewSeriesInput(editSeriesSelect, editSeriesNew);
        });
    }

    // Add modal
    var addModalEl = document.getElementById('adminAddModal');
    var addModal = addModalEl ? new bootstrap.Modal(addModalEl, { focus: false }) : null;
    var openAddBtn = document.getElementById('admin-open-add');
    var addForm = document.getElementById('admin-add-form-modal');

    if (openAddBtn) {
        openAddBtn.addEventListener('click', function (e) {
            if (e && e.preventDefault) e.preventDefault();
            if (e && e.stopPropagation) e.stopPropagation();
            try { this.blur(); } catch (err) { /* ignore */ }
            if (addModal) addModal.show();
        });
    }

    // Wire up change handlers for add-series select
    var addSeriesSelect = document.getElementById('add-series');
    var addSeriesNew = document.getElementById('add-series-new');
    if (addSeriesSelect && addSeriesNew) {
        addSeriesSelect.addEventListener('change', function () {
            toggleNewSeriesInput(addSeriesSelect, addSeriesNew);
        });
    }

    // Reset the add form when modal hides so subsequent opens are clean
    if (addModalEl && addForm) {
        addModalEl.addEventListener('hidden.bs.modal', function () {
            try { addForm.reset(); if (addSeriesNew) addSeriesNew.classList.add('d-none'); } catch (err) { /* ignore */ }
        });
    }
});