<?php

namespace NeoClocking\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserRole Model
 *
 * @property integer $id
 * @property string $code
 * @property string $name
 * @property integer $priority
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserRole whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserRole whereIn($field, $list)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserRole whereCode($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserRole whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserRole wherePriority($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserRole whereUpdatedAt($value)
 */
class UserRole extends Model
{
    const CODE_MEMBER = 'member';

    const CODE_ASSISTANT = 'assistant';

    const CODE_MANAGER = 'manager';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_roles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'priority',
        'code'
    ];
}
