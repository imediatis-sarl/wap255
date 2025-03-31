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
    use App\Tools\{F, Router, W};

    try {
        // Validate input parameters
        $params = Router::validateInputParameters([
            F::csrfToken => 'string',
            'profileId' => 'int',
            'permissions' => 'string'
        ]);

        // Load profile
        $profile = Profile::_load((int)$params['profileId'], true);
        if (!$profile->getId()) {
            throw new Exception('profile_not_found');
        }

        // Parse and validate permissions
        $permissions = json_decode($params['permissions'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('invalid_permissions_format');
        }

        // Clean and validate permissions structure
        $validatedPermissions = [];
        foreach ($permissions as $moduleId => $featureIds) {
            // Ensure moduleId is numeric
            $moduleId = (int)$moduleId;
            if ($moduleId <= 0) {
                continue;
            }

            // Ensure featureIds are all integers and unique
            $cleanFeatureIds = [];
            foreach ($featureIds as $featureId) {
                $featureId = (int)$featureId;
                if ($featureId > 0 && !in_array($featureId, $cleanFeatureIds)) {
                    $cleanFeatureIds[] = $featureId;
                }
            }

            // Only add module if it has features
            if (!empty($cleanFeatureIds)) {
                $validatedPermissions[$moduleId] = $cleanFeatureIds;
            }
        }

        $profile->setPermissions($validatedPermissions);
        // Update profile permissions
        if (!$profile->isPermissionsUpdated($validatedPermissions)) {
            throw new Exception('error_saving_profile_permissions');
        }

        F::_completed('permissions_updated_successfully');
    } catch (Exception $e) {
        F::_feedback(Lexi::_get($e->getMessage()));
    }

    // Redirect back to permissions page with profile ID
    Router::move($app->route, 'permission', null, ['profile' => $params['profileId'] ?? 0]);