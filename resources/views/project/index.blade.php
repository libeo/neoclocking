@extends('layouts.one-column')

@section('page-title', 'Projets')

@section('header-search-bar')
@endsection

@section('body-classes') in-project @endsection

@section('content')
    <projects inline-template>
        <div class="projects">
            <div class="projects-header">
                <h1>Liste des projets</h1>

                <input type="search"
                       v-model="filter"
                       debounce="250"
                       id="projects-search-input"
                       v-el:search
                       :class="{'not-empty': filter != ''}"
                       placeholder="Filtrer les projets...">
            </div>

            <div class="project-list" v-cloak>
                <template v-for="client in filtered | recordLength 'clientsCount'">
                    <div class="project-list-item" v-if="client.projects.length > 0">
                        <h2>@{{ client.name }}</h2>

                        <ul class="client-projects">
                            <li v-for="project in filterProjects(client)">
                                <a href="/projects/@{{ project.number }}">
                                    @{{ project.number }} - @{{ project.name }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </template>

                <p v-if="!loaded">Chargement des projets en cours...</p>
                <p v-if="clients.length > 0 && clientsCount == 0">Aucun projet ne correspond à ce que vous rechercher.</p>
                <p v-if="loaded && clients.length == 0">Vous n'avez actuellement accès à aucun projet.</p>
            </div>
        </div>
    </projects>
@endsection

@section('custom_script')
@endsection
