<?php

namespace NeoClocking\Transformers;

use League\Fractal\TransformerAbstract;
use NeoClocking\Models\Client;

class ClientTransformer extends TransformerAbstract
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected $availableIncludes = [
        'projects',
    ];

    /**
     * Transform the client model into the wanted formatted array.
     *
     * @param  Client $client
     * @return array
     */
    public function transform(Client $client)
    {
        return [
            'id'     => $client->id,
            'name'   => $client->name,
            'number' => $client->number,
        ];
    }

    /**
     * Include the projects related to the client.
     *
     * @param  Client $client
     * @return \League\Fractal\Resource\Collection
     */
    public function includeProjects(Client $client)
    {
        $projects = $client->projects;

        return $this->collection($projects, $this->getProjectTransformer(), 'projects');
    }

    /**
     * Get the project transformer instance.
     *
     * @return TransformerAbstract
     */
    protected function getProjectTransformer()
    {
        return app(ProjectTransformer::class);
    }
}
