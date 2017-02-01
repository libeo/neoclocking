<?php

namespace NeoClocking\Models;

use Caffeinated\Presenter\Traits\PresentableTrait;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use NeoClocking\Presenters\TaskPresenter;
use NeoClocking\Utilities\LogEntryUpdateDatetimeWindow;
use NeoClocking\Utilities\TimeFormater;
use Spatie\SearchIndex\Searchable;
use SearchIndex;

use NeoClocking\Exceptions\ModelOperationDeniedException;

/**
 * NeoClocking\Models\Task
 *
 * @property integer $id
 * @property integer $number
 * @property string $name
 * @property integer $project_id
 * @property boolean $active
 * @property integer $resource_type_id
 * @property integer $reference_type_id
 * @property string $reference_number
 * @property integer $estimation
 * @property integer|null $revised_estimation
 * @property boolean $require_comments
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property integer $clocking_id
 * @property integer $milestone_id
 * @property ResourceType $resourceType
 * @property ReferenceType $referenceType
 * @property Project $project
 * @property Milestone $milestone
 * @property \Illuminate\Database\Eloquent\Collection|LogEntry[] $logEntries
 * @property integer $logged_time
 * @property bool $can_clock
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task firstOrNew($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereNumber($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereName($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereProjectId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereActive($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereResourceTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereReferenceTypeId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereReferenceNumber($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereEstimation($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereRevisedEstimation($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereRequireComments($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereClockingId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereMilestoneId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\Task whereClockingIdAndNumber($value, $value)
 */
class Task extends Model implements Searchable
{
    use PresentableTrait, SoftDeletes;

    protected $presenter = TaskPresenter::class;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number',
        'name',
        'task_id',
        'project_id',
        'active',
        'resource_type_id',
        'reference_type_id',
        'reference_number',
        'estimation',
        'revised_estimation',
        'require_comments',
        'clocking_id',
        'milestone_id',
        'logged_time',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'can_clock' => 'bool',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'can_clock'
    ];

    protected $dates = [
        'deleted_at',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::deleting(function (Task $task) {
            if (count($task->logEntries) > 0) {
                throw new ModelOperationDeniedException('Impossible de supprimer une tâche ayant des logs');
            }
            if (LiveLogEntry::whereTaskId($task->id)->first()) {
                throw new ModelOperationDeniedException('Impossible de supprimer une tâche.'
                    .' Quelqu\'un est en train de live-clocker dedans');
            }

            $task->usersFavourite()->sync([], true);
        });

        static::saving(function (Task $task) {
            $results = DB::select(
                'SELECT TRUNC(EXTRACT(EPOCH FROM SUM(l.ended_at - l.started_at))/60) as minutes
                 FROM log_entries l
                 WHERE l.task_id = :id',
                ['id' => $task->id]
            );

            $user = user();

            $task->logged_time = (int) $results[0]->minutes;
            if ($task->isDirty('project_id')
                && self::hasLogsEntriesOutsideWindow($task)
                && (!$user || !$user->canClockOutsideTimeWindow())
            ) {
                $message = 'Impossible de déplacer une tâche car certains temps'
                    .' sont hors de la période alouée de modification.';
                throw new ModelOperationDeniedException($message);
            }

            if ($task->isDirty('resource_type_id')
                && !self::$unguarded
                && in_array((int)$task->resource_type_id, ResourceType::IDS_SHOULD_NOT_USE)
            ) {
                $resource = ResourceType::find($task->resource_type_id);
                $name = $task->resource_type_id;
                if ($resource) {
                    $name = $resource->name;
                }
                $message = 'Il est interdit d\'utiliser la type de resource "'.$name.'".';
                throw new ModelOperationDeniedException($message);
            }
        });

        static::updating(function (Task $task) {
            if ($task->isDirty('project_id')) {
                $task->milestone_id = null;
            }
            return $task;
        });

        static::saved(function (Task $task) {
            SearchIndex::upsertToIndex($task->fresh());
        });

        static::deleted(function (Task $task) {
            SearchIndex::removeFromIndex($task);
        });
    }

    /**
     * Get the related resource type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function resourceType()
    {
        return $this->belongsTo(ResourceType::class);
    }

    /**
     * Get the related reference type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function referenceType()
    {
        return $this->belongsTo(ReferenceType::class);
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
     * Get the related tag.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function milestone()
    {
        return $this->hasOne(Milestone::class);
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
     * Get users which has the task marked as favourite.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function usersFavourite()
    {
        return $this->belongsToMany(User::class, 'favourite_tasks', 'task_id', 'user_id');
    }

    protected function getCanClockAttribute()
    {
        return array_get($this->attributes, 'can_clock', true);
    }

    /**
     * @param int $estimation Estimation in minutes
     */
    protected function setEstimationAttribute($estimation)
    {
        if (empty($estimation)) {
            $estimation = 0;
        }
        $estimation = TimeFormater::formattedTimeToMinutes($estimation);

        $this->attributes['estimation'] = $estimation;
    }

    /**
     * @param int|null $revisedEstimation Revised Estimation in minutes
     */
    protected function setRevisedEstimationAttribute($revisedEstimation)
    {
        if (! is_null($revisedEstimation)) {
            $revisedEstimation = TimeFormater::formattedTimeToMinutes($revisedEstimation);
        }

        $this->attributes['revised_estimation'] = $revisedEstimation;
    }

    /**
     * The Milestone is optional, so set the value to null if empty
     * @param $value
     */
    public function setMilestoneIdAttribute($value)
    {
        $this->attributes['milestone_id'] = empty($value) ? null: $value;
    }

    /**
     * @param bool $checkProject
     * @return bool
     */
    public function getRequireComment($checkProject = false)
    {
        $commentNeeded = $this->getAttributeFromArray('require_comments');
        if (!$commentNeeded && $checkProject) {
            $commentNeeded = $this->project->require_comments;
        }
        return $commentNeeded;
    }

    /**
     * @return bool
     */
    public function hasExceededEstimation()
    {
        $estimation = $this->revised_estimation ? $this->revised_estimation : $this->estimation;
        return ($estimation > 0 && $this->logged_time > $estimation);
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
            'task_number'      => $this->number,
            'task_name'        => $this->name,
            'task_name.folded' => $this->name,
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
        return 'task';
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

    protected static function hasLogsEntriesOutsideWindow(Task $task)
    {
        $atLeastOneOutsideWindow = false;
        foreach ($task->logEntries as $logEntry) {
            if (LogEntryUpdateDatetimeWindow::isOutside($logEntry->started_at)) {
                $atLeastOneOutsideWindow = true;
                break;
            }
        }
        return $atLeastOneOutsideWindow;
    }
}
