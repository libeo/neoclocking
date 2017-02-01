Cookies = require('js-cookie');

module.exports = {
    template: '<span class="time-total time-total-byhour" v-show="byHour == true" @click="timeTotalToDays">{{ timeRemaining|minutesToHours }}</span><span class="time-total time-total-byday" v-show="byDay == true" @click="timeTotalToHours">{{ minutesToDays }}</span>',
    props: ['timeRemaining', 'timePerDay'],
    data: function (){

        var timeTotalChoice = Cookies.get('time_total_choice');
        var returnData = {
            byHour: true,
            byDay: false
        };

        if (timeTotalChoice == 'day') {
            returnData = {
                byHour: false,
                byDay: true
            };
        }

        return returnData;
    },

    ready: function() {
        this.getTimeRemaining();
        this.getTimePerDay();
        $(window).on('focus', this.getTimeRemaining);
    },

    events: {
        logsUpdated: function () {
            this.getTimeRemaining();
        }
    },

    computed: {
        minutesToDays: function() {

            if (this.timeRemaining && this.timePerDay) {

                var absTimeRemaining = Math.abs(this.timeRemaining);

                var daysTotal = Math.floor(absTimeRemaining / this.timePerDay);
                var hoursAndMinsTotal = absTimeRemaining % this.timePerDay;
                var hoursTotal = Math.floor(hoursAndMinsTotal / 60);
                var minsTotal = hoursAndMinsTotal % 60;

                var prefix = '';
                if (this.timeRemaining < 0) {
                    prefix = '-';
                }

                var newString = prefix + daysTotal + 'j ' + hoursTotal + 'h ' + minsTotal + 'm';

                return newString;
            }

            return '0j 0h 0m';

        }
    },

    methods: {
        getTimeRemaining: function() {
            this.$http.get('users/me/timeRemainingThisWeek')
                .then(function (response) {
                    this.timeRemaining = response.data.data.time_remaining;
                });
        },
        getTimePerDay: function() {
            this.$http.get('users/me/timePerDay')
                .then(function (response) {
                    this.timePerDay = response.data.data.time_per_day;
                });
        },
        timeTotalToDays: function() {
            Cookies.set('time_total_choice', 'day');
            this.byHour = false;
            this.byDay = true;
        },
        timeTotalToHours: function() {
            Cookies.set('time_total_choice', 'hour');
            this.byHour = true;
            this.byDay = false;
        }
    }

};
