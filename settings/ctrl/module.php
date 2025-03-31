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
    use App\Tools\F;
    use App\Tools\Router;
    use App\Units\Settings\SettingsCtrl;

    if (empty($route)) return;
?>
<script>
    $(document).ready(function () {
        const $form = $('#<?= SettingsCtrl::moduleRegisterForm ?>');
        $form.on('submit', handleSubmit);

        /**
         * Handles form submission
         * @param {Event} e - The submission event
         */
        function handleSubmit(e) {
            if (!validateInput($form, null, true, { checkRequired: true })) {
                e.preventDefault();
                return;
            }
            blockUI("<?= Lexi::_get(verifyingInformation) ?>...");
        }

        const dataTable = $('#ModuleTable').DataTable({
            dom: 'tp',
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= Router::generateCorePath($route[MDL], "module-handler") ?>',
                type: 'POST',
                data: function(d) {
                    // Customize server-side search
                    if (d.search && d.search.value) {
                        // Split the search value by |
                        const searchTerms = d.search.value.split('|');

                        // If multiple terms (OR search)
                        if (searchTerms.length > 1) {
                            d.searchType = 'OR';
                            d.searchTerms = searchTerms;
                        }
                    }
                    return d;
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables error:', error, thrown);
                }
            },
            columns: [
                {
                    data: 'name',
                    title: '<?= Lexi::_get('wording_name') ?>',
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex flex-column mb-0">
                              <div class="p-0 m-0 lead-1 font-secondary text-black">${data}
                                  <i class="bi bi-lock-fill lead-1 ${row.secured ? 'text-primary' : 'text-black-30'} mx-1"></i>
                                  <i class="bi bi-people-fill lead-1 ${row.assignable ? 'text-primary' : 'text-black-30'} me-1"></i>
                                  <i class="bi bi-check2-all lead-1 ${row.active ? 'text-primary' : 'text-black-30'} me-0"></i>
                              </div>
                              <div class="p-0 m-0 lead-0-85 font-secondary text-black-40">${row.description}</div>
                            </div>
                        `
                    }
                },
                {
                    data: null,
                    title: '<?= Lexi::_get('actions') ?>',
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                            <div class="dropdown">
                                <button class="btn btn-sm btn-link" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots text-primary"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?= Router::generateModulePath($route[MDL], SettingsCtrl::module) ?>?token=${row.token}">
                                        <i class="bi bi-pencil me-2"></i><?= Lexi::_get('edit') ?></a></li>
                                    <li><a class="dropdown-item toggle-module-state" href="#" data-token="${row.token}" data-name="${row.name}" data-active="${row.active}">
                                        <i class="bi bi-power me-2"></i>${row.active ? '<?= Lexi::_get('deactivate') ?>' : '<?= Lexi::_get('activate') ?>'}
                                    </a></li>
                                    <li><a class="dropdown-item manage-views" href="#" data-id="${row.id}" data-name="${row.name}">
                                        <i class="bi bi-layout-text-window me-2"></i><?= Lexi::_get('Interface') ?>
                                    </a></li>
                                    <li><a class="dropdown-item manage-features" href="#" data-id="${row.id}" data-name="${row.name}">
                                        <i class="bi bi-shield-lock me-2"></i><?= Lexi::_get('feature') ?>
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger delete-module" href="#" data-token="${row.token}" data-name="${row.name}">
                                        <i class="bi bi-trash me-2"></i><?= Lexi::_get('delete') ?>
                                    </a></li>
                                </ul>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, 'asc']],
            columnDefs: [
                { targets: 1, className: 'text-end' },
                { targets: '_all', className: 'align-middle font-primary text-start lead-1' }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/<?= ($_SESSION['language'] ?? 'fr') == 'fr' ? 'fr-FR' : 'en' ?>.json'
            },
            pageLength: 10,
            fnDrawCallback: function (oSettings) {
                $(oSettings.nTHead).hide();
            },
        });

        // Module deletion management
        $(document).on('click', '.delete-module', function(e) {
            e.preventDefault();
            const token = $(this).data('token');
            const name = $(this).data('name');

            bootbox.confirm({
                title: "<?= Lexi::_get('confirm_deletion') ?>",
                centerVertical: true,
                message: `<?= Lexi::_get('are_you_sure_you_want_to_delete') ?> <strong class="text-danger">${name}</strong> ?`,
                buttons: {
                    cancel: {
                        label: '<i class="bi bi-x"></i> <?= Lexi::_get('cancel') ?>',
                        className: 'btn-light'
                    },
                    confirm: {
                        label: '<i class="bi bi-trash"></i> <?= Lexi::_get('delete') ?>',
                        className: 'btn-danger'
                    }
                },
                callback: function(result) {
                    if (result) {
                        $.ajax({
                            url: '<?= Router::generateCorePath($route[MDL], "module-delete") ?>',
                            type: 'POST',
                            data: {
                                token: token
                            },
                            success: function(response) {
                                notify(response.message, response.success);
                                if (response.success) {
                                    dataTable.ajax.reload();
                                }
                            },
                            error: function() {
                                notify("<?= Lexi::_get('error_occurred_during_deletion') ?>", false);
                            }
                        });
                    }
                }
            });
        });

        // Activation/deactivation management
        $(document).on('click', '.toggle-module-state', function(e) {
            e.preventDefault();
            const token = $(this).data('token');
            const name = $(this).data('name');
            const isActive = $(this).data('active');
            const actionText = isActive ? '<?= Lexi::_get('deactivate') ?>' : '<?= Lexi::_get('activate') ?>';
            const actionClass = isActive ? 'btn-warning' : 'btn-success';

            bootbox.confirm({
                title: `<?= Lexi::_get('confirm') ?> ${actionText}`,
                centerVertical: true,
                message: `<?= Lexi::_get('are_you_sure_you_want_to') ?> ${actionText.toLowerCase()} <strong>${name}</strong> ?`,
                buttons: {
                    cancel: {
                        label: '<i class="bi bi-x"></i> <?= Lexi::_get('cancel') ?>',
                        className: 'btn-light'
                    },
                    confirm: {
                        label: `<i class="bi bi-power"></i> ${actionText}`,
                        className: actionClass
                    }
                },
                callback: function(result) {
                    if (result) {
                        $.ajax({
                            url: '<?= Router::generateCorePath($route[MDL], "module-toggle") ?>',
                            type: 'POST',
                            data: {
                                token: token,
                                active: isActive
                            },
                            success: function(response) {
                                notify(response.message, response.success);
                                if (response.success) {
                                    dataTable.ajax.reload();
                                }
                            },
                            error: function() {
                                notify("<?= Lexi::_get('error_occurred_during_operation') ?>", false);
                            }
                        });
                    }
                }
            });
        });
    });
</script>
<script>
    $(document).ready(function() {
        // Variables globales
        let currentModuleId = null;
        let viewsDataTable = null;

        // Initialisation du DataTable des vues
        function initViewsDataTable(moduleId) {
            if (viewsDataTable) {
                viewsDataTable.destroy();
            }

            viewsDataTable = $('#viewsDataTable').DataTable({
                dom: 'tp',
                processing: true,
                serverSide: true,
                ajax: {
                    url: '<?= Router::generateCorePath($route[MDL], "view-handler") ?>',
                    type: 'POST',
                    data: function(d) {
                        d.module_id = moduleId;
                        return d;
                    }
                },
                columns: [
                    {
                        data: 'name',
                        title: '<?= Lexi::_get('name') ?>',
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex flex-column mb-0">
                                  <div class="p-0 m-0 lead-1 font-secondary text-black">${data} (${row.filename})
                                      <i class="bi bi-house-fill lead-1 ${row.homepage ? 'text-primary' : 'text-black-30'} mx-1"></i>
                                      <i class="bi bi-database-fill lead-1 ${row.header_required ? 'text-primary' : 'text-black-30'} me-1"></i>
                                      <i class="bi bi-key-fill lead-1 ${row.identifier ? 'text-primary' : 'text-black-30'} me-1"></i>
                                      <i class="bi bi-eye-fill lead-1 ${row.active ? 'text-primary' : 'text-black-30'} me-1"></i>
                                      <i class="bi bi-image lead-1 ${row.image ? 'text-primary' : 'text-black-30'} me-0"></i>
                                  </div>
                                  <div class="p-0 m-0 lead-0-85 font-secondary text-black-40">${row.description}</div>
                                </div>
                            `
                        }
                    },
                    {
                        data: null,
                        title: '<?= Lexi::_get('actions') ?>',
                        orderable: false,
                        className: 'text-end actions-column',
                        render: function(data) {
                            return `
                                <div class="btn-group action-buttons">
                                    <button class="btn btn-sm edit-view" data-id="${data.id}">
                                        <i class="bi bi-pencil text-black-30"></i>
                                    </button>
                                    <button class="btn btn-sm toggle-view-state" data-id="${data.id}" data-active="${data.active}">
                                        <i class="bi bi-power text-black-30"></i>
                                    </button>
                                    <button class="btn btn-sm delete-view" data-id="${data.id}" data-name="${data.name}">
                                        <i class="bi bi-trash text-black-30"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: '_all', className: 'align-middle font-primary text-start lead-1' }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/<?= ($_SESSION['language'] ?? 'fr') == 'fr' ? 'fr-FR' : 'en' ?>.json'
                },
                pageLength: 5,
                lengthMenu: [5, 10, 25],
                fnDrawCallback: function (oSettings) {
                    $(oSettings.nTHead).hide();
                },
            });
        }

        // Gestionnaire d'ouverture du modal des vues
        $(document).on('click', '.manage-views', function(e) {
            e.preventDefault();
            const moduleId = $(this).data('id');
            const moduleName = $(this).data('name');

            // Mettre à jour les informations du module dans le modal
            currentModuleId = moduleId;
            $('#moduleNameDisplay').text(moduleName);
            $('#module_id').val(moduleId);

            // Réinitialiser le formulaire
            $('#viewForm')[0].reset();
            $('#view_id').val('');

            // Initialiser le DataTable
            initViewsDataTable(moduleId);

            // Afficher le modal
            $('#viewsModal').modal('show');
        });

        // Gestionnaire de soumission du formulaire
        $('#viewForm').on('submit', function(e) {
            e.preventDefault();

            if (!validateViewForm()) {
                return;
            }

            $.ajax({
                url: '<?= Router::generateCorePath($route[MDL], "view-register") ?>',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        notify(response.message, true);
                        viewsDataTable.ajax.reload();
                        resetViewForm();
                    } else {
                        notify(response.message, false);
                    }
                },
                error: function() {
                    notify("<?= Lexi::_get('error_occurred_during_operation') ?>", false);
                }
            });
        });

        // Validation du formulaire
        function validateViewForm() {
            const viewName = $('#view_name').val().trim();
            const viewFilename = $('#view_filename').val().trim();

            if (!viewName) {
                notify("<?= Lexi::_get('view_name_required') ?>", false);
                return false;
            }

            if (!viewFilename) {
                notify("<?= Lexi::_get('view_filename_required') ?>", false);
                return false;
            }

            return true;
        }

        // Réinitialisation du formulaire
        $('#resetViewForm').on('click', function() {
            resetViewForm();
        });

        function resetViewForm() {
            $('#viewForm')[0].reset();
            $('#view_id').val('');
            $('#view_header_required').prop('checked', false);
            $('#view_active').prop('checked', true);
            $('#view_homepage').prop('checked', false);
            $('#view_identifier').prop('checked', false);
        }

        // Édition d'une vue
        $(document).on('click', '.edit-view', function(e) {
            e.preventDefault();
            const viewId = $(this).data('id');

            $.ajax({
                url: '<?= Router::generateCorePath($route[MDL], "view-get") ?>',
                type: 'POST',
                data: {
                    id: viewId,
                    <?= F::csrfToken ?> : '<?= Router::generateCsrfToken("view_get") ?>'
                },
                success: function(response) {
                    if (response.success) {
                        fillViewForm(response.data);
                        // Passer à l'onglet du formulaire
                        $('#form-tab').tab('show');
                    } else {
                        notify(response.message, false);
                    }
                },
                error: function() {
                    notify("<?= Lexi::_get('error_occurred_during_operation') ?>", false);
                }
            });
        });

        // Remplir le formulaire avec les données existantes
        function fillViewForm(view) {
            $('#view_id').val(view.id);
            $('#view_name').val(view.name);
            $('#view_filename').val(view.filename);
            $('#view_description').val(view.description);
            $('#view_image').val(view.image);
            $('#view_header_required').prop('checked', view.header_required == 1);
            $('#view_homepage').prop('checked', view.homepage == 1);
            $('#view_identifier').prop('checked', view.identifier == 1);
            $('#view_active').prop('checked', view.active == 1);
        }

        // Suppression d'une vue
        $(document).on('click', '.delete-view', function(e) {
            e.preventDefault();
            const viewId = $(this).data('id');
            const viewName = $(this).data('name');

            bootbox.confirm({
                title: "<?= Lexi::_get('confirm_deletion') ?>",
                centerVertical: true,
                message: `<?= Lexi::_get('are_you_sure_you_want_to_delete') ?> <strong class="text-danger">${viewName}</strong> ?`,
                buttons: {
                    cancel: {
                        label: '<i class="bi bi-x"></i> <?= Lexi::_get('cancel') ?>',
                        className: 'btn-light'
                    },
                    confirm: {
                        label: '<i class="bi bi-trash"></i> <?= Lexi::_get('delete') ?>',
                        className: 'btn-danger'
                    }
                },
                callback: function(result) {
                    if (result) {
                        $.ajax({
                            url: '<?= Router::generateCorePath($route[MDL], "view-delete") ?>',
                            type: 'POST',
                            data: {
                                id: viewId,
                                <?= F::csrfToken ?> : '<?= Router::generateCsrfToken("view_delete") ?>'
                            },
                            success: function(response) {
                                notify(response.message, response.success);
                                if (response.success) {
                                    viewsDataTable.ajax.reload();
                                }
                            },
                            error: function() {
                                notify("<?= Lexi::_get('error_occurred_during_deletion') ?>", false);
                            }
                        });
                    }
                }
            });
        });

        // Activer/désactiver une vue
        $(document).on('click', '.toggle-view-state', function(e) {
            e.preventDefault();
            const viewId = $(this).data('id');
            const isActive = $(this).data('active');
            const actionText = isActive ? '<?= Lexi::_get('deactivate') ?>' : '<?= Lexi::_get('activate') ?>';
            const actionClass = isActive ? 'btn-warning' : 'btn-success';

            bootbox.confirm({
                title: `<?= Lexi::_get('confirm') ?> ${actionText}`,
                centerVertical: true,
                message: `<?= Lexi::_get('are_you_sure_you_want_to') ?> ${actionText.toLowerCase()} <?= Lexi::_get('this_view') ?> ?`,
                buttons: {
                    cancel: {
                        label: '<i class="bi bi-x"></i> <?= Lexi::_get('cancel') ?>',
                        className: 'btn-light'
                    },
                    confirm: {
                        label: `<i class="bi bi-power"></i> ${actionText}`,
                        className: actionClass
                    }
                },
                callback: function(result) {
                    if (result) {
                        $.ajax({
                            url: '<?= Router::generateCorePath($route[MDL], "view-toggle") ?>',
                            type: 'POST',
                            data: {
                                id: viewId,
                                <?= F::csrfToken ?> : '<?= Router::generateCsrfToken("view_toggle") ?>'
                            },
                            success: function(response) {
                                notify(response.message, response.success);
                                if (response.success) {
                                    viewsDataTable.ajax.reload();
                                }
                            },
                            error: function() {
                                notify("<?= Lexi::_get('error_occurred_during_operation') ?>", false);
                            }
                        });
                    }
                }
            });
        });
    });
</script>
<script>
    $(document).ready(function() {
        // Variables globales
        let currentModuleId = null;
        let featuresDataTable = null;

        // Initialisation du DataTable des features
        function initFeaturesDataTable(moduleId) {
            if (featuresDataTable) {
                featuresDataTable.destroy();
            }

            featuresDataTable = $('#featuresDataTable').DataTable({
                dom: 'tp',
                processing: true,
                serverSide: true,
                ajax: {
                    url: '<?= Router::generateCorePath($route[MDL], "feature-handler") ?>',
                    type: 'POST',
                    data: function(d) {
                        d.module_id = moduleId;
                        return d;
                    }
                },
                columns: [
                    {
                        data: null,
                        title: '<?= Lexi::_get('feature') ?>',
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex flex-column mb-0">
                                  <div class="p-0 m-0 lead-1 font-secondary text-black">${data.name} (<strong>${data.code}</strong>)
                                      <i class="bi bi-hash lead-1 ${row.root ? 'text-primary' : 'text-black-30'} me-0"></i>
                                      <i class="bi bi-eye-fill lead-1 ${row.active ? 'text-primary' : 'text-black-30'} me-0"></i>
                                  </div>
                                  <div class="p-0 m-0 lead-0-85 font-secondary text-black-40">${row.description}</div>
                                </div>
                            `
                        }
                    },
                    {
                        data: null,
                        title: '<?= Lexi::_get('actions') ?>',
                        orderable: false,
                        className: 'text-end',
                        render: function(data) {
                            return `
                            <div class="btn-group action-buttons">
                                <button class="btn btn-sm copy-feature-code" data-guid="${data.guid}" data-code="${data.code}" title="<?= Lexi::_get('copy_code') ?>"><i class="bi bi-clipboard"></i></button>
                                <button class="btn btn-sm edit-feature" data-id="${data.id}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn toggle-feature-state" data-id="${data.id}" data-active="${data.active}">
                                    <i class="bi bi-power"></i>
                                </button>
                                <button class="btn btn-sm delete-feature" data-id="${data.id}" data-code="${data.code}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        `;
                        }
                    }
                ],
                columnDefs: [
                    { targets: 0, className: 'align-middle', width: '80%' },
                    { targets: 1, className: 'align-middle text-end', width: '20%' },
                    { targets: '_all', className: 'align-middle font-primary text-start lead-1' }
                ],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/<?= ($_SESSION['language'] ?? 'fr') == 'fr' ? 'fr-FR' : 'en' ?>.json'
                },
                pageLength: 10,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'asc']],
                fnDrawCallback: function (oSettings) {
                    $(oSettings.nTHead).hide();
                }
            });
        }

        // Gestionnaire d'ouverture du modal des features
        $(document).on('click', '.manage-features', function(e) {
            e.preventDefault();
            const moduleId = $(this).data('id');
            const moduleName = $(this).data('name');

            // Mettre à jour les informations du module dans le modal
            currentModuleId = moduleId;
            $('#moduleNameFeatureDisplay').text(moduleName);
            $('#feature_module_id').val(moduleId);

            // Réinitialiser le formulaire
            $('#featureForm')[0].reset();
            $('#feature_id').val('');

            // Initialiser le DataTable
            initFeaturesDataTable(moduleId);

            // Afficher le modal
            $('#featuresModal').modal('show');
        });

        // Gestionnaire de soumission du formulaire
        $('#featureForm').on('submit', function(e) {
            e.preventDefault();

            if (!validateFeatureForm()) {
                return;
            }

            // Afficher un indicateur de chargement
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= Lexi::_get('saving') ?>...').prop('disabled', true);

            $.ajax({
                url: '<?= Router::generateCorePath($route[MDL], "feature-register") ?>',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        notify(response.message, true);
                        featuresDataTable.ajax.reload();
                        resetFeatureForm();
                    } else {
                        notify(response.message, false);
                    }
                },
                error: function() {
                    notify("<?= Lexi::_get('error_occurred_during_operation') ?>", false);
                },
                complete: function() {
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });

        // Validation du formulaire
        function validateFeatureForm() {
            const featureCode = $('#feature_code').val().trim();
            const featureName = $('#feature_name').val().trim();

            if (!featureCode) {
                notify("<?= Lexi::_get('feature_code_required') ?>", false);
                $('#feature_code').focus();
                return false;
            }

            if (!featureName) {
                notify("<?= Lexi::_get('feature_name_required') ?>", false);
                $('#feature_name').focus();
                return false;
            }

            return true;
        }

        // Réinitialisation du formulaire
        $('#resetFeatureForm').on('click', function() {
            resetFeatureForm();
        });

        function resetFeatureForm() {
            $('#featureForm')[0].reset();
            $('#feature_id').val('');
            $('#feature_active').prop('checked', true);
            $('#feature_root_only').prop('checked', false);
        }

        // Édition d'une feature
        $(document).on('click', '.edit-feature', function(e) {
            e.preventDefault();
            const featureId = $(this).data('id');

            // Afficher un indicateur de chargement
            const $button = $(this);
            const originalHtml = $button.html();
            $button.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>').prop('disabled', true);

            $.ajax({
                url: '<?= Router::generateCorePath($route[MDL], "feature-get") ?>',
                type: 'POST',
                data: {
                    id: featureId,
                    <?= F::csrfToken ?> : '<?= Router::generateCsrfToken("feature_get") ?>'
                },
                success: function(response) {
                    if (response.success) {
                        fillFeatureForm(response.data);
                        $('#feature-form-tab').tab('show');
                    } else {
                        notify(response.message, false);
                    }
                },
                error: function() {
                    notify("<?= Lexi::_get('error_occurred_during_operation') ?>", false);
                },
                complete: function() {
                    $button.html(originalHtml).prop('disabled', false);
                }
            });
        });

        // Remplir le formulaire avec les données existantes
        function fillFeatureForm(feature) {
            $('#feature_id').val(feature.id);
            $('#feature_code').val(feature.code);
            $('#feature_name').val(feature.name);
            $('#feature_description').val(feature.description);
            $('#feature_active').prop('checked', feature.active == 1);
            $('#feature_root_only').prop('checked', feature.root == 1);
        }

        // Suppression d'une feature
        $(document).on('click', '.delete-feature', function(e) {
            e.preventDefault();
            const featureId = $(this).data('id');
            const featureCode = $(this).data('code');

            bootbox.confirm({
                title: "<?= Lexi::_get('confirm_deletion') ?>",
                centerVertical: true,
                message: `<?= Lexi::_get('are_you_sure_you_want_to_delete_feature') ?> <strong class="text-danger">${featureCode}</strong> ?`,
                buttons: {
                    cancel: {
                        label: '<i class="bi bi-x"></i> <?= Lexi::_get('cancel') ?>',
                        className: 'btn-light'
                    },
                    confirm: {
                        label: '<i class="bi bi-trash"></i> <?= Lexi::_get('delete') ?>',
                        className: 'btn-danger'
                    }
                },
                callback: function(result) {
                    if (result) {
                        $.ajax({
                            url: '<?= Router::generateCorePath($route[MDL], "feature-delete") ?>',
                            type: 'POST',
                            data: {
                                id: featureId,
                                <?= F::csrfToken ?> : '<?= Router::generateCsrfToken("feature_delete") ?>'
                            },
                            success: function(response) {
                                notify(response.message, response.success);
                                if (response.success) {
                                    featuresDataTable.ajax.reload();
                                }
                            },
                            error: function() {
                                notify("<?= Lexi::_get('error_occurred_during_deletion') ?>", false);
                            }
                        });
                    }
                }
            });
        });

        $(document).on('click', '.toggle-feature-state', function(e) {
            e.preventDefault();
            const featureId = $(this).data('id');
            const isActive = $(this).data('active');
            const actionText = isActive ? '<?= Lexi::_get('deactivate') ?>' : '<?= Lexi::_get('activate') ?>';
            const actionClass = isActive ? 'btn-warning' : 'btn-success';

            bootbox.confirm({
                title: `<?= Lexi::_get('confirm') ?> ${actionText}`,
                centerVertical: true,
                message: `<?= Lexi::_get('are_you_sure_you_want_to') ?> ${actionText.toLowerCase()} <?= Lexi::_get('this_feature') ?> ?`,
                buttons: {
                    cancel: {
                        label: '<i class="bi bi-x"></i> <?= Lexi::_get('cancel') ?>',
                        className: 'btn-light'
                    },
                    confirm: {
                        label: `<i class="bi bi-power"></i> ${actionText}`,
                        className: actionClass
                    }
                },
                callback: function(result) {
                    if (result) {
                        $.ajax({
                            url: '<?= Router::generateCorePath($route[MDL], "feature-toggle") ?>',
                            type: 'POST',
                            data: {
                                id: featureId,
                                <?= F::csrfToken ?> : '<?= Router::generateCsrfToken("feature_toggle") ?>'
                            },
                            success: function(response) {
                                notify(response.message, response.success);
                                if (response.success) {
                                    featuresDataTable.ajax.reload();
                                }
                            },
                            error: function() {
                                notify("<?= Lexi::_get('error_occurred_during_operation') ?>", false);
                            }
                        });
                    }
                }
            });
        });

        // Copier le code d'une feature
        $(document).on('click', '.copy-feature-code', function(e) {
            e.preventDefault();
            const featureCode = `const ${$(this).data('code')} = ${$(this).data('guid')};`;

            // Copier le code dans le presse-papier
            navigator.clipboard.writeText(featureCode)
                .then(() => {
                    // Afficher un message de succès
                    notify("<?= Lexi::_get('code_copied_to_clipboard') ?>", true);

                    // Changer temporairement l'icône pour indiquer le succès
                    const $button = $(this);
                    const originalHtml = $button.html();
                    $button.html('<i class="bi bi-check2"></i>').addClass('btn-success').removeClass('btn-outline-secondary');

                    // Rétablir l'icône d'origine après un délai
                    setTimeout(() => {
                        $button.html(originalHtml).removeClass('btn-success').addClass('btn-outline-secondary');
                    }, 1500);
                })
                .catch(() => {
                    notify("<?= Lexi::_get('error_copying_to_clipboard') ?>", false);
                });
        });
    });
</script>