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

    use App\Core\{User, Lexi, Profile, Country};
    use App\Tools\{Router, F, W, Session};

    try {

        //$jsonData = file_get_contents('php://input');
        //$requestData = json_decode($jsonData, true);
        //W::_check($requestData);

        // Vérifier les autorisations
        $session = new Session();
        if (!$session->isValid()) {
            throw new Exception('session_expired');
        }

        // Récupérer les utilisateurs
        $users = [];
        $userRecords = User::_getRecords();

        // Enrichir les données des utilisateurs
        foreach ($userRecords as $user) {
            try {
                $profile = Profile::_load($user['profile']);
                $country = Country::_load($user['country']);

                $users[] = [
                    'guid' => $user['guid'],
                    'code' => $user['code'],
                    'pin' => $user['pin'],
                    'firstname' => $user['firstname'],
                    'lastname' => $user['lastname'],
                    'email' => $user['email'],
                    'mobile' => $user['mobile'],
                    'language' => $user['language'],
                    'active' => (bool)$user['active'],
                    'profile_guid' => $profile->getGuid(),
                    'profile_name' => $profile->getName(),
                    'country_guid' => $country->getGuid(),
                    'country_name' => $country->getName(),
                    'created' => $user['created'],
                    'last_connection' => $user['last_connection']
                ];
            } catch (Exception $e) {
                // Ignorer les utilisateurs avec des données incomplètes
                continue;
            }
        }

        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => Lexi::_get($e->getMessage())
        ]);
    }