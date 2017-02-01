<script type="x/template" id="last-days-template">
	<div class="lastdaysofmonth" v-if="show">
		N'oubliez pas! Vous avez jusqu'au 1<sup>er</sup> du mois à 18h59 pour terminer la saisie de votre temps.
		<span class="close" @click="close">×</span>
	</div>
</script>