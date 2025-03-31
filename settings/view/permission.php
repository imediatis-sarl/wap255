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
    use App\Core\{Feature, Lexi, Module, Profile};

    if (empty($route)) return;
    if (empty($preload)) return;

    $preload->sidebar();
    $session = new Session();
    $profile = Profile::_load(U::_fetchInt($_GET, [REF], null), true);


    // Vérifier si le profil à éditer est spécifié
    $profileId = (int)($_GET['profile'] ?? 0);
    $profile = null;
    $permissions = [];

    if ($profileId > 0) {
        try {
            $profile = Profile::_load($profileId, true);
            $permissions = $profile->getPermissions() ?: [];
        } catch (Exception $e) {
            F::_feedback(Lexi::_get('profile_not_found'), F::altWarning);
            Router::move($route, 'profile');
        }
    }

    if (!$profile) {
        F::_feedback(Lexi::_get('profile_not_found'), F::altWarning);
        Router::move($route, 'profile');
    }

    // Recover assignable and active modules
    $modules = (new Module())->getAssignable(false);
?>

<style>
    /* Custom transitions for smooth animations */
    .accordion-item {
        transition: all 0.3s ease-in-out;
    }

    .module-checkbox:checked + label,
    .feature-checkbox:checked + label {
        font-weight: 500;
        color: var(--bs-primary);
    }

    .permissions-counter {
        transition: background-color 0.3s ease;
    }

    .search-highlight {
        background-color: rgba(255, 243, 205, 0.5);
        border-radius: 3px;
    }

    /* Badge for unsaved changes */
    .unsaved-changes-badge {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 10px 15px;
        background-color: var(--bs-primary);
        color: white;
        border-radius: 30px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: none;
        z-index: 1050;
        animation: fadeInUp 0.3s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Styles for the search box */
    .search-container {
        position: relative;
    }

    .search-container .bi-search {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
    }

    .search-container input {
        padding-left: 35px;
        border-radius: 50px;
    }

    .no-results {
        display: none;
        padding: 20px;
        text-align: center;
        color: #6c757d;
    }
</style>

<div class="container py-4">
    <div class="row">
        <div class="col-12 mx-auto">
            <div class="card bg-transparent border-0 animate__animated animate__backInLeft">
                <div class="card-header bg-transparent py-3 border-0">
                    <div class="d-flex align-items-center">
                        <a href="<?= Router::generateModulePath($route[MDL], SettingsCtrl::profile) ?>" class="me-3 lead-1-4">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h5 class="mb-0 fw-normal"><?= $preload->getViewTitle() ?> (<strong class="text-primary"><?= htmlspecialchars($profile->getName()) ?></strong>)</h5>
                    </div>
                </div>
                <?php F::_alert(); ?>
                <div class="card-body bg-primary-form shadow-lg p-3 p-lg-4 rounded-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="">
                            <div class="search-container">
                                <i class="bi bi-search"></i>
                                <input type="text" class="form-control" id="searchModules" placeholder="<?= Lexi::_get('search') ?>">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end align-items-end">
                            <button type="button" class="btn btn-sm btn-outline-primary me-2" id="selectAllPermissions">
                                <i class="bi bi-check-all me-1"></i> <?= Lexi::_get('select_all') ?>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="deselectAllPermissions">
                                <i class="bi bi-x-square me-1"></i> <?= Lexi::_get('deselect_all') ?>
                            </button>
                        </div>
                    </div>

                    <div class="p-0 py-3 py-lg-4">
                        <!-- No results message -->
                        <div class="no-results alert alert-warning">
                            <?= Lexi::_get('no_modules_match_search') ?>
                        </div>

                        <div class="accordion" id="permissionsAccordion">
                            <?php if (empty($modules)): ?>
                                <div class="alert alert-info m-3">
                                    <?= Lexi::_get('no_assignable_modules_available') ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($modules as $module): ?>
                                    <?php
                                    $moduleObj = Module::_load($module['id']);
                                    $features = (new Feature())->findByModule($moduleObj, true);
                                    $moduleId = $moduleObj->getId();
                                    $hasPermissions = isset($permissions[$moduleId]) && !empty($permissions[$moduleId]);
                                    $selectedCount = $hasPermissions ? count($permissions[$moduleId]) : 0;
                                    $totalCount = count($features);
                                    $moduleName = Lexi::_get($moduleObj->getName());
                                    ?>
                                    <div class="accordion-item" data-module-name="<?= htmlspecialchars(strtolower($moduleName)) ?>">
                                        <h2 class="accordion-header" id="heading<?= $moduleId ?>">
                                            <button class="accordion-button <?= ($hasPermissions ? '' : 'collapsed') ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $moduleId ?>" aria-expanded="<?= ($hasPermissions ? 'true' : 'false') ?>" aria-controls="collapse<?= $moduleId ?>">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div>
                                                        <i class="bi bi-grid me-2"></i>
                                                        <span class="module-name"><?= $moduleName ?></span>
                                                    </div>
                                                    <div class="badge bg-<?= ($selectedCount > 0 ? 'primary' : 'secondary') ?> rounded-pill ms-2 me-2 permissions-counter">
                                                        <?= $selectedCount ?> / <?= $totalCount ?>
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse<?= $moduleId ?>" class="accordion-collapse collapse <?= ($hasPermissions ? 'show' : '') ?>" aria-labelledby="heading<?= $moduleId ?>" data-bs-parent="#permissionsAccordion">
                                            <div class="accordion-body">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input module-checkbox" type="checkbox" value="" id="module<?= $moduleId ?>Checkbox" data-module-id="<?= $moduleId ?>" <?= ($selectedCount === $totalCount && $totalCount > 0 ? 'checked' : '') ?>>
                                                    <label class="form-check-label fw-bold" for="module<?= $moduleId ?>Checkbox">
                                                        <?= Lexi::_get('select_all') ?>
                                                    </label>
                                                </div>
                                                <hr>
                                                <?php if (empty($features)): ?>
                                                    <div class="alert alert-light">
                                                        <?= Lexi::_get('no_features_available_for_this_module') ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="row g-3">
                                                        <?php foreach ($features as $feature): ?>
                                                            <?php
                                                            $featureId = (int)$feature['id'];
                                                            $isSelected = $hasPermissions && in_array($featureId, $permissions[$moduleId]);
                                                            $featureName = Lexi::_get($feature['name']);
                                                            ?>
                                                            <div class="col-md-6 col-lg-4 feature-item" data-feature-name="<?= htmlspecialchars(strtolower($featureName)) ?>">
                                                                <div class="form-check">
                                                                    <input class="form-check-input feature-checkbox" type="checkbox"
                                                                           value="<?= $featureId ?>"
                                                                           id="feature<?= $featureId ?>Checkbox"
                                                                           data-module-id="<?= $moduleId ?>"
                                                                           data-feature-id="<?= $featureId ?>"
                                                                        <?= ($isSelected ? 'checked' : '') ?>>
                                                                    <label class="form-check-label" for="feature<?= $featureId ?>Checkbox">
                                                                        <span class="feature-name"><?= $featureName ?></span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="text-end border-top pt-3 pt-lg-4">
                        <form id="permissionsForm" action="<?= Router::generateCorePath($route['module'], 'permission-register') ?>" method="post">
                            <input type="hidden" name="<?= F::csrfToken ?>" value="<?= Router::generateCsrfToken('permissions_save') ?>">
                            <input type="hidden" name="profileId" value="<?= $profile->getGuid() ?>">
                            <input type="hidden" name="permissions" id="permissionsData" value="">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> <?= Lexi::_get('save_permissions') ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Unsaved changes badge -->
<div class="unsaved-changes-badge" id="unsavedChangesBadge">
    <i class="bi bi-exclamation-circle me-2"></i> <?= Lexi::_get('unsaved_changes') ?>
</div>