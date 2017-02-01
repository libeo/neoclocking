var Mousetrap = require('mousetrap');

module.exports = {

    template: '#search-bar-template',

    props: ['favouriteToggled', 'addNewTime'],

    components: {
        'task-actions': require('./task-actions.js')
    },

    data: function() {
        return {
            term: '',
            showResults: false,
            results: {},
            emptyResults: false,
            isLoading: true
        }
    },

    attached: function(){
        var self = this;
        var searchInput = $(this.$el.parentNode).find('.search-input');
        this.$on('hideSearchResults', function() {
            self.hideResults();
        });
        Mousetrap.bind('/', function(e) {
            if ($(e.target).hasClass('mousetrap')) {
                return;
            }
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        });
    },

    methods: {
        search: function(event) {
            // Not Esc or Tab
            if(!event || !event.keyCode || (event.keyCode != 27 && event.keyCode != 9)){
                this.showResults = true;
                this.emptyResults = false;

                if (this.term == '') {
                    this.showResults = false;
                    this.results = [];
                    return;
                }

                this.isLoading = true;
                var that = this;
                this.$http.get('tasks', {term: this.term})
                .then(function (response) {
                    that.results = response.data['data'];
                    if (that.results.length == 0) {
                        that.emptyResults = true;
                    }
                    that.isLoading = false;
                });
            }
        },

        addTimeEntry: function(entry) {
            this.term = '';
            this.showResults = false;
            var self=  this;

            // Add time component expects the task in a task hash
            entry.task = {};
            entry.task.data = {
                id: entry.id,
                number: entry.number,
                name: entry.name,
                logged_time: entry.logged_time,
                estimation: entry.estimation,
                estimation_exceeded: entry.estimation_exceeded,
                require_comments: entry.require_comments
            };
            self.addNewTime(entry);

        },
        resetResults: function(){
            this.term = '';
            this.hideResults();
            $('.search-input').focus();
        },
        hideResults: function() {
            this.showResults = false;
        },
        showIfNotEmpty: function(){
            if(this.term != ''){
                this.showResults = true;
            }
        }
    },

    events: {
        'task-updated': function() {
            this.search();
        }
    }
};
