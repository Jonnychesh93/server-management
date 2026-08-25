<?php

namespace App\Events;

use App\Enums\SiteStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class SiteProvisioningFinished implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $teamId,
        public readonly int $siteId,
        public readonly SiteStatus $status,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("teams.{$this->teamId}.sites.{$this->siteId}.provisioning")];
    }

    public function broadcastAs(): string
    {
        return 'finished';
    }
}
