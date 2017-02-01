<?php

namespace NeoClocking\Http\Controllers\Api;

use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Repositories\ResourceTypeRepository;
use NeoClocking\Transformers\ResourceTypeTransformer;

/**
 * Class ResourceTypeController
 */
class ResourceTypeController extends Controller
{
    /**
     * @var ResourceTypeRepository
     */
    protected $resourceTypeRepository;

    /**
     * ResourceTypeController constructor.
     * @param ResourceTypeRepository $resourceTypeRepository
     */
    public function __construct(ResourceTypeRepository $resourceTypeRepository)
    {
        $this->resourceTypeRepository = $resourceTypeRepository;
    }

    /**
     * List all resourceTypes
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $resourceTypes = $this->resourceTypeRepository->findAll();

        return fractal()->collection($resourceTypes, ResourceTypeTransformer::class, 'resource_types');
    }
}
