<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Events\UploadCreated;

/**
 *
 * @property string $filename
 * @property string $status
 * @property int|null $total_rows
 * @property int|null $processed_rows
 * @property string|null $error
 */

class Upload extends Model
{
    protected $fillable = [
        'filename',
        'status',
        'total_rows',
        'processed_rows',
        'error',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getProgressAttribute(): int
    {
        if (!$this->total_rows || $this->total_rows == 0) {
            return 0;
        }

        return (int) min(100, round(($this->processed_rows / $this->total_rows) * 100));
    }
}