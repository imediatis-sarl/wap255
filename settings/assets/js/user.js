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

/**
 * Gestionnaire des utilisateurs
 * Gère l'affichage, la recherche, le filtrage, la pagination et les actions CRUD pour les utilisateurs
 */
class UserManager {
    /**
     * Initialise le gestionnaire d'utilisateurs
     */
    constructor() {
        this.route = $('#userRoutesManager');
        // Configuration
        this.pageSize = 10;
        this.currentPage = 1;
        this.totalPages = 1;
        this.currentFilter = 'all';
        this.searchTerm = '';
        this.users = [];
        this.filteredUsers = [];
        this.isEditing = false;
        this.userIdToDelete = null;

        // Éléments DOM
        this.userTable = document.getElementById('usersTable');
        this.userTableBody = this.userTable.querySelector('tbody');
        this.searchInput = document.getElementById('searchUsers');
        this.filterOptions = document.querySelectorAll('.filter-option');
        this.loadingIndicator = document.getElementById('loadingIndicator');
        this.noResultsMessage = document.getElementById('noResultsMessage');
        this.totalRecordsElement = document.getElementById('totalRecords');
        this.paginationElement = document.getElementById('usersPagination');

        // Formulaire modal
        this.userFormModal = new bootstrap.Modal(document.getElementById('userFormModal'));
        this.userForm = document.getElementById('userForm');
        this.userFormTitle = document.getElementById('userFormModalLabel');
        this.saveUserBtn = document.getElementById('saveUserBtn');
        this.generateCredentialsBtn = document.getElementById('generateCredentials');

        // Modal de suppression
        this.deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
        this.deleteUserName = document.getElementById('deleteUserName');
        this.confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

        // Initialiser les événements
        this.initEventListeners();

        // Charger les données initiales
        this.loadUsers();
    }

    /**
     * Initialise tous les écouteurs d'événements
     */
    initEventListeners() {
        // Recherche d'utilisateurs
        this.searchInput.addEventListener('input', this.handleSearch.bind(this));

        // Filtres
        this.filterOptions.forEach(option => {
            option.addEventListener('click', this.handleFilter.bind(this));
        });

        // Actions du formulaire
        document.getElementById('addUserBtn').addEventListener('click', this.handleAddUser.bind(this));
        this.saveUserBtn.addEventListener('click', this.handleSaveUser.bind(this));
        this.generateCredentialsBtn.addEventListener('click', this.generateUserCredentials.bind(this));

        // Action de suppression
        this.confirmDeleteBtn.addEventListener('click', this.handleDeleteUser.bind(this));

        // Écouteurs d'événements délégués pour les actions sur les lignes du tableau
        this.userTableBody.addEventListener('click', (e) => {
            const target = e.target.closest('button');
            if (!target) return;

            const userId = target.dataset.userId;

            if (target.classList.contains('edit-user-btn')) {
                this.handleEditUser(userId);
            } else if (target.classList.contains('delete-user-btn')) {
                this.handleConfirmDelete(userId);
            } else if (target.classList.contains('toggle-user-btn')) {
                this.handleToggleUserStatus(userId);
            }
        });
    }

    /**
     * Charge les utilisateurs depuis l'API
     */
    async loadUsers() {
        this.showLoading(true);

        try {
            const response = await fetch(this.route.data('handler'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: this.route.data('token'),
                })
            });

            if (!response.ok) {
                throw new Error('error_loading_users');
            }

            const data = await response.json();

            if (data.success) {
                this.users = data.users;
                this.applyFilterAndSearch();
                this.updateTotalRecords();
            } else {
                notify(data.message, false);
            }
        } catch (error) {
            console.error('Error loading users:', error);
            notify('error_loading_users', false);
        } finally {
            this.showLoading(false);
        }
    }

    /**
     * Gère la recherche d'utilisateurs
     * @param {Event} e - Événement d'entrée
     */
    handleSearch(e) {
        this.searchTerm = e.target.value.trim().toLowerCase();
        this.currentPage = 1;
        this.applyFilterAndSearch();
    }

    /**
     * Gère le filtrage des utilisateurs
     * @param {Event} e - Événement de clic
     */
    handleFilter(e) {
        e.preventDefault();
        this.currentFilter = e.target.dataset.filter;
        this.currentPage = 1;

        // Mettre à jour l'affichage du filtre sélectionné
        document.getElementById('filterDropdown').innerHTML = `
            <i class="bi bi-funnel me-1"></i> ${e.target.textContent}
        `;

        this.applyFilterAndSearch();
    }

    /**
     * Applique les filtres et recherches aux utilisateurs
     */
    applyFilterAndSearch() {
        // Appliquer le filtre
        this.filteredUsers = this.users.filter(user => {
            // Appliquer le filtre de base
            if (this.currentFilter === 'active' && !user.active) {
                return false;
            }

            if (this.currentFilter === 'inactive' && user.active) {
                return false;
            }

            if (this.currentFilter === 'recently-added') {
                const createdDate = new Date(user.created);
                const oneWeekAgo = new Date();
                oneWeekAgo.setDate(oneWeekAgo.getDate() - 7);
                if (createdDate < oneWeekAgo) {
                    return false;
                }
            }

            // Appliquer la recherche si présente
            if (this.searchTerm) {
                const searchString = `${user.firstname} ${user.lastname} ${user.email} ${user.code}`.toLowerCase();
                return searchString.includes(this.searchTerm);
            }

            return true;
        });

        // Recalculer la pagination
        this.totalPages = Math.ceil(this.filteredUsers.length / this.pageSize);
        if (this.currentPage > this.totalPages) {
            this.currentPage = Math.max(1, this.totalPages);
        }

        // Mettre à jour l'interface
        this.renderUsersTable();
        this.renderPagination();
        this.updateTotalRecords();
    }

    /**
     * Affiche les utilisateurs dans le tableau
     */
    renderUsersTable() {
        // Vider le tableau
        this.userTableBody.innerHTML = '';

        // Afficher le message de "aucun résultat" si nécessaire
        if (this.filteredUsers.length === 0) {
            this.noResultsMessage.classList.remove('d-none');
            return;
        }

        this.noResultsMessage.classList.add('d-none');

        // Calculer les index de début et de fin pour la pagination
        const startIndex = (this.currentPage - 1) * this.pageSize;
        const endIndex = Math.min(startIndex + this.pageSize, this.filteredUsers.length);

        // Afficher les utilisateurs pour la page actuelle
        for (let i = startIndex; i < endIndex; i++) {
            const user = this.filteredUsers[i];

            const row = document.createElement('tr');
            row.dataset.userId = user.guid;

            // Cellule 1: Informations de l'utilisateur
            const userInfoCell = document.createElement('td');
            userInfoCell.innerHTML = `
                <div class="d-flex align-items-center">
                    <div class="avatar-placeholder bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                        ${this.getInitials(user.firstname, user.lastname)}
                    </div>
                    <div>
                        <div class="fw-medium">${user.firstname} ${user.lastname}</div>
                        <div class="small text-muted">Code: ${user.code}</div>
                    </div>
                </div>
            `;

            // Cellule 2: Informations de contact
            const contactCell = document.createElement('td');
            contactCell.innerHTML = `
                <div>
                    <div><i class="bi bi-envelope text-muted me-1"></i> ${user.email}</div>
                    <div><i class="bi bi-phone text-muted me-1"></i> ${user.mobile}</div>
                </div>
            `;

            // Cellule 3: Profil
            const profileCell = document.createElement('td');
            profileCell.innerHTML = `
                <span class="badge bg-primary">${user.profile_name}</span>
            `;

            // Cellule 4: Statut
            const statusCell = document.createElement('td');
            statusCell.innerHTML = `
                <span class="user-badge user-status-${user.active ? 'active' : 'inactive'}">
                    ${user.active ? '<?= Lexi::_get("active") ?>' : '<?= Lexi::_get("inactive") ?>'}
                </span>
            `;

            // Cellule 5: Actions
            const actionsCell = document.createElement('td');
            actionsCell.classList.add('text-end');
            actionsCell.innerHTML = `
                <div class="btn-group">
                    <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" data-user-id="${user.guid}" title="<?= Lexi::_get("edit") ?>">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-${user.active ? 'warning' : 'success'} toggle-user-btn" data-user-id="${user.guid}" title="${user.active ? '<?= Lexi::_get("deactivate") ?>' : '<?= Lexi::_get("activate") ?>'}">
                        <i class="bi bi-${user.active ? 'toggle-off' : 'toggle-on'}"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" data-user-id="${user.guid}" title="<?= Lexi::_get("delete") ?>">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;

            // Ajouter les cellules à la ligne
            row.appendChild(userInfoCell);
            row.appendChild(contactCell);
            row.appendChild(profileCell);
            row.appendChild(statusCell);
            row.appendChild(actionsCell);

            // Ajouter la ligne au tableau
            this.userTableBody.appendChild(row);
        }
    }

    /**
     * Affiche la pagination
     */
    renderPagination() {
        this.paginationElement.innerHTML = '';

        if (this.totalPages <= 1) {
            return;
        }

        // Bouton précédent
        const prevButton = document.createElement('li');
        prevButton.classList.add('page-item');
        if (this.currentPage === 1) {
            prevButton.classList.add('disabled');
        }
        prevButton.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a>`;
        prevButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.currentPage > 1) {
                this.currentPage--;
                this.renderUsersTable();
                this.renderPagination();
            }
        });
        this.paginationElement.appendChild(prevButton);

        // Pages
        for (let i = 1; i <= this.totalPages; i++) {
            const pageButton = document.createElement('li');
            pageButton.classList.add('page-item');
            if (i === this.currentPage) {
                pageButton.classList.add('active');
            }
            pageButton.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            pageButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.currentPage = i;
                this.renderUsersTable();
                this.renderPagination();
            });
            this.paginationElement.appendChild(pageButton);
        }

        // Bouton suivant
        const nextButton = document.createElement('li');
        nextButton.classList.add('page-item');
        if (this.currentPage === this.totalPages) {
            nextButton.classList.add('disabled');
        }
        nextButton.innerHTML = `<a class="page-link" href="#" aria-label="Next"><span aria-hidden="true">&raquo;</span></a>`;
        nextButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.renderUsersTable();
                this.renderPagination();
            }
        });
        this.paginationElement.appendChild(nextButton);
    }

    /**
     * Met à jour le compteur de résultats
     */
    updateTotalRecords() {
        this.totalRecordsElement.textContent = this.filteredUsers.length;
    }

    /**
     * Affiche ou masque l'indicateur de chargement
     * @param {boolean} show - Indique si l'indicateur doit être affiché
     */
    showLoading(show) {
        if (show) {
            this.loadingIndicator.classList.remove('d-none');
            this.userTableBody.classList.add('d-none');
        } else {
            this.loadingIndicator.classList.add('d-none');
            this.userTableBody.classList.remove('d-none');
        }
    }

    /**
     * Obtient les initiales d'un utilisateur
     * @param {string} firstname - Prénom
     * @param {string} lastname - Nom
     * @returns {string} Initiales
     */
    getInitials(firstname, lastname) {
        return `${firstname.charAt(0)}${lastname.charAt(0)}`.toUpperCase();
    }

    /**
     * Gère l'ajout d'un nouvel utilisateur
     */
    handleAddUser() {
        this.isEditing = false;
        this.userFormTitle.textContent = '<?= Lexi::_get("add_user") ?>';
        this.userForm.reset();
        document.getElementById('userId').value = '';
        this.userFormModal.show();
    }

    /**
     * Gère l'édition d'un utilisateur existant
     * @param {string} userId - ID de l'utilisateur à éditer
     */
    handleEditUser(userId) {
        this.isEditing = true;
        this.userFormTitle.textContent = '<?= Lexi::_get("edit_user") ?>';

        const user = this.users.find(u => u.guid === userId);
        if (!user) return;

        // Remplir le formulaire avec les données de l'utilisateur
        document.getElementById('userId').value = user.guid;
        document.getElementById('firstname').value = user.firstname;
        document.getElementById('lastname').value = user.lastname;
        document.getElementById('email').value = user.email;
        document.getElementById('mobile').value = user.mobile;
        document.getElementById('profile').value = user.profile_guid;
        document.getElementById('country').value = user.country_guid;
        document.getElementById('language').value = user.language || 'fr';
        document.getElementById('active').checked = user.active;
        document.getElementById('userCode').value = user.code;
        document.getElementById('userPin').value = user.pin;

        this.userFormModal.show();
    }

    /**
     * Génère des identifiants d'utilisateur aléatoires
     */
    generateUserCredentials() {
        fetch('<?= Router::generateCorePath($route["module"], "user-generate-credentials") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: '<?= Router::generateCsrfToken("user_credentials") ?>'
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('userCode').value = data.code;
                    document.getElementById('userPin').value = data.pin;
                } else {
                    notify(data.message, false);
                }
            })
            .catch(error => {
                console.error('Error generating credentials:', error);
                notify('<?= Lexi::_get("error_generating_credentials") ?>', false);
            });
    }

    /**
     * Gère la sauvegarde d'un utilisateur (ajout ou mise à jour)
     */
    handleSaveUser() {
        // Valider le formulaire
        if (!this.userForm.checkValidity()) {
            this.userForm.classList.add('was-validated');
            return;
        }

        // Récupérer les données du formulaire
        const formData = new FormData(this.userForm);
        const userData = {
            userId: formData.get('userId'),
            firstname: formData.get('firstname'),
            lastname: formData.get('lastname'),
            email: formData.get('email'),
            mobile: formData.get('mobile'),
            profile: formData.get('profile'),
            country: formData.get('country'),
            language: formData.get('language'),
            userCode: formData.get('userCode'),
            userPin: formData.get('userPin'),
            active: formData.get('active') === 'on',
            csrf_token: formData.get('<?= F::csrfToken ?>')
        };

        // Afficher l'indicateur de chargement
        blockUI('<?= Lexi::_get("saving") ?>...');

        // Envoyer les données au serveur
        fetch('<?= Router::generateCorePath($route["module"], "user-register") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(userData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notify(data.message, true);
                    this.userFormModal.hide();

                    // Mettre à jour la liste des utilisateurs
                    if (this.isEditing) {
                        // Mettre à jour un utilisateur existant
                        const index = this.users.findIndex(u => u.guid === data.user.guid);
                        if (index !== -1) {
                            this.users[index] = data.user;
                        }
                    } else {
                        // Ajouter un nouvel utilisateur
                        this.users.unshift(data.user);
                    }

                    // Réinitialiser le formulaire
                    this.userForm.reset();
                    this.userForm.classList.remove('was-validated');

                    // Appliquer les filtres et actualiser l'affichage
                    this.applyFilterAndSearch();

                    // Mettre en évidence la ligne mise à jour
                    setTimeout(() => {
                        const row = this.userTableBody.querySelector(`tr[data-user-id="${data.user.guid}"]`);
                        if (row) {
                            row.classList.add('table-row-updated');
                            setTimeout(() => {
                                row.classList.remove('table-row-updated');
                            }, 2000);
                        }
                    }, 100);
                } else {
                    notify(data.message, false);
                }
            })
            .catch(error => {
                console.error('Error saving user:', error);
                notify('<?= Lexi::_get("error_saving_user") ?>', false);
            })
            .finally(() => {
                unblockUI();
            });
    }

    /**
     * Gère la demande de confirmation de suppression
     * @param {string} userId - ID de l'utilisateur à supprimer
     */
    handleConfirmDelete(userId) {
        const user = this.users.find(u => u.guid === userId);
        if (!user) return;

        this.userIdToDelete = userId;
        this.deleteUserName.textContent = `${user.firstname} ${user.lastname}`;
        this.deleteUserModal.show();
    }

    /**
     * Gère la suppression d'un utilisateur
     */
    handleDeleteUser() {
        if (!this.userIdToDelete) return;

        // Afficher l'indicateur de chargement
        blockUI('<?= Lexi::_get("deleting") ?>...');

        // Envoyer la demande de suppression
        fetch('<?= Router::generateCorePath($route["module"], "user-delete") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                userId: this.userIdToDelete,
                csrf_token: '<?= Router::generateCsrfToken("user_delete") ?>'
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notify(data.message, true);
                    this.deleteUserModal.hide();

                    // Supprimer l'utilisateur de la liste
                    this.users = this.users.filter(u => u.guid !== this.userIdToDelete);

                    // Appliquer les filtres et actualiser l'affichage
                    this.applyFilterAndSearch();
                } else {
                    notify(data.message, false);
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                notify('<?= Lexi::_get("error_deleting_user") ?>', false);
            })
            .finally(() => {
                this.userIdToDelete = null;
                unblockUI();
            });
    }

    /**
     * Gère l'activation/désactivation d'un utilisateur
     * @param {string} userId - ID de l'utilisateur à basculer
     */
    handleToggleUserStatus(userId) {
        const user = this.users.find(u => u.guid === userId);
        if (!user) return;

        // Afficher l'indicateur de chargement
        blockUI(user.active ? '<?= Lexi::_get("deactivating") ?>...' : '<?= Lexi::_get("activating") ?>...');

        // Envoyer la demande de changement de statut
        fetch('<?= Router::generateCorePath($route["module"], "user-toggle-status") ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                userId: userId,
                csrf_token: '<?= Router::generateCsrfToken("user_toggle") ?>'
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    notify(data.message, true);

                    // Mettre à jour le statut de l'utilisateur
                    const index = this.users.findIndex(u => u.guid === userId);
                    if (index !== -1) {
                        this.users[index].active = !this.users[index].active;
                    }

                    // Appliquer les filtres et actualiser l'affichage
                    this.applyFilterAndSearch();

                    // Mettre en évidence la ligne mise à jour
                    setTimeout(() => {
                        const row = this.userTableBody.querySelector(`tr[data-user-id="${userId}"]`);
                        if (row) {
                            row.classList.add('table-row-updated');
                            setTimeout(() => {
                                row.classList.remove('table-row-updated');
                            }, 2000);
                        }
                    }, 100);
                } else {
                    notify(data.message, false);
                }
            })
            .catch(error => {
                console.error('Error toggling user status:', error);
                notify('<?= Lexi::_get("error_updating_user_status") ?>', false);
            })
            .finally(() => {
                unblockUI();
            });
    }
}