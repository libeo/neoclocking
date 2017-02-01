<?php

namespace NeoClocking\Services\Updaters;

use Carbon\Carbon;
use DB;
use NeoClocking\Models\Client;
use NeoClocking\Models\Project;
use NeoClocking\Repositories\ProjectRepository;
use NeoClocking\Services\Ldap\LibeoDataService;

/**
 * A utility to help update or create a project
 *
 * Class ProjectUpdater
 *
 * @package Libeo\NeoClocking\Libraries\Updaters
 */
class ProjectUpdater extends BaseUpdater
{

    /**
     * @var Project
     */
    protected $model;

    /**
     * @var ProjectRepository
     */
    protected $projectRepository;

    /**
     * @var LibeoDataService
     */
    protected $dataService;

    /**
     * Get a project with the given number or instantiate a new one
     *
     * @param array             $defaultAttributeValues
     * @param ProjectRepository $projectRepository
     * @param LibeoDataService  $dataService
     * @internal param $projectNumber
     */
    public function __construct(
        array $defaultAttributeValues,
        ProjectRepository $projectRepository,
        LibeoDataService $dataService
    ) {
        parent::__construct($defaultAttributeValues);

        $this->projectRepository = $projectRepository;
        $this->dataService = $dataService;

        $this->preload();
    }

    protected function preload()
    {
        if (!array_key_exists('id', $this->data) && array_key_exists('number', $this->data)) {
            $data = DB::table('projects')->select('*')->where('number', $this->data['number'])->first();

            if ($data !== null) {
                $this->data = (array)$data;
            }
        }
    }

    /**
     * Get all data for a given project number and update
     *
     * @return bool
     */
    public function update()
    {
        $projectNumber = $this->data['number'];

        $clientId = $this->getClientId($projectNumber);
        if ($clientId === null) {
            return false;
        }

        $projectData = $this->dataService->getProjectData($projectNumber);

        if ($projectData->isEmpty()) {
            return false;
        }

        $projectStatus = $projectData->get('status');

        $this->updateData([
            'active' => $projectStatus === 'Active',
            'name' => $projectData->get('name'),
            'require_comments' => $this->getRequireComments($projectData),
            'max_time' => $this->getMaxTime($projectData),
            'client_id' => $clientId,
        ]);

        if (array_key_exists('id', $this->data)) {
            return DB::table('projects')->where('id', $this->data['id'])->update($this->data);
        }

        return DB::table('projects')->insert($this->data);
    }

    /**
     * @param \NeoClocking\Models\GenericData $projectData
     * @return bool
     */
    private function getRequireComments($projectData)
    {
        $requireComments = false;
        $projectTypeRaw = $projectData->get('type');
        if ($projectTypeRaw) {
            switch ($projectTypeRaw) {
                case "À l'heure":
                case "Banque d'heures":
                    $requireComments = true;
                    break;
                default:
                    break;
            }
        }
        return $requireComments;
    }

    /**
     * @param \NeoClocking\Models\GenericData $projectData
     * @return integer
     */
    private function getMaxTime($projectData)
    {
        $maxTime = 0;
        $projectEstimateRaw = $projectData->get('estimate');
        if ($projectEstimateRaw) {
            $maxTime = (int)((float)$projectEstimateRaw * 60);
        }
        return $maxTime;
    }

    /**
     * @param string $projectNumber
     * @return integer|null
     */
    private function getClientId($projectNumber)
    {
        $clientNumber = app(LibeoDataService::class)->getClientNumberForProject($projectNumber);
        /** @var Client $client */
        $client = DB::table('clients')->where('number', $clientNumber)->first(['id']);

        if (isset($client)) {
            return $client->id;
        }
        return null;
    }
}
