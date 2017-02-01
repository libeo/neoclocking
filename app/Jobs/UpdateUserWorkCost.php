<?php

namespace NeoClocking\Jobs;

use Illuminate\Support\Collection;
use Illuminate\Contracts\Bus\SelfHandling;
use NeoClocking\Models\User;
use NeoClocking\Services\LibeoDap\Request;

class UpdateUserWorkCost extends Job implements SelfHandling
{
    /**
     * The user instance.
     *
     * @var User
     */
    protected $user;

    /**
     * The categories of the user.
     *
     * @var array
     */
    protected $categories;

    /**
     * Create a new job instance.
     *
     * @param User  $user
     * @param array $categories
     */
    public function __construct(User $user, array $categories)
    {
        $this->user = $user;
        $this->categories = $categories;
    }

    /**
     * Execute the job.
     *
     * @param Request $request
     */
    public function handle(Request $request)
    {
        $data = $request->execute('/work_categories/' . $this->categories['CategoryName']);

        $subCategory = $this->getSubCategory(collect($data->sub_categories));
        $this->updateUserCost($subCategory);
    }

    /**
     * Get the sub-category in which the user is.
     *
     * @param  Collection $categories
     * @return array
     */
    protected function getSubCategory(Collection $categories)
    {
        $subCategories = $categories->where('name', $this->categories['SubCategoryName']);

        return $subCategories->first();
    }

    /**
     * Update the user's hourly cost.
     *
     * @param array $category
     */
    protected function updateUserCost(array $category)
    {
        $user = $this->user;

        $user->hourly_cost = (int)$category['cost'] * 100;
        $user->save();
    }
}
