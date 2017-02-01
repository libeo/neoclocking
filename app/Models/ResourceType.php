<?php

namespace NeoClocking\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * NeoClocking\Models\ResourceType
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\ResourceType whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\ResourceType whereCode($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\ResourceType whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\ResourceType whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\ResourceType whereUpdatedAt($value)
 */
class ResourceType extends Model
{
    const CODE_OTHER = 'autre';
    const IDS_SHOULD_NOT_USE = [1];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'resource_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Get the children.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(ResourceType::class, 'parent_id', 'id');
    }
}
