<?php

namespace NeoClocking\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\SearchIndex\Searchable;

/**
 * NeoClocking\Models\Client
 *
 * @property integer $id
 * @property integer $number
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $clocking_id
 * @property-read \Illuminate\Database\Eloquent\Collection|Project[] $projects
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Client whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Client firstOrNew($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Client whereNumber($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Client whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Client whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Client whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Client whereClockingId($value)
 */
class Client extends Model implements Searchable
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'clients';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number',
        'name',
        'clocking_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'clocking_id',
    ];

    /**
     * Get related projects.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch configuration
    |--------------------------------------------------------------------------
    /**
     * Returns an array with properties which must be indexed
     *
     * @return array
     */
    public function getSearchableBody()
    {
        $searchableProperties = [
            'client_name'      => $this->name,
            'client_name.folded'      => $this->name,
        ];

        return $searchableProperties;
    }

    /**
     * Return the type of the searchable subject
     *
     * @return string
     */
    public function getSearchableType()
    {
        return 'client';
    }

    /**
     * Return the id of the searchable subject
     *
     * @return string
     */
    public function getSearchableId()
    {
        return $this->id;
    }
}
