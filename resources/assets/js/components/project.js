var Alert = require('../helpers/alert');

module.exports = {
    props: ['project', 'client', 'resources', 'milestones', 'editable'],

    data: function () {
        return {
            tasks:          [],
            tasksCount:     0,
            resourceFilter: '',
            statusFilter:   true,
            sortColumn:     null,
            sortOrder:      -1,
            tasksSelected:  [],
            currentTask:    null,
            newTasks:       [],
            action:         null,
            buzy:           false,
            rowsPerPage:    10,
            startRow:       0,
            oldData:        {},
            dirty:          {},
            columns:        ['name', 'revised_estimation', 'resource_type_id', 'require_comments', 'milestone_id']
        }
    },

    watch: {
        action: function (value) {
            if (value != 0) {
                this[value + 'Action']();
            }

            this.$nextTick(function () {
                this.action = 0;
            });
        }
    },

    components: {
        'project-summary': require('./project/project-summary.js'),
    },

    ready: function () {
        this.getTasks();
    },

    methods: {
        change: function (task) {
            this.$set('dirty[' + task.id + ']', this.isDirty(task, this.oldData[task.number]));
        },

        isDirty: function (task, old) {
            for (var i = 0; i < this.columns.length; i++) {
                if (task[this.columns[i]] != old[this.columns[i]] && !(old[this.columns[i]] == null && task[this.columns[i]] == '')) {
                    return true;
                }
            }

            return false;
        },

        getSelectedTasks: function () {
            var tasks = {};

            for (var i = 0; i < this.tasks.length; i++) {
                if (this.tasksSelected.indexOf(this.tasks[i].number.toString()) > -1) {
                    tasks[this.tasks[i].id] = this.tasks[i];
                }
            }

            return tasks;
        },

        openAction: function () {
            var tasks = this.getSelectedTasks();
            for (var i in tasks) {
                tasks[i].active = 1;
            }

            this.updateTasks(tasks);
        },

        closeAction: function () {
            var tasks = this.getSelectedTasks();
            for (var i in tasks) {
                tasks[i].active = 0;
            }

            this.updateTasks(tasks);
        },

        deleteAction: function () {
            var that = this;

            Alert.confirm('Supprimer', 'Êtes-vous sûr de vouloir supprimer les tâches sélectionnées?', function () {
                var tasks = this.getSelectedTasks();
                var ids   = [];
                for (var i in tasks) {
                    ids.push(i);
                }

                this.buzy = true;
                this.$http.delete(this.urlForTasks, {ids: ids})
                    .then(function () {
                        Alert.success('Les tâches sélectionnées ont bien été supprimées.');
                        this.buzy          = false;
                        this.tasksSelected = [];
                        this.getTasks();
                    }, function (response) {
                        Alert.error(response.data.message)
                        this.buzy = false;
                    });
            }.bind(that));
        },

        changeProjectAction: function () {
            var that = this;

            Alert.move(function (projectNumber) {
                this.buzy = true;

                this.$http.get('/api/projects/' + projectNumber)
                    .then(function (response) {
                        var id    = response.data.data.id;
                        var tasks = this.getSelectedTasks();

                        for (var i in tasks) {
                            tasks[i].old_project_id = tasks[i].project_id;
                            tasks[i].project_id     = id;
                        }

                        this.$http.put(this.urlForTasks, {tasks: tasks})
                            .then(function () {
                                Alert.success();
                                this.buzy          = false;
                                this.tasksSelected = [];
                                this.getTasks();
                            }, function (response) {
                                Alert.error(response.data.message);
                                this.buzy = false;
                                for (var i in tasks) {
                                    tasks[i].project_id = tasks[i].old_project_id;
                                    delete tasks[i].old_project_id;
                                }
                            });
                    }, function (response) {
                        Alert.error(response.data.message);
                        this.buzy = false;
                    });
            }.bind(that));
        },

        changeResourceAction: function () {
            var that = this;

            Alert.resource(this.resources, function (resource) {
                var tasks = this.getSelectedTasks();

                for (var i in tasks) {
                    tasks[i].resource_type_id = resource;
                }

                this.$http.put(this.urlForTasks, {tasks: tasks})
                    .then(function () {
                        Alert.success();
                        this.buzy = false;
                        this.getTasks();
                    }, function (response) {
                        Alert.error(response.data.message);
                        this.buzy = false;
                    });
            }.bind(that));
        },

        updateTasks: function (tasks) {
            this.buzy = true;
            this.$http.put(this.urlForTasks, {tasks: tasks})
                .then(function () {
                    Alert.success();
                    this.buzy     = false;
                    this.newTasks = [];
                    this.resetDirty();
                    this.getTasks();
                }, function (response) {
                    Alert.error(response.data.message);
                    this.buzy = false;
                    this.manageErrors(response.data.errors);
                });
        },

        sort: function (column) {
            if (column == this.sortColumn) {
                this.sortOrder *= -1;
            }

            this.sortColumn = column;
        },

        save: function () {
            var tasks = {};
            var that  = this;

            $('#project-tasks-editable :checkbox').each(function () {
                for (var i = 0; i < that.tasks.length; i++) {
                    if (that.tasks[i].number == $(this).val()) {
                        tasks[that.tasks[i].id] = that.tasks[i];
                    }
                }
            });

            for (var i = 0; i < this.newTasks.length; i++) {
                tasks['new_' + i] = this.newTasks[i];
            }

            this.updateTasks(tasks);
        },

        getTasks: function () {
            this.$http.get(this.urlForTasks)
                .then(function (response) {
                    this.tasks = response.data.data;
                    this.setPage(this.page || 0);
                    this.keepOldValues();
                });
        },

        keepOldValues: function () {
            for (var i = 0; i < this.tasks.length; i++) {
                this.oldData[this.tasks[i].number] = JSON.parse(JSON.stringify(this.tasks[i]));
                this.$set('dirty[' + this.tasks[i].id + ']', false);
            }
        },

        toggleSelected: function (task) {
            if (task) {
                var index = this.tasksSelected.indexOf(task.number);
                if (index > -1) {
                    this.tasksSelected.splice(index, 1);
                } else {
                    this.tasksSelected.push(task.number);
                }

                return;
            }

            if (this.tasksSelected.length == 0) {
                this.tasksSelected = this.tasks;
            } else {
                this.tasksSelected = [];
            }
        },

        createTask: function () {
            this.newTasks.push({resource_type_id: 7, errors: {}}); // Default resource to "Programmeur"

            Inputmask('9{1,4}:99', {
                numericInput:       true,
                placeholder:        '0',
                positionCaretOnTab: false
            }).mask($('.newTask:last-child .estimation input').focus(function () {
                $(this).select();
            }));
        },

        manageMilestone: function (task) {
            if (task.milestone_id != -1) {
                this.change(task);
                return;
            }

            this.currentTask = task;

            Alert.addMilestone(this.createMilestone);
        },

        createMilestone: function (name) {
            if (name == '') {
                Alert.showInputError('Vous devez saisir un nom d\'étape.');
                return false;
            }

            this.$http.post(this.urlForMilestone, {milestone_name: name})
                .then(function (response) {
                    Alert.close();

                    this.milestones               = response.data.milestones;
                    this.currentTask.milestone_id = response.data.id;
                    this.change(this.currentTask);
                }, function (response) {
                    if (response.status == 422) {
                        Alert.showInputError(response.data.errors.milestone_name[0]);
                    } else {
                        Alert.error(response.data.message);
                    }
                });
        },

        deleteNewTask: function (task) {
            this.newTasks.$remove(task);
        },

        manageErrors: function (errors) {
            for (var i = 0; i < this.newTasks.length; i++) {
                this.newTasks[i].errors = {};
            }

            for (var i in errors) {
                var id = parseInt(i.substr(4));

                this.newTasks[id]['errors'] = errors[i];
            }
        },

        currentPage: function (page) {
            return page * this.rowsPerPage == this.startRow;
        },

        setPage: function (page) {
            if (this.hasDirty()) {
                var that = this;
                Alert.confirm(
                    'Le formulaire a été modifié',
                    'Si vous procédez, vos modifications vont être perdues. Souhaitez-vous continuer?',
                    function (isConfirm) {
                        return this.cleanDirtyAndSetPage(isConfirm, page);
                    }.bind(that)
                );
                return;
            }

            this.startRow = Math.max(0, Math.min(this.totalPages - 1, page)) * this.rowsPerPage;

            this.$nextTick(function () {
                $('[time]').inputmask('9{1,4}:99', {
                    numericInput:         true,
                    placeholder:          '0',
                    clearMaskOnLostFocus: false
                }).focus(function () {
                    $(this).select();
                });
            });
        },

        hasDirty: function () {
            for (var i in this.dirty) {
                if (this.dirty[i]) {
                    return true;
                }
            }

            return false;
        },

        cleanDirtyAndSetPage: function (isConfirm, page) {
            if (!isConfirm) {
                return;
            }

            for (var i in this.dirty) {
                if (this.dirty[i]) {
                    this.replaceTask(i);
                }
            }

            this.setPage(page);
        },

        replaceTask: function (id) {
            for (var i = 0; i < this.tasks.length; i++) {
                if (this.tasks[i].id == id) {
                    var data = this.oldData[this.tasks[i].number];
                    for (var n in data) {
                        this.tasks[i][n] = data[n];
                    }

                    this.dirty[id] = false;
                    break;
                }
            }
        },

        resetDirty: function () {
            for (var i in this.dirty) {
                this.dirty[i] = false;
            }
        }
    },

    computed: {
        urlForMilestone: function () {
            return '/api/projects/' + this.project.number + '/milestones';
        },

        urlForTasks: function () {
            return '/api/projects/' + this.project.number + '/tasks';
        },

        order: function () {
            return this.sortOrder == -1 ? 'desc' : 'asc';
        },

        numberClasses: function () {
            return this.sortColumn == 'number' ? 'sorting_' + this.order : '';
        },

        nameClasses: function () {
            return this.sortColumn == 'name' ? 'sorting_' + this.order : '';
        },

        estimationClasses: function () {
            return this.sortColumn == 'estimation' ? 'sorting_' + this.order : '';
        },

        resourceTypeClasses: function () {
            return this.sortColumn == 'resource_type' ? 'sorting_' + this.order : '';
        },

        requireCommentsClasses: function () {
            return this.sortColumn == 'require_comments' ? 'sorting_' + this.order : '';
        },

        milestoneClasses: function () {
            return this.sortColumn == 'milestone' ? 'sorting_' + this.order : '';
        },

        allSelected: {
            get: function () {
                return this.tasks.length > 0 && this.tasks.length == this.tasksSelected.length;
            },
            set: function (value) {
                if (value) {
                    for (var i in this.tasks) {
                        this.tasksSelected.push(this.tasks[i].number.toString());
                    }
                } else {
                    this.tasksSelected = [];
                }
            }
        },

        newTasksCount: function () {
            return this.newTasks.length;
        },

        areDeletable: function () {
            var tasks = this.getSelectedTasks();

            for (var i in tasks) {
                if (tasks[i].logged_time > 0) {
                    return false;
                }
            }

            return true;
        },

        isFirstPage: function () {
            return this.startRow == 0;
        },

        isLastPage: function () {
            return this.page == this.totalPages - 1;
        },

        page: function () {
            return Math.round(this.totalPages * (this.startRow / this.tasksCount));
        },

        showBeforeLastPage: function () {
            if (this.totalPages < 7) {
                return -1;
            }

            return this.totalPages - 5;
        },

        totalPages: function () {
            return Math.ceil(this.tasksCount / this.rowsPerPage);
        },

        pagesToShow: function () {
            var pages   = [];
            var startPage, endPage;
            var minPage = 2;
            var maxPage = this.totalPages - 3;

            if (this.page < minPage + 2) {
                startPage = minPage;
                endPage   = Math.min(maxPage, startPage + 2);
            } else if (this.page > maxPage - 2) {
                endPage   = maxPage;
                startPage = endPage - 2;
            } else {
                startPage = this.page - 1;
                endPage   = this.page + 1;
            }

            startPage = Math.max(2, startPage);

            for (var i = startPage; i <= endPage; i++) {
                pages.push(i);
            }

            return pages;
        }
    }
};
