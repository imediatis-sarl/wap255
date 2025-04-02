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

    use App\Core\Lexi;
    use App\Tools\{F, Session, Sidebar, Tile};

    if (empty($route)) return;
    Sidebar::_show();
?>
<?php F::_alert(true); ?>
<div class="tiles-container">
    <div class="nav-tiles mx-6 mx-lg-7">
        <?php
            echo Tile::build(
                 "/{$route[MDL]}/lexicon",
                'bi bi-translate',
                Lexi::_get('lexicon'),
                Lexi::_get('lexicon_management'),
                Session::user()->isRoot()
            );

            echo Tile::build(
                "/{$route[MDL]}/user",
                'bi bi-people',
                Lexi::_get('user'),
                Lexi::_get('user_account'),
                Session::user()->isRoot()
            );

            echo Tile::build(
                "/{$route[MDL]}/module",
                'bi bi-power',
                'Module',
                Lexi::_get('app_management'),
                Session::user()->isRoot()
            );

            // Sans vérification de permission (accessible à tous)
            echo Tile::build(
                '/settings/help',
                'bi bi-question-circle',
                Lexi::_get('help_center'),
                Lexi::_get('get_help_and_support'),
                false // Pas de vérification de permission
            );
        ?>
    </div>
</div>