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
    use App\Tools\{Router, F};

    try {
        // Valider les paramètres
        $params = Router::validateInputParameters([
            'csrf_token' => 'string',
            'firstname' => 'string',
            'lastname' => 'string',
            'email' => 'email',
            'mobile' => 'string',
            'profile' => 'int',
            'country' => 'int',
            'language' => 'string',
            'active' => 'bool'
        ], [
            'userId' => 'int',
            'userCode' => 'int',
            'userPin' => 'int'
        ]);

        // Récupérer les objets Profile et Country
        $profile = Profile::_load($params['profile'], true);
        $country = Country::_load($params['country'], true);

        if (!$profile->getId()) {
            throw new Exception('profile_not_found');
        }

        if (!$country->getId()) {
            throw new Exception('country_not_found');
        }

        // Déterminer s'il s'agit d'une création ou d'une mise à jour
        $isUpdate = !empty($params['userId']);

        if ($isUpdate) {
            // Modification d'un utilisateur existant
            $user = User::_load($params['userId'], true);
            if (!$user->getId()) {
                throw new Exception('user_not_found');
            }
        } else {
            // Création d'un nouvel utilisateur
            $user = new User();

            // Vérifier si le code et le PIN sont fournis, sinon les générer
            if (empty($params['userCode']) || empty($params['userPin'])) {
                $user->generateCodeAndPin();
            } else {
                $user->setCode((int)$params['userCode']);
                $user->setPin((int)$params['userPin']);
            }
        }

        // Définir les propriétés de l'utilisateur
        $user->setFirstname($params['firstname']);
        $user->setLastname($params['lastname']);
        $user->setEmail($params['email']);
        $user->setMobile((int)preg_replace('/[^0-9]/', '', $params['mobile']));
        $user->setLanguage($params['language']);
        $user->setProfile($profile);
        $user->setCountry($country);
        $user->setActive($params['active']);

        // Enregistrer l'utilisateur
        if (!$user->save()) {
            throw new Exception($isUpdate ? 'error_updating_user' : 'error_creating_user');
        }

        // Récupérer les données complètes de l'utilisateur pour le retour
        $userData = [
            'guid' => $user->getGuid(),
            'code' => $user->getCode(),
            'pin' => $user->getPin(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'mobile' => $user->getMobile(),
            'language' => $user->getLanguage(),
            'active' => $user->isActive(),
            'profile_guid' => $profile->getGuid(),
            'profile_name' => $profile->getName(),
            'country_guid' => $country->getGuid(),
            'country_name' => $country->getName(),
            'created' => $user->getCreated() ? $user->getCreated()->format('Y-m-d H:i:s') : null,
            'last_connection' => $user->getLastConnection() ? $user->getLastConnection()->format('Y-m-d H:i:s') : null
        ];

        echo json_encode([
            'success' => true,
            'message' => Lexi::_get($isUpdate ? 'user_updated_successfully' : 'user_created_successfully'),
            'user' => $userData
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => Lexi::_get($e->getMessage())
        ]);
    }