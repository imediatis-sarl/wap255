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
    use App\Tools\{F, Router, Session, Sidebar, U};
    use App\Core\{Lexi, Profile};

    if (empty($route)) return;

    Sidebar::_show();
    $session = new Session();
    $profile = Profile::_load(U::_fetchInt($_GET, [REF], null), true);
?>
<div class="container mt-4">
    <div class="row">
        <div class="col col-md-8 mx-auto">
            <div class="card bg-transparent border-0 animate__animated animate__backInLeft">
                <div class="card-header bg-transparent py-3 border-0">
                    <div class="d-flex align-items-center">
                        <a href="<?= Router::generateModulePath($route[MDL], SettingsCtrl::user) ?>" class="me-3 lead-1-4">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h5 class="mb-0 fw-normal"><?= Sidebar::_viewTitle() ?></h5>
                    </div>
                </div>
                <?php F::_alert(); ?>
                <div class="card-body bg-primary-form shadow-lg p-0 rounded-3">
                    <?php
                        echo F::_form(true, [
                            name => SettingsCtrl::profileRegisterForm,
                            action => Router::generateCorePath($route[MDL], 'profile-register')
                        ]);
                        echo F::_hidden(F::csrfToken, Router::generateCsrfToken('profileRegisterForm'));
                        echo F::_hidden(SettingsCtrl::profileGuid, $profile->getGuid());
                    ?>
                    <div class="px-4 py-3 border-bottom">
                        <h6 class="mb-3 text-dark font-secondary text-muted lead-1-1"><?= Lexi::_get('user_profiles_management') ?></h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <?php
                                    echo F::_text([
                                        'name' => SettingsCtrl::profileCode,
                                        'placeholder' => Lexi::_get('code'),
                                        'maxLength' => 30,
                                        'class' => 'form-control',
                                        'value' => $profile->getCode(),
                                        'upper' => true,
                                    ]);
                                ?>
                            </div>
                            <div class="col-12 col-md-8">
                                <?php
                                    echo F::_text([
                                        'name' => SettingsCtrl::profileName,
                                        'placeholder' => Lexi::_get('name'),
                                        'maxLength' => 128,
                                        'class' => 'form-control',
                                        'value' => $profile->getName()
                                    ]);
                                ?>
                            </div>
                            <div class="col-12 col-md-11">
                                <?php
                                    echo F::_textarea([
                                        'name' => SettingsCtrl::profileDescription,
                                        'placeholder' => 'Description',
                                        'class' => 'form-control',
                                        'value' => $profile->getDescription()
                                    ]);
                                ?>
                            </div>
                            <div class="col-12 col-md-1">
                                <button type="submit" class="btn btn-lg btn-primary w-100 btn-col">
                                    <i class="bi bi-check2-all"></i>
                                </button>
                            </div>
                            <div class="col-12">
                                <?php
                                    echo F::_checkbox([
                                        name => SettingsCtrl::profileIsAssignable,
                                        'label' => Lexi::_get('profile_is_assignable'),
                                        'divClasses' => 'mb-3 form-check form-check-inline',
                                        'checked' => $profile->isAssignable()
                                    ]);
                                    echo F::_checkbox([
                                        name => SettingsCtrl::profileIsActive,
                                        'label' => Lexi::_get('profile_is_active'),
                                        'divClasses' => 'mb-3 ms-md-4 form-check form-check-inline',
                                        'checked' => $profile->isActive()
                                    ])
                                ?>
                            </div>
                        </div>
                    </div>
                    <?= F::_form(false) ?>

                    <div class="px-4 py-3">
                        <table id="profileTable" class="table datatable w-100"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>