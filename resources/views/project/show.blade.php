@extends('layouts.one-column')

@section('page-title', "{$project->number} - {$project->name}")

@section('header-search-bar')
@endsection

@section('custom_style')
@show

@section('body-classes') in-project @endsection

@section('content')
    <project :project="{{ $project }}"
             :client="{{ $project->client }}"
             :resources="{{ json_encode($resourceTypes) }}"
             :milestones="{{ json_encode($milestones) }}"
             :editable="{{ $user->can('manage', $project) ? 'true' : 'false' }}"
             inline-template>
        <div class="project-info-wrapper">
            @include('project.partials.project-summary')
        </div>

        <div class="project-info-wrapper">
            <form v-on:submit.prevent="save">
                <div class="project-info-actions">
                    @include('project.partials.filters')

                    @can('manage', $project)
                        @include('project.partials.actions')
                    @endcan
                </div>

                <div class="dataTables_wrapper no-footer">
                    <table id="project-tasks-editable" v-cloak>
                        <thead>
                            <tr>
                                <th class="select-toggle" v-if="editable"><input type="checkbox" v-model="allSelected"></th>
                                <th :class="numberClasses" @click="sort('number')" class="sorting number">Numéro</th>
                                <th :class="nameClasses" @click="sort('name')" class="sorting name">Nom de la tâche</th>
                                <th :class="estimationClasses" @click="sort('estimation')" class="sorting estimation">
                                Estimation</th>
                                <th :class="resourceTypeClasses" @click="sort('resource_type')" class="sorting resource_type">
                                Ressource</th>
                                <th :class="requireCommentsClasses" @click="sort('require_comments')" class="
                                sorting require_comments">Commentaires</th>
                                <th :class="milestoneClasses" @click="sort('milestone')" class="sorting milestone">Étape</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="task in newTasks" class="newTask is-new">
                                <td></td>
                                <td class="number">--</td>
                                <td class="name">
                                    <textarea v-model="task.name" rows="2"></textarea>
                                    <div class="error" v-for="error in task.errors.name">@{{ error }}</div>
                                </td>
                                <td class="estimation">
                                    0:00 / <input type="text" v-model="task.revised_estimation" time value="@{{ 0 | timeDisplay }}"></td>
                                <td class="resource_type">
                                    <select v-model="task.resource_type_id">
                                        <option v-for="(index, resource) in resources"
                                                :selected="task.resource_type_id == index"
                                                value="@{{ index }}">@{{ resource }}</option>
                                    </select>
                                    <div class="error" v-for="error in task.errors.resource_type_id">@{{ error }}</div>
                                </td>
                                <td class="require_comments">
                                    <label>
                                        <input type="checkbox"
                                               v-model="task.require_comments"
                                               value="1"
                                               :checked="project.require_comments">
                                        <span>Requis</span>
                                    </label>
                                </td>
                                <td class="milestone">
                                    <select v-model="task.milestone_id" @change="manageMilestone(task)">
                                        <option value="" selected="selected">- Aucune -</option>
                                        <option v-for="(index, milestone) in milestones"
                                                value="@{{ index }}">@{{ milestone }}</option>
                                        <option value="-1">+ Ajouter une étape</option>
                                    </select>
                                    <button class="btn_delete_task button is-gradient-purple" @click="deleteNewTask(task)">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr v-for="task in tasks | filterBy resourceFilter in 'resource_type_id' | filterBy statusFilter in 'active' | orderBy sortColumn sortOrder | recordLength 'tasksCount' | limitBy rowsPerPage startRow"
                                :class="{'dirty': dirty[task.id], 'closed': !task.active}">
                                <td v-if="editable">
                                    <input type="checkbox" v-model="tasksSelected" value="@{{ task.number }}">
                                </td>
                                <td class="number"><a href="/tasks/@{{ task.number }}">#@{{ task.number }}</a></td>
                                <td class="name">
                                    <textarea v-model="task.name" rows="2" v-if="editable" @keyup="change(task)"></textarea>
                                    <span v-else>@{{ task.name }}</span>
                                </td>
                                <td class="estimation">
                                    <span :class="{'exceeded': task.logged_time > (task.revised_estimation || task.estimation || 0)}">@{{ task.logged_time | timeDisplay }}</span>
                                    /
                                    <input type="text" time v-model="task.revised_estimation | timeDisplay task.estimation"
                                           v-if="editable" @keyup="change(task)">
                                    <span v-else>@{{ task.estimation | timeDisplay }}</span>
                                </td>
                                <td class="resource_type">
                                    <select v-model="task.resource_type_id" v-if="editable" @change="change(task)">
                                        <option v-for="(index, resource) in resources"
                                                :selected="task.resource_type_id == index"
                                                value="@{{ index }}">@{{ resource }}</option>
                                    </select>
                                    <span v-else>@{{ resources[task.resource_type_id] }}</span>
                                </td>
                                <td class="require_comments">
                                    <label v-if="editable">
                                        <input type="checkbox" v-model="task.require_comments" value="1" @change="change(task)">
                                        <span>Requis</span>
                                    </label>
                                    <span v-else>
                                        <span v-if="task.require_comments">Requis</span>
                                        <span v-else>Optionnel</span>
                                    </span>
                                </td>
                                <td class="milestone">
                                    <select v-model="task.milestone_id" v-if="editable" @change="manageMilestone(task)">
                                        <option value="" :selected="!task.milestone_id">- Aucune -</option>
                                        <option v-for="(index, milestone) in milestones"
                                                value="@{{ index }}">@{{ milestone }}</option>
                                        <option value="-1">+ Ajouter une étape</option>
                                    </select>
                                    <span v-else>
                                        <span v-if="task.milestone">@{{ task.milestone.name }}</span>
                                        <span v-else>-&nbsp;Aucune&nbsp;-</span>
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!tasksCount && !newTasksCount">
                                <td colspan="@{{ editable ? 7 : 6 }}" style="text-align: center;">Aucun élément à afficher</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="pagination-wrapper" v-if="tasksCount > rowsPerPage" v-cloak>
                        <div class="dataTables_paginate paging_simple_numbers">
                            <a class="paginate_button previous" :class="{'disabled': isFirstPage}" @click="setPage(page - 1)">&lt;</a>{{--
                            --}}<span>{{--
                                --}}<a class="paginate_button" :class="{'current': currentPage(0)}" @click="setPage(0)">1</a>{{--

                                --}}<a v-if="page < 4 || totalPages < 7" class="paginate_button" :class="{'current': currentPage(1)}" @click="setPage(1)">2</a>{{--
                                --}}<span v-else class="ellipsis">&hellip;</span>{{--

                                --}}<a v-for="n in pagesToShow" class="paginate_button" :class="{'current': currentPage(n)}" @click="setPage(n)">@{{ n + 1 }}</a>{{--

                                --}}<span v-if="totalPages > 3">{{--
                                    --}}<a v-if="page > showBeforeLastPage" class="paginate_button" :class="{'current': currentPage(totalPages - 2)}" @click="setPage(totalPages - 2)">@{{ totalPages - 1 }}</a>{{--
                                    --}}<span v-else class="ellipsis">&hellip;</span>{{--
                                --}}</span>{{--

                                --}}<a v-if="totalPages > 2" class="paginate_button" :class="{'current': currentPage(totalPages - 1)}" @click="setPage(totalPages - 1)">@{{ totalPages }}</a>{{--
                            --}}</span>{{--
                            --}}<a class="paginate_button next" :class="{'disabled': isLastPage}" @click="setPage(page + 1)">&gt;</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </project>
@endsection
