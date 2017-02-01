<script type="x/template" id="log-entry-template">
    <div class="clock-wrapper @{{ isGapping ? 'is-gapping' : '' }}" v-bind:style="{borderLeftColor: color}">
        <div class="clock-left" v-bind:style="{backgroundColor: temporaryColor}">
            <button class="clock-add-time-trigger" @click="addNewEntry" :disabled="!temp_entry.task.data.active">
                <svg width="40" height="40" class="icon-add_clock" v-bind:class="{ 'disabled': !temp_entry.task.data.active }">
                    <use xlink:href="/svg/symbols.svg#add_clock"></use>
                </svg>
                <span class="visuallyhidden">Ajouter du temps dans cette tâche</span>
            </button>
        </div>
        <div class="clock-middle" v-bind:style="{backgroundColor: temporaryColor}">
            <h3 class="clock-title-wrapper">
                <a href="/tasks/@{{ temp_entry.task.data.number }}/" class="clock-title-link">
                    <span class="clock-title-task-number">#@{{ temp_entry.task.data.number }}</span>
                    <span class="clock-title">@{{ temp_entry.task.data.name }}</span>
                </a>
            </h3>
            <div class="clock-informations">
                <a href="/projects/#client_@{{ temp_entry.client.data.number }}" class="clock-client">@{{ temp_entry.client.data.name }}</a>
                <a href="/projects/@{{ temp_entry.project.data.number }}/" class="clock-project">@{{ temp_entry.project.data.number }} - @{{ temp_entry.project.data.name }}</a>
                <span class="clock-time@{{ temp_entry.task.data.estimation_exceeded ? ' is-over' : '' }}">
                    <span class="clock-time-current">@{{ temp_entry.task.data.logged_time | minutesToHours }}</span>
                    <span class="clock-time-total">@{{ temp_entry.task.data.revised_estimation ? temp_entry.task.data.revised_estimation : temp_entry.task.data.estimation | minutesToHours }}</span>
                </span>
            </div>
            <div class="clock-links" v-show="showEdit">
                <div class="click-link-wrapper">
                    <button @click="replaceTask" class="button clock-replace-trigger">
                        <svg width="15" height="15" class="icon icon-remplacer">
                            <use xlink:href="/svg/symbols.svg#remplacer"></use>
                        </svg>
                        <span>Remplacer la tache</span>
                    </button>
                </div>
                <div class="clock-link-wrapper" v-if="entry.task.data.reference_number">
                    <a href="@{{ temp_entry.task.data.reference.data.prefix }}@{{ temp_entry.task.data.reference_number }}"
                        target="_blank" class="button clock-redmine-trigger">

                        <svg width="15" height="15" class="icon icon-reference">
                            <use xlink:href="/svg/symbols.svg#redmine"></use>
                        </svg>
                        <span>Voir dans @{{ temp_entry.task.data.reference.data.name }}</span>
                    </a>
                </div>
            </div>
        </div>
        <template v-if="entry.can_be_edited">
            <div class="clock-right clock-edit-trigger @{{ overlaps ? 'is-overlaping' : '' }}" role="button" tabindex="0"
                v-show="showEntryTime"
                @click="toggleEditMode"
            >
                <span class="clock-hour">
                    <span class="clock-hour-wrapper">
                        <span class="clock-hour-start">@{{ entry.started_at | dateToHours }}</span>
                        <span class="clock-hour-end">@{{ entry.ended_at | dateToHours }}</span>
                    </span>
                </span>
                <span class="clock-hour-total">@{{ entry.duration | minutesToHours }}</span>
                <button class="clock-delete-trigger" v-show="entry.can_be_deleted" @click="deleteEntry" data-delete-log="@{{ entry.task.data.number }}">
                    <i class="fa fa-trash"></i>
                    <span class="visuallyhidden">Supprimer cette entrée</span>
                </button>
                <div class="clock-description" v-if="entry.comment">@{{ entry.comment }}</div>
            </div>

            <div class="add-time-right edit-time-wrapper" v-show="showEdit">
                <div class="edit-time-top">
                    <div class="edit-time-line">
                        <div class="edit-time-input-wrapper">
                            <label for="edit-time-input-hour-start">De</label>
                            <input type="text" id="edit-time-input-hour-start"
                                   class="edit-time-input-hour-start mousetrap"
                                   v-model="temp_entry.start_time"
                            >
                        </div>
                        <div class="edit-time-input-wrapper">
                            <label for="edit-time-input-hour-end">À</label>
                            <input type="text" id="edit-time-input-hour-end"
                                   class="edit-time-input-hour-end mousetrap"
                                   v-model="temp_entry.end_time"
                            >
                        </div>
                        <div class="edit-time-input-wrapper">
                            <label for="edit-time-input-hour-total">Durée</label>
                            <input type="text" id="edit-time-input-hour-total"
                                   class="edit-time-input-hour-total mousetrap"
                                   v-model="temp_entry_duration"
                            >
                        </div>
                        <div class="edit-time-input-wrapper">
                            <label for="edit-time-input-date">
                                <img src="/img/icon-date.png" alt="Date">
                            </label>
                            <input type="text" id="edit-time-input-date" tabindex="-1"
                                   class="edit-time-input-date mousetrap"
                                   v-model="temp_entry.date"
                            >
                        </div>
                    </div>
                    <div class="edit-time-input-wrapper is-fullwidth">
                        <label for="edit-time-input-comment">
                            <img src="/img/icon-comment.png" class="Commentaire">
                        </label>
                        <textarea name="edit-time-input-comment" id="edit-time-input-comment"
                                  class="edit-time-input-comment mousetrap"
                                  v-model="temp_entry.comment"
                        >
                        </textarea>
                        <div class="edit-time-comment-infos">
                            <p class="edit-time-comment-required" v-show="comments_required">Commentaire requis</p>
                        </div>
                    </div>
                </div>
                <div class="edit-time-bottom">
                    <div class="edit-time-bottom-right">
                        <button class="button is-gradient-purple edit-time-clock-submit" @click="saveEdit" :disabled="!validSaveTime">Enregistrer</button>
                    </div>
                    <div class="edit-time-bottom-left">
                        <button class="button edit-time-clock-cancel" @click="cancelEdit">Annuler</button>
                    </div>
                </div>
            </div>
        </template>
        <template v-else="entry.can_be_edited">
            <div class="clock-right clock-edit-trigger @{{ overlaps ? 'is-overlaping' : '' }}">
                <span class="clock-hour">
                    <span class="clock-hour-wrapper">
                        <span class="clock-hour-start">@{{ entry.started_at | dateToHours }}</span>
                        <span class="clock-hour-end">@{{ entry.ended_at | dateToHours }}</span>
                    </span>
                </span>
            <span class="clock-hour-total">@{{ entry.duration | minutesToHours }}</span>
            <div class="clock-description" v-if="entry.comment">@{{ entry.comment }}</div>
            </div>
        </template>
    </div>
</script>
