<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Globe, Rocket, Server as ServerIcon } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { capitalize } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show as showDeployment } from '@/routes/deployments';
import {
    create as createServer,
    index as serversIndex,
    show as showServer,
} from '@/routes/servers';
import type {
    ActivityLogEntry,
    Deployment,
    DeploymentStatus,
    ProvisioningStatus,
    Server,
} from '@/types';

const { stats, recentServers, recentDeployments, recentActivity } =
    defineProps<{
        stats: {
            servers: number;
            activeServers: number;
            sites: number;
            deployments: number;
        };
        recentServers: Server[];
        recentDeployments: Deployment[];
        recentActivity: ActivityLogEntry[];
    }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const page = usePage();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);
const teamName = computed(() => page.props.currentTeam?.name ?? '');
const hasServers = computed(() => stats.servers > 0);

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

const deploymentVariant: Record<
    DeploymentStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    queued: 'outline',
    running: 'secondary',
    success: 'default',
    failed: 'destructive',
};

function timeAgo(date: string): string {
    const seconds = Math.floor((Date.now() - new Date(date).getTime()) / 1000);
    const units: [string, number][] = [
        ['y', 31536000],
        ['mo', 2592000],
        ['d', 86400],
        ['h', 3600],
        ['m', 60],
    ];

    for (const [label, secondsInUnit] of units) {
        const value = Math.floor(seconds / secondsInUnit);

        if (value >= 1) {
            return `${value}${label} ago`;
        }
    }

    return 'just now';
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-8 p-4 md:p-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Welcome back, {{ firstName }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ teamName }}
                </p>
            </div>
            <Button as-child>
                <Link :href="createServer()">
                    <ServerIcon class="mr-2 size-4" />
                    Add a server
                </Link>
            </Button>
        </div>

        <!-- Empty state: no servers yet -->
        <div v-if="!hasServers" class="flex flex-col gap-8">
            <Card
                class="overflow-hidden border-primary/15 bg-gradient-to-br from-primary/5 via-transparent to-transparent"
            >
                <CardContent
                    class="flex flex-col items-center gap-4 py-16 text-center"
                >
                    <div
                        class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary"
                    >
                        <ServerIcon class="size-7" />
                    </div>
                    <div class="space-y-1.5">
                        <h2 class="text-xl font-semibold">
                            Let's get your first server online
                        </h2>
                        <p
                            class="mx-auto max-w-md text-sm text-muted-foreground"
                        >
                            Connect a server over SSH and we'll provision nginx,
                            PHP, MySQL, Redis, and everything else it needs.
                        </p>
                    </div>
                    <Button as-child size="lg" class="mt-2">
                        <Link :href="createServer()">
                            Add your first server
                            <ArrowRight class="ml-2 size-4" />
                        </Link>
                    </Button>
                </CardContent>
            </Card>

            <div class="grid gap-4 sm:grid-cols-3">
                <div
                    v-for="(step, index) in [
                        {
                            icon: ServerIcon,
                            title: 'Add a server',
                            description:
                                'Point us at a fresh Ubuntu box and its root credentials.',
                        },
                        {
                            icon: Globe,
                            title: 'Add a site',
                            description:
                                'We configure nginx, PHP-FPM, and SSL for your domain.',
                        },
                        {
                            icon: Rocket,
                            title: 'Deploy',
                            description:
                                'Connect a repository and push to deploy automatically.',
                        },
                    ]"
                    :key="step.title"
                    class="flex gap-3 rounded-xl border p-4"
                >
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-sm font-medium text-muted-foreground"
                    >
                        {{ index + 1 }}
                    </div>
                    <div class="space-y-1">
                        <p
                            class="flex items-center gap-1.5 text-sm font-medium"
                        >
                            <component
                                :is="step.icon"
                                class="size-4 text-primary"
                            />
                            {{ step.title }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ step.description }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Populated state -->
        <div v-else class="flex flex-col gap-8">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <Link :href="serversIndex()">
                    <Card class="transition-colors hover:bg-muted/50">
                        <CardContent class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-muted-foreground">
                                    Servers
                                </p>
                                <p class="text-2xl font-semibold">
                                    {{ stats.servers }}
                                </p>
                            </div>
                            <div
                                class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <ServerIcon class="size-5" />
                            </div>
                        </CardContent>
                    </Card>
                </Link>

                <Card>
                    <CardContent class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Active</p>
                            <p class="text-2xl font-semibold">
                                {{ stats.activeServers }}
                            </p>
                        </div>
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        >
                            <ServerIcon class="size-5" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">Sites</p>
                            <p class="text-2xl font-semibold">
                                {{ stats.sites }}
                            </p>
                        </div>
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400"
                        >
                            <Globe class="size-5" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-muted-foreground">
                                Deployments
                            </p>
                            <p class="text-2xl font-semibold">
                                {{ stats.deployments }}
                            </p>
                        </div>
                        <div
                            class="flex size-10 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:text-violet-400"
                        >
                            <Rocket class="size-5" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardContent class="space-y-1">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="font-medium">Servers</h2>
                            <Link
                                :href="serversIndex()"
                                class="text-sm text-muted-foreground hover:text-foreground"
                                >View all</Link
                            >
                        </div>
                        <p
                            v-if="recentServers.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            No servers yet.
                        </p>
                        <Link
                            v-for="server in recentServers"
                            :key="server.id"
                            :href="showServer(server.uuid)"
                            class="flex items-center justify-between rounded-lg p-2 text-sm hover:bg-muted"
                        >
                            <span class="font-medium">{{ server.name }}</span>
                            <Badge
                                :variant="
                                    provisioningVariant[
                                        server.provisioning_status
                                    ]
                                "
                            >
                                {{ capitalize(server.provisioning_status) }}
                            </Badge>
                        </Link>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="space-y-1">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="font-medium">Recent deployments</h2>
                        </div>
                        <p
                            v-if="recentDeployments.length === 0"
                            class="py-6 text-center text-sm text-muted-foreground"
                        >
                            No deployments yet.
                        </p>
                        <Link
                            v-for="deploy in recentDeployments"
                            :key="deploy.id"
                            :href="showDeployment(deploy.uuid)"
                            class="flex items-center justify-between rounded-lg p-2 text-sm hover:bg-muted"
                        >
                            <span>{{ deploy.site?.domain }}</span>
                            <Badge :variant="deploymentVariant[deploy.status]">
                                {{ capitalize(deploy.status) }}
                            </Badge>
                        </Link>
                    </CardContent>
                </Card>
            </div>

            <Card v-if="recentActivity.length > 0">
                <CardContent>
                    <h2 class="mb-3 font-medium">Recent activity</h2>
                    <ul class="space-y-3">
                        <li
                            v-for="entry in recentActivity"
                            :key="entry.id"
                            class="flex items-center justify-between text-sm"
                        >
                            <span class="text-muted-foreground">{{
                                entry.description
                            }}</span>
                            <span
                                class="shrink-0 text-xs text-muted-foreground"
                            >
                                {{ timeAgo(entry.created_at) }}
                            </span>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
