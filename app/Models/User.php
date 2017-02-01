<?php

namespace NeoClocking\Models;

use Caffeinated\Presenter\Traits\PresentableTrait;
use Caffeinated\Presenter\Contracts\Presentable as PresentableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

use NeoClocking\Services\UserService;
use NeoClocking\Utilities\KeyGenerator;

/**
 * Class User Model
 *
 * @property integer $id
 * @property string $username
 * @property string $mail
 * @property boolean $active
 * @property string $first_name
 * @property string $last_name
 * @property integer $week_duration
 * @property integer $hourly_cost
 * @property string $api_key
 * @property string $remember_token
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $clocking_id
 * @property-read \Illuminate\Database\Eloquent\Collection|LogEntry[] $logEntries
 * @property-read \Illuminate\Database\Eloquent\Collection|Task[] $favouriteTasks
 * @property User|null $originalUser
 * @property-read LogEntry $ongoingTask
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User firstOrNew($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereMail($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereFirstName($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereLastName($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereWeekDuration($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereHourlyCost($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereApiKey($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereRememberToken($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\User whereClockingId($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|UserProjectRole[] $projectRoles
 * @property-read \Illuminate\Database\Eloquent\Collection|Project[] $projects
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract, PresentableContract
{
    use Authenticatable, Authorizable, PresentableTrait;

    protected $presenter = 'NeoClocking\Presenters\UserPresenter';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    protected $hidden = [
        'password',
        'api_key',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'original_user',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'mail',
        'active',
        'first_name',
        'last_name',
        'week_duration',
        'hourly_cost',
        'api_key',
        'clocking_id',
    ];

    protected $service;

    /**
     * Set a new API key for this user
     */
    public function applyNewApiKey()
    {
        $this->attributes['api_key'] = KeyGenerator::generateRandomKey();
    }

    /**
     * Get the related log entries.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logEntries()
    {
        return $this->hasMany(LogEntry::class);
    }

    /**
     * A User may have multiple Tasks as favourite
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function favouriteTasks()
    {
        return $this->belongsToMany(Task::class, 'favourite_tasks', 'user_id', 'task_id');
    }

    public function addFavouriteTask(Task $task)
    {
        if (! $this->favouriteTasks()->whereTaskId($task->id)->count()) {
            $this->favouriteTasks()->attach($task);
        }
    }

    public function removeFavouriteTasks(Task $task)
    {
        $this->favouriteTasks()->detach($task);
    }

    public function projects($id = null)
    {
        if ($this->canClockAnyProject()) {
            $query = Project::query();
            if ($id) {
                $query->whereId($id);
            }
        } else {
            $query= $this->belongsToMany(Project::class, 'user_project_roles')
                        ->withTimestamps()
                        ->withPivot(['id', 'user_role_id']);
            if ($id) {
                $query->whereProjectId($id);
            }
        }
        return $query;
    }

    public function projectRoles()
    {
        return $this->hasMany(UserProjectRole::class);
    }

    /**
     * Get the related permissions
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function permissions()
    {
        return $this->hasMany(UserPermission::class);
    }


    //-----------------------------------------
    // Getters and Setters to access attributes
    //-----------------------------------------

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getAttributeFromArray('active');
    }

    public function setOriginalUserAttribute(User $user = null)
    {
        if ($user) {
            $user = $user->toArray();
        }
        return session(['originalUser' => $user]);
    }

    public function getOriginalUserAttribute()
    {
        $originalUser = session('originalUser');
        if ($originalUser) {
            $originalUser = User::whereId($originalUser['id'])->first();

            if ($originalUser->id == $this->id) {
                $originalUser = null;
            }
        }
        return $originalUser;
    }

    /**
     * @return LogEntry
     */
    protected function getOngoingTaskAttribute()
    {
        return $this->logEntries()->whereNull('ended_at')->first();
    }

    /**
     * Get the full name of the user.
     *
     * @return string
     * @throws \Caffeinated\Presenter\Exceptions\PresenterException
     */
    protected function getFullNameAttribute()
    {
        return $this->present()->fullName;
    }

    /**
     * Return a Gravatar Url for the user email
     * @return string
     */
    public function gravatar()
    {
        $hash = md5(strtolower($this->mail));
        return "https://www.gravatar.com/avatar/" . $hash.'?d=mm';
    }

    /**
     * Check if a given task has been added to the current user's favourites
     *
     * @param Task $task
     * @return bool
     */
    public function hasFavourited(Task $task)
    {
        $taskId = $task->id;
        return $this->favouriteTasks->contains(function ($key, $task) use ($taskId) {
            return $task->id == $taskId;
        });
    }

    /**
     * @return UserService
     */
    public function getService()
    {
        if (!isset($this->service)) {
            $this->service = app(UserService::class, [$this]);
        }
        return $this->service;
    }

    public function canControlUsers()
    {
        return $this->hasPermissionTo(UserPermission::CONTROL_USERS);
    }

    public function canClockAnyProject()
    {
        return true;
    }

    public function canClockOutsideTimeWindow()
    {
        return $this->hasPermissionTo(UserPermission::CLOCK_OUTSIDE_TIME_WINDOW);
    }

    public function canManageAnyProject()
    {
        return $this->hasPermissionTo(UserPermission::MANAGE_ANY_PROJECT, false);
    }

    protected function getMergedPermissions($recursive)
    {
        $permissions = $this->permissions->lists('name')->toArray();
        if ($this->originalUser && $recursive) {
            $originalPermissions = UserPermission::whereUserId($this->originalUser['id'])->lists('name')->toArray();
            $permissions = array_filter(array_merge($permissions, $originalPermissions));
        }
        return $permissions;
    }

    public function hasPermissionTo($permissionToValidate, $recursive = true)
    {
        $permissions = $this->getMergedPermissions($recursive);
        return in_array($permissionToValidate, $permissions);
    }
}
