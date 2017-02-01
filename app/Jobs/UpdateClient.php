<?php

namespace NeoClocking\Jobs;

use Illuminate\Contracts\Bus\SelfHandling;
use NeoClocking\Models\Client;
use NeoClocking\Services\LibeoDap\Request;
use NeoClocking\Services\LibeoDap\Response;

class UpdateClient extends Job implements SelfHandling
{
    /**
     * The client instance.
     *
     * @var Client
     */
    protected $client;

    /**
     * Create a new job instance.
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Execute the job.
     *
     * @param Request $request
     */
    public function handle(Request $request)
    {
        $data = $request->execute('/customers/' . $this->client->number);

        $this->updateClient($data);
    }

    /**
     * Update the client with the data from LibeoDap.
     *
     * @param Response $response
     */
    protected function updateClient(Response $response)
    {
        $client = $this->client;

        $client->name = $response->name;
        $client->save();
    }
}
