<?php

namespace NeoClocking\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * NeoClocking\Models\ReferenceType
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property string $prefix
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\ReferenceType whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\ReferenceType whereCode($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\ReferenceType whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\ReferenceType wherePrefix($value)
 */
class ReferenceType extends Model
{
    const CODE_REDMINE = 'redmine';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'reference_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'prefix',
    ];
}
