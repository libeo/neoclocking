<script type="x/template" id="task-actions-template">
    <div class="clock-actions" v-if="!form.show">
        <div class="clock-action" v-if="task.can_clock">
            <button tabindex="-1" class="clock-favorite-trigger icon-button" @click="toggleFavourite(task)" :title="favoriteActionTitle">
                <span v-if="task.favourited" class="on">
                    <i class="fa fa-star"></i>
                    <span class="visuallyhidden">Enlever cette tâche de mes favoris</span>
                </span>
                <span v-else  class="off">
                    <i class="fa fa-star-o"></i>
                    <span class="visuallyhidden">Mettre cette tâche dans mes favoris</span>
                </span>
            </button>
            <button tabindex="-1" class="clock-active-trigger icon-button" v-if="task.user_can_edit && !favouriteOnly && task.project.data.active" @click="toggleState(task)" :title="activeActionTitle">
                <span v-if="task.active" class="on">
                    <i class="fa fa-toggle-on"></i>
                    <span class="visuallyhidden">Fermer cette tâche</span>
                </span>
                <span v-else class="off">
                    <i class="fa fa-toggle-off"></i>
                    <span class="visuallyhidden">Ouvrir cette tâche</span>
                </span>
            </button>
        </div>
        <div class="clock-action" v-else>
            <button tabindex="-1" class="clock-ask-access-trigger icon-button" @click="showFormAccess(task)" title="Demander les accès à la tâche">
                <span class="off">
                    <i class="fa fa-envelope-o"></i>
                    <span class="visuallyhidden">Demander les accès à la tâche</span>
                </span>
            </button>
        </div>
    </div>
    <template v-else>
        <div class="ask-access-right ask-access-wrapper">
            <div class="ask-access-top">
                <label for="ask-access-input">
                    <img src="/img/icon-comment.png" class="Commentaire">
                </label>
                <textarea name="ask-access-input"
                          id="ask-access-input"
                          v-model="form.reason"
                          class="ask-access-input-reason"
                          placeholder="Raison de la demande..."
                          autofocus
                >
                </textarea>
            </div>
            <div class="ask-access-bottom">
                <div class="ask-access-bottom-right">
                    <button class="button is-gradient-purple ask-access-submit" @click="askAccess">Demander les accès</button>
                </div>
                <div class="ask-access-bottom-left">
                    <button class="button ask-access-cancel" @click="cancelAccess">Annuler</button>
                </div>
            </div>
        </div>
    </template>
</script>
