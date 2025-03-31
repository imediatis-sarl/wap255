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

    use App\Core\{Lexi, Feature, Module};
    use App\Tools\{Router, U};

    # Paramètres reçus de DataTables
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';
    $orderColumn = intval($_POST['order'][0]['column'] ?? 0);
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'ASC');
    $moduleId = intval($_POST['module_id'] ?? 0);

    # Validation du module
    if ($moduleId <= 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Module ID is required'
        ]);
        exit;
    }

    try {
        $module = Module::_load($moduleId);
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => 'Module not found'
        ]);
        exit;
    }

    # Configuration des colonnes
    $columns = [
        0 => 'code',
        1 => 'name'
    ];

    # Construire les conditions de recherche
    $conditions = ['module' => $moduleId];
    if (!empty($search)) {
        $conditions['code'] = "%{$search}%";
        $conditions['name'] = "%{$search}%";
        $conditions['description'] = "%{$search}%";
    }

    # Récupérer le nombre total de features pour ce module
    $totalRecords = Feature::_getTotalForModule($moduleId);

    # Récupérer le nombre de features filtrées pour ce module
    $totalRecordWithFilter = Feature::_getTotalWithFilter($conditions);

    # Construire la clause d'ordre
    $orderBy = "{$columns[$orderColumn]} {$orderDir}";

    # Récupérer les données
    $features = Feature::_getRecords($conditions, $orderBy, $start, $length);

    # Formatage des données pour le DataTable
    $data = [];
    foreach ($features as $feature) {
        $data[] = [
            'id' => $feature['id'],
            'guid' => $feature['guid'],
            'code' => $feature['code'],
            'name' => Lexi::_get($feature['name']),
            'description' => $feature['description'] ?? '',
            'active' => (bool)$feature['active'],
            'root' => (bool)$feature['root'],
            'module_id' => $moduleId
        ];
    }

    # Préparer la réponse
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecordWithFilter,
        'data' => $data
    ];

    # Envoyer la réponse en JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;