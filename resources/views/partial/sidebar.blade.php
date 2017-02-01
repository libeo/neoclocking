<script type="text/x-template" id="dashboard-sidebar-template">
<div class="sidebar-tabs">
    <div class="sidebar-tabs-header">
        <button
            class="sidebar-tab-trigger"
            v-bind:class="{ 'is-active': sideBarViews.showFavorites }"
            @click="changeSideBarToFavorites"
        >
            Mes favoris
        </button>
        <button
            class="sidebar-tab-trigger"
            v-bind:class="{ 'is-active': sideBarViews.showSummary }"
            @click="changeSideBarToSummary"
        >
            Résumé
        </button>
    </div>
    <div class="sidebar-tabs-content">
        <div v-show="sideBarViews.showFavorites" class="sidebar-tab fav">
            <div class="clock-wrapper is-summary" v-for="myFavourite in myFavourites">
                <div class="clock-left">
                    <button class="clock-add-time-trigger" @click="addTimeEntry(myFavourite)" :disabled="!myFavourite.active">
                        <svg width="40" height="40" class="icon-add_clock" v-bind:class="{ 'disabled': !myFavourite.active }">
                            <use xlink:href="/svg/symbols.svg#add_clock"></use>
                        </svg>
                        <span class="visuallyhidden">Ajouter du temps dans cette tâche</span>
                    </button>
                </div>
                <div class="clock-middle">
                    <h3 class="clock-title-wrapper">
                        <a href="/tasks/@{{ myFavourite.number }}/" class="clock-title-link">
                            <span class="clock-title-task-number">#@{{ myFavourite.number }}</span>
                            <span class="clock-title">@{{ myFavourite.name }}</span>
                        </a>
                    </h3>
                    <div class="clock-informations">
                        <a href="/projects/#client_@{{ myFavourite.client.data.number }}" class="clock-client">@{{ myFavourite.client.data.name }}</a>
                        <a href="/projects/@{{ myFavourite.project.data.number }}/" class="clock-project">@{{ myFavourite.project.data.number }} - @{{ myFavourite.project.data.name }}</a>
                        <span class="clock-time@{{ myFavourite.estimation_exceeded ? ' is-over' : '' }}">
                            <span class="clock-time-current">@{{ myFavourite.logged_time | minutesToHours }}</span>
                            <span class="clock-time-total">@{{  myFavourite.revised_estimation ? myFavourite.revised_estimation : myFavourite.estimation | minutesToHours }}</span>
                        </span>
                    </div>
                </div>

                <task-actions
                    :task="myFavourite"
                    :favourite-toggled="favouriteToggled"
                    :favourite-only="true"
                ></task-actions>
            </div>
        </div>
        <div class="sidebar-tab-content" v-show="sideBarViews.showSummary">
            <select name="clock-summary-filter" id="clock-summary-filter" @change="changeWeekFilter">
                <option value="projects" selected="selected">Par projet</option>
                <option value="clients">Par client</option>
                <option value="days">Par jour</option>
            </select>

            <div v-show="summaryTabs.clients" class="by-clients">
                <ul class="clock-summary-list">
                    <li class="clock-summary" v-for="client in weekClients">
                        <div class="clock-summary-left">
                            <div class="clock-summary-name">@{{ client.client_name }}</div>
                        </div>
                        <div class="clock-summary-right">
                            <div class="clock-summary-time">@{{ client.time | minutesToHours }}</div>
                        </div>
                    </li>
                    <li class="clock-summary clock-summary-total">
                        <div class="clock-summary-left">
                            <div class="clock-summary-name">Total</div>
                        </div>
                        <div class="clock-summary-right">
                            <div class="clock-summary-time">@{{ totalTime | minutesToHours }}</div>
                        </div>
                    </li>
                </ul>
            </div>

            <div v-show="summaryTabs.projects" class="by-projects">
                <ul class="clock-summary-list">
                    <li class="clock-summary" v-for="project in weekProjects">
                         <div class="clock-summary-left">
                            <div class="clock-summary-name">
                                @{{ project.client_name }}/
                                @{{ project. project_number }} - @{{ project.project_name }}
                            </div>
                        </div>
                        <div class="clock-summary-right">
                            <div class="clock-summary-time">@{{ project.time | minutesToHours }}</div>
                        </div>
                    </li>
                    <li class="clock-summary clock-summary-total">
                        <div class="clock-summary-left">
                            <div class="clock-summary-name">Total</div>
                        </div>
                        <div class="clock-summary-right">
                            <div class="clock-summary-time">@{{ totalTime | minutesToHours }}</div>
                        </div>
                    </li>
                </ul>
            </div>

            <div v-show="summaryTabs.days" class="by-days">
                <ul class="clock-summary-list">
                    <li class="clock-summary" v-for="day in weekDays">
                        <div class="clock-summary-left">
                            <div class="clock-summary-name">
                                @{{ day.day }}
                            </div>
                        </div>
                        <div class="clock-summary-right">
                            <div class="clock-summary-time">@{{ day.time_logged | minutesToHours }}</div>
                        </div>
                    </li>
                    <li class="clock-summary clock-summary-total">
                        <div class="clock-summary-left">
                            <div class="clock-summary-name">Total</div>
                        </div>
                        <div class="clock-summary-right">
                            <div class="clock-summary-time">@{{ totalTime | minutesToHours }}</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</script>
