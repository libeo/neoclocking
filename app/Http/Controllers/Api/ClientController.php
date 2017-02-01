<?php

namespace NeoClocking\Http\Controllers\Api;

use NeoClocking\Http\Controllers\Controller;
use NeoClocking\Repositories\ClientRepository;
use NeoClocking\Repositories\ProjectRepository;
use NeoClocking\Transformers\ClientTransformer;
use NeoClocking\Transformers\ProjectTransformer;

/**
 * Class ClientController
 */
class ClientController extends Controller
{
    /**
     * @var ClientRepository
     */
    protected $clientRepository;

    /**
     * @var ProjectRepository
     */
    protected $projectRepository;

    /**
     * @param ClientRepository  $clients
     * @param ProjectRepository $projects
     */
    public function __construct(ClientRepository $clients, ProjectRepository $projects)
    {
        $this->clientRepository = $clients;
        $this->projectRepository = $projects;
    }

    /**
     * List all clients
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $clients = $this->clientRepository->findAll();

        return fractal()->collection($clients, ClientTransformer::class, 'clients');
    }

    /**
     * Display a client
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $client = $this->clientRepository->findById($id);

        return fractal()->item($client, ClientTransformer::class, 'client');
    }

    /**
     * List all projects for a client
     *
     * @param int $id Client ID
     * @return \Illuminate\Http\Response
     */
    public function listProjects($id)
    {
        $client = $this->clientRepository->findById($id);
        $projects = $this->projectRepository->findByClient($client);

        return fractal()->collection($projects, ProjectTransformer::class, 'projects');
    }
}
