<?php

namespace NeoClocking\Transformers;

use League\Fractal\TransformerAbstract;
use NeoClocking\Models\Milestone;

class MilestoneTransformer extends TransformerAbstract
{
    /**
     * Transform the reference type model into the wanted formatted array.
     *
     * @param  Milestone $referenceType
     * @return array
     */
    public function transform(Milestone $referenceType)
    {
        return [
            'id'     => $referenceType->id,
            'name'   => $referenceType->name,
        ];
    }
}
