<?php

namespace App\Models;

use Database\Factories\ServerMetricFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $server_id
 * @property int $cpu
 * @property int $memory
 * @property int $disk
 * @property float $load_1m
 * @property Carbon $recorded_at
 */
#[Fillable(['server_id', 'cpu', 'memory', 'disk', 'load_1m', 'recorded_at'])]
class ServerMetric extends Model
{
    /** @use HasFactory<ServerMetricFactory> */
    use HasFactory;

    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'load_1m' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    /**
     * Get the server this metric snapshot belongs to.
     *
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
