window.$ = window.jQuery = require('jquery');

window.moment = require('moment');
require('moment/locale/fr');
require('moment-duration-format');
require('moment-range');
require('moment-timezone');

window.timezone = 'America/Montreal';


var Vue = require('vue');
//Vue.config.debug = true;
Vue.use(require('vue-resource'));

Vue.filter('recordLength', function (result, key) {
    this.$set(key, result.length);

    return result;
});

Vue.filter('timeDisplay', {
    read: function (value, defaults) {
        var minutes = value || defaults || 0;
        var hours = Math.floor(minutes / 60);
        minutes -= hours * 60;

        if (minutes < 10) {
            return hours + ':0' + minutes;
        }

        return hours + ':' + minutes;
    },

    write: function (value) {
        var parts = value.split(':');
        var minutes = parseInt(parts[0]) * 60;
        if (parts.length > 1) {
            minutes += parseInt(parts[1]);
        }

        return minutes;
    }
});

// Config vue js
Vue.filter('dateToHours', function (value) {
    return moment.utc(value).tz(window.timezone).format('HH:mm');
});
Vue.filter('minutesToHours', function (value) {
    return moment.duration(value, 'minutes').format('h:mm', { trim: false });
});

Vue.http.options.root = '/api';
var xAuthTag = document.querySelector('#X-Authorization');
Vue.http.headers.common['X-Authorization'] = xAuthTag ? xAuthTag.getAttribute('value') : null;

Vue.http.interceptors.push({
    response: function (response) {
        if (response.status == 401) { //Unauthorized
            window.location.replace("/");
        }
        return response;
    }
});

// Project
var CLOCKING = require('./project.js');
var ProjectTasks = require('./project-tasks.js');


CLOCKING.Components.projectTask = new ProjectTasks();
CLOCKING.Components.SelectCreateMilestone = require('./modules/selectCreateMilestone.js');

CLOCKING.Vues = {
    configs: {
        dashboard: require('./dashboard.js'),
        generic: require('./generic-page.js')
    },
    initVue: function (configName) {
        if (! configName) {
            configName = 'generic';
        }
        return new Vue(this.configs[configName]);
    }
};
var SweetAlert = require('sweetalert');


window.onbeforeunload = function(event) {
    if(CLOCKING.editing > 0 || CLOCKING.adding > 0){
        var message = 'Vous êtes en train d\'ajouter ou modifier du temps.';
        var e = event || window.event;

        // For IE and Firefox
        if (e) {
            e.returnValue = message;
        }

        // For Safari
        return message;
    }
};

$('[data-deletetask]').on('click', function(e) {
    e.preventDefault();
    var self =  this;
    SweetAlert({
        title: 'Attention!',
        text: 'Souhaitez-vous réellement supprimer cette tâche?',
        type: 'warning',
        showCancelButton: true,
        cancelButtonText: 'Non',
        confirmButtonText: 'Oui',
        showLoaderOnConfirm: true
    }, function() {
        var url = $(self).data('deletetask');
        $.ajax(url, {
            method : 'DELETE',
            data: {
                _token : $(self).closest('form').find('[name=_token]').val()
            },
            'beforeSend' : function(request) {
                request.setRequestHeader("X-Authorization", xAuthTag.getAttribute('value'));
            }
        }).success(function() {
            window.location.href = "/dashboard";
        }).error(function(response){
            SweetAlert({
                title: 'Erreur',
                text: response.message,
                type: 'error'
            }, function(){
                location.reload();
            })
        })
    });
});

// *************** JS STATIC

var menuOffCanvas = $("[data-menu]");
function sizeMenu() {
    var wrapperWidth =  $('.l-page-wrapper').innerWidth();
    var baseWidth = wrapperWidth * 0.3574144;
    var diff = ($(window).innerWidth() - wrapperWidth) /2;

    var width = baseWidth + diff + 5;
    menuOffCanvas.css('max-width', width);
}
sizeMenu();
$(window).on('resize', sizeMenu);

$('[data-menuopener]').on('click', function() {
    menuOffCanvas.toggleClass('is-open');
});
$('[data-menucloser]').on('click', function() {
    menuOffCanvas.removeClass('is-open');
});
