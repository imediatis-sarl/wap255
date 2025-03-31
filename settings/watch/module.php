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
    use App\Tools\{F, Router, Session};

    try {
        if (empty($route)) return;

        if (!Session::user()->isRoot()) {
            throw new Exception('access_denied');
        }

    } catch (Exception $e) {
        F::_feedback(Lexi::_get($e->getMessage()));
        Router::move($route);
    }