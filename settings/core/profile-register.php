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

    use App\Core\{Lexi, Profile};
    use App\Units\Settings\SettingsCtrl;
    use App\Tools\{F, Router, T, U};

    try {

        $params = Router::validateInputParameters([
            F::csrfToken => 'string',
            SettingsCtrl::profileCode => 'string',
            SettingsCtrl::profileName => 'string',
            SettingsCtrl::profileDescription => 'string'
        ]);

        $profile = Profile::_load(U::_fetchInt($_POST, [SettingsCtrl::profileGuid], null), true);
        $profile->setCode($params[SettingsCtrl::profileCode]);
        $profile->setName(T::_sanitize($params[SettingsCtrl::profileName]));
        $profile->setDescription(T::_sanitize($params[SettingsCtrl::profileDescription]));
        $profile->setAssignable(isset($_POST[SettingsCtrl::profileIsAssignable]));
        $profile->setActive(isset($_POST[SettingsCtrl::profileIsActive]));

        if (!$profile->save())
            throw new Exception('error_prevents_registration');

        F::_completed();

    } catch (Exception $e) {
        F::_feedback(Lexi::_get($e->getMessage()));
    }

    Router::move($app->route, SettingsCtrl::profile);