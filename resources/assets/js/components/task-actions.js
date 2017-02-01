var SweetAlert = require('sweetalert');

module.exports = {
    template: '#task-actions-template',

    props: ['task', 'taskNumber', 'favouriteToggled', 'favouriteOnly'],

    data: function() {
        return {
            form: {
                show: false,
                task: null,
                reason: null
            }
        };
    },

    ready: function() {
        if (this.task && this.task.id) {
            this.taskNumber = this.task.id;
        } else {
            this.getTask();
        }
    },

    computed: {
        favoriteActionTitle: function() {
            if (this.task.favourited) {
                return 'Enlever cette tâche de mes favoris';
            }

            return 'Mettre cette tâche dans mes favoris';
        },

        activeActionTitle: function() {
            if (this.task.active) {
                return 'Fermer cette tâche';
            }

            return 'Ouvrir cette tâche';
        }
    },

    methods: {
        getTask: function() {
            this.$http.get('tasks/' + this.taskNumber)
                .then(function (response) {
                    this.task = response.data.data;
                    this.$dispatch('task-updated', this.task);
                });
        },

        toggleState: function(task) {
            if (! task.user_can_edit) {
                return;
            }
            var updatedTask = {
                active: !task.active
            };
            this.$http.patch('tasks/' + task.number, updatedTask)
            .then(function (response) {
                this.task = response.data.data;
                this.$dispatch('task-updated', this.task);
            });
        },

        toggleFavourite: function(task) {
            if (task.favourited) {
                this.$http.delete('favourite-tasks', {number: task.number})
                    .then(function (response) {
                        task.favourited = false;
                        this.favouriteToggled();
                    });
                return;
            }

            this.$http.post('favourite-tasks', {number: task.number})
                .then(function (response) {
                    task.favourited = true;
                    this.favouriteToggled();
                });
        },

        showFormAccess: function(task) {
            this.form.task = task;
            this.form.show = true;
        },

        cancelAccess: function() {
            this.form.show = false;
            this.form.task = null;
            this.form.reason = null;
        },

        askAccess: function() {
            this.$http.post('tasks/' + this.form.task.number + '/access', {number: this.form.task.number, reason: this.form.reason})
                .then(function (response) {
                    this.form.task = null;
                    this.form.show = false;
                    this.form.reason = null;

                    SweetAlert({
                        title: 'Demande envoyée!',
                        text: 'Votre demande d\'accès a été envoyée.',
                        type: 'success'
                    });
                }, function (response) {
                    var message = [];
                    for (var i in response.data) {
                        message.push(response.data[i]);
                    }

                    SweetAlert({
                        title: 'Erreur!',
                        text: message.join('<br>'),
                        type: 'error'
                    });
                });
        }
    }
};
