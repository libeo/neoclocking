module.exports = {
    props: ['project', 'client'],

    computed: {
        totalHours: function () {
            return this._formatHours(this.project.max_time);
        },

        allowedHours: function () {
            return this._formatHours(this.project.allowed_time);
        },

        usedHours: function () {
            return this._formatHours(this.project.logged_time);
        },

        remainingHours: function () {
            return this._formatHours(this.project.remaining_time);
        }
    },

    methods: {
        _formatHours: function (time) {
            var negative = time < 0 ? '-' : '';
            time = Math.abs(time);
            var hours    = Math.floor(time / 60);
            var minutes  = time - (hours * 60);

            if (minutes < 10) {
                minutes = '0' + minutes;
            }

            return negative + hours + ':' + minutes + ' heure' + (hours > 1 ? 's' : '');
        }
    }
};
