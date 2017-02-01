module.exports = {
    template: '#groups-users-template',
    components: {
        'user-list' : require('./user-list.js')
    },
    ready: function(){
        var self = this;
        this.$http.get('users').then(function(response){
            var usersGroups = {
                actifs: [],
                inactifs: []
            };
            $(response.data.data).each(function(i,user){
                if(user.active){
                    usersGroups.actifs.push(user);
                }else{
                    usersGroups.inactifs.push(user);
                }
            });
            self.usersGroups = usersGroups;
        })
    },
    data: function(){
        return {
            usersGroups : {},
            search: ''
        }
    }
};