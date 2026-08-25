<?php

namespace App\Events;

use App\Enums\ServerProvisioningStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class ServerProvisioningFinished implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $teamId,
        public readonly int $serverId,
        public readonly ServerProvisioningStatus $status,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("teams.{$this->teamId}.servers.{$this->serverId}.provisioning")];
    }

    public function broadcastAs(): string
    {
        return 'finished';
    }
}
