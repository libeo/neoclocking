<script type="x/template" id="user-list-template">
	<template v-if="hasUsers">
		<p class="menu-content-title">@{{ title }}</p>
		<ul class="list-resources">
			<li class="resources" v-for="user in users">
				<a href="{{route('control_user')}}?username=@{{user.username}}">
					<div class="resources-image">
						<img :src="user.gravatar">
					</div>
					<div class="resources-name">
						@{{user.fullname}}
					</div>
				</a>
			</li>
		</ul>
	</template>
</script>