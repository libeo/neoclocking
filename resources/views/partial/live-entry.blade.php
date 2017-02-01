<script type="x/template" id="live-entry-template">
	<div class="clock-wrapper live-clock">
		<div class="clock-middle">
			<h2 class="add-time-title">Enregistrement</h2>
			<h3 class="clock-title-wrapper">
				<a href="/tasks/@{{ entry.task.data.number }}/" class="clock-title-link">
					<span class="clock-title-task-number">#@{{ entry.task.data.number }}</span>
					<span class="clock-title">@{{ entry.task.data.name }}</span>
				</a>
			</h3>
			<div class="clock-informations">
				<a href="/projects/#client_@{{ entry.task.data.project.data.client.data.number }}" class="clock-client">@{{ entry.task.data.project.data.client.data.name }}</a>
				<a href="/projects/@{{ entry.task.data.project.data.number }}/" class="clock-project">@{{ entry.task.data.project.data.number }} - @{{ entry.task.data.project.data.name }}</a>
                <span class="clock-time@{{ entry.task.data.estimation_exceeded ? ' is-over' : '' }}">
                    <span class="clock-time-current">@{{ entry.task.data.logged_time | minutesToHours }}</span>
                    <span class="clock-time-total">@{{ entry.task.data.revised_estimation ? entry.task.data.revised_estimation : entry.task.data.estimation | minutesToHours }}</span>
                </span>
			</div>
			<div class="clock-links" v-if="entry.task.data.reference_number">
				<div class="clock-link-wrapper">
					<a href="@{{ entry.task.data.reference.data.prefix }}@{{ entry.task.data.reference_number }}"
					   target="_blank" class="button clock-redmine-trigger">

						<svg width="15" height="15" class="icon icon-reference">
							<use xlink:href="/svg/symbols.svg#redmine"></use>
						</svg>
						<span>Voir dans @{{ entry.task.data.reference.data.name }}</span>
					</a>
				</div>
			</div>
		</div>
		<div class="clock-right  edit-time-wrapper">
            <span class="clock-hour">
                <label for="edit-time-input-hour-start">De</label><span class="clock-hour-start">@{{ entry.started_at.date | dateToHours }}</span>
            </span>
			<span class="clock-hour-total"><label for="edit-time-input-hour-start">En cours</label> @{{ timer | minutesToHours }}</span>
			<div class="edit-time-input-wrapper is-fullwidth">
				<label for="edit-time-input-comment"><img src="/img/icon-comment.png"></label>
				<textarea @keyup="saveComment | debounce" v-model="entry.comment" class="mousetrap"></textarea>

				<div class="edit-time-comment-infos">
					<p class="edit-time-comment-required" v-if="entry.task.data.require_comments">Commentaire requis</p>
					<p class="edit-time-comment-saved" v-if="lastSave">
						<svg width="16" height="13" class="icon icon-check">
							<use xlink:href="/svg/symbols.svg#check"></use>
						</svg>
						@{{lastSave}}</p>
				</div>
			</div>
			<div class="edit-time-bottom">
				<div class="edit-time-bottom-right">
					<button class="button is-gradient-purple" @click="save" :disabled="!validClock">Ajouter</button>
				</div>
				<div class="edit-time-bottom-left">
					<button class="button" @click="cancel">Annuler</button>
				</div>
			</div>
		</div>
	</div>
</script>
