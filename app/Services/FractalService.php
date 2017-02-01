<?php

namespace NeoClocking\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use IteratorAggregate;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item as FractalItem;
use League\Fractal\TransformerAbstract;

class FractalService extends Response
{
    /**
     * @var Collection|Model
     */
    protected $items;

    /**
     * @var TransformerAbstract
     */
    protected $transformer;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $includes;

    public function collection($collection, $transformer = null, $collectionName = null, array $includes = null)
    {
        $this->items = $collection;
        $this->name = $collectionName;
        $this->includes = $includes;
        $this->transformed($transformer);

        $this->compile();

        return $this;
    }

    public function item($model, $transformer = null, $itemName = null, array $includes = null)
    {
        $this->items = $model;
        $this->name = $itemName;
        $this->includes = $includes;
        $this->transformed($transformer);

        $this->compile();

        return $this;
    }

    protected function transformed($transformer)
    {
        if (is_string($transformer)) {
            $this->transformer = app($transformer);
        } elseif ($transformer instanceof TransformerAbstract) {
            $this->transformer = $transformer;
        }
    }

    public function compile()
    {
        /** @var Manager $manager */
        $manager = app(Manager::class);

        $serializer = config('api.serializer', DataArraySerializer::class);
        $manager->setSerializer(new $serializer);

        $resources = $this->getResources();

        if (is_array($this->includes)) {
            $manager->parseIncludes($this->includes);
        }

        $this->setContent($manager->createData($resources)->toArray());
    }

    protected function getResources()
    {
        if ($this->items === null) {
            return app(FractalCollection::class, [[]]);
        }

        if ($this->items instanceof IteratorAggregate) {
            return new FractalCollection($this->items, $this->transformer, $this->name);
        }

        return new FractalItem($this->items, $this->transformer, $this->name);
    }
}
