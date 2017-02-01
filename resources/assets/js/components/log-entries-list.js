var colors = [
    '#a1da73',
    '#25e1cd',
    '#f48178',
    '#4c6bcf',
    '#f9bb00',
    '#9d3176'
];
var projectColorCounter = 0;
var projectColor = {};
var temporaryColor = '#910000';

module.exports = {
    template: '#log-entries-list-template',
    props: ['whenEdited', 'whenDeleted', 'whenNewEntry', 'entries', 'date', 'setDate'],
    components: {
        'log-entry': require('./log-entry.js'),
        'day-slider': require('./day-slider.js')
    },
    computed:{
        noEntries: function(){
            return this.entries.length == 0;
        },
        workedTimeToday: function(){
            var workedTime = 0;
            $.each(this.entries, function(i,el){
                workedTime += el.duration;
            });
            return workedTime;
        }
    },
    watch: {
        'entries': {
            handler: function () {
                projectColor = {};
                projectColorCounter = 0;
            },
            deep: true
        }
    },
    methods: {
        getProjectColor: function(entry){
            if(this.isTemporary(entry)) {
                return temporaryColor;
            }

            if(!projectColor.hasOwnProperty(entry.project.data.id)){
                projectColor[entry.project.data.id] = colors[projectColorCounter++];
                if(projectColorCounter >= colors.length){
                    projectColorCounter = 0;
                }
            }
            return projectColor[entry.project.data.id];
        },

        isTemporary: function(entry) {
            return entry.task.data.number == 40354; // Tâche temporaire
        }
    }
};
