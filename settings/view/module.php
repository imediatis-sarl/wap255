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
    use App\Tools\{F, Router, Session, U};
    use App\Core\{Lexi, Module};

    if (empty($route)) return;
    if (empty($preload)) return;

    $preload->sidebar();
    $session = new Session();
    $module = Module::_load(U::_fetchString($_GET, [TOKEN]), true);
?>
<div class="container mt-4">
    <div class="row">
        <div class="col col-md-6 mx-auto">
            <div class="card bg-transparent border-0 animate__animated animate__backInLeft">
                <div class="card-header bg-transparent py-3 border-0">
                    <div class="d-flex align-items-center">
                        <a href="<?= Router::generateModulePath($route[MDL]) ?>" class="me-3 lead-1-4">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h5 class="mb-0 fw-normal"><?= $preload->getViewTitle() ?></h5>
                    </div>
                </div>
                <?php F::_alert(); ?>
                <div class="card-body bg-primary-form shadow-lg p-0 rounded-3">
                    <?php
                        echo F::_form(true, [
                            name => SettingsCtrl::moduleRegisterForm,
                            action => Router::generateCorePath($route[MDL], 'module-register')
                        ]);
                        echo F::_hidden(SettingsCtrl::itemReference, $module->getToken());
                    ?>
                    <div class="px-4 py-4 border-bottom">
                        <div class="row g-3">
                            <div class="col-12">
                                <?php
                                    echo F::_text([
                                        'name' => SettingsCtrl::itemName,
                                        'placeholder' => Lexi::_get('wording_name'),
                                        'maxLength' => 50,
                                        'class' => 'form-control',
                                        'value' => $module->getName(),
                                    ]);
                                ?>
                            </div>
                            <div class="col-12">
                                <?php
                                    echo F::_text([
                                        'name' => SettingsCtrl::itemDescription,
                                        'placeholder' => 'Description',
                                        'maxLength' => 50,
                                        'class' => 'form-control',
                                        'value' => $module->getDescription()
                                    ]);
                                ?>
                            </div>
                            <div class="col-12">
                                <?php
                                    echo F::_checkbox([
                                        name => SettingsCtrl::itemIsAssignable,
                                        'label' => Lexi::_get('module_can_be_assigned'),
                                        'divClasses' => 'mb-0 form-check form-check-inline',
                                        'checked' => $module->isAssignable()
                                    ]);
                                    echo F::_checkbox([
                                        name => SettingsCtrl::itemIsActive,
                                        'label' => Lexi::_get('module_is_active'),
                                        'divClasses' => 'mb-0 ms-md-4 form-check form-check-inline',
                                        'checked' => $module->isActive()
                                    ])
                                ?>
                            </div>
                            <div class="col-12 mt-1">
                                <?php
                                    echo F::_checkbox([
                                        name => SettingsCtrl::itemIsSecured,
                                        'label' => Lexi::_get('module_is_secure'),
                                        'divClasses' => 'mb-3 form-check form-check-inline',
                                        'checked' => $module->isSecured()
                                    ]);
                                ?>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-lg btn-primary w-100 btn-col">
                                    <?= Lexi::_get('save') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?= F::_form(false) ?>
                    <div class="px-4 py-3">
                        <table id="ModuleTable" class="table datatable w-100"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour la gestion des vues -->
<div class="modal fade" id="viewsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="viewsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewsModalLabel"><?= Lexi::_get('manage_views') ?> - <span
                            class="text-primary" id="moduleNameDisplay"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-primary-form">
                <!-- Navigation par onglets pour le formulaire et la liste -->
                <nav class="nav nav-tabs" id="viewsTabs" role="tablist">
                    <button class="nav-link active" id="form-tab" data-bs-toggle="tab" data-bs-target="#form-content"
                            type="button" role="tab" aria-controls="form-content" aria-selected="true">
                        <?= Lexi::_get('view_editor') ?>
                    </button>
                    <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list-content"
                            type="button" role="tab" aria-controls="list-content" aria-selected="false">
                        <?= Lexi::_get('data_list') ?>
                    </button>
                </nav>

                <div class="tab-content px-3 pt-4 pb-3" id="viewsTabsContent">
                    <!-- Onglet du formulaire -->
                    <div class="tab-pane fade show active" id="form-content" role="tabpanel" aria-labelledby="form-tab">
                        <form id="viewForm">
                            <input type="hidden" name="view_id" id="view_id">
                            <input type="hidden" name="module_id" id="module_id">
                            <input type="hidden" name="<?= F::csrfToken ?>"
                                   value="<?= Router::generateCsrfToken('view_form') ?>">

                            <div class="row g-3">
                                <div class="col-12 col-md-4">
                                    <?= F::_text([
                                        'name' => 'view_name',
                                        'placeholder' => Lexi::_get('wording_name'),
                                        'required' => true,
                                        'maxLength' => 100,
                                        'divClasses' => 'mb-3 mb-md-1'
                                    ]) ?>
                                </div>
                                <div class="col-12 col-md-4">
                                    <?= F::_text([
                                        'name' => 'view_filename',
                                        'placeholder' => Lexi::_get('filename'),
                                        'required' => true,
                                        'maxLength' => 100,
                                        'divClasses' => 'mb-3 mb-md-1'
                                    ]) ?>
                                </div>
                                <div class="col-12 col-md-4">
                                    <?= F::_text([
                                        'name' => 'view_image',
                                        'placeholder' => Lexi::_get('thumbnail_image'),
                                        'required' => false,
                                        'maxLength' => 255,
                                        'divClasses' => 'mb-3 mb-md-1'
                                    ]) ?>
                                </div>
                                <div class="col-12">
                                    <?= F::_textarea([
                                        'name' => 'view_description',
                                        'placeholder' => Lexi::_get('detail_description'),
                                        'required' => false,
                                        'rows' => 3,
                                        'maxLength' => 500,
                                        'divClasses' => 'mb-3 mb-md-1'
                                    ]) ?>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="view_header_required"
                                               name="view_header_required">
                                        <label class="form-check-label"
                                               for="view_header_required"><?= Lexi::_get('header_data_required') ?></label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="view_homepage"
                                               name="view_homepage">
                                        <label class="form-check-label"
                                               for="view_homepage"><?= Lexi::_get('default_module_view') ?></label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="view_identifier"
                                               name="view_identifier">
                                        <label class="form-check-label"
                                               for="view_identifier"><?= Lexi::_get('view_enables_authentication') ?></label>
                                    </div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="view_active"
                                               name="view_active" checked>
                                        <label class="form-check-label"
                                               for="view_active"><?= Lexi::_get('item_is_active') ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-light"
                                        id="resetViewForm"><?= Lexi::_get('reset') ?></button>
                                <button type="submit" class="btn btn-primary"><?= Lexi::_get('save') ?></button>
                            </div>
                        </form>
                    </div>

                    <!-- Onglet de la liste des vues -->
                    <div class="tab-pane fade" id="list-content" role="tabpanel" aria-labelledby="list-tab">
                        <div class="table-responsive">
                            <table id="viewsDataTable" class="table datatable table-sm table-hover w-100">
                                <thead>
                                <tr>
                                    <th><?= Lexi::_get('name') ?></th>
                                    <th><?= Lexi::_get('filename') ?></th>
                                    <th><?= Lexi::_get('homepage') ?></th>
                                    <th><?= Lexi::_get('active') ?></th>
                                    <th><?= Lexi::_get('actions') ?></th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour la gestion des features -->
<div class="modal fade" id="featuresModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
     aria-labelledby="featuresModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="featuresModalLabel"><?= Lexi::_get('manage_features') ?> - <span
                            class="text-primary" id="moduleNameFeatureDisplay"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-primary-form">
                <nav class="nav nav-tabs" id="featuresTabs" role="tablist">
                    <button class="nav-link active" id="feature-form-tab" data-bs-toggle="tab"
                            data-bs-target="#feature-form-content"
                            type="button" role="tab" aria-controls="feature-form-content" aria-selected="true">
                        <?= Lexi::_get('view_editor') ?>
                    </button>
                    <button class="nav-link" id="feature-list-tab" data-bs-toggle="tab"
                            data-bs-target="#feature-list-content"
                            type="button" role="tab" aria-controls="feature-list-content" aria-selected="false">
                        <?= Lexi::_get('data_list') ?>
                    </button>
                </nav>

                <div class="tab-content p-3" id="featuresTabsContent">
                    <!-- Onglet du formulaire -->
                    <div class="tab-pane fade show active pt-lg-3" id="feature-form-content" role="tabpanel"
                         aria-labelledby="feature-form-tab">
                        <form id="featureForm">
                            <input type="hidden" name="feature_id" id="feature_id">
                            <input type="hidden" name="module_id" id="feature_module_id">
                            <input type="hidden" name="<?= F::csrfToken ?>"
                                   value="<?= Router::generateCsrfToken('feature_form') ?>">

                            <div class="row g-3">
                                <div class="col-12 col-md-5">
                                    <?= F::_text([
                                        'name' => 'feature_code',
                                        'placeholder' => Lexi::_get('Code'),
                                        'required' => true,
                                        'maxLength' => 30,
                                        'divClasses' => 'mb-3 mb-md-1'
                                    ]) ?>
                                </div>
                                <div class="col-12 col-md-7">
                                    <?= F::_text([
                                        'name' => 'feature_name',
                                        'placeholder' => Lexi::_get('wording_name'),
                                        'required' => true,
                                        'maxLength' => 100,
                                        'divClasses' => 'mb-3 mb-md-1'
                                    ]) ?>
                                </div>
                                <div class="col-12">
                                    <?= F::_textarea([
                                        'name' => 'feature_description',
                                        'placeholder' => Lexi::_get('detail_description'),
                                        'required' => false,
                                        'rows' => 3,
                                        'maxLength' => 150,
                                        'divClasses' => 'mb-3 mb-md-2'
                                    ]) ?>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="feature_active"
                                               name="feature_active" checked>
                                        <label class="form-check-label"
                                               for="feature_active"><?= Lexi::_get('item_is_active') ?></label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="feature_root_only"
                                               name="feature_root_only">
                                        <label class="form-check-label"
                                               for="feature_root_only"><?= Lexi::_get('only_authorised_by_root') ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-light"
                                        id="resetFeatureForm"><?= Lexi::_get('reset') ?></button>
                                <button type="submit" class="btn btn-primary"><?= Lexi::_get('save') ?></button>
                            </div>
                        </form>
                    </div>

                    <!-- Onglet de la liste des features -->
                    <div class="tab-pane fade" id="feature-list-content" role="tabpanel"
                         aria-labelledby="feature-list-tab">
                        <div class="table-responsive">
                            <table id="featuresDataTable" class="table datatable table-sm table-hover w-100">
                                <thead class="d-none">
                                <tr>
                                    <th><?= Lexi::_get('code') ?></th>
                                    <th><?= Lexi::_get('name') ?></th>
                                    <th><?= Lexi::_get('active') ?></th>
                                    <th><?= Lexi::_get('actions') ?></th>
                                </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>