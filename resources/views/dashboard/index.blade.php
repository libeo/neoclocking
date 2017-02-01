@extends('layouts.two-column')

@section('body-classes') dashboard @endsection
@section('body-attr') v-on:keyup.esc="hideSearchResults" @endsection

@section('extra-header')
    <search-bar
            :favourite-toggled="favouriteToggled"
            :add-new-time="addNewTime"
            ></search-bar>
@endsection

@section('content')
    <last-days></last-days>
    <live-entry v-if="liveClock" :entry="liveClock" :save="saveLiveClocking" :cancel="cancelLiveClocking" :stop-live-clocking="stopLiveClocking"></live-entry>
    <div class="add-time-wrapper" v-if="isAddingTime">
        <new-entry
            :my-task="selectedTask"
            :my-project="selectedProject"
            :my-client="selectedClient"
            :when-saved="saveNewTimeEntry"
            :when-canceled="cancelAddNewTime"
            :current-date="currentDate"
            :start-time="startTime"
            :when-live-clocking-start="startLiveClocking"
            :override-entry.sync="overrideNewEntry"
        >
        </new-entry>
    </div>
    <div class="clock-history" id="dashboard">
        <log-entries-list
            :date="currentDate"
            :set-date="setDate"
            :when-edited="saveEntryModification"
            :when-deleted="removeTimeEntry"
            :when-new-entry="addNewTime"
            :entries="weekLogEntries | filterBy filterweekLogEntriesCurrentDate | orderBy 'started_at' -1"
        ></log-entries-list>
    </div>
    @include('partial/add-time')
    @include('partial/sidebar')
    @include('partial/log-entries-list')
    @include('partial/log-entry')
    @include('partial/live-entry')
    @include('partial/day-slider')
    @include('partial/last-days')
@endsection

@section('sidebar')
    <dashboard-sidebar
        :week-clients="weekClients"
        :week-projects="weekProjects"
        :week-days="weekDays"
        :my-favorites="myFavorites"
        :add-new-time="addNewTime"
        :total-time="currentWeekTotalTime"
        :favourite-toggled="favouriteToggled"
    >
    </dashboard-sidebar>
@endsection

@section('define-vue')
    <script>
        $(function() {
            CLOCKING.Vues.initVue('dashboard');
        });
    </script>
@endsection

@section('custom_script')
@endsection
