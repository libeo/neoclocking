<script type="x/template" id="addTimeTemplate">
    <div class="add-time-left">
        <h2 class="add-time-title">Ajout de temps</h2>
        <!--
        <div><pre>@{{ myTask | json }}</pre></div>
        <div><pre>@{{ myClient | json }}</pre></div>
        <div><pre>@{{ myProject | json }}</pre></div>
        -->
        <div class="add-time-clock-summary">
            <h3 class="clock-title-wrapper">
                <a href="/tasks/@{{ myTask.data.number }}/" class="clock-title-link">
                    <span class="clock-title-task-number">#@{{ myTask.data.number }}</span>
                    <span class="clock-title">@{{ myTask.data.name }}</span>
                </a>
            </h3>
            <div class="clock-informations">
                <a href="/projects/#client_@{{ myClient.data.number }}" class="clock-client">@{{ myClient.data.name }}</a>
                <a href="/projects/@{{ myProject.data.number }}/" class="clock-project">@{{ myProject.data.number }} - @{{ myProject.data.name }}</a>
                <span class="clock-time@{{ myTask.data.estimation_exceeded ? ' is-over' : '' }}">
                    <span class="clock-time-current">@{{ myTask.data.logged_time | minutesToHours }}</span>
                    <span class="clock-time-total">@{{ myTask.data.revised_estimation ? myTask.data.revised_estimation : myTask.data.estimation | minutesToHours }}</span>
                </span>
            </div>
        </div>
    </div>
    <div class="add-time-right edit-time-wrapper">
        <div class="edit-time-top">
            <div class="edit-time-line">
                <div class="edit-time-input-wrapper">
                    <label for="edit-time-input-hour-start">De</label>
                    <input type="text" id="edit-time-input-hour-start"
                        class="edit-time-input-hour-start mousetrap"
                        v-model="entry.time_start"
                    >
                </div>
                <div class="edit-time-input-wrapper">
                    <label for="edit-time-input-hour-end">À</label>
                    <input type="text" id="edit-time-input-hour-end"
                        class="edit-time-input-hour-end mousetrap"
                        v-model="entry.time_end"
                    >
                </div>
                <div class="edit-time-input-wrapper">
                    <label for="edit-time-input-hour-total">Durée</label>
                    <input type="text" id="edit-time-input-hour-total"
                        class="edit-time-input-hour-total mousetrap"
                        v-model="entry_duration"
                    >
                </div>
                <div class="edit-time-input-wrapper">
                    <label for="edit-time-input-date"><img src="/img/icon-date.png"></label>
                    <input type="text" id="edit-time-input-date" tabindex="-1"
                        class="edit-time-input-date"
                        v-model="entry.date"
                    >
                </div>
            </div>
            <div class="edit-time-input-wrapper is-fullwidth">
                <label for="edit-time-input-comment"><img src="/img/icon-comment.png"></label>
                <textarea name="edit-time-input-comment" id="edit-time-input-comment"
                    class="edit-time-input-comment mousetrap"
                    v-model="entry.comment"
                >
                </textarea>
                <div class="edit-time-comment-infos">
                    <p class="edit-time-comment-required" v-show="comments_required">Commentaire requis</p>
                </div>
            </div>
        </div>
        <div class="edit-time-bottom">
            <div class="edit-time-bottom-right" v-if="canLiveClock">
                <button class="button is-gradient-purple edit-time-clock-submit" @click="startLiveClocking">Démarrer</button>
            </div>
            <div class="edit-time-bottom-right" v-if="!canLiveClock">
                <button class="button is-gradient-purple edit-time-clock-submit" @click="saveEntry" :disabled="!validAddTime">Ajouter</button>
            </div>
            <div class="edit-time-bottom-left">
                <button class="button edit-time-clock-cancel" @click="cancelEntry">Annuler</button>
            </div>
        </div>
    </div>
</script>
