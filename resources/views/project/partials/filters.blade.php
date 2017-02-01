<div class="info-actions-filtres">
    <span>Filtrer:</span>
    <div class="active-filters">
        <button :class="{'active': statusFilter === null}" type="button" @click="statusFilter = null">Tous</button>
        <button :class="{'active': statusFilter === true}" type="button" @click="statusFilter = true">Ouverts</button>
        <button :class="{'active': statusFilter === false}" type="button" @click="statusFilter = false">Fermés</button>
    </div>
    {!! Form::select('resource-filter', $resourceTypes, null, ['v-model' => 'resourceFilter', 'placeholder' => 'Ressources', 'class' => 'resource-filter', 'data-tasksfilters'=>'resourcestype']) !!}
</div>
