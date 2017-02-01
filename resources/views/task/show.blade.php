@extends('layouts.two-column')

@section('page-title', "{$task->number} - {$task->name}")

@section('header-search-bar')
@endsection

@section('content')
    <div class="clock-history">
        <div class="clock-history-header">
            <div class="clock-history-header-left">
                <h2 class="clock-history-title">Historique</h2>
            </div>
            <div class="clock-history-header-right">
                <div class="time-total-wrapper">
                    <span class="time-total-label">
                        <span>Temps total</span>
                        <span class="icon-wrapper time-wrapper">
                            <svg width="20" height="20" class="time"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="/svg/symbols.svg#time"></use></svg>
                        </span>
                    </span>
                    <span class="time-total">{{ $task->present()->loggedTime() }} / {{ $task->present()->estimation() }}</span>
                </div>
            </div>
        </div>
        @include('task/partials/log-entry-list')
    </div>
@endsection

@section('sidebar')
    <div class="task-summary">
        <h2 class="task-summary-title">Informations sur la tâche</h2>
        <div class="clock-wrapper">
            <div class="clock-left">
                <button class="clock-add-time-trigger" title="Ajouter du temps dans cette tâche" @click="clockTime({{ $task->number }})" :disabled="!task.active || !task.project.data.active">
                    <svg width="40" height="40" class="icon-add_clock disabled" v-bind:class="{ 'disabled': !task.active || !task.project.data.active }">
                        <use xlink:href="/svg/symbols.svg#add_clock"></use>
                    </svg>
                    <span class="visuallyhidden">Ajouter du temps dans cette tâche</span>
                </button>
            </div>
            <div class="clock-middle">
                <h3 class="clock-title-wrapper">
                    <a href="{{ URL::route('task.show', $task->number) }}" class="clock-title-link">
                        <span class="clock-title-task-number">#{{ $task->number }}</span>
                        <span class="clock-title">{{ $task->name }}</span>
                    </a>
                </h3>
                <div class="clock-informations">
                    <a href="{{ URL::route('project.index') }}#client_{{ $task->project->client->number }}" class="clock-client">{{ $task->project->client->name }}</a>
                    <a href="{{ URL::route('project.show', $task->project->number) }}" class="clock-project">#{{ $task->project->number }} -{{ $task->project->name }}</a>
                </div>
            </div>
            <task-actions
                :task-number="{{ $task->number }}"
                :task="{}"
                :favourite-toggled="favouriteToggled"
            ></task-actions>
        </div>
        @if ($user->can('update', $task))
            <div class="task-summary-edit">
                @include('task/partials/edit-form')
            </div>
        @endif
    </div>
@endsection

@section('custom_script')
    <script>
        $(function() {
            CLOCKING.Components.SelectCreateMilestone($('[data-addmilestone]'),'- Aucune -', '+ Ajouter une étape', '/api/projects/{!! $task->project->number !!}/milestones');
        });
    </script>
@endsection


