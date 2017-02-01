var Fuse      = require('fuse.js');
var Mousetrap = require('mousetrap');
var trim      = require('trim');

module.exports = {
    data: function () {
        return {
            clients:      [],
            clientsCount: 0,
            filter:       '',
            loaded:       false
        };
    },

    ready: function () {
        this.getClients();

        Mousetrap.bind('/', this.focusSearch);
    },

    methods: {
        focusSearch: function (e) {
            e.preventDefault();
            this.$els.search.focus();
        },

        getClients: function () {
            this.$http.get(this.urlForProjects)
                .then(function (response) {
                    this.parseResponse(response.data.data);
                    this.loaded = true;
                }, function () {
                    this.loaded = true;
                });
        },

        parseResponse: function (projects) {
            var clients = {};
            var count   = 0;

            for (var i in projects) {
                var project    = projects[i];
                var clientId   = project.client.data.id;
                var clientName = project.client.data.name;

                if (!clients[clientId]) {
                    clients[clientId] = {
                        name:     clientName,
                        projects: []
                    };

                    count++;
                }

                clients[clientId].projects.push(project);
            }

            for (var i in clients) {
                this.clients.push(clients[i]);
            }

            this.clients.sort(function (a, b) {
                return a.name.localeCompare(b.name);
            });
        },

        filterProjects: function (client) {
            if (this.filter == '') {
                return client.projects;
            }

            var fuse = new Fuse(client.projects, {
                threshold: 0.2,
                keys:      ['number', 'name']
            });

            var results = fuse.search(trim(this.filter));

            if (results.length == 0) {
                return client.projects;
            }

            return results;
        }
    },

    computed: {
        urlForProjects: function () {
            return '/api/projects/lists';
        },

        filtered: function () {
            if (this.filter == '') {
                return this.clients;
            }

            var fuse = new Fuse(this.clients, {
                tokenize:  true,
                threshold: 0.08,
                keys:      ['name', 'projects.number', 'projects.name']
            });

            return fuse.search(trim(this.filter));
        }
    }
};
