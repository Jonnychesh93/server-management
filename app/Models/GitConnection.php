<?php

namespace App\Models;

use App\Enums\GitProvider;
use Database\Factories\GitConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $site_id
 * @property GitProvider $provider
 * @property string $repository
 * @property string $branch
 * @property int|null $deploy_key_id
 * @property string|null $installation_id
 * @property array<string, mixed>|null $metadata
 * @property string|null $webhook_secret
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['site_id', 'provider', 'repository', 'branch', 'deploy_key_id', 'installation_id', 'metadata', 'webhook_secret'])]
#[Hidden(['webhook_secret'])]
class GitConnection extends Model
{
    /** @use HasFactory<GitConnectionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => GitProvider::class,
            'metadata' => 'array',
            'webhook_secret' => 'encrypted',
        ];
    }

    /**
     * Get the site this connection belongs to.
     *
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the deploy key used to authenticate against the git host.
     *
     * @return BelongsTo<SshKey, $this>
     */
    public function deployKey(): BelongsTo
    {
        return $this->belongsTo(SshKey::class);
    }
}
