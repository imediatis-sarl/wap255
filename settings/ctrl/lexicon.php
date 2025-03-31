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
    document.addEventListener('DOMContentLoaded', () => {
        // Form input elements for filtering
        const referenceInput = document.querySelector('input[name="<?php echo SettingsCtrl::lexRefText?>"]');
        const englishInput = document.querySelector('textarea[name="<?php echo SettingsCtrl::lexEnText?>"]');
        const frenchInput = document.querySelector('textarea[name="<?php echo SettingsCtrl::lexFrText?>"]');
        const copyButton = document.querySelector('.btn-copy-reference');
        const lexiGuidField = document.querySelector('input[name="<?php echo SettingsCtrl::lexGuid?>"]');

        // Timeout for delayed search
        let searchTimeout;

        /**
         * Converts a string to snake_case format
         */
        const toSnakeCase = (text) => {
            if (!text) return '';

            return text
                .toLowerCase()
                .replace(/[^a-z0-9]/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_|_$/g, '')
                .substring(0, 50);
        };

        /**
         * Updates the reference field with snake_case version of the English text
         */
        const updateReference = () => {
            if (lexiGuidField && lexiGuidField.value) {
                return;
            }

            const englishText = englishInput.value;
            referenceInput.value = toSnakeCase(englishText);
        };

        /**
         * Copies the reference text to clipboard
         */
        const copyToClipboard = () => {
            if (!referenceInput.value) return;

            navigator.clipboard.writeText(referenceInput.value)
                .then(() => {
                    const originalTitle = copyButton.getAttribute('title');
                    copyButton.setAttribute('title', 'Copied!');
                    if (typeof notify === 'function') {
                        notify('Copied!', 1);
                    }

                    setTimeout(() => {
                        copyButton.setAttribute('title', originalTitle);
                    }, 2000);
                })
                .catch(err => {
                    console.error('Failed to copy text: ', err);
                });
        };

        /**
         * Filter the DataTable with a debounce effect
         */
        const filterDataTable = () => {
            // Clear existing timeout
            clearTimeout(searchTimeout);

            // Set a new timeout to avoid too many requests
            searchTimeout = setTimeout(() => {
                // Get DataTable instance
                if ($.fn.DataTable.isDataTable('.datatable')) {
                    const dataTable = $('.datatable').DataTable();

                    // Get search values
                    let searchTerms;
                    if (referenceInput && referenceInput.value) searchTerms = referenceInput.value;
                    if (englishInput && englishInput.value) searchTerms = englishInput.value;
                    if (frenchInput && frenchInput.value) searchTerms = frenchInput.value;

                    // Apply search - use OR logic with | as separator
                    dataTable.search(searchTerms).draw();
                }
            }, 300); // Delay search by 300ms
        };

        // Event Listeners for Form Elements
        if (englishInput) {
            englishInput.addEventListener('input', () => {
                updateReference();
                filterDataTable();
            });
        }

        if (referenceInput) {
            referenceInput.addEventListener('input', filterDataTable);
        }

        if (frenchInput) {
            frenchInput.addEventListener('input', filterDataTable);
        }

        if (copyButton) {
            copyButton.addEventListener('click', copyToClipboard);
        }
    });

    $(document).ready(function() {
        const dataTable = $('.datatable').DataTable({
            dom: 'tp',
            processing: true,
            serverSide: true,
            ajax: {
                url: '<?= Router::generateCorePath($route[MDL], "lexicon-handler") ?>',
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
                { data: 'reference', title: '<?= Lexi::_get('reference') ?>' },
                { data: 'translation', title: '<?= Lexi::_get('translation') ?>' },
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
                            <li><a class="dropdown-item" href="<?= Router::generateModulePath($route[MDL], 'lexicon') ?>?ref=${row.reference}">
                                <i class="bi bi-pencil me-2"></i><?= Lexi::_get('edit') ?>
                            </a></li>
                            <li><a class="dropdown-item copy-ref" href="#" data-ref="${row.reference}">
                                <i class="bi bi-clipboard me-2"></i><?= Lexi::_get('copy') ?>
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger delete-ref" href="#" data-id="${row.id}" data-ref="${row.reference}">
                                <i class="bi bi-trash me-2"></i><?= Lexi::_get('delete') ?>
                            </a></li>
                        </ul>
                    </div>
                `;
                    }
                },
                { data: 'viewed', title: 'Vu', className: 'text-center' }
            ],
            order: [[1, 'asc']],
            columnDefs: [
                { targets: 2, className: 'text-end' },
                { targets: 3, orderable: false },
                { targets: '_all', className: 'align-middle font-primary text-start lead-1' }
            ],
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/<?= ($_SESSION['language'] ?? 'fr') == 'fr' ? 'fr-FR' : 'en' ?>.json'
            },
            pageLength: 8,
            fnDrawCallback: function (oSettings) {
                $(oSettings.nTHead).hide();
            },
        });

        // Gestion de la copie
        $(document).on('click', '.copy-ref', function(e) {
            e.preventDefault();
            const ref = $(this).data('ref');
            navigator.clipboard.writeText(ref)
                .then(() => {
                    const notyf = new Notyf();
                    notyf.success("Référence copiée dans le presse-papier");
                })
                .catch(err => {
                    console.error('Impossible de copier: ', err);
                });
        });

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
                            url: '<?= Router::generateCorePath($route[MDL], "lexicon-delete") ?>',
                            type: 'POST',
                            data: {
                                id: id,
                                <?= F::csrfToken ?> : '<?= Router::generateCsrfToken("lexicon_delete") ?>'
                            },
                            success: function(response) {
                                notify(response.message, response.success);
                                if (response.success) {
                                    $('#lexiconTable').DataTable().ajax.reload();
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
    });
</script>