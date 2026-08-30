<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { capitalize } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show as showSite } from '@/routes/sites';
import type { Site, SiteStatus } from '@/types';

defineProps<{
    sites: Site[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Sites', href: dashboard() }],
    },
});

const statusVariant: Record<
    SiteStatus,
    'default' | 'secondary' | 'destructive' | 'success' | 'outline'
> = {
    provisioning: 'secondary',
    active: 'success',
    failed: 'destructive',
    disabled: 'outline',
};
</script>

<template>
    <Head title="Sites" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <Heading title="Sites" description="Sites across all your servers" />

        <p v-if="sites.length === 0" class="text-sm text-muted-foreground">
            No sites yet.
        </p>

        <div v-else class="grid gap-4 md:grid-cols-2">
            <Link
                v-for="site in sites"
                :key="site.id"
                :href="showSite(site.uuid)"
            >
                <Card class="transition-colors hover:bg-muted/50">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <CardTitle>{{ site.domain }}</CardTitle>
                            <Badge :variant="statusVariant[site.status]">
                                {{ capitalize(site.status) }}
                            </Badge>
                        </div>
                        <CardDescription v-if="site.server">
                            {{ site.server.name }}
                        </CardDescription>
                    </CardHeader>
                </Card>
            </Link>
        </div>
    </div>
</template>
