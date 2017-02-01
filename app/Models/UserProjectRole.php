<?php

namespace NeoClocking\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class UserProjectRole Model
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $project_id
 * @property integer $user_role_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read Project $project
 * @property-read UserRole $userRole
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserProjectRole whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserProjectRole whereIn(string $field, array $values)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserProjectRole whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserProjectRole whereProjectId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserProjectRole whereUserRoleId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserProjectRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\UserProjectRole whereUpdatedAt($value)
 */
class UserProjectRole extends Model
{
    protected $fillable = [
        'user_role_id',
        'user_id',
        'project_id',
    ];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_project_roles';

    /**
     * Get the related user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related project.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the related role.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function userRole()
    {
        return $this->belongsTo(UserRole::class);
    }
}
