{!! Form::model($task, ['route' => ['task.update', $task->number], 'method' => 'PATCH']) !!}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="errors">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @include('partial/messages')
    <div class="edit-task-input-wrapper is-fullwidth">
        {!! Form::label('name', 'Nom de la tâche') !!}
        {!! Form::text('name') !!}
    </div>
    <div class="edit-task-input-wrapper is-fullwidth">
        {!! Form::label('project-search-field', 'Projet') !!}
        <autocomplete-project
            project-id="{{$task->project_id}}"
            project-name="{{ $task->project->present()->numberAndName() }}"
        ></autocomplete-project>
        <script type="x/template" id="autocomplete-project-tmpl">
            <input name="project_id" type="hidden" v-model="projectId" value="{{ $task->project->id }}">
            <input autocomplete="off" class="project-search-field" id="project-search-field" value="{{ $task->project->present()->numberAndName() }}" name="project-search-field" type="text" v-model="projectName" readonly="{{ $task->project->active ? 'false' : 'readonly' }}">
        </script>

        <span class="edit-task-search-project-trigger">
            <span class="fa fa-search"></span>
        </span>
    </div>
    <div class="edit-task-input-wrapper">
        {!! Form::label('estimation', 'Estimation') !!}
        {!! Form::text('estimation', $task->present()->estimation(), ['readonly' => 'readonly']) !!}
    </div>
    <div class="edit-task-input-wrapper">
        {!! Form::label('revised_estimation', 'Nouvelle estimation') !!}
        {!! Form::text('revised_estimation', $task->present()->revisedEstimation(), $task->project->active ? [] : ['readonly' => 'readonly']) !!}
    </div>
    <div class="edit-task-input-wrapper">
        {!! Form::label('reference_type_id', 'Type de référence') !!}
        {!! Form::select('reference_type_id', $referenceTypes) !!}
    </div>
    <div class="edit-task-input-wrapper">
        {!! Form::label('reference_number', 'Numéro de Référence') !!}
        {!! Form::text('reference_number') !!}
    </div>
    <div class="edit-task-input-wrapper">
        {!! Form::label('resource_type_id', 'Type de ressource') !!}
        {!! Form::select('resource_type_id', $resourceTypes, null, $task->project->active ? [] : ['readonly' => 'readonly', 'disabled' => 'disabled']) !!}
    </div>
    <div class="edit-task-input-wrapper">
        {!! Form::label('milestone_id', 'Étape du projet') !!}
        {!! Form::select('milestone_id', $milestones, null, $task->project->active ? ['placeholder' => '- Aucune -', 'data-addmilestone'=>''] : ['readonly' => 'readonly', 'disabled' => 'disabled', 'placeholder' => '- Aucune -', 'data-addmilestone'=>'']) !!}
    </div>
    <div class="edit-task-input-wrapper is-fullwidth">
        {!! Form::label('require_comments', 'Commentaires requis') !!}
        {!! Form::hidden('require_comments', '0', ['id'=>'']) !!}
        {!! Form::checkbox('require_comments', 1, null, $task->project->active ? [] : ['readonly' => 'readonly', 'disabled' => 'disabled']) !!}
    </div>
    <div class="edit-task-actions">
        <div class="edit-task-action-right">
            {!! Form::submit('Enregistrer', ['class' => 'button is-gradient-purple edit-task-submit']) !!}
        </div>
        <div class="edit-task-action-left">
            @if ($task->logged_time <= 0 && $task->project->active)
                <a href="#" data-deletetask="/api/tasks/{!! $task->number !!}" class="button is-gradient-purple">Supprimer</a>
            @endif
        </div>
    </div>
{!! Form::close() !!}
