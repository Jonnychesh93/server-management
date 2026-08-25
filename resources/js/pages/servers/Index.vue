<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create as createServer, show as showServer } from '@/routes/servers';
import type { ConnectionStatus, ProvisioningStatus, Server } from '@/types';

defineProps<{
    servers: Server[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Servers', href: dashboard() }],
    },
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

const connectionLabel: Record<ConnectionStatus, string> = {
    online: 'Online',
    offline: 'Offline',
    unknown: 'Unknown',
};
</script>

<template>
    <Head title="Servers" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                title="Servers"
                description="Servers connected to this team"
            />
            <Button as-child>
                <Link :href="createServer()">
                    <Plus class="mr-2 size-4" />
                    Add server
                </Link>
            </Button>
        </div>

        <p v-if="servers.length === 0" class="text-sm text-muted-foreground">
            No servers yet.
            <Link :href="createServer()" class="underline"
                >Add your first server</Link
            >.
        </p>

        <div v-else class="grid gap-4 md:grid-cols-2">
            <Link
                v-for="server in servers"
                :key="server.id"
                :href="showServer(server.id)"
            >
                <Card class="transition-colors hover:bg-muted/50">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>{{ server.name }}</CardTitle>
                            <Badge
                                :variant="
                                    provisioningVariant[
                                        server.provisioning_status
                                    ]
                                "
                            >
                                {{ server.provisioning_status }}
                            </Badge>
                        </div>
                        <CardDescription
                            >{{ server.ip_address }}:{{
                                server.ssh_port
                            }}</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            {{ connectionLabel[server.connection_status] }}
                        </p>
                    </CardContent>
                </Card>
            </Link>
        </div>
    </div>
</template>
