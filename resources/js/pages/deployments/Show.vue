<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useLiveOutput } from '@/composables/useLiveOutput';
import { capitalize } from '@/lib/utils';
import { show as showSite } from '@/routes/sites';
import type { Deployment, DeploymentStatus } from '@/types';

const { deployment } = defineProps<{
    deployment: Deployment;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: deployment.site?.domain ?? 'Site',
            href: deployment.site ? showSite(deployment.site.id) : '',
        },
        { title: `Deployment #${deployment.id}`, href: '' },
    ],
});

const statusVariant: Record<
    DeploymentStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    queued: 'outline',
    running: 'secondary',
    success: 'default',
    failed: 'destructive',
};

const { output, finished } = useLiveOutput(
    `teams.${deployment.team_id}.deployments.${deployment.id}`,
    deployment.output,
);

watch(finished, (isFinished) => {
    if (isFinished) {
        router.reload({ only: ['deployment'] });
    }
});
</script>

<template>
    <Head :title="`Deployment #${deployment.id}`" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <Heading
            :title="`Deployment #${deployment.id}`"
            :description="deployment.commit_message ?? undefined"
        />

        <div class="flex items-center gap-2">
            <Badge :variant="statusVariant[deployment.status]">
                {{ capitalize(deployment.status) }}
            </Badge>
            <span
                v-if="deployment.commit_sha"
                class="text-sm text-muted-foreground"
            >
                {{ deployment.commit_sha.slice(0, 7) }}
            </span>
            <span
                v-if="deployment.triggered_by_user"
                class="text-sm text-muted-foreground"
            >
                triggered by {{ deployment.triggered_by_user.name }}
            </span>
            <span v-else class="text-sm text-muted-foreground">
                triggered by webhook
            </span>
        </div>

        <p v-if="deployment.failed_step" class="text-sm text-destructive">
            Failed at step: {{ deployment.failed_step }}
        </p>

        <Card>
            <CardContent>
                <pre
                    class="max-h-[32rem] overflow-auto rounded-md bg-muted p-4 text-xs"
                    >{{ output }}</pre>
            </CardContent>
        </Card>
    </div>
</template>
