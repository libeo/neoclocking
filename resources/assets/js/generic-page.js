var SweetAlert = require('sweetalert');

module.exports = {

    //container
    el: 'body',

    components: {
        'time-remaining': require('./components/time-remaining.js'),
        'task-actions': require('./components/task-actions.js'),
        'autocomplete-project': require('./components/autocomplete-project.js'),
        'groups-users': require('./components/groups-users.js'),
        'project': require('./components/project.js'),
        'projects': require('./components/projects.js')
    },

    //data binding
    data: {
        workedTime: {},
        task: {}
    },

    events: {
        'task-updated': function(task) {
            this.task = task;
        }
    },

    //methods
    methods: {
        favouriteToggled: function() {
            this.$broadcast('favouritesUpdated');
        },

        clockTime: function (taskNumber) {
            SweetAlert({
                title: "Redirection",
                text: "Pour ajouter du temps dans cette tâche, vous allez être redirigé à l'accueil.",
                type: "warning",
                showCancelButton: true,
                cancelButtonText: "Annuler",
                confirmButtonText: "Procéder",
                closeOnConfirm: false
            }, function() {
                window.location = "/#/clock/" + taskNumber;
            });
        }
    }
};
