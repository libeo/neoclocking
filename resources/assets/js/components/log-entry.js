require('jquery.inputmask');
var SweetAlert = require('sweetalert');

module.exports = {

    template: '#log-entry-template',

    props: ['entry', 'whenEdited', 'whenDeleted', 'whenNewEntry', 'allEntries', 'color', 'temporary'],

    created: function(){
        this.resetTempEntry();
    },

    ready: function(){
        var $this = $(this.$el.parentNode);
        $this.find('.edit-time-input-date').pickadate({
            format: 'yyyy-mm-dd',
            clear: false,
            firstDay: 0
        });

        var $inputs = $this.find('input.edit-time-input-hour-start, input.edit-time-input-hour-end');
        Inputmask('9{1,2}:99', { numericInput: true, placeholder: '0' }).mask($inputs);
    },

    data: function(){
        return {
            showEdit: false,
            showEntryTime: true,
            temp_entry: {}
        }
    },

    computed: {
        temp_entry_duration: {
            get: function () {
                // Validate the time entries
                var start = moment(this.temp_entry.date +' '+ this.temp_entry.start_time, 'YYYY-M-D HH:mm');
                var end = moment(this.temp_entry.date +' '+ this.temp_entry.end_time, 'YYYY-M-D HH:mm');
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
                this.temp_entry.end_time = moment(this.temp_entry.date +' '+ this.temp_entry.start_time, 'YYYY-M-D HH:mm').add(duration, 'minutes').format('H:mm');
            }
        },
        validSaveTime: function(){
            var validity = true;
            var start = moment(this.temp_entry.date +' '+ this.temp_entry.start_time, 'YYYY-M-D HH:mm');
            var end = moment(this.temp_entry.date +' '+ this.temp_entry.end_time, 'YYYY-M-D HH:mm');
            var duration = moment.duration(end.diff(start));
            if(duration.asMinutes() <= 0){
                validity = false
            }

            if(this.entry.task.data.require_comments && ( ! this.temp_entry.comment || this.temp_entry.comment.length === 0 || ! this.temp_entry.comment.trim())){
                validity = false;
            }

            return validity;
        },
        comments_required: function(){
            return this.entry.task.data.require_comments;
        },
        isGapping: function(){
            var self = this;
            var entryIndex = null;
            this.allEntries.forEach(function(entry, index){
                if(entry.id == self.entry.id){
                    entryIndex = index;
                    return false;
                }
            });
            if(entryIndex != 0){
                var prevEntry = this.allEntries[entryIndex-1];
                var diff = moment(prevEntry.started_at).diff(this.entry.ended_at);
                if(diff > 0){
                    return true;
                }
            }

            return false;
        },
        overlaps: function(){
            var overlaps = false;
            var self =  this;
            this.allEntries.forEach(function(entry, index){
                if(entry.id !== self.entry.id){
                    var currentEntryRange = moment.range(self.entry.started_at, self.entry.ended_at);
                    var otherEntryRange = moment.range(entry.started_at, entry.ended_at);
                    if(currentEntryRange.overlaps(otherEntryRange)){
                        overlaps = true;
                    }
                }
            });
            return overlaps;
        },
        temporaryColor: function() {
            if (this.temporary) {
                return '#ffefef';
            }

            return false;
        }
    },

    methods: {

        toggleEntryTime: function(){
            this.showEntryTime = !this.showEntryTime;
        },

        toggleEditMode: function(){
            var self = this;
            if(this.showEdit == false && CLOCKING.editing > 0) {
                SweetAlert({
                    title: 'Attention!',
                    text: 'Vous êtes en train de modifier du temps. Souhaitez-vous abandonner ces changements?',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonText: 'Non',
                    confirmButtonText: 'Oui'
                }, function (isConfirm) {
                    if (isConfirm) {
                        CLOCKING.inEdition.cancelEdit();
                        self.doToggleEditMode();
                    }
                })
            }else{
                self.doToggleEditMode();
            }
        },
        doToggleEditMode: function(){
            this.toggleEntryTime();
            this.showEdit = !this.showEdit;
            if(this.showEdit){
                CLOCKING.inEdition = this;
                CLOCKING.editing++;
            }else{
                CLOCKING.editing--;
            }
        },

        cancelEdit: function(){
            this.toggleEditMode();
            this.resetTempEntry();
        },

        saveEdit: function() {
            var self = this;
            self.whenEdited(self.entry, self.temp_entry, self.toggleEditMode);
        },

        deleteEntry: function(e){
            // Clicking on the parent element triggers edit mode; that's not what we want.
            e.stopPropagation();
            var self = this;
            if(CLOCKING.editing > 0){
                SweetAlert({
                    title: 'Attention!',
                    text: 'Vous être en train de modifier du temps. Terminez les changements et reessayez.',
                    type: 'warning',
                    showCancelButton: false,
                    confirmButtonText: 'OK'
                })
            }else{
                this.whenDeleted(this.entry);
            }
        },

        addNewEntry: function(){
            this.whenNewEntry(this.temp_entry);
        },

        replaceTask: function(){
            var self = this;
            SweetAlert({
                title: 'Remplacer la tâche',
                text: 'Entrez le nouveau numéro de tâche pour cette entrée de temps.',
                type: 'input',
                showCancelButton: true,
                cancelButtonText: 'Annuler',
                closeOnConfirm: false,
                inputPlaceholder: '00000'
            }, function(taskNumber) {
                var numberRegExp = /^\d{1,9}$/;
                if(!numberRegExp.test(taskNumber)){
                    SweetAlert.showInputError('Le numéro de tâche n\'est pas valide.')
                    return false;
                }
                self.$http.get('tasks/'+taskNumber).then(function(response){
                    SweetAlert.close();
                    self.temp_entry.task = response.data;
                    self.temp_entry.project = response.data.data.project;
                    self.temp_entry.client = response.data.data.project.data.client;
                }).catch(function(response){
                    SweetAlert.showInputError(response.data.message ? response.data.message : response.statusText)
                });
            });
        },

        resetTempEntry: function() {
            this.temp_entry = {
                task: this.entry.task,
                project: this.entry.project,
                client: this.entry.client,
                comment: this.entry.comment,
                start_time: moment.utc(this.entry.started_at).local().format('H:mm'),
                end_time: moment.utc(this.entry.ended_at).local().format('H:mm'),
                date:  moment.utc(this.entry.started_at).local().format('YYYY-MM-DD')
            };
        }
    }
};
