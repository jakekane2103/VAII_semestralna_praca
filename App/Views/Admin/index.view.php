<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Core\IAuthenticator $auth */
/** @var array $books */
/** @var array $series */
/** @var string|null $flash */
?>

<div class="container my-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2>Admin panel</h2>
            <p class="text-muted"><?= htmlspecialchars($welcome ?? 'Správa kníh: pridávajte nové tituly, aktualizujte existujúce alebo ich odstraňujte.', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-success" id="admin-open-add">Pridať knihu</button>
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
                                    <th scope="col" style="width:48px;">&nbsp;</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($books as $b): ?>
                                    <?php
                                        // Normalize thumbnail path for display (prefix bare filename with images/books/)
                                        $rawImg = $b['obrazok'] ?? '';
                                        if ($rawImg === null || $rawImg === '') {
                                            $imgPath = 'images/Real_Estate_(101).jpg';
                                        } elseif (strpos($rawImg, '/') === false) {
                                            $imgPath = 'images/books/' . $rawImg;
                                        } else {
                                            $imgPath = $rawImg;
                                        }
                                    ?>
                                    <tr class="admin-book-row" data-id="<?= (int)$b['id'] ?>" data-nazov="<?= htmlspecialchars($b['nazov'], ENT_QUOTES, 'UTF-8') ?>" data-autor="<?= htmlspecialchars($b['autor'], ENT_QUOTES, 'UTF-8') ?>" data-cena="<?= htmlspecialchars($b['cena'], ENT_QUOTES, 'UTF-8') ?>" data-obrazok="<?= htmlspecialchars($b['obrazok'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-series-id="<?= htmlspecialchars($b['series_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-series-name="<?= htmlspecialchars($b['series_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-popis="<?= htmlspecialchars($b['popis'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                        <td><?= (int)$b['id'] ?></td>
                                        <td style="width:72px;"><img src="<?= $link->asset($imgPath) ?>" alt="" style="height:48px;object-fit:cover;border-radius:.25rem;"></td>
                                        <td><?= htmlspecialchars($b['nazov'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($b['autor'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($b['cena'], ENT_QUOTES, 'UTF-8') ?> €</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-outline-danger admin-delete-button" data-id="<?= (int)$b['id'] ?>" data-nazov="<?= htmlspecialchars($b['nazov'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Odstrániť knihu">&times;</button>
                                        </td>
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

    <!-- Previously the Add book card lived here; replaced by modal triggered from header button -->
    <div class="row g-4">
        <!-- Placeholder column to keep spacing if needed (can be removed) -->
        <div class="col-12">
            <!-- Content continues: book list below -->
        </div>
    </div>

    <!-- Add book modal -->
    <div class="modal fade" id="adminAddModal" tabindex="-1" aria-labelledby="adminAddModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $link->url('Admin.adminAdd') ?>" method="post" id="admin-add-form-modal">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminAddModalLabel">Pridať knihu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="add-nazov" class="form-label">Názov</label>
                            <input id="add-nazov" type="text" name="nazov" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label for="add-autor" class="form-label">Autor</label>
                            <input id="add-autor" type="text" name="autor" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label for="add-series" class="form-label">Séria</label>
                            <select id="add-series" name="series_id" class="form-select">
                                <option value="">(žiadna)</option>
                                <?php foreach ($series as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                                <option value="new">-- Pridať novú sériu --</option>
                            </select>
                            <input id="add-series-new" name="series_name_new" type="text" class="form-control mt-2 d-none" placeholder="Názov novej série">
                        </div>
                        <div class="mb-2">
                            <label for="add-cena" class="form-label">Cena (€)</label>
                            <input id="add-cena" type="number" name="cena" step="0.01" min="0" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label for="add-obrazok" class="form-label">Obrázok (cesta)</label>
                            <input id="add-obrazok" type="text" name="obrazok" class="form-control" placeholder="images/nieco.jpg">
                        </div>
                        <div class="mb-3">
                            <label for="add-popis" class="form-label">Popis</label>
                            <textarea id="add-popis" name="popis" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-success">Pridať knihu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete confirmation modal -->
    <div class="modal fade" id="adminDeleteModal" tabindex="-1" aria-labelledby="adminDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $link->url('Admin.adminDelete') ?>" method="post" id="admin-delete-form-modal">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminDeleteModalLabel">Potvrdiť odstránenie</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_kniha" id="modal-delete-id" value="">
                        <p>Chcete odstrániť knihu <strong id="modal-delete-nazov"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-danger">Odstrániť</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit book modal -->
    <div class="modal fade" id="adminEditModal" tabindex="-1" aria-labelledby="adminEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $link->url('Admin.adminUpdate') ?>" method="post" id="admin-edit-form-modal">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminEditModalLabel">Upraviť knihu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_kniha" id="edit-id" value="">

                        <div class="mb-2">
                            <label for="edit-nazov" class="form-label">Názov</label>
                            <input id="edit-nazov" name="nazov" type="text" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label for="edit-autor" class="form-label">Autor</label>
                            <input id="edit-autor" name="autor" type="text" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label for="edit-series" class="form-label">Séria</label>
                            <select id="edit-series" name="series_id" class="form-select">
                                <option value="none">(žiadna)</option>
                                <?php foreach ($series as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                                <option value="new">-- Pridať novú sériu --</option>
                            </select>
                            <input id="edit-series-new" name="series_name_new" type="text" class="form-control mt-2 d-none" placeholder="Názov novej série">
                        </div>

                        <div class="mb-2">
                            <label for="edit-cena" class="form-label">Cena (€)</label>
                            <input id="edit-cena" name="cena" type="number" step="0.01" min="0" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label for="edit-obrazok" class="form-label">Obrázok (cesta)</label>
                            <input id="edit-obrazok" name="obrazok" type="text" class="form-control" placeholder="images/nieco.jpg">
                        </div>

                        <div class="mb-3">
                            <label for="edit-popis" class="form-label">Popis</label>
                            <textarea id="edit-popis" name="popis" rows="3" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-primary">Uložiť zmeny</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
