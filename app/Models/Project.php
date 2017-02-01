<?php

namespace NeoClocking\Models;

use Caffeinated\Presenter\Traits\PresentableTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\SearchIndex\Searchable;
use NeoClocking\Presenters\ProjectPresenter;

/**
 * Class Project
 *
 * @property integer $id
 * @property string $number
 * @property string $name
 * @property integer $client_id
 * @property boolean $active
 * @property integer $max_time
 * @property integer $allocated_time
 * @property integer $unplanned_hours
 * @property integer $warranty_hours
 * @property string $type
 * @property boolean $require_comments
 * @property \Carbon\Carbon $production_end_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $clocking_id
 * @property-read \Illuminate\Database\Eloquent\Collection|Task[] $tasks
 * @property-read Client $client
 * @property-read \Illuminate\Database\Eloquent\Collection|Milestone[] $milestones
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project firstOrNew($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project findOrFail($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereNumber($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereClientId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereMaxTime($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereRequireComments($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project whereClockingId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project active()
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Project canBeViewedBy(User $user)
 */
class Project extends Model implements Searchable
{
    use PresentableTrait;

    protected $presenter = ProjectPresenter::class;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projects';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'number',
        'client_id',
        'active',
        'max_time',
        'allocated_time',
        'require_comments',
        'clocking_id',
        'type',
    ];

    protected $hidden = [
        'clocking_id'
    ];

    protected $appends = [
        'allowed_time',
        'logged_time',
        'remaining_time',
    ];

    protected $dates = ['production_end_date'];

    /**
     * Get the related tasks.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the related client.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the related milestones.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function milestones()
    {
        return $this->hasMany(Milestone::class);
    }

    /**
     * Get the related users' roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function userProjectRoles()
    {
        return $this->hasMany(UserProjectRole::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_project_roles')->withTimestamps()->withPivot(['user_role_id']);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->getAttributeFromArray('active');
    }


    public function scopeActive($query)
    {
        return $query->whereActive(true);
    }

    public function scopeCanBeViewedBy($query, User $user)
    {
        if (!$user->canClockAnyProject() && !$user->canManageAnyProject()) {
            $query
                ->join('user_project_roles', 'projects.id', '=', 'user_project_roles.project_id')
                ->where('user_project_roles.user_id', '=', $user->id);
        }
        /*return $query->join('')
            ->where('user_project_roles.user_id', '=', $user->id);*/
        return $this->scopeActive($query);
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
            'project_name'    => $this->name,
            'project_name.folded'    => $this->name,
            'project_number'  => $this->number,
            'project_number_name_client' => $this->name.' '.$this->number.' '.$this->client->name,
            'project_client_name' => $this->client->name,
            'project_client_name.folded' => $this->client->name,
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
        return 'project';
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

    public function getShouldNotExceedAttribute()
    {
        return $this->type == 'Banque d\'heures';
    }

    protected function getAllowedTimeAttribute()
    {
        $totalAllottedTime = 0;

        foreach ($this->tasks as $task) {
            if ($task->revised_estimation) {
                $totalAllottedTime += $task->revised_estimation;
            } else {
                $totalAllottedTime += $task->estimation;
            }
        }

        return $totalAllottedTime;
    }

    protected function getLoggedTimeAttribute()
    {
        return $this->tasks()->sum('logged_time');
    }

    protected function getRemainingTimeAttribute()
    {
        return $this->max_time - $this->tasks()->sum('logged_time');
    }
}
