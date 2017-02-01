<?php

namespace NeoClocking\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use NeoClocking\Models\ReferenceType;

class ReferenceTypeRepository
{

    /**
     * @return ReferenceType[]
     */
    public function findAll()
    {
        return ReferenceType::all();
    }

    /**
     * @param integer $id Id of the reference type
     *
     * @return ReferenceType
     * @throws ModelNotFoundException
     */
    public function findById($id)
    {
        return ReferenceType::findOrFail($id);
    }

    /**
     * @param string $code
     *
     * @return ReferenceType
     * @throws ModelNotFoundException
     */
    public function findOneByCode($code)
    {
        return ReferenceType::where('code', $code)->firstOrFail();
    }
}
