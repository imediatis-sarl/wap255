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

    use App\Core\{View, Lexi};
    use App\Tools\{Router, F};

    try {
        $params = Router::validateInputParameters([
            F::csrfToken => 'string',
            ID => 'int',
        ]);

        $view = View::_load((int)$params[ID]);

        if (!$view->getId()) {
            throw new Exception('view_not_found');
        }

        echo json_encode([
            SUCCESS => true,
            'data' => [
                'id' => $view->getId(),
                'module_id' => $view->getModule()->getId(),
                'name' => $view->getName(),
                'description' => $view->getDescription(),
                'filename' => $view->getFilename(),
                'image' => $view->getImage(),
                'header_required' => $view->isHeaderRequired() ? 1 : 0,
                'homepage' => $view->isHomepage() ? 1 : 0,
                'identifier' => $view->isIdentifier() ? 1 : 0,
                'active' => $view->isActive() ? 1 : 0
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            SUCCESS => false,
            MSG => Lexi::_get($e->getMessage())
        ]);
    }