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

    use App\Core\{Lexi, Module};
    use App\Units\Settings\SettingsCtrl;
    use App\Tools\{F, Router, U};

    try {

        $params = Router::validateInputParameters([
            SettingsCtrl::itemName => 'string',
            SettingsCtrl::itemDescription => 'string'
        ]);

        $module = Module::_load(U::_fetchString($_POST, [SettingsCtrl::itemReference]), true);
        $module->setName($params[SettingsCtrl::itemName]);
        $module->setDescription($params[SettingsCtrl::itemDescription]);
        $module->setAssignable(isset($_POST[SettingsCtrl::itemIsAssignable]));
        $module->setActive(isset($_POST[SettingsCtrl::itemIsActive]));
        $module->setSecured(isset($_POST[SettingsCtrl::itemIsSecured]));

        if (!$module->save())
            throw new Exception('error_prevents_registration');


        F::_completed();
    } catch (Exception $e) {
        F::_feedback(Lexi::_get($e->getMessage()));
    }

    Router::move($app->route, SettingsCtrl::module);