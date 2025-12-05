<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Core\IAuthenticator $auth */
?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Admin panel</h2>
            <p class="text-muted mb-1">Welcome, <strong><?= htmlspecialchars($auth->user->name ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong>.</p>
            <p class="text-muted">Manage books: add new titles, update existing ones or remove them.</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Add book -->
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-success text-white">
                    <strong>Pridať knihu</strong>
                </div>
                <div class="card-body">
                    <form action="<?= $link->url('Books.adminAdd') ?>" method="post">
                        <div class="mb-2">
                            <label class="form-label">Názov</label>
                            <input type="text" name="nazov" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Autor</label>
                            <input type="text" name="autor" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Séria</label>
                            <input type="text" name="seria" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Cena (€)</label>
                            <input type="number" name="cena" step="0.01" min="0" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Obrázok (cesta)</label>
                            <input type="text" name="obrazok" class="form-control" placeholder="images/nieco.jpg">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Popis</label>
                            <textarea name="popis" rows="3" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Pridať knihu</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Update book -->
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <strong>Upraviť knihu</strong>
                </div>
                <div class="card-body">
                    <form action="<?= $link->url('Books.adminUpdate') ?>" method="post">
                        <div class="mb-2">
                            <label class="form-label">ID knihy</label>
                            <input type="number" name="id_kniha" class="form-control" min="1" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Názov (voliteľné)</label>
                            <input type="text" name="nazov" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Autor (voliteľné)</label>
                            <input type="text" name="autor" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Cena (€) (voliteľné)</label>
                            <input type="number" name="cena" step="0.01" min="0" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Obrázok (cesta) (voliteľné)</label>
                            <input type="text" name="obrazok" class="form-control" placeholder="images/nieco.jpg">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Popis (voliteľné)</label>
                            <textarea name="popis" rows="3" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Uložiť zmeny</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete book -->
        <div class="col-12 col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-danger text-white">
                    <strong>Odstrániť knihu</strong>
                </div>
                <div class="card-body">
                    <form action="<?= $link->url('Books.adminDelete') ?>" method="post">
                        <div class="mb-3">
                            <label class="form-label">ID knihy</label>
                            <input type="number" name="id_kniha" class="form-control" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Odstrániť knihu</button>
                    </form>

                    <hr>
                    <p class="small text-muted mb-1">Tip: Zoznam ID kníh nájdeš v prehľade kníh.</p>
                    <a href="<?= $link->url('Books.index') ?>" class="small">Otvoriť stránku s knihami</a>
                </div>
            </div>
        </div>
    </div>
</div>