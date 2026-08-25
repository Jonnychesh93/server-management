<?php

namespace App\Jobs;

use App\Enums\ServerConnectionStatus;
use App\Enums\ServerProvisioningStatus;
use App\Models\Server;
use App\Models\ServerMetric;
use App\Services\Ssh\SshConnector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class HeartbeatServerJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 30;

    public int $tries = 1;

    private const SCRIPT = <<<'BASH'
        set -e
        MEM_TOTAL=$(awk '/MemTotal/ {print $2}' /proc/meminfo)
        MEM_AVAILABLE=$(awk '/MemAvailable/ {print $2}' /proc/meminfo)
        MEM_PCT=$(( (MEM_TOTAL - MEM_AVAILABLE) * 100 / MEM_TOTAL ))
        DISK_PCT=$(df --output=pcent / | tail -1 | tr -dc '0-9')
        LOAD1=$(awk '{print $1}' /proc/loadavg)
        read -r _ u1 n1 s1 i1 io1 irq1 si1 st1 _ < /proc/stat
        total1=$((u1+n1+s1+i1+io1+irq1+si1+st1))
        idle1=$((i1+io1))
        sleep 1
        read -r _ u2 n2 s2 i2 io2 irq2 si2 st2 _ < /proc/stat
        total2=$((u2+n2+s2+i2+io2+irq2+si2+st2))
        idle2=$((i2+io2))
        totald=$((total2-total1))
        idled=$((idle2-idle1))
        if [ "$totald" -gt 0 ]; then CPU_PCT=$(( (100 * (totald - idled)) / totald )); else CPU_PCT=0; fi
        echo "HEARTBEAT cpu=${CPU_PCT} memory=${MEM_PCT} disk=${DISK_PCT} load1=${LOAD1}"
        BASH;

    private const PATTERN = '/HEARTBEAT cpu=(\d+) memory=(\d+) disk=(\d+) load1=([\d.]+)/';

    public function __construct(public readonly Server $server)
    {
        $this->onQueue('ssh');
    }

    /**
     * Execute the job.
     */
    public function handle(SshConnector $connector): void
    {
        if ($this->server->provisioning_status !== ServerProvisioningStatus::Active) {
            return;
        }

        try {
            $result = $connector->connect($this->server)->run(self::SCRIPT);

            if (! $result->successful() || ! preg_match(self::PATTERN, $result->output, $matches)) {
                $this->markOffline();

                return;
            }

            [$cpu, $memory, $disk, $load1] = [(int) $matches[1], (int) $matches[2], (int) $matches[3], (float) $matches[4]];

            $this->server->forceFill([
                'connection_status' => ServerConnectionStatus::Online,
                'last_heartbeat_at' => now(),
                'cpu_usage' => $cpu,
                'memory_usage' => $memory,
                'disk_usage' => $disk,
            ])->save();

            ServerMetric::create([
                'server_id' => $this->server->id,
                'cpu' => $cpu,
                'memory' => $memory,
                'disk' => $disk,
                'load_1m' => $load1,
                'recorded_at' => now(),
            ]);
        } catch (Throwable) {
            $this->markOffline();
        }
    }

    private function markOffline(): void
    {
        $this->server->forceFill([
            'connection_status' => ServerConnectionStatus::Offline,
            'last_heartbeat_at' => now(),
        ])->save();
    }
}
