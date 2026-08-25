<?php

namespace App\Events;

use App\Enums\DeploymentStatus;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class DeploymentFinished implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $teamId,
        public readonly int $deploymentId,
        public readonly DeploymentStatus $status,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("teams.{$this->teamId}.deployments.{$this->deploymentId}")];
    }

    public function broadcastAs(): string
    {
        return 'finished';
    }
}
