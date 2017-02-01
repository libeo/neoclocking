<?php

namespace NeoClocking\Transformers;

use League\Fractal\TransformerAbstract;
use NeoClocking\Models\ReferenceType;

class ReferenceTypeTransformer extends TransformerAbstract
{
    /**
     * Transform the reference type model into the wanted formatted array.
     *
     * @param  ReferenceType $referenceType
     * @return array
     */
    public function transform(ReferenceType $referenceType)
    {
        return [
            'id'     => $referenceType->id,
            'code'   => $referenceType->code,
            'name'   => $referenceType->name,
            'prefix' => $referenceType->prefix,
        ];
    }
}
