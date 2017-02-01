<?php

namespace NeoClocking\Jobs;

use NeoClocking\Jobs\Job;
use Illuminate\Contracts\Bus\SelfHandling;
use NeoClocking\Models\Client;
use NeoClocking\Services\LibeoDap\Request;
use NeoClocking\Services\LibeoDap\Response;

class SyncClient extends Job implements SelfHandling
{
    /**
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
     * Update client with data from LibeoDap.
     *
     * @param  Response $response
     * @return void
     */
    protected function updateClient(Response $response)
    {
        $client = $this->client;

        $client->number = $response->customer_number;
        $client->name = $response->name;
        $client->save();
    }
}
