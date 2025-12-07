// When an admin clicks a row in the books table, populate the update/delete forms for convenience.
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.admin-book-row').forEach(function (row) {
        row.addEventListener('click', function () {
            var id = this.dataset.id || '';
            var nazov = this.dataset.nazov || '';
            var autor = this.dataset.autor || '';
            var cena = this.dataset.cena || '';
            var obrazok = this.dataset.obrazok || '';

            var updateId = document.getElementById('update-id');
            var updateNazov = document.getElementById('update-nazov');
            var updateAutor = document.getElementById('update-autor');
            var updateCena = document.getElementById('update-cena');
            var updateObrazok = document.getElementById('update-obrazok');
            var deleteId = document.getElementById('delete-id');

            if (updateId) updateId.value = id;
            if (updateNazov) updateNazov.value = nazov;
            if (updateAutor) updateAutor.value = autor;
            if (updateCena) updateCena.value = cena;
            if (updateObrazok) updateObrazok.value = obrazok;
            if (deleteId) deleteId.value = id;

            // scroll to update form (small UX improvement)
            var updateForm = document.getElementById('admin-update-form');
            if (updateForm) updateForm.scrollIntoView({behavior: 'smooth', block: 'center'});
        });
    });
});