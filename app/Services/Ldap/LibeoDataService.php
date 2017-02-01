<?php

namespace NeoClocking\Services\Ldap;

use Illuminate\Support\Facades\Config;
use Libeo\LDAP\LDAP;
use NeoClocking\Models\GenericData;
use NeoClocking\Utilities\KeyGenerator;

/**
 * Class which allows access to an external data source; currently via LDAP.
 */
class LibeoDataService
{
    /**
     * @var LDAP
     */
    private $ldap;

    /**
     * @var array
     */
    private static $users = [];

    /**
     * @var array
     */
    private static $workCategories;

    /**
     * @var array
     */
    private static $clients = [];

    /**
     * @var array
     */
    private static $projects = [];

    /**
     * Verify if php5-ldap is loaded
     */
    public function __construct()
    {
        if (!extension_loaded('ldap')) {
            throw new \Exception("PHP LDAP extension not loaded.");
        }
    }

    public function preloadUsers($activeOnly = true)
    {
        $wheres = [];

        if ($activeOnly) {
            $wheres[] = new LdapCondition('libeoStatus', '=', 'Active');
        }

        $usersData = $this->search('people', 'person', [], $wheres);

        foreach ($usersData as $user) {
            $uid = $user->get('uid');
            static::$users[(string)$uid] = $user;
        }
    }

    public function preloadClients()
    {
        $clients = $this->search(
            'customers',
            'libeoCustomer',
            ['uid', 'cn', 'libeoContactName', 'mail']
        );

        foreach ($clients as $client) {
            static::$clients[(string)$client->get('uid')] = $client;
        }
    }

    public function preloadProjects()
    {
        $projects = $this->search('projects', 'libeoProject', []);

        foreach ($projects as $project) {
            static::$projects[(string)$project->get('uid')] = $project;
        }
    }

    public function preloadWorkCategories()
    {
        static::$workCategories = $this->getWorkCategoriesData();
    }

    /**
     * @param string $username
     * @param bool $activeOnly
     *
     * @return GenericData
     */
    public function getUserData($username, $activeOnly = true)
    {
        $overrides = [
            'cn'        => 'full_name',
            'sn'        => 'last_name',
            'givenname' => 'first_name',
        ];

        if (array_key_exists($username, static::$users)) {
            $userData = $this->convertLdapObjectToModel(static::$users[$username], 'person', $overrides);

            $workSubCat = $userData->get('worksubcategory');
            if ($workSubCat !== null) {
                $userData->set('worksubcategory', $this->parseWorkCategoryDn($workSubCat));
            }

            return $userData;
        }

        $wheres = [];

        if ($activeOnly) {
            $wheres[] = new LdapCondition('libeoStatus', '=', 'Active');
        }

        $wheres[] = new LdapCondition('uid', '=', $username);

        $userData = $this->search('people', 'person', [], $wheres, true);
        $userData = $this->convertLdapObjectToModel($userData, 'person', $overrides);

        $workSubCat = $userData->get('worksubcategory');

        if ($workSubCat !== null) {
            $userData->set('worksubcategory', $this->parseWorkCategoryDn($workSubCat));
        }

        return $userData;
    }

    /**
     * @param $table
     * @param string $objectClass
     * @param array $fields
     * @param LdapCondition[] $wheres
     * @param bool $onlyFirstValue
     *
     * @return \Libeo\LDAP\Collection|\Libeo\LDAP\Model
     */
    private function search($table, $objectClass = "*", $fields = [], $wheres = [], $onlyFirstValue = false)
    {
        $this->connect();

        $query = $this->ldap->newQuery($table)
            ->select($fields);

        $query->where('objectClass', '=', $objectClass);

        if (!empty($wheres)) {
            foreach ($wheres as $where) {
                $query->andWhere(
                    $where->getAttribute(),
                    $where->getOperator(),
                    $where->getValue()
                );
            }
        }

        if ($onlyFirstValue) {
            return $query->getOne();
        }

        return $query->get();
    }

    /**
     * Instantiate LDAP connection.
     */
    private function connect()
    {
        if (!isset($this->ldap)) {
            $this->ldap = new LDAP(
                [
                    'hostname' => config('ldap.protocol') . '://' . config('ldap.host'),
                    'port'     => config('ldap.port'),
                    'base_dn'  => config('ldap.rdn'),
                    'username' => config('ldap.username_dn'),
                    'password' => config('ldap.password'),
                ]
            );
        }
    }

    /**
     * Remove the LDAP type prefix from keys
     * And return a generic object not reliant on LDAP
     *
     * @param \Libeo\LDAP\Model $dataObject
     * @param string            $type Type prefix to remove; not always needed.
     * @param array             $overrideKeys A list of keys to map differently
     *
     * @return GenericData
     */
    private function convertLdapObjectToModel($dataObject, $type = '', array $overrideKeys = [])
    {
        $dataArray = ($dataObject !== null) ? $dataObject->toArray() : [];
        $remappedArray = [];

        foreach ($dataArray as $key => $value) {
            if ($key && $key !== 'objectclass') {
                if (array_key_exists($key, $overrideKeys)) {
                    $newKey = $overrideKeys[$key];
                } else {
                    $newKey = preg_replace('/^libeo/', '', $key);
                    $newKey = preg_replace('/^'.$type.'/', '', $newKey);
                }
                if (is_array($value) && count($value) === 1) {
                    $value = $value[0];
                }
                $remappedArray[$newKey] = $value;
            }
        }
        
        return new GenericData($remappedArray);
    }

    /**
     * Extract category and grade data from a given WorkSubCategory DN
     *
     * @param String $workCatDN
     *
     * @return array
     */
    private function parseWorkCategoryDn($workCatDN)
    {
        $parts = $this->extractLdapDnParameters($workCatDN);
        return [
            'category' => $parts['libeoWorkCategoryName'],
            'grade'    => $parts['libeoWorkSubCategoryName'],
        ];
    }

    /**
     * @param string $dn
     *
     * @return string[]
     */
    private function extractLdapDnParameters($dn)
    {
        $data = [];

        foreach (explode(',', $dn) as $pair) {
            list($k, $v) = explode('=', $pair);
            $data[$k] = $v;
        }

        return $data;
    }

    /**
     * @param string $projectNumber
     *
     * @return GenericData
     */
    public function getProjectData($projectNumber)
    {
        if (array_key_exists($projectNumber, static::$projects)) {
            $projectData = static::$projects[$projectNumber];
        } else {
            $projectData = $this->getRawProjectData($projectNumber);
        }

        return $this->convertLdapObjectToModel($projectData, 'project');
    }

    /**
     * @param string $projectNumber
     * @return \Libeo\LDAP\Collection|\Libeo\LDAP\Model
     */
    private function getRawProjectData($projectNumber)
    {
        if (array_key_exists($projectNumber, static::$projects)) {
            return static::$projects[$projectNumber];
        }

        $wheres = [
            new LdapCondition('libeoProjectNumber', '=', $projectNumber),
        ];
        return $this->search('projects', 'libeoProject', [], $wheres, true);
    }

    /**
     * @param integer $clientNumber
     * @return GenericData
     */
    public function getClientData($clientNumber)
    {
        $attrRemap = [
            'uid' => 'number',
            'cn'  => 'name',
        ];

        if (array_key_exists($clientNumber, static::$clients)) {
            return $this->convertLdapObjectToModel(static::$clients[$clientNumber], 'customer', $attrRemap);
        }

        $wheres = [
            new LdapCondition('uid', '=', $clientNumber),
        ];

        $ldapData = $this->search(
            'customers',
            'libeoCustomer',
            ['uid', 'cn', 'libeoContactName', 'mail'],
            $wheres,
            true
        );
        return $this->convertLdapObjectToModel($ldapData, 'customer', $attrRemap);
    }

    /**
     * @param $workCatModel array
     * @return int|mixed|null
     */
    public function getCostForWorkCategory($workCatModel)
    {
        $workCatData = $this->getWorkCategoriesData();
        foreach ($workCatData as $item) {
            $category = $item->get('category');
            $grade = $item->get('grade');
            if ($category === $workCatModel['category'] && $grade === $workCatModel['grade']) {
                return $item->get('cost') * 100;
            }
        }
        return 0;
    }

    /**
     * @return GenericData[]
     */
    public function getWorkCategoriesData()
    {
        if (static::$workCategories !== null) {
            return static::$workCategories;
        }

        $data = [];
        $ldapData = $this->search('workcategories', 'libeoWorkSubCategory');
        foreach ($ldapData as $singleLdapData) {
            $catData = $this->parseWorkCategoryDn($singleLdapData->getDN());
            $catData['cost'] = $singleLdapData->get('libeoworksubcategorycost');
            $data[] = new GenericData($catData);
        }
        return $data;
    }

    /**
     * @param string $projectNumber
     * @return integer
     */
    public function getClientNumberForProject($projectNumber)
    {
        if (array_key_exists($projectNumber, static::$projects)) {
            $projectData = static::$projects[$projectNumber];
        } else {
            $wheres = [
                new LdapCondition('libeoProjectNumber', '=', $projectNumber),
            ];

            $projectData = $this->search('projects', 'libeoProject', ['libeoProjectCustomer'], $wheres, true);
        }

        if (isset($projectData)) {
            $clientDn = $projectData->get('libeoprojectcustomer');
            return (integer)$this->extractLdapDnParameters($clientDn)['uid'];
        }
        return 0;
    }

    /**
     * @param string $username
     * @return string[] List of project numbers
     */
    public function getProjectsForUser($username)
    {
        $this->connect();

        $usernameDn = "uid={$username},ou=people,dc=libeo,dc=com";
        $projectsData = $this->ldap
            ->newQuery('projects')
            ->select([
                'libeoProjectNumber',
            ])->orWhere("libeoProjectMember", "=", $usernameDn)
            ->addWhere("libeoProjectManagerAssistant", "=", $usernameDn)
            ->addWhere("libeoProjectManager", "=", $usernameDn)
            ->andWhere('objectClass', '=', 'libeoProject')->get();

        $data = [];
        foreach ($projectsData as $projectData) {
            $data[] = $projectData->get('number');
        }
        return $data;
    }

    /**
     * @param string $projectNumber
     * @return array|bool
     */
    public function getRolesForProject($projectNumber)
    {
        $projectData = $this->getRawProjectData($projectNumber);

        if (!isset($projectData)) {
            return false;
        }

        $rolesData = [
            'manager'          => [],
            'managerassistant' => [],
            'member'           => [],
        ];

        foreach ($rolesData as $roleIndex => $roleData) {
            $roleMembers = $projectData->get('libeoproject' . $roleIndex);
            if (isset($roleMembers)) {
                if (!is_array($roleMembers)) {
                    $roleMembers = [$roleMembers];
                }
                foreach ($roleMembers as $key => $roleMemberDn) {
                    $roleMembers[$key] = $this->extractLdapDnParameters($roleMemberDn)['uid'];
                }
                $rolesData[$roleIndex] = $roleMembers;
            }
        }
        return $rolesData;
    }
}
