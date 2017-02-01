<?php

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Artisan;
use NeoClocking\Models\Client;
use NeoClocking\Models\Task;
use NeoClocking\Models\User;

class SearchTest extends TestCase
{
    use DatabaseTransactions;
    use DatabaseMigrations;
    // use WithoutMiddleware;

    protected $baseUrl = 'http://neoclocking.local';
    private $user;

    public static function setUpBeforeClass()
    {
    }

    public function setUp()
    {
        parent::setUp();
        $this->runDatabaseMigrations();
        $this->seed('SearchSeeder');
        $this->artisan('neoclocking:buildSearchIndex');
        $this->user = User::first();
        $this->headerAuthorization = ['X-Authorization' => $this->user->api_key];
        // $this->actingAs($this->user);
    }

    private function responseData()
    {
        $response = json_decode($this->response->getContent(), true);
        return $response['data'];
    }

    /**
     * @group search
     */
    public function testSearchClientName()
    {
        // 2 tâches reliées au client
        $this
            ->get('/api/search/Walmart', $this->headerAuthorization)
            ->seeJson(['name' => 'Ajout de produit dans magasin en ligne Walmart'])
            ->seeJson(['name' => 'Refactor le tout en Stylus'])
        ;
    }

    /**
     * @group search
     */
    public function testSearchProjetName()
    {
        // 1 tâche relié au projet
        $this
            ->get('/api/search/Construction de super slides', $this->headerAuthorization)
            ->seeJson(['name' => 'Évaluer comment ca marche'])
        ;
        $this->assertCount(1, $this->responseData());
    }

    /**
     * @group search
     */
    public function testSearchTaskName()
    {
        $this
            ->get('/api/search/Stylus', $this->headerAuthorization)
            ->seeJson(['name' => 'Refactor le tout en Stylus'])
        ;
        $this->assertCount(1, $this->responseData());
    }

    /**
     * @group search
     */
    public function testSearchTaskID()
    {
        $this
            ->get('/api/search/4444444', $this->headerAuthorization)
            ->seeJson(['name' => 'Évaluer comment ca marche'])
        ;
        $this->assertCount(1, $this->responseData());
    }
}
