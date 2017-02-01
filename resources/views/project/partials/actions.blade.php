<div class="info-actions-groupe">
    <select :disabled="!tasksSelected.length" v-model="action">
        <option value="0" selected="selected">Actions groupées</option>
        <option value="open">Ouvrir</option>
        <option value="close">Fermer</option>
        <option value="delete" :disabled="!areDeletable">Supprimer</option>
        <option value="changeProject">Déplacer vers…</option>
        <option value="changeResource">Changer type de ressource</option>
    </select>
</div>

<div class="info-actions-button">
    <button class="button is-gradient-purple addtask" type="button" @click="createTask" :disabled="buzy">
        <i></i>Ajouter une tâche
    </button>

    <input type="submit" class="button is-gradient-purple save-button" :disabled="buzy" value="Sauvegarder">
</div>
