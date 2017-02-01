var swal = require('sweetalert');

module.exports = {
    addMilestone: function (callback) {
        swal({
            title:               "Ajouter une étape",
            text:                "Veuillez entrer le nom de l'étape à ajouter.",
            type:                "input",
            showCancelButton:    true,
            cancelButtonText:    "Annuler",
            confirmButtonText:   "Créer",
            closeOnConfirm:      false,
            inputPlaceholder:    "Nom de l'étape",
            showLoaderOnConfirm: true
        }, callback);
    },

    showInputError: function (message) {
        swal.showInputError(message);
    },

    close: function () {
        swal.close();
    },

    success: function (message, title) {
        title = title || 'Succès';
        message = message || 'Les données ont été enregistrées correctement';

        this.popup(title, message, 'success');
    },

    error: function (message, title) {
        title = title || 'Erreur';
        message = message || 'Une erreur s\'est produite lors de la sauvegarde.';

        this.popup(title, message, 'error');
    },

    popup: function (title, message, type) {
        swal({
            title:             title,
            text:              message,
            type:              type,
            allowOutsideClick: true
        });
    },

    confirm: function (title, message, callback) {
        swal({
            title: title,
            text: message,
            type: 'warning',
            allowOutsideClick: true,
            showCancelButton: true,
            cancelButtonText: 'Annuler',
            showLoaderOnConfirm: true
        }, callback);
    },

    move: function (callback) {
        swal({
            title: 'Changement de projet',
            text: 'Veuillez entrer le nouveau numéro de projet pour ces tâches.',
            type: 'input',
            showCancelButton: true,
            cancelButtonText: 'Annuler',
            closeOnConfirm: false,
            inputPlaceholder: 'P-0000-0',
            showLoaderOnConfirm: true,
        }, callback);
    },

    resource: function (resources, callback) {
        swal({
            title: 'Choisir le nouveau type de ressource pour le(s) tâche(s) séléctionnée(s)',
            text: this._createResourcesBox(resources),
            type: 'info',
            html: true,
            showCancelButton: true,
            cancelButtonText: 'Annuler',
            closeOnConfirm: false,
            customClass: 'resource-choice-modal'
        }, function () {
            var resourceId = $('.sweet-alert.resource-choice-modal').find('select').val();
            if (resourceId) {
                callback(resourceId);
            }
        });
    },

    _createResourcesBox: function (resources) {
        var select = $('<select></select>');

        for (var i in resources) {
            select.append($('<option></option>').attr('value', i).text(resources[i]));
        }

        return $('<div></div>').append(select).html();
    }
};
