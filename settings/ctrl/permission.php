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
    if (empty($route)) return;
?>

<script>
    /**
     * Permissions Manager for handling user profile permissions
     * Manages accordion interaction, checkbox selection, and form submission
     */
    class PermissionsManager {
        /**
         * Initialize permissions manager with default state
         */
        constructor() {
            // Current permissions state
            this.permissions = {};
            // Original permissions state for change detection
            this.originalPermissions = {};

            // DOM elements
            this.form = document.getElementById('permissionsForm');
            this.permissionsData = document.getElementById('permissionsData');
            this.selectAllBtn = document.getElementById('selectAllPermissions');
            this.deselectAllBtn = document.getElementById('deselectAllPermissions');
            this.moduleCheckboxes = document.querySelectorAll('.module-checkbox');
            this.featureCheckboxes = document.querySelectorAll('.feature-checkbox');
            this.searchInput = document.getElementById('searchModules');
            this.unsavedChangesBadge = document.getElementById('unsavedChangesBadge');
            this.accordionItems = document.querySelectorAll('.accordion-item');
            this.noResultsMessage = document.querySelector('.no-results');

            // Initialize event listeners and state
            this.initEventListeners();
            this.loadCurrentState();

            // Store original permissions for change detection
            this.originalPermissions = JSON.parse(JSON.stringify(this.permissions));

            // Add window beforeunload event for unsaved changes
            window.addEventListener('beforeunload', (e) => {
                if (this.hasUnsavedChanges()) {
                    e.preventDefault();
                    e.returnValue = '<?= Lexi::_get('unsaved_changes_warning') ?>';
                    return e.returnValue;
                }
            });
        }

        /**
         * Initialize all event listeners
         */
        initEventListeners() {
            if (this.form) {
                this.form.addEventListener('submit', this.handleFormSubmit.bind(this));
            }

            if (this.selectAllBtn) {
                this.selectAllBtn.addEventListener('click', this.handleSelectAll.bind(this));
            }

            if (this.deselectAllBtn) {
                this.deselectAllBtn.addEventListener('click', this.handleDeselectAll.bind(this));
            }

            // Add event listeners for module checkboxes
            this.moduleCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', this.handleModuleCheckboxChange.bind(this));
            });

            // Add event listeners for feature checkboxes
            this.featureCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', this.handleFeatureCheckboxChange.bind(this));
            });

            // Add event listener for search input
            if (this.searchInput) {
                this.searchInput.addEventListener('input', this.handleSearch.bind(this));
                this.searchInput.addEventListener('keydown', (e) => {
                    // Clear search on Escape key
                    if (e.key === 'Escape') {
                        this.searchInput.value = '';
                        this.handleSearch();
                    }
                });
            }
        }

        /**
         * Load current permissions state from checkboxes
         */
        loadCurrentState() {
            this.featureCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const moduleId = checkbox.dataset.moduleId;
                    const featureId = parseInt(checkbox.dataset.featureId);

                    if (!this.permissions[moduleId]) {
                        this.permissions[moduleId] = [];
                    }

                    if (!this.permissions[moduleId].includes(featureId)) {
                        this.permissions[moduleId].push(featureId);
                    }
                }
            });

            // Update hidden input with initial state
            this.updatePermissionsData();
        }

        /**
         * Handle form submission
         * @param {Event} e - Form submit event
         */
        handleFormSubmit(e) {
            e.preventDefault();
            this.updatePermissionsData();

            // Show loading indicator
            blockUI("<?= Lexi::_get('saving_permissions') ?>...");

            // Submit the form
            this.form.submit();
        }

        /**
         * Update the hidden input with current permissions state
         */
        updatePermissionsData() {
            if (this.permissionsData) {
                this.permissionsData.value = JSON.stringify(this.permissions);
            }

            // Check for unsaved changes
            this.checkForChanges();
        }

        /**
         * Check if there are unsaved changes and update UI accordingly
         */
        checkForChanges() {
            if (this.hasUnsavedChanges()) {
                this.unsavedChangesBadge.style.display = 'block';
            } else {
                this.unsavedChangesBadge.style.display = 'none';
            }
        }

        /**
         * Determines if there are unsaved changes
         * @return {boolean} True if changes are detected
         */
        hasUnsavedChanges() {
            // Compare current permissions with original
            const currentJson = JSON.stringify(this.permissions);
            const originalJson = JSON.stringify(this.originalPermissions);

            return currentJson !== originalJson;
        }

        /**
         * Select all permissions for all modules
         */
        handleSelectAll() {
            this.moduleCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
                this.toggleModuleFeatures(checkbox.dataset.moduleId, true);
            });

            this.updatePermissionsData();
            this.updateCounters();
        }

        /**
         * Deselect all permissions for all modules
         */
        handleDeselectAll() {
            this.moduleCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
                this.toggleModuleFeatures(checkbox.dataset.moduleId, false);
            });

            this.permissions = {};
            this.updatePermissionsData();
            this.updateCounters();
        }

        /**
         * Handle module checkbox change
         * @param {Event} e - Change event
         */
        handleModuleCheckboxChange(e) {
            const checkbox = e.target;
            const moduleId = checkbox.dataset.moduleId;
            const isChecked = checkbox.checked;

            this.toggleModuleFeatures(moduleId, isChecked);
            this.updatePermissionsData();
            this.updateCounters();
        }

        /**
         * Handle feature checkbox change
         * @param {Event} e - Change event
         */
        handleFeatureCheckboxChange(e) {
            const checkbox = e.target;
            const moduleId = checkbox.dataset.moduleId;
            const featureId = parseInt(checkbox.dataset.featureId);
            const isChecked = checkbox.checked;

            if (!this.permissions[moduleId]) {
                this.permissions[moduleId] = [];
            }

            if (isChecked) {
                // Add feature if not already in array
                if (!this.permissions[moduleId].includes(featureId)) {
                    this.permissions[moduleId].push(featureId);
                }
            } else {
                // Remove feature if in array
                this.permissions[moduleId] = this.permissions[moduleId].filter(id => id !== featureId);

                // Remove module key if empty
                if (this.permissions[moduleId].length === 0) {
                    delete this.permissions[moduleId];
                }
            }

            // Update module checkbox state
            this.updateModuleCheckboxState(moduleId);
            this.updatePermissionsData();
            this.updateCounters();
        }

        /**
         * Toggle all features for a module
         * @param {string} moduleId - Module ID
         * @param {boolean} state - Checked state
         */
        toggleModuleFeatures(moduleId, state) {
            // Get all feature checkboxes for this module
            const featureCheckboxes = document.querySelectorAll(`.feature-checkbox[data-module-id="${moduleId}"]`);

            // Clear module permissions array
            if (!state) {
                delete this.permissions[moduleId];
            } else {
                this.permissions[moduleId] = [];
            }

            // Update each feature checkbox
            featureCheckboxes.forEach(checkbox => {
                checkbox.checked = state;
                const featureId = parseInt(checkbox.dataset.featureId);

                if (state) {
                    if (!this.permissions[moduleId]) {
                        this.permissions[moduleId] = [];
                    }
                    this.permissions[moduleId].push(featureId);
                }
            });
        }

        /**
         * Update module checkbox state based on feature selections
         * @param {string} moduleId - Module ID
         */
        updateModuleCheckboxState(moduleId) {
            const moduleCheckbox = document.querySelector(`.module-checkbox[data-module-id="${moduleId}"]`);
            const featureCheckboxes = document.querySelectorAll(`.feature-checkbox[data-module-id="${moduleId}"]`);

            if (moduleCheckbox) {
                const totalFeatures = featureCheckboxes.length;
                const selectedFeatures = document.querySelectorAll(`.feature-checkbox[data-module-id="${moduleId}"]:checked`).length;

                // Module checkbox is checked if all features are selected
                moduleCheckbox.checked = (totalFeatures > 0 && selectedFeatures === totalFeatures);

                // Update indeterminate state
                moduleCheckbox.indeterminate = (selectedFeatures > 0 && selectedFeatures < totalFeatures);
            }
        }

        /**
         * Update feature counters in accordion headers
         */
        updateCounters() {
            const modules = document.querySelectorAll('.accordion-item');

            modules.forEach(module => {
                const moduleId = module.querySelector('.module-checkbox')?.dataset.moduleId;
                if (moduleId) {
                    const counter = module.querySelector('.permissions-counter');
                    const featureCheckboxes = module.querySelectorAll('.feature-checkbox');
                    const selectedFeatures = module.querySelectorAll('.feature-checkbox:checked').length;
                    const totalFeatures = featureCheckboxes.length;

                    if (counter) {
                        counter.textContent = `${selectedFeatures} / ${totalFeatures}`;

                        // Update badge color
                        if (selectedFeatures > 0) {
                            counter.classList.remove('bg-secondary');
                            counter.classList.add('bg-primary');
                        } else {
                            counter.classList.remove('bg-primary');
                            counter.classList.add('bg-secondary');
                        }
                    }
                }
            });
        }

        /**
         * Handle search functionality
         * @param {Event} e - Input event (optional)
         */
        handleSearch() {
            const searchTerm = this.searchInput.value.trim().toLowerCase();
            let visibleModules = 0;

            // Remove any existing highlights
            document.querySelectorAll('.search-highlight').forEach(el => {
                el.outerHTML = el.innerHTML;
            });

            // Check each accordion item (module)
            this.accordionItems.forEach(item => {
                const moduleNameEl = item.querySelector('.module-name');
                const moduleName = item.getAttribute('data-module-name');
                const featureItems = item.querySelectorAll('.feature-item');

                // Check if module name contains search term
                let moduleMatch = false;
                if (moduleName) {
                    moduleMatch = moduleName.includes(searchTerm);
                }

                // Check if any features contain search term
                let featuresMatch = false;
                let visibleFeatures = 0;

                featureItems.forEach(featureItem => {
                    const featureName = featureItem.getAttribute('data-feature-name');
                    const featureNameEl = featureItem.querySelector('.feature-name');

                    if (featureName && (featureName.includes(searchTerm) || searchTerm === '')) {
                        featureItem.style.display = '';
                        visibleFeatures++;
                        featuresMatch = true;

                        // Highlight matching text if there's a search term
                        if (searchTerm !== '' && featureNameEl) {
                            const regex = new RegExp(`(${searchTerm})`, 'gi');
                            featureNameEl.innerHTML = featureNameEl.textContent.replace(regex, '<span class="search-highlight">$1</span>');
                        }
                    } else {
                        featureItem.style.display = 'none';
                    }
                });

                // Show/hide module based on matches
                if (moduleMatch || featuresMatch || searchTerm === '') {
                    item.style.display = '';
                    visibleModules++;

                    // Highlight matching text in module name if there's a search term
                    if (searchTerm !== '' && moduleMatch && moduleNameEl) {
                        const regex = new RegExp(`(${searchTerm})`, 'gi');
                        moduleNameEl.innerHTML = moduleNameEl.textContent.replace(regex, '<span class="search-highlight">$1</span>');
                    }

                    // Expand accordion if search term matches
                    if (searchTerm !== '' && (moduleMatch || featuresMatch)) {
                        // Find collapse element
                        const button = item.querySelector('.accordion-button');
                        if (button) {
                            const target = button.getAttribute('data-bs-target');
                            if (target) {
                                const collapseEl = document.querySelector(target);
                                if (collapseEl) {
                                    // Add show class and set aria-expanded to true
                                    collapseEl.classList.add('show');
                                    button.classList.remove('collapsed');
                                    button.setAttribute('aria-expanded', 'true');
                                }
                            }
                        }
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            // Show/hide no results message
            if (this.noResultsMessage) {
                if (visibleModules === 0 && searchTerm !== '') {
                    this.noResultsMessage.style.display = 'block';
                } else {
                    this.noResultsMessage.style.display = 'none';
                }
            }
        }
    }

    // Initialize permissions manager when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        const permissionsManager = new PermissionsManager();
    });
</script>