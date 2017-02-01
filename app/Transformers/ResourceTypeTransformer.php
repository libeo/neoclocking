<?php

namespace NeoClocking\Transformers;

use League\Fractal\TransformerAbstract;
use NeoClocking\Models\ResourceType;

class ResourceTypeTransformer extends TransformerAbstract
{
    /**
     * Resources that can be included if requested.
     *
     * @var array
     */
    protected $availableIncludes = [
        'children',
    ];

    /**
     * Include resources without needing it to be requested.
     *
     * @var array
     */
    protected $defaultIncludes = [
        'children',
    ];
    /**
     * Transform the resource type model into the wanted formatted array.
     *
     * @param  ResourceType $resourceType
     * @return array
     */
    public function transform(ResourceType $resourceType)
    {
        return [
            'id'   => $resourceType->id,
            'code' => $resourceType->code,
            'name' => $resourceType->name,
        ];
    }

    /**
     * Include the children
     *
     * @param ResourceType $resourceType
     * @return \League\Fractal\Resource\Collection
     */
    public function includeChildren(ResourceType $resourceType)
    {
        return $this->collection($resourceType->children, new self, 'clients');
    }
}
