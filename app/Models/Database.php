<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\DatabaseStatus;
use Database\Factories\DatabaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int $server_id
 * @property string $name
 * @property string $username
 * @property string $password
 * @property DatabaseStatus $status
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'server_id', 'name', 'username', 'password'])]
#[Hidden(['password'])]
class Database extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<DatabaseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'status' => DatabaseStatus::class,
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the server this database lives on.
     *
     * @return BelongsTo<Server, $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
