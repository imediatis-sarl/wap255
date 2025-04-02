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
        this.routesManager = document.getElementById('userRoutesManager');

        // Configuration des URL à partir des attributs data
        this.config = {
            csrfToken: this.routesManager.dataset.csrfToken,
            handlerUrl: this.routesManager.dataset.handlerUrl,
            toggleUrl: this.routesManager.dataset.toggleUrl,
            deleteUrl: this.routesManager.dataset.deleteUrl,
            registerUrl: this.routesManager.dataset.registerUrl,
            credentialsUrl: this.routesManager.dataset.credentialsUrl
        };

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
        console.log("Loading users from", this.config.handlerUrl);

        try {
            const response = await fetch(this.config.handlerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: this.config.csrfToken,
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const data = await response.json();
            console.log("User data received:", data);

            if (data.success) {
                this.users = data.users || [];
                this.applyFilterAndSearch();
                this.updateTotalRecords();
            } else {
                console.error("Error loading users:", data.message);
                notify(data.message, false);
            }
        } catch (error) {
            console.error('Error loading users:', error);
            notify('Error loading users. Please try again.', false);
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
        // Vérifier si les utilisateurs sont chargés
        if (!this.users || !Array.isArray(this.users)) {
            console.error("Users data is not available or not an array:", this.users);
            this.filteredUsers = [];
            this.renderUsersTable();
            this.updateTotalRecords();
            return;
        }

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
        if (!this.filteredUsers || this.filteredUsers.length === 0) {
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
            if (!user) continue; // Skip undefined users

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
                   ${user.active ? 'Active' : 'Inactive'}
               </span>
           `;

            // Cellule 5: Actions
            const actionsCell = document.createElement('td');
            actionsCell.classList.add('text-end');
            actionsCell.innerHTML = `
               <div class="btn-group">
                   <button type="button" class="btn btn-sm btn-outline-primary edit-user-btn" data-user-id="${user.guid}" title="Edit">
                       <i class="bi bi-pencil"></i>
                   </button>
                   <button type="button" class="btn btn-sm btn-outline-${user.active ? 'warning' : 'success'} toggle-user-btn" data-user-id="${user.guid}" title="${user.active ? 'Deactivate' : 'Activate'}">
                       <i class="bi bi-${user.active ? 'toggle-off' : 'toggle-on'}"></i>
                   </button>
                   <button type="button" class="btn btn-sm btn-outline-danger delete-user-btn" data-user-id="${user.guid}" title="Delete">
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
        this.totalRecordsElement.textContent = this.filteredUsers ? this.filteredUsers.length : 0;
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
        if (!firstname || !lastname) return '';
        return `${firstname.charAt(0)}${lastname.charAt(0)}`.toUpperCase();
    }

    /**
     * Gère l'ajout d'un nouvel utilisateur
     */
    handleAddUser() {
        this.isEditing = false;
        this.userFormTitle.textContent = 'Add User';
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
        this.userFormTitle.textContent = 'Edit User';

        const user = this.users.find(u => u.guid === userId);
        if (!user) return;

        // Remplir le formulaire avec les données de l'utilisateur
        document.getElementById('userId').value = user.guid;
        document.getElementById('firstname').value = user.firstname || '';
        document.getElementById('lastname').value = user.lastname || '';
        document.getElementById('email').value = user.email || '';
        document.getElementById('mobile').value = user.mobile || '';

        // Gérer les sélecteurs
        const profileSelect = document.getElementById('profile');
        if (profileSelect) {
            for (let i = 0; i < profileSelect.options.length; i++) {
                if (profileSelect.options[i].value === user.profile_guid) {
                    profileSelect.selectedIndex = i;
                    break;
                }
            }
        }

        const countrySelect = document.getElementById('country');
        if (countrySelect) {
            for (let i = 0; i < countrySelect.options.length; i++) {
                if (countrySelect.options[i].value === user.country_guid) {
                    countrySelect.selectedIndex = i;
                    break;
                }
            }
        }

        const languageSelect = document.getElementById('language');
        if (languageSelect) {
            for (let i = 0; i < languageSelect.options.length; i++) {
                if (languageSelect.options[i].value === user.language) {
                    languageSelect.selectedIndex = i;
                    break;
                }
            }
        }

        document.getElementById('active').checked = user.active;
        document.getElementById('userCode').value = user.code || '';
        document.getElementById('userPin').value = user.pin || '';

        this.userFormModal.show();
    }

    /**
     * Génère des identifiants d'utilisateur aléatoires
     */
    generateUserCredentials() {
        console.log("Generating credentials using URL:", this.config.credentialsUrl);

        fetch(this.config.credentialsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                csrf_token: document.querySelector('input[name="csrf_token"]').value
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Credentials received:", data);
                if (data.success) {
                    document.getElementById('userCode').value = data.code;
                    document.getElementById('userPin').value = data.pin;
                } else {
                    notify(data.message || 'Error generating credentials', false);
                }
            })
            .catch(error => {
                console.error('Error generating credentials:', error);
                notify('Error generating credentials. Please try again.', false);
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
            csrf_token: formData.get('csrf_token')
        };

        console.log("Saving user data:", userData);

        // Afficher l'indicateur de chargement
        if (typeof blockUI === 'function') {
            blockUI('Saving...');
        }

        // Envoyer les données au serveur
        fetch(this.config.registerUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(userData)
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Save user response:", data);
                if (data.success) {
                    notify(data.message || 'User saved successfully', true);
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
                    notify(data.message || 'Error saving user', false);
                }
            })
            .catch(error => {
                console.error('Error saving user:', error);
                notify('Error saving user. Please try again.', false);
            })
            .finally(() => {
                if (typeof unblockUI === 'function') {
                    unblockUI();
                }
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

        console.log("Deleting user:", this.userIdToDelete);

        // Afficher l'indicateur de chargement
        if (typeof blockUI === 'function') {
            blockUI('Deleting...');
        }

        // Envoyer la demande de suppression
        fetch(this.config.deleteUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                userId: this.userIdToDelete,
                csrf_token: document.querySelector('input[name="csrf_token"]').value
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Delete user response:", data);
                if (data.success) {
                    notify(data.message || 'User deleted successfully', true);
                    this.deleteUserModal.hide();

                    // Supprimer l'utilisateur de la liste
                    this.users = this.users.filter(u => u.guid !== this.userIdToDelete);

                    // Appliquer les filtres et actualiser l'affichage
                    this.applyFilterAndSearch();
                } else {
                    notify(data.message || 'Error deleting user', false);
                }
            })
            .catch(error => {
                console.error('Error deleting user:', error);
                notify('Error deleting user. Please try again.', false);
            })
            .finally(() => {
                this.userIdToDelete = null;
                if (typeof unblockUI === 'function') {
                    unblockUI();
                }
            });
    }

    /**
     * Gère l'activation/désactivation d'un utilisateur
     * @param {string} userId - ID de l'utilisateur à basculer
     */
    handleToggleUserStatus(userId) {
        const user = this.users.find(u => u.guid === userId);
        if (!user) return;

        console.log("Toggling user status:", userId, "current active state:", user.active);

        // Afficher l'indicateur de chargement
        if (typeof blockUI === 'function') {
            blockUI(user.active ? 'Deactivating...' : 'Activating...');
        }

        // Envoyer la demande de changement de statut
        fetch(this.config.toggleUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                userId: userId,
                csrf_token: document.querySelector('input[name="csrf_token"]').value
            })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log("Toggle user status response:", data);
                if (data.success) {
                    notify(data.message || 'User status updated successfully', true);

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
                    notify(data.message || 'Error updating user status', false);
                }
            })
            .catch(error => {
                console.error('Error toggling user status:', error);
                notify('Error updating user status. Please try again.', false);
            })
            .finally(() => {
                if (typeof unblockUI === 'function') {
                    unblockUI();
                }
            });
    }
}

/**
 * Fonction utilitaire pour afficher les notifications
 * Vérifie d'abord si une bibliothèque de notifications est disponible,
 * sinon utilise window.alert comme solution de secours
 *
 * @param {string} message - Message à afficher
 * @param {boolean} success - Si true, affiche une notification de succès, sinon une erreur
 */
function notify(message, success = true) {
    // Vérifier si une bibliothèque de notifications est disponible
    if (typeof Notyf === 'function') {
        const notyf = new Notyf();
        if (success) {
            notyf.success(message);
        } else {
            notyf.error(message);
        }
    } else if (window.toastr) {
        if (success) {
            toastr.success(message);
        } else {
            toastr.error(message);
        }
    } else {
        // Solution de secours simple
        console.log(`${success ? 'Success' : 'Error'}: ${message}`);
    }
}

/**
 * Fonction pour bloquer l'interface utilisateur pendant le chargement
 *
 * @param {string} message - Message de chargement à afficher
 */
function blockUI(message = 'Loading...') {
    // Vérifier si une bibliothèque de blocage UI est disponible
    if (window.$.blockUI) {
        $.blockUI({
            message: `<div class="spinner-border text-primary" role="status"></div><h3>${message}</h3>`,
            css: {
                border: 'none',
                padding: '15px',
                backgroundColor: '#fff',
                borderRadius: '10px',
                opacity: 0.7,
                color: '#000'
            }
        });
    } else {
        // Solution de secours - créer un élément de blocage
        const blockElement = document.createElement('div');
        blockElement.id = 'manualBlockUI';
        blockElement.style.position = 'fixed';
        blockElement.style.top = '0';
        blockElement.style.left = '0';
        blockElement.style.width = '100%';
        blockElement.style.height = '100%';
        blockElement.style.backgroundColor = 'rgba(0,0,0,0.5)';
        blockElement.style.zIndex = '9999';
        blockElement.style.display = 'flex';
        blockElement.style.justifyContent = 'center';
        blockElement.style.alignItems = 'center';
        blockElement.style.flexDirection = 'column';
        blockElement.innerHTML = `
           <div class="spinner-border text-light" role="status"></div>
           <div style="color: white; margin-top: 10px;">${message}</div>
       `;
        document.body.appendChild(blockElement);
    }
}

/**
 * Fonction pour débloquer l'interface utilisateur
 */
function unblockUI() {
    // Vérifier si une bibliothèque de blocage UI est disponible
    if (window.$.unblockUI) {
        $.unblockUI();
    } else {
        // Solution de secours - supprimer l'élément de blocage
        const blockElement = document.getElementById('manualBlockUI');
        if (blockElement) {
            blockElement.parentNode.removeChild(blockElement);
        }
    }
}

// Initialiser le gestionnaire d'utilisateurs lorsque le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    console.log("Initializing UserManager");
    try {
        const userManager = new UserManager();
    } catch (error) {
        console.error("Error initializing UserManager:", error);
    }
});