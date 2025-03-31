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

    use App\Core\Module;

    # Paramètres reçus de DataTables
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';
    $orderColumn = intval($_POST['order'][0]['column'] ?? 1);
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'ASC');

    # Configuration des colonnes
    $columns = [
        0 => 'name',
        1 => 'description',
        2 => 'token'
    ];

    # Construire les conditions de recherche
    $conditions = [];
    if (!empty($search)) {
        $conditions["name"] = "%{$search}%";
        $conditions["description"] = "%{$search}%";
    }

    # Construire la clause d'ordre
    $orderBy = $columns[$orderColumn] . ' ' . $orderDir;

    # Récupérer les données
    $modules = Module::_getRecords($conditions, $orderBy, $start, $length);

    # Récupérer le nombre total de modules
    $totalRecords = Module::_getTotal();

    # Récupérer le nombre de modules filtrés
    $totalRecordWithFilter = Module::_getTotalWithFilter($conditions);

    # Préparer la réponse
    $response = [
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecordWithFilter,
        'data' => $modules
    ];

    # Envoyer la réponse en JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;