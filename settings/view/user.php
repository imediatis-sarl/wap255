<?php
    /*
     * --------------
     * WAP
     * --------------
     *
     * This file is part of the Meta WhatsApp messaging API management project.
     *
     * WAP is a private library: you may not redistribute
     * and/or modify it without the prior consent of IMEDIATIS Ltd.
     *
     * Copyright (c) IMEDIATIS Ltd.
     * --------------------------------------------------------------------
     * @author Cyrille WOUPO (cyrille@imediatis.net)
     * @copyright (c) IMEDIATIS Ltd 2025
     * @license IMEDIATIS Ltd. (https://imediatis.net)
     * @github https://github.com/team-imediatis
     * --------------------------------------------------------------------
     */

    use App\Units\Settings\SettingsCtrl;
    use App\Tools\{F, Router, Session, Sidebar};
    use App\Core\{User, Profile, Country, Lexi};

    if (empty($route)) return;
    Sidebar::_show();
    $session = new Session();

    // Charger les profils et pays pour le formulaire d'ajout/édition
    $profiles = Profile::_getRecords(['active' => true, 'assignable' => true]);
    $countries = []; //(new Country())->getActiveCountries();
?>

<div class="container py-4">
    <div id="userRoutesManager" class="row"
         data-csrf-token="<?= Router::generateCsrfToken("user_handler") ?>"
         data-handler-url="<?= Router::generateCorePath($route["module"], "user-handler") ?>"
         data-toggle-url="<?= Router::generateCorePath($route["module"], "user-toggle-status") ?>"
         data-delete-url="<?= Router::generateCorePath($route["module"], "user-delete") ?>"
         data-register-url="<?= Router::generateCorePath($route["module"], "user-register") ?>"
         data-credentials-url="<?= Router::generateCorePath($route["module"], "user-generate-credentials") ?>">
        <div class="col-12">
            <div class="card bg-transparent border-0">
                <div class="card-header bg-transparent py-3 border-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <a href="<?= Router::generateModulePath($route[MDL]) ?>" class="me-3 lead-1-4 form-icon-link">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h5 class="mb-0 fw-normal"><?= Sidebar::_viewTitle() ?></h5>
                        <a href="#" class="ms-auto lead-1-4 form-icon-link" data-bs-toggle="modal" data-bs-target="#userFormModal" id="addUserBtn">
                            <i class="bi bi-plus-lg"></i>
                        </a>
                        <?php if($session->getUser()->isRoot()): ?>
                            <a href="<?= Router::generateModulePath($route[MDL], SettingsCtrl::profile) ?>" class="ms-2 lead-1-4 form-icon-link">
                                <i class="bi bi-people-fill"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php F::_alert(); ?>
                <div class="card-body bg-primary-form shadow-lg p-3 p-lg-4 rounded-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="search-container">
                            <i class="bi bi-search"></i>
                            <input type="text" class="form-control" id="searchUsers" placeholder="<?= Lexi::_get('search_users') ?>">
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel me-1"></i> <?= Lexi::_get('filters') ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item filter-option" href="#" data-filter="all"><?= Lexi::_get('all_users') ?></a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-filter="active"><?= Lexi::_get('active_users') ?></a></li>
                                <li><a class="dropdown-item filter-option" href="#" data-filter="inactive"><?= Lexi::_get('inactive_users') ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item filter-option" href="#" data-filter="recently-added"><?= Lexi::_get('recently_added') ?></a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table datatable table-hover" id="usersTable">
                            <thead>
                            <tr>
                                <th><?= Lexi::_get('user_info') ?></th>
                                <th><?= Lexi::_get('contact') ?></th>
                                <th><?= Lexi::_get('profile') ?></th>
                                <th><?= Lexi::_get('status') ?></th>
                                <th class="text-end"><?= Lexi::_get('actions') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <!-- Les données seront chargées dynamiquement via AJAX -->
                            </tbody>
                        </table>
                        <div id="loadingIndicator" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden"><?= Lexi::_get('loading') ?></span>
                            </div>
                        </div>
                        <div id="noResultsMessage" class="alert alert-info text-center d-none">
                            <?= Lexi::_get('no_users_found') ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted small">
                            <span id="totalRecords">0</span> <?= Lexi::_get('users') ?>
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination pagination-sm" id="usersPagination">
                                <!-- Pagination générée dynamiquement -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour ajouter/éditer un utilisateur -->
<div class="modal fade" id="userFormModal" tabindex="-1" aria-labelledby="userFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="userFormModalLabel"><?= Lexi::_get('add_user') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="userForm" class="needs-validation" novalidate>
                    <input type="hidden" name="<?= F::csrfToken ?>" value="<?= Router::generateCsrfToken('user_form') ?>">
                    <input type="hidden" name="userId" id="userId" value="">

                    <div class="row g-3">
                        <!-- Informations personnelles -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2"><?= Lexi::_get('personal_information') ?></h6>
                        </div>

                        <div class="col-md-6">
                            <?= F::_text([
                                'name' => 'firstname',
                                'placeholder' => Lexi::_get('firstname'),
                                'required' => true,
                                'maxLength' => 100,
                                'divClasses' => 'mb-3'
                            ]) ?>
                        </div>

                        <div class="col-md-6">
                            <?= F::_text([
                                'name' => 'lastname',
                                'placeholder' => Lexi::_get('lastname'),
                                'required' => true,
                                'maxLength' => 100,
                                'divClasses' => 'mb-3'
                            ]) ?>
                        </div>

                        <!-- Informations de connexion -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2"><?= Lexi::_get('connection_information') ?></h6>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="profile" name="profile" required>
                                    <option value="" selected disabled><?= Lexi::_get('select_profile') ?></option>
                                    <?php foreach ($profiles as $profile): ?>
                                        <option value="<?= $profile['guid'] ?>"><?= $profile['name'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="profile"><?= Lexi::_get('profile') ?></label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="language" name="language" required>
                                    <option value="fr"><?= Lexi::_get('french') ?></option>
                                    <option value="en"><?= Lexi::_get('english') ?></option>
                                </select>
                                <label for="language"><?= Lexi::_get('language') ?></label>
                            </div>
                        </div>

                        <div class="col-md-6 user-code-container">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="userCode" name="userCode" placeholder="<?= Lexi::_get('user_code') ?>" readonly>
                                <label for="userCode"><?= Lexi::_get('user_code') ?></label>
                            </div>
                        </div>

                        <div class="col-md-6 user-pin-container">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="userPin" name="userPin" placeholder="<?= Lexi::_get('user_pin') ?>" readonly>
                                <label for="userPin"><?= Lexi::_get('user_pin') ?></label>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="generateCredentials">
                                <i class="bi bi-key me-1"></i> <?= Lexi::_get('generate_credentials') ?>
                            </button>
                        </div>

                        <!-- Informations de contact -->
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-2"><?= Lexi::_get('contact_information') ?></h6>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="country" name="country" required>
                                    <option value="" selected disabled><?= Lexi::_get('select_country') ?></option>
                                    <?php foreach ($countries as $country): ?>
                                        <option value="<?= $country['guid'] ?>"><?= Lexi::_get($country['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="country"><?= Lexi::_get('country') ?></label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <?= F::_text([
                                'name' => 'mobile',
                                'placeholder' => Lexi::_get('mobile'),
                                'required' => true,
                                'type' => 'tel',
                                'maxLength' => 20,
                                'divClasses' => 'mb-3'
                            ]) ?>
                        </div>

                        <div class="col-12">
                            <?= F::_text([
                                'name' => 'email',
                                'placeholder' => Lexi::_get('email'),
                                'required' => true,
                                'type' => 'email',
                                'maxLength' => 128,
                                'divClasses' => 'mb-3'
                            ]) ?>
                        </div>

                        <!-- Statut du compte -->
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="active" name="active" checked>
                                <label class="form-check-label" for="active"><?= Lexi::_get('account_active') ?></label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= Lexi::_get('cancel') ?>
                </button>
                <button type="button" class="btn btn-primary" id="saveUserBtn">
                    <i class="bi bi-save me-1"></i> <?= Lexi::_get('save') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour la suppression -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteUserModalLabel"><?= Lexi::_get('confirm_deletion') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><?= Lexi::_get('delete_user_confirmation') ?></p>
                <p class="fw-bold" id="deleteUserName"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <?= Lexi::_get('cancel') ?>
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-1"></i> <?= Lexi::_get('delete') ?>
                </button>
            </div>
        </div>
    </div>
</div>