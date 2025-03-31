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

    use App\Core\Profile;

    # Paramètres reçus de DataTables
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';
    $orderColumn = intval($_POST['order'][0]['column'] ?? 1);
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'ASC');

    # Configuration des colonnes
//    $columns = [
//        0 => 'reference',
//        1 => $_SESSION['language'] ?? FR,
//        2 => 'viewed'
//    ];

    # Construire les conditions de recherche
    $conditions = [];
    if (!empty($search)) {
        $lang = $_SESSION['language'] ?? FR;
        $conditions["reference"] = "%{$search}%";
        $conditions[$lang] = "%{$search}%";
    }

    # Récupérer le nombre total d'enregistrements
    $totalRecords = Profile::_getTotal();

    # Récupérer le nombre d'enregistrements filtrés
    $totalRecordWithFilter = Profile::_getTotalWithFilter($conditions);

    # Récupérer les données
    $data = Profile::_getRecords(
        $conditions,
        '',
        $start,
        $length
    );

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