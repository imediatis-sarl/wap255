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

    use App\Core\{User, Lexi};
    use App\Tools\{Router, F};

    try {
        $params = Router::validateInputParameters([
            'csrf_token' => 'string'
        ]);

        // Créer un utilisateur temporaire pour générer les identifiants
        $tempUser = new User();
        $tempUser->generateCodeAndPin();

        echo json_encode([
            'success' => true,
            'code' => $tempUser->getCode(),
            'pin' => $tempUser->getPin()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => Lexi::_get($e->getMessage())
        ]);
    }