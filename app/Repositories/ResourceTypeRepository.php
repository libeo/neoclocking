<?php

namespace NeoClocking\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use NeoClocking\Models\ResourceType;

class ResourceTypeRepository
{

    /**
     * @return ResourceType[]
     */
    public function findAll()
    {
        return ResourceType::with('children')->whereNull('parent_id')->get();
    }

    /**
     * @param $id
     * @return ResourceType
     * @throws ModelNotFoundException
     */
    public function findById($id)
    {
        return ResourceType::findOrFail($id);
    }


    /**
     * @param $code
     * @return ResourceType
     * @throws ModelNotFoundException
     */
    public function findOneByCode($code)
    {
        return ResourceType::where('code', $code)->firstOrFail();
    }

    public function findAllForSelect()
    {
        $resourceTypes = $this->findAll();
        $select = [];
        foreach ($resourceTypes as $resourceType) {
            if ($resourceType->children->isEmpty()) {
                $select[$resourceType->id] = $resourceType->name;
            } else {
                $select[$resourceType->name] = [];
                foreach ($resourceType->children as $child) {
                    $select[$resourceType->name][$child->id] = $child->name;
                }
            }
        }
        return $select;
    }
}
