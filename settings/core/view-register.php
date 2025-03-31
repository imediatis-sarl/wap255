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

    use App\Core\{View, Module, Lexi};
    use App\Tools\{Router, F, U, T};

    try {
        $params = Router::validateInputParameters([
            F::csrfToken => 'string',
            'module_id' => 'int',
            'view_name' => 'string',
            'view_filename' => 'string'
        ], [
            'view_id' => 'int',
            'view_description' => 'string',
            'view_image' => 'string'
        ]);

        // Charger le module
        $module = Module::_load((int)$params['module_id']);

        // Vérifier si on est en mode édition ou création
        $viewId = U::_fetchInt($_POST, ['view_id'], null);

        if ($viewId) {
            $view = View::_load($viewId);
        } else {
            $view = new View();
            $view->setModule($module);
        }

        $view->setName(T::_sanitize($params['view_name']));
        $view->setFilename(T::_sanitize($params['view_filename']));
        $view->setDescription(T::_sanitize($_POST['view_description'] ?? ''));
        $view->setImage(T::_sanitize($_POST['view_image'] ?? ''));
        $view->setHeaderRequired(isset($_POST['view_header_required']));
        $view->setHomepage(isset($_POST['view_homepage']));
        $view->setIdentifier(isset($_POST['view_identifier']));
        $view->setActive(isset($_POST['view_active']));

        // Vérifier si l'utilisateur essaie de définir cette vue comme page d'accueil
        // alors qu'une autre vue du même module est déjà définie comme telle
        if ($view->isHomepage()) {
            // Trouver la page d'accueil existante
            $existingHomepage = $view->getHomepage($module);
            // Si une page d'accueil existe déjà et que ce n'est pas la vue actuelle
            if ($existingHomepage && $viewId != $existingHomepage['id']) {
                throw new Exception('module_homepage_already_exists');
            }
        }

        if (!$view->save())
            throw new Exception('error_prevents_view_registration');

        echo json_encode([
            SUCCESS => true,
            MSG => Lexi::_get('process_completed_successfully')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            SUCCESS => false,
            MSG => Lexi::_get($e->getMessage())
        ]);
    }