<project-summary :project="project" :client="client" inline-template>
    <div class="project-info-summary" v-cloak>
        <div class="project-info-summary-client">@{{ client.name }}</div>
        <h2 class="project-info-summary-title">@{{ project.number }} - @{{ project.name }}</h2>
        <ul class="project-info-summary-details">
            <li class="summary-details-total">
                Temps total <span>@{{ totalHours }}</span>
            </li>
            <li class="summary-details-alloue">
                Temps alloué <span>@{{ allowedHours }}</span>
            </li>
            <li class="summary-details-utilise">
                Temps utilisé <span>@{{ usedHours }}</span>
            </li>
            <li class="summary-details-restant">
                Temps restant <span>@{{ remainingHours }}</span>
            </li>
        </ul>
    </div>
</project-summary>
