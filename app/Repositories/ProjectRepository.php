<?php

namespace NeoClocking\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use NeoClocking\Exceptions\ModelValidationException;
use NeoClocking\Models\Client;
use NeoClocking\Models\Project;
use NeoClocking\Models\User;
use NeoClocking\Models\UserProjectRole;
use SearchIndex;

/**
 * Project-related data
 * No create or save here since Data is copied from LDAP
 */
class ProjectRepository
{

    /**
     * @return Collection|Project[] List of project
     */
    public function findAll()
    {
        return Project::all();
    }

    /**
     * @param int $id
     *
     * @return Project
     * @throws ModelNotFoundException
     */
    public function findById($id)
    {
        return Project::whereId($id)->first();
    }

    /**
     * @param string $number Number of the project
     *
     * @return Project
     * @throws ModelNotFoundException
     */
    public function findOneByNumberOrFail($number)
    {
        return Project::whereNumber($number)->firstOrFail();
    }

    /**
     * @param string $id Id of the project
     *
     * @return Project
     * @throws ModelNotFoundException
     */
    public function findOneByIdOrFail($id)
    {
        return Project::findOrFail($id);
    }

    /**
     * @param Client $client
     *
     * @return Project[]
     */
    public function findByClient(Client $client)
    {
        return Project::whereClientId($client->id)->get();
    }

    /**
     * Get Projects visible by a specific user using the provided search terms
     *
     * @param User $user
     * @param string $terms
     * @param int $limit
     *
     * @return Project[]
     */
    public function search(User $user, $terms, $limit = 20)
    {
        $query['size'] = 500;

        $fields = ['client_name^2', 'project_name^3', 'project_number^4'];

        $query['body']['query']['multi_match'] = [
            'type' => 'cross_fields',
            'fields' => $fields,
            'query' => $terms,
            'operator' => 'and',
            'fuzziness' => 'AUTO'
        ];

        $hits = SearchIndex::getResults($query)['hits'];

        $results = $hits['hits'];
        $projectsIds = [];
        foreach ($results as $hit) {
            $hitId = $hit['_id'];
            switch ($hit['_type']) {
                case 'project':
                    $projectsIds[] = (int)$hitId;
                    break;
                case 'client':
                    $clientProjectsIds = Project::whereClientId($hitId)->lists('id');

                    $projectsIds += $clientProjectsIds->toArray();
                    break;
                default:
                    continue;
                    break;
            }
        }
        $projectsIds = array_filter($projectsIds);

        $roles = manager_assistant_roles()->lists('id')->toArray();
        $projectsUserCanManage =
            UserProjectRole::whereIn('user_role_id', $roles)
            ->whereIn('project_id', $projectsIds)
            ->where('user_id', $user->id)
            ->lists('project_id');

        return Project::active()->whereIn('id', $projectsUserCanManage->toArray())->limit($limit)->get();
    }

    /**
     * @param Project $project
     * @return bool
     * @throws ModelValidationException
     */
    public function save(Project $project)
    {
        //TODO : add validator
        return $project->save();
    }

    public function saveOrUpdate(Project $project)
    {
        try {
            $project->save();
        } catch (\Exception $e) {
            $project->update();
        }
    }

    /**
     * Return all projects, by client, that a user have access to
     *
     * @param User $user
     * @return Collection
     */
    public function findAllByClientForUser(User $user)
    {
        $projects = Project::canBeViewedBy($user)
            ->select('number', 'name', 'client_id')
            ->with(['client'=>function ($query) {
                $query->select('id', 'number', 'name');
            }])
            ->groupBy('projects.id')
            ->orderBy('number')
            ->orderBy('name')
        ->get();

        // Group project by client and order them by client name
        $projectsByClient = $projects->groupBy('client_id')->sortBy(function ($item) {
            return $item->first()->client->name;
        });

        return $projectsByClient;
    }
}
