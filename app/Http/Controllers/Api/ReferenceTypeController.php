<?php

namespace NeoClocking\Http\Controllers\Api;

use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Repositories\ReferenceTypeRepository;
use NeoClocking\Transformers\ReferenceTypeTransformer;

/**
 * Class ReferenceTypeController
 */
class ReferenceTypeController extends Controller
{
    /**
     * @var ReferenceTypeRepository
     */
    protected $referenceTypeRepository;

    /**
     * @param ReferenceTypeRepository $referenceTypeRepository
     */
    public function __construct(ReferenceTypeRepository $referenceTypeRepository)
    {
        $this->referenceTypeRepository = $referenceTypeRepository;
    }

    /**
     * List all referenceTypes
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $referenceTypes = $this->referenceTypeRepository->findAll();

        return fractal()->collection($referenceTypes, ReferenceTypeTransformer::class, 'reference_types');
    }
}
