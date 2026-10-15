<?php

namespace App\Modules\Backup\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Une exécution de sauvegarde (archive ZIP).
 */
class BackupRun extends Model
{
    public const TYPE_ARCHIVE = 'archive';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'type',
        'status',
        'target',
        'message',
        'size_bytes',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
