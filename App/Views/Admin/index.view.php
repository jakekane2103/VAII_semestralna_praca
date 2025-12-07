<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Core\IAuthenticator $auth */
/** @var array $books */
/** @var string|null $flash */
?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Admin panel</h2>
            <p class="text-muted"><?= htmlspecialchars($welcome ?? 'Správa kníh: pridávajte nové tituly, aktualizujte existujúce alebo ich odstraňujte.', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
    </div>

    <!-- Flash message -->
    <?php if (!empty($flash)): ?>
        <div class="row mb-3">
            <div class="col">
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Books overview table -->
    <div class="row mb-4">
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-header">
                    <strong>Prehľad kníh</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($books)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0" aria-label="Prehľad kníh" role="table">
                                <caption class="visually-hidden">Prehľad kníh</caption>
                                <thead class="table-light">
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Obrázok</th>
                                    <th scope="col">Názov</th>
                                    <th scope="col">Autor</th>
                                    <th scope="col">Cena</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($books as $b): ?>
                                    <tr class="admin-book-row" data-id="<?= (int)$b['id'] ?>" data-nazov="<?= htmlspecialchars($b['nazov'], ENT_QUOTES, 'UTF-8') ?>" data-autor="<?= htmlspecialchars($b['autor'], ENT_QUOTES, 'UTF-8') ?>" data-cena="<?= htmlspecialchars($b['cena'], ENT_QUOTES, 'UTF-8') ?>" data-obrazok="<?= htmlspecialchars($b['obrazok'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <td><?= (int)$b['id'] ?></td>
                                        <td style="width:72px;"><img src="<?= $link->asset($b['obrazok'] ?? 'images/Real_Estate_(101).jpg') ?>" alt="" style="height:48px;object-fit:cover;border-radius:.25rem;"></td>
                                        <td><?= htmlspecialchars($b['nazov'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($b['autor'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($b['cena'], ENT_QUOTES, 'UTF-8') ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-3 small text-muted">Žiadne knihy v databáze.</div>
                    <?php endif; ?>
                </div>
            </div>
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
                    <form action="<?= $link->url('Admin.adminAdd') ?>" method="post" id="admin-add-form">
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
                    <form action="<?= $link->url('Admin.adminUpdate') ?>" method="post" id="admin-update-form">
                        <div class="mb-2">
                            <label class="form-label">ID knihy</label>
                            <input type="number" name="id_kniha" id="update-id" class="form-control" min="1" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Názov (voliteľné)</label>
                            <input type="text" name="nazov" id="update-nazov" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Autor (voliteľné)</label>
                            <input type="text" name="autor" id="update-autor" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Cena (€) (voliteľné)</label>
                            <input type="number" name="cena" id="update-cena" step="0.01" min="0" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Obrázok (cesta) (voliteľné)</label>
                            <input type="text" name="obrazok" id="update-obrazok" class="form-control" placeholder="images/nieco.jpg">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Popis (voliteľné)</label>
                            <textarea name="popis" id="update-popis" rows="3" class="form-control"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Séria (voliteľné)</label>
                            <input type="text" name="seria" id="update-seria" class="form-control">
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
                    <form action="<?= $link->url('Admin.adminDelete') ?>" method="post" id="admin-delete-form">
                        <div class="mb-3">
                            <label class="form-label">ID knihy</label>
                            <input type="number" name="id_kniha" id="delete-id" class="form-control" min="1" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Odstrániť knihu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

</script>
