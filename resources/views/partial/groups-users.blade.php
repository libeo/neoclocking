<script type="x/template" id="groups-users-template">
	<div v-if="usersGroups" class="control-users">
		<p class="menu-content-title">Prendre le contrôle de...</p>
		<input type="text" v-model="search" class="menu-content-search" placeholder="Filtrer les utilisateurs">
		<template v-for="(group,users) in usersGroups">
			<user-list :title="'Utilisateurs '+group" :users="users | filterBy search in 'username' 'fullname'" :filter="search" />
		</template>
	</div>
</script>