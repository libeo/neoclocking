module.exports = {

    template: '#dashboard-sidebar-template',

    props: ['weekClients', 'weekProjects', 'weekDays', 'addNewTime', 'totalTime', 'favouriteToggled'],

    components: {
        'task-actions': require('./task-actions.js')
    },

    data: function(){
        return {
            sideBarViews: {
                showSummary: false,
                showFavorites: true
            },
            summaryTabs: {
                clients: false,
                projects: true,
                days: false
            },
            myFavourites: []
        }
    },

    ready: function(){
        this.getFavourites();
    },

    events: {
        favouritesUpdated: function () {
            this.getFavourites();
        }
    },

    methods: {
        changeWeekFilter: function(event) {
            event.preventDefault();
            var selectValue = event.target.value;
            if(this.summaryTabs.hasOwnProperty(selectValue)){
                this.changeSummaryTabView(selectValue);
            }
        },

        changeSideBarToSummary: function(event){
            event.preventDefault();
            this.sideBarViews.showFavorites = false;
            this.sideBarViews.showSummary = true;
        },

        changeSideBarToFavorites: function(event){
            event.preventDefault();
            this.sideBarViews.showSummary = false;
            this.sideBarViews.showFavorites = true;
        },

        /**
         * Change the view based on the variable received
         * @param {string} view
         */
        changeSummaryTabView: function(view){
            var that = this;
            Object.keys(this.summaryTabs).map(function(value) {
                that.summaryTabs[value] = value == view;
            });
        },

        addTimeEntry: function(entry){
            // Add time component expects the task in a task hash
            entry.task = {};
            entry.task.data = {
                id: entry.id,
                number: entry.number,
                name: entry.name,
                logged_time: entry.logged_time,
                estimation: entry.estimation,
                require_comments: entry.require_comments
            };
            this.addNewTime(entry);
        },

        getFavourites: function() {
            this.$http.get('favourite-tasks')
            .then(function (response) {
                this.myFavourites = response.data['data'];
            }).catch(function(response) {
                SweetAlert({
                    title: response.status,
                    text: response.data.message ? response.data.message : response.statusText,
                    type: "error"
                });
            });
        }
    }
};
