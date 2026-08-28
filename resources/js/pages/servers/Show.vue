<script setup lang="ts">
import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { watch } from 'vue';
import ServerController from '@/actions/App/Http/Controllers/ServerController';
import AddCronDialog from '@/components/AddCronDialog.vue';
import AddDaemonDialog from '@/components/AddDaemonDialog.vue';
import AddDatabaseDialog from '@/components/AddDatabaseDialog.vue';
import DeleteServerDialog from '@/components/DeleteServerDialog.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useLiveOutput } from '@/composables/useLiveOutput';
import { capitalize } from '@/lib/utils';
import { destroy as destroyCron } from '@/routes/crons';
import { destroy as destroyDaemon } from '@/routes/daemons';
import { destroy as destroyDatabase } from '@/routes/databases';
import { edit as editServer, index as serversIndex } from '@/routes/servers';
import { create as createSite, show as showSite } from '@/routes/sites';
import type {
    ConnectionStatus,
    Cron,
    Daemon,
    Database,
    ProvisioningStatus,
    Server,
    Site,
    SiteStatus,
} from '@/types';

const { server, sites, daemons, crons, databases } = defineProps<{
    server: Server;
    sites: Site[];
    daemons: Daemon[];
    crons: Cron[];
    databases: Database[];
}>();

const siteStatusVariant: Record<
    SiteStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    provisioning: 'secondary',
    active: 'default',
    failed: 'destructive',
    disabled: 'outline',
};

setLayoutProps({
    breadcrumbs: [
        { title: 'Servers', href: serversIndex() },
        { title: server.name, href: '' },
    ],
});

const provisioningVariant: Record<
    ProvisioningStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    pending: 'outline',
    connecting: 'secondary',
    installing: 'secondary',
    active: 'default',
    failed: 'destructive',
};

const connectionVariant: Record<
    ConnectionStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    online: 'default',
    offline: 'destructive',
    unknown: 'outline',
};

const isProvisioning =
    server.provisioning_status === 'connecting' ||
    server.provisioning_status === 'installing' ||
    server.provisioning_status === 'pending';

const { output, finished } = useLiveOutput(
    `teams.${server.team_id}.servers.${server.id}.provisioning`,
    server.provisioning_output,
);

watch(finished, (isFinished) => {
    if (isFinished) {
        router.reload({ only: ['server'] });
    }
});
</script>

<template>
    <Head :title="server.name" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <div class="flex items-center justify-between">
            <Heading
                :title="server.name"
                :description="`${server.ip_address}:${server.ssh_port}`"
            />
            <div class="flex items-center gap-2">
                <Button variant="outline" as-child>
                    <Link :href="editServer(server.uuid)">
                        <Pencil class="mr-2 size-4" />
                        Edit
                    </Link>
                </Button>
                <DeleteServerDialog :server="server" />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <Badge :variant="provisioningVariant[server.provisioning_status]">
                Provisioning: {{ capitalize(server.provisioning_status) }}
            </Badge>
            <Badge :variant="connectionVariant[server.connection_status]">
                {{ capitalize(server.connection_status) }}
            </Badge>
        </div>

        <Card>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-sm text-muted-foreground">SSH user</p>
                    <p class="font-medium">{{ server.ssh_user }}</p>
                </div>
                <div>
                    <p class="text-sm text-muted-foreground">
                        Operating system
                    </p>
                    <p class="font-medium">{{ server.os }}</p>
                </div>
                <div v-if="server.cpu_usage !== null">
                    <p class="text-sm text-muted-foreground">CPU</p>
                    <p class="font-medium">{{ server.cpu_usage }}%</p>
                </div>
                <div v-if="server.memory_usage !== null">
                    <p class="text-sm text-muted-foreground">Memory</p>
                    <p class="font-medium">{{ server.memory_usage }}%</p>
                </div>
                <div v-if="server.disk_usage !== null">
                    <p class="text-sm text-muted-foreground">Disk</p>
                    <p class="font-medium">{{ server.disk_usage }}%</p>
                </div>
                <div v-if="server.provisioning_failed_step">
                    <p class="text-sm text-muted-foreground">Failed step</p>
                    <p class="font-medium text-destructive">
                        {{ server.provisioning_failed_step }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Form
            v-if="server.provisioning_status === 'failed'"
            v-bind="ServerController.retry.form(server.uuid)"
            v-slot="{ processing }"
        >
            <Button type="submit" :disabled="processing">
                Retry provisioning
            </Button>
        </Form>

        <Card v-if="output || isProvisioning">
            <CardContent>
                <p class="mb-2 text-sm text-muted-foreground">
                    Provisioning output
                </p>
                <pre
                    class="max-h-96 overflow-auto rounded-md bg-muted p-4 text-xs"
                    >{{ output }}</pre>
            </CardContent>
        </Card>

        <div
            v-if="server.provisioning_status === 'active'"
            class="flex flex-col space-y-4"
        >
            <div class="flex items-center justify-between">
                <Heading variant="small" title="Sites" />
                <Button variant="outline" as-child>
                    <Link :href="createSite(server.uuid)">
                        <Plus class="mr-2 size-4" />
                        Add site
                    </Link>
                </Button>
            </div>

            <p v-if="sites.length === 0" class="text-sm text-muted-foreground">
                No sites yet.
            </p>

            <div v-else class="grid gap-4 md:grid-cols-2">
                <Link
                    v-for="site in sites"
                    :key="site.id"
                    :href="showSite(site.id)"
                >
                    <Card class="transition-colors hover:bg-muted/50">
                        <CardContent class="flex items-center justify-between">
                            <span class="font-medium">{{ site.domain }}</span>
                            <Badge :variant="siteStatusVariant[site.status]">
                                {{ capitalize(site.status) }}
                            </Badge>
                        </CardContent>
                    </Card>
                </Link>
            </div>
        </div>

        <div
            v-if="server.provisioning_status === 'active'"
            class="flex flex-col space-y-4"
        >
            <div class="flex items-center justify-between">
                <Heading variant="small" title="Databases" />
                <AddDatabaseDialog :server="server" />
            </div>

            <p
                v-if="databases.length === 0"
                class="text-sm text-muted-foreground"
            >
                No databases yet.
            </p>

            <div v-else class="space-y-2">
                <div
                    v-for="database in databases"
                    :key="database.id"
                    class="flex items-center justify-between rounded-md border p-3"
                >
                    <div>
                        <p class="font-mono text-sm">{{ database.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ database.username }}
                            <template v-if="database.password">
                                &middot; {{ database.password }}
                            </template>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge
                            :variant="
                                database.status === 'active'
                                    ? 'default'
                                    : database.status === 'failed'
                                      ? 'destructive'
                                      : 'outline'
                            "
                        >
                            {{ capitalize(database.status) }}
                        </Badge>
                        <Link
                            :href="destroyDatabase(database.id)"
                            method="delete"
                            as="button"
                        >
                            <Trash2
                                class="size-4 text-muted-foreground hover:text-destructive"
                            />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="server.provisioning_status === 'active'"
            class="flex flex-col space-y-4"
        >
            <div class="flex items-center justify-between">
                <Heading variant="small" title="Daemons" />
                <AddDaemonDialog :server="server" />
            </div>

            <p
                v-if="daemons.length === 0"
                class="text-sm text-muted-foreground"
            >
                No daemons yet.
            </p>

            <div v-else class="space-y-2">
                <div
                    v-for="daemon in daemons"
                    :key="daemon.id"
                    class="flex items-center justify-between rounded-md border p-3"
                >
                    <div>
                        <p class="font-mono text-sm">{{ daemon.command }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ daemon.user }} &middot; {{ daemon.processes }}
                            process(es)
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge
                            :variant="
                                daemon.status === 'active'
                                    ? 'default'
                                    : daemon.status === 'failed'
                                      ? 'destructive'
                                      : 'outline'
                            "
                        >
                            {{ capitalize(daemon.status) }}
                        </Badge>
                        <Link
                            :href="destroyDaemon(daemon.id)"
                            method="delete"
                            as="button"
                        >
                            <Trash2
                                class="size-4 text-muted-foreground hover:text-destructive"
                            />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="server.provisioning_status === 'active'"
            class="flex flex-col space-y-4"
        >
            <div class="flex items-center justify-between">
                <Heading variant="small" title="Cron jobs" />
                <AddCronDialog :server="server" />
            </div>

            <p v-if="crons.length === 0" class="text-sm text-muted-foreground">
                No cron jobs yet.
            </p>

            <div v-else class="space-y-2">
                <div
                    v-for="cron in crons"
                    :key="cron.id"
                    class="flex items-center justify-between rounded-md border p-3"
                >
                    <div>
                        <p class="font-mono text-sm">
                            {{ cron.schedule }} {{ cron.command }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ cron.user }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge
                            :variant="
                                cron.status === 'active'
                                    ? 'default'
                                    : cron.status === 'failed'
                                      ? 'destructive'
                                      : 'outline'
                            "
                        >
                            {{ capitalize(cron.status) }}
                        </Badge>
                        <Link
                            :href="destroyCron(cron.id)"
                            method="delete"
                            as="button"
                        >
                            <Trash2
                                class="size-4 text-muted-foreground hover:text-destructive"
                            />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
