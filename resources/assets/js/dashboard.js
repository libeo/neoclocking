var SweetAlert = require('sweetalert');
var Mousetrap = require('mousetrap');

module.exports = {
    //container
    el: 'body',

    ready: function() {
        this.setDate(this.currentDate);
        var self = this;
        this.$http.get('live-entries').then(function(response) {
            if (response.data.data.started_at) {
                self.liveClock = response.data.data;
                if (moment(self.liveClock.started_at.date).format('YYYY-MM-DD') != moment().format('YYYY-MM-DD')) {
                    this.genericErrorResponse({
                        status: 'Vous avez oublié d\'arrêter le chronomètre?',
                        data:{message: 'Vous avez du temps en cours de saisie. Veuillez le sauvegarder ou il sera perdu.'}
                    });
                    this.editLiveClocking();
                }
            }
        }).finally(function() {
            // Other pages may redirect here with #/clock/{taskId} in the URL
            var hashData = window.location.hash.slice(1);
            var match = /^\/clock\/([0-9]+)$/.exec(hashData);
            if (match !== null && match.length == 2) {
                // If an ID is found, get the data for the task and display the form to clock
                self.addTimeToTask(match[1]);
            }
        });

        $(window).on('blur', this.stopWaiting);
        $(window).on('focus', this.waitForHour);
        this.waitForHour();

        Mousetrap.bind(['command+enter', 'ctrl+enter'], function(e) {
            $(e.target).parents('.add-time-wrapper, .clock-wrapper').find('.edit-time-bottom-right button').click();
        });
    },

    components: {
        'time-remaining': require('./components/time-remaining.js'),
        'new-entry': require('./components/add-time.js'),
        'search-bar': require('./components/search-bar.js'),
        'day-slider': require('./components/day-slider.js'),
        'log-entries-list': require('./components/log-entries-list.js'),
        'live-entry': require('./components/live-entry.js'),
        'dashboard-sidebar': require('./components/dashboard-sidebar.js'),
        'groups-users': require('./components/groups-users.js'),
        'last-days': require('./components/last-days.js')
    },

    //data binding
    data: {
        isAddingTime: false,
        weekClients: {},
        weekProjects: {},
        weekDays: {},
        startTime: '',
        currentDate: moment(),
        currentWeekTotalTime: 0,
        nextHourInterval: null,
        selectedTask: {},
        selectedProject: {},
        selectedClient: {},
        weekLogEntries: [],
        sideBar: {
            showSummary: false,
            showFavorites: true
        },
        summaryTab : {
            clients: false,
            projects: true,
            days: false
        },
        currentSummaryFilter: 'by-project',
        workedTime: {},
        tempEntry: {},
        liveClock : null,
        overrideNewEntry: null,
        newClockWaiting: null
    },

    //methods
    methods: {
        hideSearchResults: function() {
            this.$broadcast('hideSearchResults');
        },
        genericErrorResponse: function (response) {
            SweetAlert({
                title: response.status,
                text: response.data.message ? response.data.message : response.statusText,
                type: 'error'
            });
        },
        addTimeToTask: function(taskNumber) {
            this.$http.get('tasks/' + taskNumber)
            .then(function (response) {
                var task = response.data;
                this.addNewTime({
                    task: task,
                    project: task.data.project,
                    client: task.data.project.data.client
                });
            })
            .catch(this.genericErrorResponse);
        },
        filterweekLogEntriesCurrentDate: function(weekLogEntry) {
            return moment.utc(weekLogEntry.started_at).tz(window.timezone).format('YYYY-MM-DD') == this.currentDate;
        },
        setDate: function(date) {
            var newDate = date.format('YYYY-MM-DD');
            if (this.currentDate !== newDate) {
                this.currentDate = newDate;
                var self = this;
                clearTimeout(this.loadTimeout);
                this.loadTimeout = setTimeout(function() {
                    self.getWeekLogEntries();
                }, 200);
            }
        },
        saveNewTimeEntry: function(entry, callback, errorCallback) {
            var self = this;
            if (CLOCKING.editing > 0) {
                SweetAlert({
                    title: 'Attention!',
                    text: 'Vous êtes en train de modifier du temps. Souhaitez-vous abandonner ces changements?',
                    type: 'warning',
                    showCancelButton: true,
                    cancelButtonText: 'Non',
                    confirmButtonText: 'Oui'
                }, function(isConfirm) {
                    if (isConfirm) {
                        self.doSaveNewTimeEntry(entry, callback, errorCallback);
                    }
                })
            } else {
                self.doSaveNewTimeEntry(entry, callback, errorCallback);
            }
        },
        doSaveNewTimeEntry : function(entry, callback, errorCallback) {
            var self = this;

            var started_at = moment(entry.date + ' ' + entry.time_start, 'YYYY-M-D HH:mm').utc().format('YYYY-M-D HH:mm:00');
            var ended_at = moment(entry.date + ' ' + entry.time_end, 'YYYY-M-D HH:mm').utc().format('YYYY-M-D HH:mm:00');
            self.$http.post('log-entries', {
                task_id: entry.task.data.id,
                started_at: started_at,
                ended_at: ended_at,
                comment: entry.comment
            })
            .then(function (response) {
                self.logsUpdated();
                self.isAddingTime = false;
                CLOCKING.adding--;
                if (callback) {
                  callback();
                }

                if (self.newClockWaiting) {
                  self.addNewTime(self.newClockWaiting);
                  self.newClockWaiting = null;
                }
            })
            .catch(function(response) {
                if (errorCallback) {
                    errorCallback();
                }
                self.errorSave(response)
            });
        },
        errorSave: function(response) {
            if (response.status == 422) {
                var messages = '';
                $.each(response.data.errors, function(key, data) {
                    messages += data[0] + '<br>';
                });
                SweetAlert({
                    title: 'Oups!',
                    text: messages,
                    type: 'error',
                    html: true
                });
            } else {
                this.genericErrorResponse(response);
            }
        },
        cancelAddNewTime: function() {
            this.isAddingTime = false;

            CLOCKING.adding--;
            if (this.newClockWaiting) {
                this.addNewTime(this.newClockWaiting);
                this.newClockWaiting = null;
            }
        },
        addNewTime: function(entry) {
            var self = this;
            if (this.liveClock) {
                SweetAlert({
                    title: 'Vous avez un enregistrement de temps en cours.',
                    text: 'Souhaitez-vous arrêter et commencer un nouvel enregistrement?',
                    type: 'error',
                    showCancelButton: true,
                    confirmButtonText: 'Oui',
                    cancelButtonText: 'Non'
                }, function(isConfirm) {
                    if (isConfirm) {
                        self.newClockWaiting = entry;
                        self.saveLiveClocking();
                    }
                });
            } else {
                this.addNewTimeAction(entry);
            }
        },
        addNewTimeAction: function(entry) {
            var currentDayLogs = this.weekLogEntries.filter(this.filterweekLogEntriesCurrentDate);
            var lastCurrentDayLog = currentDayLogs.pop();
            var startTime = '';
            if (lastCurrentDayLog) {
                startTime = moment.utc(lastCurrentDayLog.ended_at).tz(window.timezone).format('HH:mm');
            } else {
                if (moment().isSame(this.currentDate, 'day')) {
                    startTime = moment().format('HH:mm');
                }
            }

            this.startTime = startTime;
            this.selectedTask = entry.task;
            this.selectedProject = entry.project;
            this.selectedClient = entry.client;
            this.isAddingTime = true;
            CLOCKING.adding++;

            this.messageToNotExceed(entry.project.data);
        },
        removeTimeEntry: function(entry) {
            var self = this;
            SweetAlert({
                title: 'Suppression de temps',
                text: 'Êtes-vous certain de vouloir supprimer ce temps?',
                type: 'warning',
                showCancelButton: true,
                cancelButtonText: 'Non',
                closeOnConfirm: false,
                confirmButtonColor: '#d44141',
                confirmButtonText: 'Oui'
            }, function () {
                self.$http.delete('log-entries/' + entry.id)
                    .then(function() {
                        SweetAlert.close();
                        self.weekLogEntries.$remove(entry);
                        self.logsUpdated();
                    })
                    .catch(self.genericErrorResponse);
            });
        },
        saveEntryModification: function(entry, temp_entry, callback) {
            var started_at = moment(temp_entry.date + ' ' + temp_entry.start_time, 'YYYY-M-D HH:mm').utc().format('YYYY-M-D HH:mm:00');
            var ended_at = moment(temp_entry.date + ' ' + temp_entry.end_time, 'YYYY-M-D HH:mm').utc().format('YYYY-M-D HH:mm:00');
            this.$http.patch('log-entries/' + entry.id, {
                    task_id: temp_entry.task.data.id,
                    started_at: started_at,
                    ended_at: ended_at,
                    comment: temp_entry.comment
                })
                .then(function (response) {
                    this.logsUpdated();
                    this.favouriteToggled();
                    callback();
                })
                .catch(this.errorSave);
        },
        aggregateProjects: function() {
            var self = this;
            this.weekProjects = {};
            this.weekLogEntries.forEach(function(entry, index, array) {
                if (! self.weekProjects.hasOwnProperty(entry.project.data.id)) {
                    self.weekProjects[entry.project.data.id] = {
                        project_id: entry.project.data.id,
                        project_number: entry.project.data.number,
                        project_name: entry.project.data.name,
                        client_name: entry.client.data.name,
                        time: entry.duration
                    };
                } else {
                    self.weekProjects[entry.project.data.id].time += entry.duration;
                }
            });
        },
        aggregateClients: function() {
            var self = this;
            this.weekClients = {};
            this.weekLogEntries.forEach(function(entry, index, array) {
                if (! self.weekClients.hasOwnProperty(entry.client.data.id)) {
                    self.weekClients[entry.client.data.id] = {
                        client_id: entry.client.data.id,
                        client_name: entry.client.data.name,
                        time: entry.duration
                    };
                } else {
                    self.weekClients[entry.client.data.id].time += entry.duration;
                }
            });
        },
        aggregateWeekDays: function() {
            var self = this;
            this.weekDays = {};
            this.weekLogEntries.forEach(function(entry, index, array) {
                var moment_date = moment.utc(entry.started_at, 'YYYY-MM-DD h:mm:ss').local();
                var day = moment_date.format('E');
                if (day == 7) {
                    day = 0;
                }

                if (! self.weekDays.hasOwnProperty(day)) {
                    self.weekDays[day] = {
                        day: moment_date.format('dddd'),
                        time_logged: entry.duration
                    };
                } else {
                    self.weekDays[day].time_logged += entry.duration;
                }
            });
        },
        favouriteToggled: function() {
            this.$broadcast('favouritesUpdated');
        },
        logsUpdated: function() {
            this.$broadcast('logsUpdated');
            this.getWeekLogEntries();
        },
        getDayLogEntries: function() {
            this.$http.get('log-entries', { filterBy: 'day', date: this.currentDate })
                .then(function (response) {
                    this.weekLogEntries = response.data['data'];
                    this.aggregateClients();
                    this.aggregateProjects();
                    this.aggregateWeekDays();
                    this.updateCurrentWeekTotalTime();
                })
                .catch(this.genericErrorResponse);
        },
        getWeekLogEntries: function(callback) {
            this.$http.get('log-entries', { filterBy: 'week', date: this.currentDate })
                .then(function (response) {
                    CLOCKING.editing = 0;
                    this.weekLogEntries = response.data['data'];
                    this.aggregateClients();
                    this.aggregateProjects();
                    this.aggregateWeekDays();
                    this.updateCurrentWeekTotalTime();
                    if (callback) {
                        callback();
                    }
                })
                .catch(this.genericErrorResponse);
            this.$broadcast('logEntriesUpdate', this.currentDate)
        },
        updateCurrentWeekTotalTime: function() {
            var total = 0;
            this.weekLogEntries.forEach(function(entry, index, array) {
                total += entry.duration;
            });
            this.currentWeekTotalTime = total;
        },
        waitForHour: function() {
            if (this.nextHourInterval) {
                clearInterval(this.nextHourInterval);
            }

            var now = moment();
            var nextChime = moment();

            var nextHour = nextChime.hours() + 1;
            if (nextHour > 23) {
                nextChime.add(1, 'd');
                nextHour = 0;
            }
            nextChime.hours(nextHour);
            nextChime.minutes(0);
            nextChime.seconds(0);

            var self = this;
            this.nextHourInterval = setInterval(function () {
                (new Audio('/audio/bigben_strike.mp3')).play();
                console.log('🕐');
                self.waitForHour();
            }, nextChime.valueOf() - now.valueOf());
        },
        stopWaiting: function() {
            if (this.nextHourInterval) {
                clearInterval(this.nextHourInterval);
            }
        },
        startLiveClocking: function(entry, task) {
            var self = this;
            var start = moment(entry.date + ' ' + entry.time_start, 'YYYY-M-D HH:mm').utc().format('YYYY-M-D HH:mm:00');
            this.$http.post('live-entries', {
                task_id: task.data.id,
                started_at: start,
                comment: entry.comment
            }).then(function(response) {
                this.liveClock = response.data.data;
                self.cancelAddNewTime();
            }, function(response) {
                SweetAlert({
                    title: 'Erreur',
                    text: response.data.message,
                    type: 'error',
                    allowOutsideClick: true
                });
            });
        },
        cancelLiveClocking:function() {
            var self = this;
            this.$http.delete('live-entries').then(function() {
                self.liveClock = false;
            });
        },
        saveLiveClocking: function() {
            var entry = {
                task: this.liveClock.task,
                date: moment.utc(this.liveClock.started_at.date).local().format('YYYY-MM-DD'),
                time_start: moment.utc(this.liveClock.started_at.date).local().format('HH:mm'),
                time_end: moment().format('HH:mm'),
                comment: this.liveClock.comment
            };
            var self = this;
            this.saveNewTimeEntry(
                entry,
                function() {
                    self.liveClock = false;
                    self.$http.delete('live-entries');
                    if (self.newClockWaiting) {
                        self.addNewTime(self.newClockWaiting);
                        self.newClockWaiting = null;
                    }
                },
                this.editLiveClocking
            );
        },
        editLiveClocking: function() {
            this.startTime = moment.utc(this.liveClock.started_at.date).local().format('HH:mm');
            this.selectedTask = this.liveClock.task;
            this.selectedProject = this.liveClock.task.data.project;
            this.selectedClient = this.liveClock.task.data.project.data.client;
            this.isAddingTime = true;

            CLOCKING.adding++;
            var time_end = moment().format('HH:mm');
            if (moment(this.liveClock.started_at.date).format('YYYY-MM-DD') != moment().format('YYYY-MM-DD')) {
                time_end = '23:59'
            }
            this.overrideNewEntry = {
                time_start:  moment.utc(this.liveClock.started_at.date).local().format('HH:mm'),
                time_end: time_end,
                date : moment.utc(this.liveClock.started_at.date).local().format('YYYY-MM-DD')
            };
            this.liveClock = false;
            this.$http.delete('live-entries');
        },
        stopLiveClocking: function() {
            this.genericErrorResponse({
                status: 'Stop!',
                data:{message: 'Il ne reste plus de temps dans cette banque d\'heures.'}
            });
            this.editLiveClocking();
        },
        messageToNotExceed: function(projectData) {
            var alertWhenLessThan = 8 * 60;
            this.$http.get('projects/' + projectData.number).then(function(response) {
                var project = response.data.data;
                if (project.should_not_exceed && project.remaining_time <= alertWhenLessThan) {
                    var message = 'Il ne reste plus de temps dans cette banque d\'heures.';
                    if (project.remaining_time > 0) {
                        var time = moment.utc(moment.duration(project.remaining_time, 'minutes').asMilliseconds()).format('H:mm');
                        message = 'Il ne reste que ' + time + ' à la banque de temps.';
                    }
                    SweetAlert({
                        title: 'Attention!',
                        text: message,
                        type: 'warning'
                    });
                }
            });
        }
    }
};
