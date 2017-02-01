<?php

namespace NeoClocking\Models;

use Illuminate\Database\Eloquent\Model;

class UserPermission extends Model
{
    const CONTROL_USERS = 'control_users';
    const CLOCK_ANY_PROJECT = 'clock_any_project';
    const MANAGE_ANY_PROJECT = 'manage_all_projects';
    const CLOCK_OUTSIDE_TIME_WINDOW = 'clock_outside_time_window';
    const LIBEO_DAP_SYNC = 'libeodap';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users_permissions';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'name',
    ];

    /**
     * Get related user.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
