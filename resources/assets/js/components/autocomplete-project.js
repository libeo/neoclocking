require('jquery-autocomplete');

module.exports = {
    template: '#autocomplete-project-tmpl',
    props: ['projectId','projectName'],
    ready:function(){
        var self = this;
        var input = $(this.$el.parentNode).find('#project-search-field');
        input.autocomplete({
            source: function(request, response){
                self.$http.get('projects', {term: request.term})
                    .then(function (dataResponse) {
                          var formatedData = [];
                          $.each(dataResponse.data['data'], function(i,data){
                              var label = data.number+ ' - ' + data.name;
                              formatedData.push( {
                                  label : label + ' (client: '+data.client.data.name+')',
                                  value : label,
                                  data : data
                              });
                          });
                          response( formatedData );
                    });
            },
            minLength: 2,
            select: function(event, ui){
                self.projectId = ui.item.data.id;
            },
            search: function(){
                self.projectId='';
            }
        })
    },
    watch: {
        'projectName' : function(){
        }
    }
};
