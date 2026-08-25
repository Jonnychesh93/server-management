<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A chunk of live provisioning output. Payloads carry only IDs and text,
 * never a serialized model, so encrypted server attributes can never leak
 * into a broadcast payload.
 */
class ServerProvisioningOutputReceived implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public readonly int $teamId,
        public readonly int $serverId,
        public readonly string $chunk,
        public readonly int $sequence,
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
        return 'output';
    }
}
