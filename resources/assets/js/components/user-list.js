module.exports = {
    template: '#user-list-template',
    props: ['title', 'users', 'filter'],
    computed: {
        hasUsers: function(){
            return this.users.length > 0;
        }
    }
};