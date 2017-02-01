var Cookie = require('js-cookie');

module.exports = {
    template: '#last-days-template',
    ready: function(){
        var isClose = Cookie.get('lastdayofmonth');
        var isDayToShow = moment().endOf('month').date() == moment().date() || moment().date() == 1;
        var isEndOfMonthWhileWeekend = [6,7].indexOf(moment().endOf('month').day()) > -1 &&
            [5,6,7].indexOf(moment().day()) > -1 &&
            moment().endOf('month').date() - moment().date() < 3;

        if((isDayToShow || isEndOfMonthWhileWeekend) && isClose != "closed"){
            this.show = true;
        }
    },
    data: function(){
        return {
            show : false
        }
    },
    methods: {
        close: function(){
            Cookie.set('lastdayofmonth', "closed")
            this.show = false;
        }
    }
};
