<?php

namespace NeoClocking\Jobs;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Contracts\Bus\SelfHandling;
use NeoClocking\Models\User;
use NeoClocking\Services\LibeoDap\Request;
use NeoClocking\Services\LibeoDap\Response;

class SyncUser extends Job implements SelfHandling
{
    use DispatchesJobs;

    /**
     * The instance of user.
     *
     * @var User
     */
    protected $user;

    /**
     * Create a new job instance.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @param Request $request
     */
    public function handle(Request $request)
    {
        $data = $request->execute('/users/' . $this->user->username);

        $this->updateUser($data);
        $this->updatePermissions();
        $this->updateUserWorkCost($data->work_sub_category);
    }

    /**
     * Update user with data from LibeoDap.
     *
     * @param Response $data
     */
    protected function updateUser($data)
    {
        $user = $this->user;

        $user->first_name = $data->first_name;
        $user->last_name = $data->last_name;
        $user->mail = $data->contact_email;
        $user->week_duration = $data->working_time * 60;
        $user->active = ($data->status === 'Active');

        if (!$user->exists) {
            $user->hourly_cost = 0;
            $user->applyNewApiKey();
        }

        $user->save();
    }

    /**
     * Dispatch an update user's permissions job.
     *
     * @return void
     */
    protected function updatePermissions()
    {
        $this->dispatch(new UpdateUserPermissions($this->user));
    }

    /**
     * Update the user's hourly cost by dispatching a new job.
     *
     * @param  string $workCategories
     * @return void
     */
    protected function updateUserWorkCost($workCategories)
    {
        $categories = $this->getWorkCategories($workCategories);

        $this->dispatch(new UpdateUserWorkCost($this->user, $categories));
    }

    /**
     * Get the category and sub-category in the user's work categories.
     *
     * @param  string $dn
     * @return array
     */
    protected function getWorkCategories($dn)
    {
        preg_match_all('/libeoWork(?P<categories>(Sub)?CategoryName)=(?P<values>[^,]+)/', $dn, $matches);

        return array_combine($matches['categories'], $matches['values']);
    }
}
