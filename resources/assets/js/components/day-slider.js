module.exports = {
    template: '#day-slider-template',

    props: ['date', 'setDate', 'workedTimeToday'],

    ready: function() {
        this.momentDate = moment(this.date);
        this.formatFullDate();
        $(this.$el).find('.clock-history-title').pickadate({
            format: 'dddd DD MMMM YYYY',
            onSet: this.goToDate,
            clear: false,
            firstDay: 0
        });
    },

    data: function() {
        return {
            currentDate: '',
            momentDate: ''
        }
    },

    methods: {
        goToDate: function(context) {
            if(context.select){
                var chosenDate = moment(context.select);
                if (chosenDate.isValid()) {
                    this.momentDate = chosenDate;
                    this.setDate(chosenDate);
                }
                this.formatFullDate();
            }
        },
        addDay: function(event){
            event.preventDefault();
            this.setDate(this.momentDate.add(1, 'd'));
            this.formatFullDate();
        },

        subtractDay: function(event){
            event.preventDefault();
            this.setDate(this.momentDate.subtract(1, 'd'));
            this.formatFullDate();
        },

        fullDayDate: function(date){
            return date.format();
        },

        formatFullDate: function(){
            this.currentDate = this.momentDate.format('dddd DD MMMM YYYY');
        }
    }
};
