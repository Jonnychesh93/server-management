<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useLiveOutput } from '@/composables/useLiveOutput';
import { capitalize } from '@/lib/utils';
import { show as showSite } from '@/routes/sites';
import type { Command, CommandStatus } from '@/types';

const { command } = defineProps<{
    command: Command;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: command.site?.domain ?? 'Site',
            href: command.site ? showSite(command.site.uuid) : '',
        },
        { title: 'Command', href: '' },
    ],
});

const statusVariant: Record<
    CommandStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    queued: 'outline',
    running: 'secondary',
    success: 'default',
    failed: 'destructive',
};

const { output, finished } = useLiveOutput(
    `teams.${command.team_id}.commands.${command.id}`,
    command.output,
);

watch(finished, (isFinished) => {
    if (isFinished) {
        router.reload({ only: ['command'] });
    }
});
</script>

<template>
    <Head title="Command" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <Heading title="Command" :description="command.command" />

        <div class="flex items-center gap-2">
            <Badge :variant="statusVariant[command.status]">
                {{ capitalize(command.status) }}
            </Badge>
        </div>

        <Card>
            <CardContent>
                <pre
                    class="max-h-[32rem] overflow-auto rounded-md bg-muted p-4 text-xs"
                    >{{ output }}</pre>
            </CardContent>
        </Card>
    </div>
</template>
