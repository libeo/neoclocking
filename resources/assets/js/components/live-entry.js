var SweetAlert = require('sweetalert');

module.exports = {
    template: '#live-entry-template',
    props: ['entry', 'save', 'cancel','stopLiveClocking'],
    data: function(){
        return {
            comment: null,
            currentTime: null,
            lastSave: null,
            timerInterval:null
        }
    },
    ready: function(){
        this.currentTime = moment().utc().format();
        var self = this;
        this.timerInterval = setInterval(function() {
            var project = self.entry.task.data.project.data;
            self.currentTime = moment().utc().format();
            if (project.should_not_exceed && self.timer >= project.remaining_time) {
                self.stopLiveClocking();
            }
        });
    },
    beforeDestroy: function(){
        clearInterval(this.timerInterval);
    },
    computed: {
        timer: function(){
            var start = moment.utc(this.entry.started_at.date)
            return moment.utc(this.currentTime).diff(start, 'minutes');
        },
        validClock: function(){
            if(this.entry.task.data.require_comments && ( ! this.entry.comment || this.entry.comment.length === 0 || ! this.entry.comment.trim())){
                return false;
            }
            return true;
        }
    },
    methods: {
        saveComment: function(e){
            if(e.keyCode !== 13){
                var self = this;
                this.$http.patch('live-entries', {
                    comment: this.entry.comment
                }).then(function(response){
                    self.lastSave = moment().format('H:mm');
                }, function(response){
                    SweetAlert({
                        title: 'Erreur',
                        text: response.data.message,
                        type: 'error',
                        allowOutsideClick: true
                    }, function(){
                        self.cancel();
                    });
                });
            }
        }
    }
}
