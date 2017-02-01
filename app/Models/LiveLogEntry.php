<?php

namespace NeoClocking\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * NeoClocking\Models\LogEntry
 *
 * @property integer $user_id
 * @property integer $task_id
 * @property \Carbon\Carbon $started_at
 * @property string $comment
 * @property-read Task $task
 * @property-read User $user
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\LogEntry firstOrNew($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\LogEntry whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\LogEntry whereTaskId($value)
 * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\LogEntry whereStartedAt($value)
 * * @method static \Illuminate\Database\Query\Builder|\NeoClocking\Models\LogEntry whereComment($value)
 */
class LiveLogEntry extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'live_log_entries';

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
        'task_id',
        'started_at',
        'comment'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'started_at'
    ];


    /**
     * Get related task.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }


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
