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
    use App\Tools\{Router, F};
    use App\Units\Settings\SettingsCtrl;

    if (empty($route)) return;
?>
<script>
    $(document).ready(function () {
        const $form = $('#<?= SettingsCtrl::profileRegisterForm ?>');
        $form.on('submit', handleSubmit); // Form submit handler

        /**
         * Handles form submission
         * @param {Event} e - The submission event
         */
        function handleSubmit(e) {
            if (!validateInput($form, null, true, { checkRequired: true })) {
                e.preventDefault();
                return;
            }
            blockUI("<?= Lexi::_get('verifying_information') ?>...");
        }

        // Gestion de la suppression
        $(document).on('click', '.delete-ref', function(e) {
            e.preventDefault();
            const id = $(this).data('id');
            const ref = $(this).data('ref');

            bootbox.confirm({
                title: "<?= Lexi::_get('confirm_deletion') ?>",
                centerVertical: true,
                message: `<?= Lexi::_get('are_you_sure_you_want_to_delete') ?> <strong class="text-danger">${ref}</strong> ?`,
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
                            url: '<?= Router::generateCorePath($route[MDL], "profile-delete") ?>',
                            type: 'POST',
                            data: {
                                id: id,
                                <?= F::csrfToken ?> : '<?= Router::generateCsrfToken("profile_delete") ?>'
                            },
                            success: function(response) {
                                notify(response.message, response.success);
                                if (response.success) {
                                    $('#profileTable').DataTable().ajax.reload();
                                }
                            },
                            error: function() {
                                notify("Une erreur est survenue lors de la suppression", false);
                            }
                        });
                    }
                }
            });
        });

        const dataTable = $('.datatable').DataTable({
            dom: 'tp',
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= Router::generateCorePath($route[MDL], "profile-handler") ?>',
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
                { data: 'code', title: '<?= Lexi::_get('profile') ?>' },
                {
                    data: null,
                    title: 'Action',
                    orderable: false,
                    render: function(data, type, row) {
                        return `
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots text-primary"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= Router::generateModulePath($route[MDL], 'profile') ?>?ref=${row.guid}">
                                <i class="bi bi-pencil me-2"></i><?= Lexi::_get('edit') ?>
                            </a></li>
                            <li><a class="dropdown-item copy-ref" href="<?= Router::generateModulePath($route[MDL], 'permission') ?>?profile=${row.guid}">
                                <i class="bi bi-clipboard me-2"></i><?= Lexi::_get('authorization') ?>
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger delete-ref" href="#" data-id="${row.guid}" data-ref="${row.name}">
                                <i class="bi bi-trash me-2"></i><?= Lexi::_get('delete') ?>
                            </a></li>
                        </ul>
                    </div>
                `;
                    }
                },
                { data: 'name', title: 'name' }
            ],
            order: [[2, 'asc']],
            columnDefs: [
                { targets: 0, orderable: false, visible: true },
                { targets: 1, orderable: false, visible: true, className: 'text-end' },
                { targets: 2, orderable: true, visible: false },
                { targets: '_all', className: 'align-middle' }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/fr-FR.json'
            },
            pageLength: 10,
            fnDrawCallback: function (oSettings) {
                $(oSettings.nTHead).hide();
            },
        });
    });
</script>