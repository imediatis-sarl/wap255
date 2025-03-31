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
    header('Content-Type: application/json');
    $app = require_once dirname(__DIR__, 2) . '/core.bootstrap.php';

    use App\Core\{Profile, Lexi};
    use App\Tools\{Router, F, W};

    try {
        $params = Router::validateInputParameters([
            F::csrfToken => 'string',
            ID => 'string',
        ]);

        $profile = Profile::_load((int)$params[ID], true);
        if (!$profile->delete())
            throw new Exception(errorPreventsDeletion);

        echo json_encode([SUCCESS => true, MSG => Lexi::_get(processCompleted)]);
    } catch (Exception $e) {
        echo json_encode([SUCCESS => false, MSG => Lexi::_get($e->getMessage())]);
    }