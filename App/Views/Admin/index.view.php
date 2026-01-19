<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Core\IAuthenticator $auth */
/** @var array $books */
/** @var array $series */
/** @var string|null $flash */
?>

<!-- Include consolidated admin stylesheet -->
<link rel="stylesheet" href="<?= $link->asset('css/admin.css') ?>">

<div class="container my-4">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2>Admin panel</h2>
            <p class="text-muted"><?= htmlspecialchars($welcome ?? 'Správa kníh: pridávajte nové tituly, aktualizujte existujúce alebo ich odstraňujte.', ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="col-auto d-flex align-items-center">
            <!-- View toggle: Books / Series -->
            <div class="btn-group me-3" role="group" aria-label="Prehľad toggle" id="admin-overview-toggle">
                <input type="radio" class="btn-check" name="overview" id="overview-books" autocomplete="off" checked>
                <label class="btn btn-outline-primary btn-sm" for="overview-books">Prehľad kníh</label>

                <input type="radio" class="btn-check" name="overview" id="overview-series" autocomplete="off">
                <label class="btn btn-outline-primary btn-sm" for="overview-series">Prehľad sérií</label>
            </div>
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
    <div class="row mb-4 admin-overview admin-overview-books">
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Prehľad kníh</strong>
                    <div>
                        <button type="button" class="admin-action-btn active " id="admin-open-add-book">Pridať knihu</button>
                    </div>
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
                                    <th scope="col" class="admin-col-small">&nbsp;</th>
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
                                        <td class="admin-thumb-cell"><img src="<?= $link->asset($imgPath) ?>" alt="" class="admin-thumbnail"></td>
                                        <td><?= htmlspecialchars($b['nazov'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($b['autor'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($b['cena'], ENT_QUOTES, 'UTF-8') ?> €</td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm admin-delete-button" data-id="<?= (int)$b['id'] ?>" data-nazov="<?= htmlspecialchars($b['nazov'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Odstrániť knihu">&times;</button>
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

    <!-- Series overview table (hidden by default) -->
    <div class="row mb-4 admin-overview admin-overview-series d-none">
        <div class="col">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Prehľad sérií</strong>
                    <div>
                        <button type="button" class="admin-action-btn" id="admin-open-add-series">Pridať sériu</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($series)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0" aria-label="Prehľad sérií" role="table">
                                <caption class="visually-hidden">Prehľad sérií</caption>
                                <thead class="table-light">
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Názov</th>
                                    <th scope="col">Počet kníh</th>
                                    <th scope="col" class="admin-col-actions">Akcie</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($series as $s): ?>
                                    <tr class="admin-series-row" tabindex="0" role="button" data-id="<?= (int)$s['id'] ?>" data-name="<?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>" data-count="<?= (int)($s['count'] ?? 0) ?>">
                                        <td><?= (int)$s['id'] ?></td>
                                        <td><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-muted"><?= (int)($s['count'] ?? 0) ?></td>
                                        <td class="text-end">
                                            <div class="btn-group" role="group">
                                                <!-- Row click opens edit modal; keep only delete button here -->
                                                <button type="button" class="btn btn-sm admin-delete-series" data-id="<?= (int)$s['id'] ?>" data-name="<?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?>" aria-label="Odstrániť sériu">&times;</button>
                                             </div>
                                         </td>
                                     </tr>
                                 <?php endforeach; ?>
                                 </tbody>
                             </table>
                         </div>
                     <?php else: ?>
                         <div class="p-3 small text-muted">Žiadne série v databáze.</div>
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
                            <label for="add-series-new" class="visually-hidden">Názov novej série</label>
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
                        <p>Chcete odstrániť <span id="modal-delete-type">knihu</span> <strong id="modal-delete-nazov"></strong>?</p>
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
                            <label for="edit-series-new" class="visually-hidden">Názov novej série</label>
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

    <!-- Series add/edit/delete modals -->
    <div class="modal fade" id="adminAddSeriesModal" tabindex="-1" aria-labelledby="adminAddSeriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $link->url('Admin.seriesAdd') ?>" method="post" id="admin-add-series-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminAddSeriesModalLabel">Pridať sériu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label for="add-series-name" class="form-label">Názov série</label>
                            <input id="add-series-name" name="name" type="text" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-success">Pridať sériu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="adminEditSeriesModal" tabindex="-1" aria-labelledby="adminEditSeriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $link->url('Admin.seriesEdit') ?>" method="post" id="admin-edit-series-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminEditSeriesModalLabel">Upraviť sériu</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit-series-id" value="">
                        <div class="mb-2">
                            <label for="edit-series-name" class="form-label">Názov série</label>
                            <input id="edit-series-name" name="name" type="text" class="form-control" required>
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

    <div class="modal fade" id="adminDeleteSeriesModal" tabindex="-1" aria-labelledby="adminDeleteSeriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="<?= $link->url('Admin.seriesDelete') ?>" method="post" id="admin-delete-series-form">
                    <div class="modal-header">
                        <h5 class="modal-title" id="adminDeleteSeriesModalLabel">Potvrdiť odstránenie série</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="modal-delete-series-id" value="">
                        <p>Chcete odstrániť sériu <strong id="modal-delete-series-name"></strong>?<br>
                            (Poznámka: knihy v tejto sérii nebudú odstránené automaticky.)</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zrušiť</button>
                        <button type="submit" class="btn btn-danger">Odstrániť sériu</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>

<?php /* expose delete URLs via data-attributes on a hidden element so the external admin.js can read them */ ?>
<div id="admin-root" style="display:none"
     data-delete-book-url="<?= htmlspecialchars($link->url('Admin.adminDelete'), ENT_QUOTES, 'UTF-8') ?>"
     data-delete-series-url="<?= htmlspecialchars($link->url('Admin.seriesDelete'), ENT_QUOTES, 'UTF-8') ?>">
</div>

<!-- admin.js is included globally in the layout; do not include it here to avoid duplicate execution -->
