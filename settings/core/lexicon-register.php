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

    declare(strict_types=1);
    $app = require_once dirname(__DIR__, 2) . '/core.bootstrap.php';

    use App\Core\Lexi;
    use App\Units\Settings\SettingsCtrl;
    use App\Tools\{F, Router, U};

    try {

        $params = Router::validateInputParameters([
            F::csrfToken => 'string',
            SettingsCtrl::lexRefText => 'string',
            SettingsCtrl::lexEnText => 'string',
            SettingsCtrl::lexFrText => 'string'
        ]);

        $lexi = Lexi::_load(U::_fetchString($_POST, [SettingsCtrl::lexGuid]), true);
        $lexi->setReference($params[SettingsCtrl::lexRefText]);
        $lexi->setEn($params[SettingsCtrl::lexEnText]);
        $lexi->setFr($params[SettingsCtrl::lexFrText]);

        if (!$lexi->save())
            throw new Exception('error_prevents_registration');

        $_SESSION['LEXI_LAST_REFERENCE'] = $params[SettingsCtrl::lexRefText];
        F::_completed();

    } catch (Exception $e) {
        F::_feedback(Lexi::_get($e->getMessage()));
    }

    Router::move($app->route, SettingsCtrl::lexi);