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

    use App\Core\{Feature, Module, Lexi};
    use App\Tools\{Router, F, U, T};

    try {
        $params = Router::validateInputParameters([
            F::csrfToken => 'string',
            'module_id' => 'int',
            'feature_code' => 'string',
            'feature_name' => 'string'
        ], [
            'feature_id' => 'int',
            'feature_description' => 'string'
        ]);

        // Vérifier que le code est au format snake_case
        $featureCode = $params['feature_code'];
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $featureCode)) {
            throw new Exception('feature_code_invalid_format');
        }

        // Charger le module
        $module = Module::_load((int)$params['module_id']);

        // Vérifier si on est en mode édition ou création
        $featureId = U::_fetchInt($_POST, ['feature_id'], null);

        if ($featureId) {
            // Mode édition
            $feature = Feature::_load($featureId);

            // Vérifier que la feature appartient bien au module
            if ($feature->getModule()->getId() !== $module->getId()) {
                throw new Exception('feature_not_belongs_to_module');
            }
        } else {
            // Mode création
            $feature = new Feature();
            $feature->setModule($module);
        }

        $feature->setCode($featureCode);
        $feature->setName(T::_sanitize($params['feature_name']));
        $feature->setDescription(T::_sanitize($_POST['feature_description'] ?? ''));
        $feature->setActive(isset($_POST['feature_active']));
        $feature->setRoot(isset($_POST['feature_root_only']));

        if (!$feature->save())
            throw new Exception('error_prevents_feature_registration');

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