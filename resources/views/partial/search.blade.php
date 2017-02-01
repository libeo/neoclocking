<script type="text/x-template" id="search-bar-template">
<div class="l-header-search">
    <div class="search-wrapper">
        <div class="search-input-wrapper">
            <input type="text" class="search-input" placeholder="Rechercher" name="recherche"
                v-model="term"
                @keyup="search | debounce"
                @focus="showIfNotEmpty"
            >
            <div class="close-search-results" v-if="showResults" @click="resetResults()">&times;</div>
        </div>
    </div>
</div>
<div class="search-results-wrapper" v-if="showResults" transition="expand" @click="hideResults()">
    <span class="loading is-loading" v-show="isLoading"></span>
    <ul class="search-results" @click.stop> {{-- Prevent le hideResults du results-wrapper --}}
        <li class="search-result" v-show="emptyResults">
            <div class="clock-wrapper">
                <div class="clock-middle">Aucun résultat de recherche</div>
            </div>
        </li>
        <li class="search-result" v-for="result in results">
            <div class="clock-wrapper">
                <div class="clock-left">
                    <button class="clock-add-time-trigger" @click="addTimeEntry(result)" data-clock-task="@{{ result.number }}" :disabled="!result.can_clock" title="Ajouter du temps dans cette tâche">
                        <svg width="40" height="40" class="icon-add_clock" v-bind:class="{ 'disabled': !result.can_clock }">
                            <use xlink:href="/svg/symbols.svg#add_clock"></use>
                        </svg>
                        <span class="visuallyhidden">Ajouter du temps dans cette tâche</span>
                    </button>
                </div>
                <div class="clock-middle">
                    <h3 class="clock-title-wrapper">
                        <a tabindex="-1" href="/tasks/@{{ result.number }}/" class="clock-title-link" v-if="result.can_clock">
                            <span class="clock-title-task-number">#@{{ result.number }}</span>
                            <span class="clock-title">@{{ result.name }}</span>
                        </a>
                        <span v-else>
                            <span class="clock-title-task-number">#@{{ result.number }}</span>
                            <span class="clock-title">@{{ result.name }}</span>
                        </span>
                    </h3>
                    <div class="clock-informations" v-if="result.can_clock">
                        <a tabindex="-1" href="/projects/#client_@{{ result.client['data']['number'] }}" class="clock-client">
                            @{{ result.client['data']['name'] }}
                        </a>
                        <a tabindex="-1" href="/projects/@{{ result.project['data']['number'] }}/" class="clock-project">
                            @{{ result.project['data']['number'] }} - @{{ result.project['data']['name'] }}
                        </a>
                    </div>
                    <div class="clock-informations" v-else>
                        <span class="clock-client">@{{ result.client.data.name }}</span>
                        <span class="clock-project">@{{ result.project.data.number }} - @{{ result.project.data.name }}</span>
                    </div>
                </div>
                <task-actions
                    :task="result"
                    :favourite-toggled="favouriteToggled"
                ></task-actions>
            </div>
        </li>
    </ul>
</div>
</script>
