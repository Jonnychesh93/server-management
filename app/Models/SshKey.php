<?php

namespace App\Models;

use App\Concerns\BelongsToTeam;
use App\Enums\SshKeyType;
use Database\Factories\SshKeyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property int|null $site_id
 * @property string $name
 * @property string $public_key
 * @property string $private_key
 * @property SshKeyType $type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['team_id', 'site_id', 'name', 'public_key', 'private_key', 'type'])]
#[Hidden(['private_key'])]
class SshKey extends Model
{
    use BelongsToTeam;

    /** @use HasFactory<SshKeyFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'private_key' => 'encrypted',
            'type' => SshKeyType::class,
        ];
    }

    /**
     * Get the site this key was generated for, if any.
     *
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
