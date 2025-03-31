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

    use App\Core\Lexi;

    # Paramètres reçus de DataTables
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $search = $_POST['search']['value'] ?? '';
    $orderColumn = intval($_POST['order'][0]['column'] ?? 1);
    $orderDir = strtoupper($_POST['order'][0]['dir'] ?? 'ASC');

    # Configuration des colonnes
    $columns = [
        0 => 'reference',
        1 => $_SESSION['language'] ?? FR,
        2 => 'viewed'
    ];

    # Construire les conditions de recherche
    $conditions = [];
    if (!empty($search)) {
        $lang = $_SESSION['language'] ?? FR;
        $conditions["reference"] = "%{$search}%";
        $conditions[$lang] = "%{$search}%";
    }

    # Récupérer le nombre total d'enregistrements
    $totalRecords = Lexi::_getTotal();
    $yes = Lexi::_get('yes');
    $no = Lexi::_get('no');

    # Récupérer le nombre d'enregistrements filtrés
    $totalRecordWithFilter = Lexi::_getTotalWithFilter($conditions);

    # Récupérer les données
    $records = Lexi::_getRecords(
        $conditions,
        "{$columns[$orderColumn]} {$orderDir}",
        $start,
        $length
    );

    # Préparer les données avec les actions
    $data = [];
    foreach ($records as $row) {
        $lang = $_SESSION['language'] ?? FR;
        $data[] = [
            'id' => $row['id'],
            'reference' => $row['reference'],
            'translation' => $row[$lang],
            'actions' => '', // Sera rempli côté client avec JS
            'viewed' => $row['viewed'] ? '<span class="badge bg-success">' . $yes . '</span>' : '<span class="badge bg-danger">' . $no . '</span>',
            'created' => date('Y-m-d H:i', strtotime($row['created'])),
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