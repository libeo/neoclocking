<script type="x/template" id="log-entries-list-template">
	<day-slider
			:date="currentDate"
			:set-date="setDate"
			:worked-time-today="workedTimeToday"
			>
	</day-slider>
	<div class="clock-history-list">
		<log-entry v-for="entry in entries"
				   :entry="entry"
				   :when-edited="whenEdited"
				   :when-deleted="whenDeleted"
				   :when-new-entry="whenNewEntry"
				   :all-entries="entries"
				   :color="getProjectColor(entry)"
                   :temporary="isTemporary(entry)"
				>
		</log-entry>
		<p class="empty-entries-list" v-if="noEntries">Aucune entrée de temps pour cette journée</p>
	</div>
</script>
