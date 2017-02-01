var SweetAlert = require('sweetalert');

module.exports = function(selects, emptyLabel, createLabel, routePostMilestone, callbackAfterUpdate) {
    selects.append('<option value="-1">'+createLabel+'</option>');

    $('body').on('change', '[data-addmilestone]', function(e){
        if($(this).val() == -1){
            $(this).val('');
            var token = $(this).parents('form').find('[name=_token]').val();
            var thisSelect = $(this);
            SweetAlert({
                title: "Ajouter une étape",
                text: "Veuillez entrer le nom de l'étape à ajouter.",
                type: "input",
                showCancelButton: true,
                cancelButtonText: "Annuler",
                confirmButtonText : "Créer",
                closeOnConfirm: false,
                inputPlaceholder: "Nom de l'étape",
                showLoaderOnConfirm: true
            },
            function(milestone_name){
                if(milestone_name !== false){
                    if(milestone_name == ''){
                        SweetAlert.showInputError("Vous devez saisir un nom d'étape.");
                        return false
                    }
                    $.ajax({
                        url: routePostMilestone,
                        method: 'POST',
                        data: {
                            _token: token,
                            milestone_name: milestone_name
                        },
                        'beforeSend' : function(request){
                            request.setRequestHeader("X-Authorization", document.querySelector('#X-Authorization').getAttribute('value'));
                        }
                    }).success(function(responseData) {
                        SweetAlert.close();
                        var options = '<option value="">'+emptyLabel+'</option>';
                        $.each(responseData.updated_list, function(i, option){
                            options += '<option value="'+option.value+'">'+option.label+'</option>';
                        });
                        options += '<option value="-1">'+createLabel+'</option>';
                        $('body').find('[data-addmilestone]').each(function(i,select){
                            updateSelect(select, options)
                        });
                        thisSelect.val(responseData.id_created);
                        thisSelect.trigger('change');
                        if(typeof callbackAfterUpdate === "function"){
                            callbackAfterUpdate(options);
                        }
                    })
                    .fail(function(jqXHR) {
                        if(jqXHR.status == 422){
                            SweetAlert.showInputError(jqXHR.responseJSON.errors.milestone_name[0]);
                        }else{
                            SweetAlert({
                                'title': 'Erreur',
                                'text' : jqXHR.responseJSON.message,
                                'type' : 'error'
                            });
                        }
                    });
                }
            });
        }
    });
    function updateSelect(select, options){
        var $select = $(select);
        var val = $select.val();
        $select.empty();
        $select.append(options);
        $select.val(val);
    }
};
