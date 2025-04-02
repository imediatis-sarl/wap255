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

    use App\Core\Lexi;
    use App\Units\Settings\SettingsCtrl;
    use App\Tools\{F, Router, Session, Sidebar, U};

    if (empty($route)) return;
    if (empty($assetsLoader)) return;

    Sidebar::_show();
    $session = new Session();
    $lexi = Lexi::_load(U::_fetchString($_GET, ['ref']), true);
?>
<div class="container mt-4">
    <div class="row">
        <div class="col-11 mx-auto">
            <div class="card bg-transparent border-0 animate__animated animate__backInLeft">
                <div class="card-header bg-transparent py-3 border-0">
                    <div class="d-flex align-items-center">
                        <a href="<?php echo Router::generateModulePath($route[MDL]) ?>" class="me-3 lead-1-4">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <h5 class="mb-0 fw-normal"><?= Sidebar::_viewTitle() ?></h5>
                        <?php if(!empty($lexi->getReference())): ?>
                            <a href="<?php echo Router::generateModulePath($route[MDL], $route[VIEW]) ?>" class="ms-auto lead-1-4"><i class="bi bi-x-lg text-danger"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php F::_alert(); ?>
                <div class="card-body bg-white shadow-lg p-0 rounded-3">
                    <form method="post" action="<?php echo Router::generateCorePath($route[MDL], 'lexicon-register'); ?>"
                          name="lexiconRegisterForm" id="lexiconRegisterForm" autocomplete="off">
                        <?php
                            echo F::_hidden(F::csrfToken, Router::generateCsrfToken('lexiconRegisterForm'));
                            echo F::_hidden(SettingsCtrl::lexGuid, $lexi->getReference());
                        ?>
                        <div class="px-4 py-3 border-bottom">
                            <h6 class="mb-3 text-dark font-secondary text-muted lead-1-1">Translation is an essential aid for multilingual systems</h6>
                            <div class="row g-3">
                                <div class="col-2 col-md-1">
                                    <button type="button" class="btn btn-lg btn-light w-100 btn-copy-reference">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                </div>
                                <div class="col-8 col-md-10">
                                    <?php
                                        echo F::_text([
                                            'name' => SettingsCtrl::lexRefText,
                                            'placeholder' => Lexi::_get('reference'),
                                            'maxLength' => 50,
                                            'class' => 'form-control',
                                            'value' => empty($lexi->getReference()) ? $_SESSION['LEXI_LAST_REFERENCE'] ?? '' : $lexi->getReference(),
                                        ]);
                                    ?>
                                </div>
                                <div class="col-2 col-md-1">
                                    <button type="submit" class="btn btn-lg btn-secondary w-100 btn-col">
                                        <i class="bi bi-floppy"></i>
                                    </button>
                                </div>
                                <div class="col-12 col-md-6">
                                    <?php
                                        echo F::_textarea([
                                            'name' => SettingsCtrl::lexEnText,
                                            'placeholder' => 'Enter English translation',
                                            'class' => 'form-control',
                                            'value' => $lexi->getEn()
                                        ]);
                                    ?>
                                </div>
                                <div class="col-12 col-md-6">
                                    <?php
                                        echo F::_textarea([
                                            'name' => SettingsCtrl::lexFrText,
                                            'placeholder' => 'Saisir la traduction française',
                                            'class' => 'form-control',
                                            'value' => $lexi->getFr()
                                        ]);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </form>
                    <div class="px-4 py-3">
                        <table id="lexiconTable" class="table datatable w-100"></table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>