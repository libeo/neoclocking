require('jquery.inputmask');

module.exports = {

    template: '#addTimeTemplate',

    props: ['startTime', 'currentDate', 'myTask', 'myProject', 'myClient', 'whenSaved', 'whenCanceled', 'whenLiveClockingStart', 'overrideEntry'],

    ready: function() {
        var $this = $(this.$el.parentNode);
        $this.find('.edit-time-input-date').pickadate({
            format: 'yyyy-mm-dd',
            clear: false,
            firstDay: 0
        });

        var $inputs = $this.find('input.edit-time-input-hour-start, input.edit-time-input-hour-end');
        Inputmask('9{1,2}:99', { numericInput: true, placeholder: '0' }).mask($inputs);

        this.entry.time_start = this.startTime;
        if(this.overrideEntry !== null){
            this.entry.time_start = this.overrideEntry.time_start;
            this.entry.time_end = this.overrideEntry.time_end;
            this.entry.date = this.overrideEntry.date;
        }
        this.overrideEntry = null;
    },

    attached : function(){
        var startTimeInput = $(this.$el.parentNode).find('input.edit-time-input-hour-start');
        startTimeInput.focus();
        startTimeInput.select();
    },
    watch: {
        'startTime' : function(startTime){
            this.entry.time_start = startTime;
        }
    },

    data: function(){
        return {
            entry: {
                time_start: '',
                time_end: '',
                duration: '',
                date : moment(this.currentDate).format('YYYY-MM-DD'),
                comment: ''
            },
            entry_duration: ''
        }
    },

    computed: {
        entry_duration: {
            get: function () {
                // Validate the time entries
                start = moment(this.entry.date +' '+ this.entry.time_start, 'YYYY-M-D HH:mm');
                end = moment(this.entry.date +' '+ this.entry.time_end, 'YYYY-M-D HH:mm');
                if (start.isValid() && end.isValid()) {
                    duration = moment.duration(end.diff(start));
                    if (duration > 0) {
                        return duration.format('h:mm');
                    }
                }
            },
            set : function(duration){
                if(duration.trim() == ''){
                    return;
                }
                if(duration.indexOf(':') !== -1){
                    duration = moment.duration(duration).asMinutes();
                }
                this.entry.time_end = moment(this.entry.date +' '+ this.entry.time_start, 'YYYY-M-D HH:mm').add(duration, 'minutes').format('H:mm');
            }
        },
        validAddTime: function(){
            var validity = true;
            var start = moment(this.entry.date +' '+ this.entry.time_start, 'YYYY-M-D HH:mm');
            var end = moment(this.entry.date +' '+ this.entry.time_end, 'YYYY-M-D HH:mm');
            var duration =  moment.duration(end.diff(start));

            if(duration.asMinutes() <= 0){
                validity = false
            }

            if(this.myTask.data.require_comments && ( this.entry.comment.length === 0 || ! this.entry.comment.trim())){
                validity = false
            }

            return validity;
        },
        comments_required: function(){
            return this.myTask.data.require_comments;
        },
        canLiveClock: function(){
            var validity = true;
            var start = moment(this.entry.date +' '+ this.entry.time_start, 'YYYY-M-D HH:mm');
            var end = moment(this.entry.date +' '+ this.entry.time_end, 'YYYY-M-D HH:mm');
            var duration =  moment.duration(end.diff(start));
            if(duration > 0){
                validity = false
            }
            if(this.entry.date != moment().format('YYYY-MM-DD')){
                validity = false;
            }

            return validity;
        }
    },

    methods: {
        resetEntry: function(){
            this.entry = {
                time_start: this.startTime,
                time_end: '',
                duration: '',
                date : moment(this.currentDate).format('YYYY-MM-DD'),
                comment: ''
            }
        },

        cancelEntry : function(){
            this.whenCanceled();
            this.resetEntry();
        },

        saveEntry: function(){
            var new_entry = this.entry;
            new_entry.task = this.myTask;
            new_entry.project = this.myProject;
            new_entry.client = this.myClient;

            this.whenSaved(new_entry, this.resetEntry);
        },
        startLiveClocking: function(){
            this.whenLiveClockingStart(this.entry, this.myTask);
        }
    }
};
