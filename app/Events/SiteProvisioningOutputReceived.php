<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class SiteProvisioningOutputReceived implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $teamId,
        public readonly int $siteId,
        public readonly string $chunk,
        public readonly int $sequence,
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
        return 'output';
    }
}
