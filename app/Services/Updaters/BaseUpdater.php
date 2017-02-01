<?php

namespace NeoClocking\Services\Updaters;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BaseUpdater
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * A list of attributes that can't be null
     *
     * @var array
     */
    protected $nonNullableFields = [];

    public function __construct(array $defaultAttributeValues = [])
    {
        $this->nonNullableFields[] = 'created_at';
        $this->data = array_merge(
            [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            $defaultAttributeValues
        );
    }

    /**
     * Force updates to be allowed on all fields
     * And update the given model with data from array
     */
    protected function forceUpdate()
    {
        if (!isset($this->model)) {
            return false;
        }

        $data = $this->removeForbiddenNullValues();
        \Eloquent::unguard();
        $this->model->fill($data);
        \Eloquent::reguard();
    }

    /**
     * Remove fields that are null and in the list of non nullable values
     *
     * @return array
     */
    private function removeForbiddenNullValues()
    {
        $data = $this->data;
        foreach ($this->nonNullableFields as $nonNullableField) {
            if (array_key_exists($nonNullableField, $data) && is_null($data[$nonNullableField])) {
                unset($data[$nonNullableField]);
            }
        }
        return $data;
    }

    /**
     * @param array $newData
     */
    protected function updateData($newData)
    {
        $this->data = array_merge($this->data, $newData);
    }
}
