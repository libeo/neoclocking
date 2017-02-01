require('datatables.net')(window, $);
require('jquery.inputmask');
var SelectCreateMilestone = require('./modules/selectCreateMilestone.js');

var SweetAlert = require('sweetalert');

var AuthorizationKey;

var ProjectTasks = function() {
    var $table, $emptyRow;
    var newForms = 0;
    var commentRequired = false;
    var that = this;

    var projectTasksTableBaseConfig = {
        dom: 't<"pagination-wrapper"p>',
        language: {
            paginate: {
                previous: "<",
                next: ">"
            },
            aria: {
                'sortAscending': ': activer pour trier la colonne par ordre croissant',
                'sortDescending': ': activer pour trier la colonne par ordre décroissant'
            },
            loadingRecords: 'Chargement en cours...',
            zeroRecords: 'Aucun élément à afficher',
            emptyTable: 'Aucune donnée disponible dans le tableau',
            processing: 'Traitement en cours...'
        },
        processing: true,
        serverSide: true
    };

    var initFilters = function() {
        $('[data-tasksfilters=status] button').on('click', function(e) {
            var $btn = $(this);
            if (! $btn.hasClass('active')) {
                $('[data-tasksfilters=status] button').removeClass('active');
                $btn.addClass('active');
                var searchValue = $btn.data('value');
                $table.DataTable().column($btn.data('column') + ':name')
                    .search(searchValue)
                    .draw();
            }
        });
        $('[data-tasksfilters=resourcestype] ').on('change', function() {
            $table.DataTable().column('resource_type_id:name')
                .search($(this).val() || '')
                .draw();

        });
    };

    this.initTable = function(route) {
        AuthorizationKey = document.querySelector('#X-Authorization').getAttribute('value');
        $table = $('#project-tasks');
        var tableConfig = projectTasksTableBaseConfig;
        tableConfig.order = [[0,'desc']];
        tableConfig.ajax = {
            'url': route,
            'beforeSend' : function(request){
                request.setRequestHeader("X-Authorization", AuthorizationKey);
            }
        };
        tableConfig.columns = configureColumns({
            number: {
                render: renderNumberCol
            },
            name: null,
            estimation: {
                render: renderEstimationCol
            },
            resource_type: null,
            require_comments: {
                render: function (require_comments, type) {
                    if (type == 'display') {
                        return require_comments ? 'Requis' : 'Optionnels';
                    }
                    return require_comments;
                }
            },
            milestone: {
                defaultContent: '- Aucune -'
            },
            is_active: {
                visible: false
            },
            resource_type_id: {
                visible: false
            }
        });
        $table.DataTable(tableConfig).draw().on('draw.dt', function() {
            Inputmask('9{1,2}:99', {
                numericInput: true,
                placeholder: '0',
                positionCaretOnTab: false
            }).mask($('.estimation input', this).focus(function() {
                $(this).select();
            }));
        });
        initFilters();
    };
    var isDirty = function() {
        return $table.find('.dirty').length > 0;
    };
    var dirtyConfirmation = function(e) {
        if (isDirty()) {
            var shouldContinue = window.confirm(
                "Le formulaire a été modifié. Si vous procédez, vos modifications vont être perdues. Souhaitez-vous continuer?"
            );
            if (! shouldContinue) {
                e.stopImmediatePropagation();
                e.preventDefault();
                return false;
            }
        }
    };
    this.initEditableTable = function(routeTable, routePostMilestone) {
        return;
        AuthorizationKey = document.querySelector('#X-Authorization').getAttribute('value');
        $table = $('#project-tasks-editable');
        $emptyRow = $table.find('tr.empty-row').detach();
        $table.find('thead th').on('click.DT', dirtyConfirmation);
        SelectCreateMilestone($emptyRow ,'- Aucune -', '+ Ajouter une étape', routePostMilestone, function(options){
            $emptyRow.find('[data-addmilestone]').empty().append(options).children().last().remove();
        });
        $('.modal .close, .modal .cancel').on('click', function (e) {
            e.preventDefault();
            $(this).closest('.modal').hide();
        });
        var tableConfig = projectTasksTableBaseConfig;
        tableConfig.order = [[1,'desc']];
        tableConfig.ajax = {
            'url': routeTable,
            'beforeSend' : function(request){
                request.setRequestHeader("X-Authorization", AuthorizationKey);
            }
        };
        var columns = configureColumns({
            number: {
                render: renderNumberCol
            },
            name: {
                render: function (name, type, task) {
                    if (type == 'display') {
                        var $textarea = getNewInput('td.name textarea', task.id);
                        $textarea.html(name);
                        return $textarea[0].outerHTML;
                    }
                    return name;
                }
            },
            estimation: {
                render: renderEditableEstimationCol
            },
            resource_type: {
                render: function (resource_type, type, task) {
                    if (type == 'display') {
                        var $selectList = getNewInput('td.resource_type select', task.id);
                        $selectList.find('option[value="'+task.resource_type_id+'"]').attr('selected','selected');
                        return $selectList[0].outerHTML;
                    }
                    return resource_type;
                }
            },
            require_comments: {
                render: function (require_comments, type, task) {
                    if (type == 'display') {
                        var $toggles = $emptyRow.find('td.require_comments label').clone();
                        var $input = $toggles.find('input');
                        $input.attr('name', 'tasks[' + task.id + ']'+$input.attr('name'));
                        if (require_comments === true) {
                            $input.attr('checked', 'checked');
                        }
                        return $toggles[0].outerHTML;
                    }
                    return require_comments;
                }
            },
            milestone: {
                render: function (milestone, type, task) {
                    if (type == 'display') {
                        var $selectList = getNewInput('td.milestone select', task.id);
                        $selectList.find('option[value="'+task.milestone_id+'"]').attr('selected','selected');
                        $selectList.append('<option value="-1">+ Ajouter une étape</options>');
                        return $selectList[0].outerHTML;
                    }
                    return milestone;
                }
            },
            is_active: {
                visible: false
            },
            resource_type_id: {
                visible: false
            }
        });
        tableConfig.drawCallback = function () {
            $table.find('input, select, textarea').each(function(i, el) {
                var $el =  $(el);
                $el.data('initialValue',  $el.val());
                if($el.is('[type=checkbox]') && !$el.is(':checked')){
                    $el.data('initialValue',  0);
                }
            });

            $table.find('tbody .selected-toggle input').on('change', actionOnCheck);
            actionOnCheck();
        };
        tableConfig.initComplete = function () {
            $(document).on('click.DT', '#project-tasks-editable_paginate a.paginate_button', dirtyConfirmation);
            $table.find('tbody').on('change blur', 'input, select, textarea', function() {
                var $el = $(this);
                if($el.is('[data-addmilestone]') && $el.val() == -1){
                    return;
                }
                var $parentRow = $el.closest('tr');
                var value = $el.val();
                if($el.is('[type=checkbox]') && !$el.is(':checked')){
                    value = 0;
                }
                if ($el.data('initialValue') != value) {
                    $el.addClass('dirty');
                    $parentRow.addClass('dirty');
                } else {
                    $el.removeClass('dirty');
                    if ($parentRow.find('.dirty').length === 0) {
                        $parentRow.removeClass('dirty');
                    }
                }
            });
        };
        tableConfig.columns = columns.slice(0);
        tableConfig.columns.unshift({
            className:      'selected-toggle',
            orderable:      false,
            data:           null,
            defaultContent: '<input type="checkbox" />'
        });
        $table.DataTable(tableConfig).draw().on('draw.dt', function() {
            Inputmask('9{1,2}:99', {
                numericInput: true,
                placeholder: '0'
            }).mask($('.estimation input', this).focus(function() {
                $(this).select();
            }));
        });
        initFilters();
        $table.find('th.select-toggle input').on('click', function() {
            $table.find('td.selected-toggle input').prop('checked', $(this).prop('checked'));
            actionOnCheck();
        });

        $('[data-addtask]').on('click', addNewTaskForm);
        $('form').on('submit', saveVisibleTasks);
        $('[data-bulkactions] option').attr('disabled', true);
        $('[data-bulkactions]').on('change', executeBulkActions);
    };

    var actionOnCheck = function(){
        var inputChecked = $table.find('tbody .selected-toggle input:checked');
        $('[data-bulkactions] option').attr('disabled', inputChecked.length == 0);

        var table = $table.DataTable();
        $.each(inputChecked, function(i,el){
            var tr = $(el).closest('tr');
            var data = table.row(tr).data();
            if(data.logged_time > 0){
                 $('[data-bulkactions] option[value=delete]').attr('disabled', true);
            }
        });

    }

    var getNewInput = function(selector, id) {
        var input = $emptyRow.find(selector).clone();
        input.attr('name', 'tasks[' + id + ']' + input.prop('name'));
        return input;
    };
    var configureColumns = function(columns) {
        var columnConfig = [];
        for (var colName in columns) {
            var colData = columns[colName] || {};
            colData['name'] = colName;
            colData['className'] = colName;
            colData['data'] = colName;
            columnConfig.push(colData);
        }
        return columnConfig;
    };
    var addNewTaskForm = function (e) {
        e.preventDefault();
        newForms ++;
        var newIdName = 'new_' + newForms;
        var newRow = $table.DataTable().row.add({
            DT_RowId:   newIdName,
            id:   newIdName,
            number: null,
            estimation: 0,
            revised_estimation: 0,
            resource_type: null,
            require_comments: that.commentRequired,
            milestone: null,
            name: null,
            active: true,
            is_active: 'true',
            resource_type_id: null
        }).node();
        $(newRow).find('td.selected-toggle input').remove();
        $table.find('thead').first().append($(newRow).detach().addClass('is-new'));
        var deleteBtn = $('<button class="btn_delete_task  button is-gradient-purple"><i class="fa fa-trash"></i></button>');
        deleteBtn.on('click', function(e){
            e.preventDefault();
            $(this).parents('tr').remove();
        });
        $(newRow).find('td').last().append(deleteBtn);
        Inputmask('9{1,2}:99', {
            numericInput: true,
            placeholder: '0'
        }).mask($('.estimation input', newRow).focus(function() {
            $(this).select();
        }));
    };
    var saveVisibleTasks = function(e) {
        e.preventDefault();
        var $form = $('form#project-tasks-form');
        $form.find('.save-button').val('Sauvegarde...');
        $form.find('.button').prop('disabled', true);
        updateTasks($form.serialize());
    };
    var manageSuccessfulSave = function(message) {
        if (message == undefined) {
            message = "Les données ont été enregistrées correctement";
        }
        showPopup('Succès', 'Les données ont été enregistrées correctement', 'success');
        $table.find('thead tr[id^="new_"]').remove();
        $table.DataTable().ajax.reload();
    };
    var manageUnsuccessfulSave = function(errors, message) {
        if (message == undefined) {
            message = "Non enregistrées";
        }
        showPopup('Erreur', message, 'error');
        for (var id in errors) {
            var $row = $table.find('tr#' + id);
            var rowErrors = errors[id];
            for (var fieldName in rowErrors) {
                var $field = $row.find('[name="tasks[' + id + '][' + fieldName + ']"]');
                if ($field.length) {
                    $field.after(
                        $('<div />').addClass('error').html(rowErrors[fieldName][0])
                    );
                } else {
                    $row.append($('<div />').addClass('error').html(rowErrors[fieldName][0]))
                }
            }
        }
    };
    var executeBulkActions = function () {
        var action = $(this).val();
        $(this).val(-1);
        var $selected = $table.find('td.selected-toggle input:checked');
        if ($selected.length === 0) {
            showPopup(
                'Action Groupée',
                "Il faut sélectionner au moins une tâche pour faire une action groupée",
                'error'
            );
            return;
        }

        if (action == 'change-project') {
            SweetAlert({
                title: "Changement de projet",
                text: "Veuillez entrer le nouveau numéro de projet pour ces tâches.",
                type: "input",
                showCancelButton: true,
                cancelButtonText: "Annuler",
                closeOnConfirm: false,
                inputPlaceholder: "P-0000-0",
                showLoaderOnConfirm: true,
            }, function(projectNumber) {
                if(projectNumber !== false){
                    if(projectNumber == ''){
                        SweetAlert.showInputError("Vous devez saisir un numéro de projet.");
                        return false
                    }
                    $.ajax({
                        url: '/api/projects/'+projectNumber,
                        beforeSend : function(request){
                            request.setRequestHeader("X-Authorization", AuthorizationKey);
                        }
                    }).success(function(responseData) {
                        var data = getDataForSelectedRows($selected);
                        data = updatePropertyForTasks(data, 'project_id', responseData.data.id);
                        updateTasks($.param({tasks: data, _method: 'PUT', _token: $('input[name="_token"]').val()}));
                    })
                    .fail(function(jqXHR) {
                        var message = jqXHR.responseJSON.message;
                        showPopup('Erreur', message, 'error');
                    });
                }
            });
        }

        if (action == 'open' || action == 'close') {
            var data = getDataForSelectedRows($selected);
            data = updatePropertyForTasks(data, 'active', action == 'open' ? 1 : 0);
            updateTasks($.param({tasks: data, _method: 'PUT', _token: $('input[name="_token"]').val()}));
        }

        if (action == 'delete'){
            var data = getDataForSelectedRows($selected);
            var ids = [];
            for (var id in data) {
                ids.push(id);
            }
            deleteTasks(ids)
        }

        if (action == 'change-resource') {
            var $resources = $('[data-tasksfilters=resourcestype]').clone();
            $resources.prop('id', 'resource-choice');
            SweetAlert({
                title: "Choisir le nouveau type de ressource pour le(s) tâche(s) séléctionnée(s)",
                text: $resources[0].outerHTML,
                type: "info",
                html: true,
                showCancelButton: true,
                cancelButtonText: "Annuler",
                closeOnConfirm: false,
                customClass: 'resource-choice-modal'
            }, function () {
                var resourceId = $('.sweet-alert.resource-choice-modal').find('select#resource-choice').val();
                if (resourceId) {
                    SweetAlert.close();
                    var data = getDataForSelectedRows($selected);
                    data = updatePropertyForTasks(data, 'resource_type_id', resourceId);
                    updateTasks($.param({tasks: data, _method: 'PUT', _token: $('input[name="_token"]').val()}));
                }
            });
        }
    };
    var updatePropertyForTasks = function(data, propertyName, value) {
        for (var id in data) {
            data[id][propertyName] = value;
        }
        return data;
    };
    var updateTasks = function(serializedData) {
        var $form = $('form#project-tasks-form');
        $.ajax({
            type: $form.attr('method'),
            url: $form.attr('action'),
            data: serializedData,
            beforeSend : function(request){
                request.setRequestHeader("X-Authorization", AuthorizationKey);
            }
        }).done(function(responseData) {
            $table.find('.error').remove();
            manageSuccessfulSave();
            $form.find('.save-button').val('Sauvegarde');
            $form.find('.button').prop('disabled', false);
        }).fail(function(jqXHR, state, error){
            manageUnsuccessfulSave(jqXHR.responseJSON.errors, jqXHR.responseJSON.message);
            $form.find('.save-button').val('Sauvegarde');
            $form.find('.button').prop('disabled', false);
        });
    };
    var deleteTasks = function(ids){
        $.ajax({
            type: 'DELETE',
            url: $('input[name="_token"]').closest('form').data('deleteurl'),
            data: {
                _method: 'DELETE',
                ids: ids,
                _token: $('input[name="_token"]').val()
            },
            beforeSend : function(request){
                request.setRequestHeader("X-Authorization", AuthorizationKey);
            }
        }).success(function(responseData) {
            $table.find('.error').remove();
            manageSuccessfulSave('Les tâches sélectionnées ont bien été supprimées.');
        }).fail(function(jqXHR, state, error){
            showPopup('Erreur', jqXHR.responseJSON.message, 'error');
        });
    };
    var getDataForSelectedRows = function ($selectedRows) {
        var table = $table.DataTable();
        var data = {};
        $selectedRows.each(function (i, el) {
            var $row = $(el).closest('tr');
            var allData = table.row($row).data();
            data[$row.attr('id')] = {
                name: allData.name,
                id: allData.id,
                resource_type_id: allData.resource_type_id,
                require_comments: allData.require_comments ? 1 :0,
                active: allData.active ? 1 :0,
                milestone_id: allData.milestone_id,
                estimation: allData.estimation,
                revised_estimation: allData.revised_estimation
            }
        });
        return data;
    };
    var renderNumberCol = function (number, type) {
        if (type == 'display') {
            return number ? '<a href="/tasks/'+number+'">#' + number +'</a>' : '--';
        }
        return number;
    };
    var minutesToHours = function (totalMinutes) {
        var hours = parseInt(totalMinutes / 60);
        var minutes = String(Math.abs(totalMinutes) % 60);
        return hours + ':' + ('0' + minutes.substring(0, 2)).slice(-2);
    };
    var getLoggedTimeDisplay = function (loggedTime, estimation) {
        var displayLoggedTime = minutesToHours(loggedTime);
        if (estimation > 0 && loggedTime > estimation) {
            displayLoggedTime = '<span class="exceeded">' + displayLoggedTime + '</span>';
        }
        return displayLoggedTime;
    };
    var renderEstimationCol = function (originalEstimation, type, task) {
        var estimation = task.revised_estimation ? task.revised_estimation : originalEstimation;
        if (type == 'display') {
            return getLoggedTimeDisplay(task.logged_time, estimation) + ' / ' + minutesToHours(estimation);
        }
        return estimation;
    };
    var renderEditableEstimationCol = function (originalEstimation, type, task) {
        var estimation = task.revised_estimation ? task.revised_estimation: originalEstimation;
        if (type == 'display') {
            var estimationInput = '<input name="tasks['+ task.id +'][revised_estimation]" value="' + minutesToHours(estimation) + '" type="text"/>';
            if (task.logged_time == undefined) {
                return '0:00 / '+estimationInput;
            }
            return getLoggedTimeDisplay(task.logged_time, estimation) + ' / ' + estimationInput;
        }
        return estimation;
    };
    var showPopup = function(title, message, type) {
        SweetAlert({
            title: title,
            text: message,
            type: type,
            allowOutsideClick: true
        });
    }
};

module.exports = ProjectTasks;
