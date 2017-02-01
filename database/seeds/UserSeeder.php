<?php

use Illuminate\Database\Seeder;
use NeoClocking\Models\User;
use NeoClocking\Repositories\UserRepository;
use NeoClocking\Utilities\KeyGenerator;

class UserSeeder extends Seeder
{
    /** @var UserRepository */
    protected $userRepo;

    public function __construct()
    {
        $this->userRepo = app(UserRepository::class);
    }


    public function run()
    {
        $this->generateTestUser('test', 'test', 'test', 'test@exemple.com', 'testrandompasswordisrandom');
        $this->generateTestUser('jsopel', 'Jesse', 'Sopel', 'jesse.sopel@libeo.com');
        $this->generateTestUser('marcabm', 'Marc-Antoine', 'Bouchard Marceau', 'marc-antoine.bm@libeo.com');
        $this->generateTestUser('schouinard', 'Stéphane', 'Chouinard', 'stephane.chouinard@libeo.com');
        $this->generateTestUser('fcapovilla', 'Frédérick', 'Capovilla', 'frederick.capovilla@libeo.com');
        $this->generateTestUser('mtremblay', 'Michel', 'Tremblay', 'michel.tremblay@libeo.com');
        $this->generateTestUser('libeodap', 'LibéoDap', 'Dap', 'no-reply@libeo.com');
        $this->generateTestUser('vtalbot', 'Vincent', 'Talbot', 'vincent.talbot@libeo.com');
        $this->generateTestUser('asylvestre', 'Arthur', 'Sylvestre', 'arthur.sylvestre@libeo.com');
    }

    /**
     * @param string $username
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string|null $apiKey
     * @return User
     */
    private function generateTestUser($username, $firstName, $lastName, $email, $apiKey = null)
    {
        if ($apiKey === null) {
            $apiKey = KeyGenerator::generateRandomKey();
        }
        // Not using repo since User's are normally created via LDAP import
        $user = new User(
            [
                'username'      => $username,
                'first_name'    => $firstName,
                'last_name'     => $lastName,
                'hourly_cost'   => (20 * 100),
                'week_duration' => (37.5 * 60),
                'api_key'       => $apiKey,
                'mail'          => $email,
                'active'        => true,
            ]
        );

        $this->userRepo->saveOrUpdate($user);
    }
}
