<script setup lang="ts">
import { Form, Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { Pencil, Rocket } from '@lucide/vue';
import { watch } from 'vue';
import DeploymentController from '@/actions/App/Http/Controllers/DeploymentController';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import SiteEnvironmentController from '@/actions/App/Http/Controllers/SiteEnvironmentController';
import SiteSslController from '@/actions/App/Http/Controllers/SiteSslController';
import DeleteSiteDialog from '@/components/DeleteSiteDialog.vue';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { useLiveOutput } from '@/composables/useLiveOutput';
import { capitalize } from '@/lib/utils';
import { show as showDeployment } from '@/routes/deployments';
import { index as serversIndex, show as showServer } from '@/routes/servers';
import { edit as editSite } from '@/routes/sites';
import type { DeploymentStatus, Site, SiteStatus, SslStatus } from '@/types';

const { site, canManageEnvironment, env, webhookUrl, webhookSecret } =
    defineProps<{
        site: Site;
        canManageEnvironment: boolean;
        env: string | null;
        webhookUrl: string | null;
        webhookSecret: string | null;
    }>();

setLayoutProps({
    breadcrumbs: site.server
        ? [
              { title: 'Servers', href: serversIndex() },
              { title: site.server.name, href: showServer(site.server.id) },
              { title: site.domain, href: '' },
          ]
        : [{ title: site.domain, href: '' }],
});

const statusVariant: Record<
    SiteStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    provisioning: 'secondary',
    active: 'default',
    failed: 'destructive',
    disabled: 'outline',
};

const sslVariant: Record<
    SslStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    none: 'outline',
    pending: 'secondary',
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

const { output, finished } = useLiveOutput(
    `teams.${site.team_id}.sites.${site.id}.provisioning`,
    site.provisioning_output,
);

watch(finished, (isFinished) => {
    if (isFinished) {
        router.reload({ only: ['site'] });
    }
});
</script>

<template>
    <Head :title="site.domain" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <div class="flex items-center justify-between">
            <Heading :title="site.domain" />
            <div class="flex items-center gap-2">
                <Form
                    v-if="site.status === 'active'"
                    v-bind="DeploymentController.store.form(site.id)"
                    v-slot="{ processing }"
                >
                    <Button :disabled="processing">
                        <Rocket class="mr-2 size-4" />
                        Deploy now
                    </Button>
                </Form>
                <Button variant="outline" as-child>
                    <Link :href="editSite(site.id)">
                        <Pencil class="mr-2 size-4" />
                        Deploy script
                    </Link>
                </Button>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <Badge :variant="statusVariant[site.status]">
                {{ capitalize(site.status) }}
            </Badge>
            <Badge :variant="sslVariant[site.ssl_status]">
                SSL: {{ capitalize(site.ssl_status) }}
            </Badge>
            <span class="text-sm text-muted-foreground">
                PHP {{ site.php_version }}
            </span>
        </div>

        <Tabs default-value="overview">
            <TabsList>
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="deployments">Deployments</TabsTrigger>
                <TabsTrigger value="domain">Domain</TabsTrigger>
                <TabsTrigger value="settings">Settings</TabsTrigger>
            </TabsList>

            <TabsContent value="overview">
                <Form
                    v-if="site.status === 'failed'"
                    v-bind="SiteController.retry.form(site.id)"
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        Retry provisioning
                    </Button>
                </Form>

                <Card v-if="output || site.status === 'provisioning'">
                    <CardContent>
                        <p class="mb-2 text-sm text-muted-foreground">
                            Provisioning output
                        </p>
                        <pre
                            class="max-h-96 overflow-auto rounded-md bg-muted p-4 text-xs"
                            >{{ output }}</pre
                        >
                    </CardContent>
                </Card>

                <Card v-if="site.deployments && site.deployments.length > 0">
                    <CardHeader>
                        <CardTitle>Recent deployments</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Link
                            v-for="deploy in site.deployments.slice(0, 5)"
                            :key="deploy.id"
                            :href="showDeployment(deploy.id)"
                            class="flex items-center justify-between rounded-md p-2 text-sm hover:bg-muted"
                        >
                            <span class="flex items-center gap-2">
                                <Badge
                                    :variant="deploymentVariant[deploy.status]"
                                >
                                    {{ capitalize(deploy.status) }}
                                </Badge>
                                <span v-if="deploy.commit_sha">{{
                                    deploy.commit_sha.slice(0, 7)
                                }}</span>
                                <span class="text-muted-foreground">{{
                                    deploy.commit_message
                                }}</span>
                            </span>
                            <span class="text-muted-foreground">{{
                                deploy.triggered_by_user?.name ?? 'webhook'
                            }}</span>
                        </Link>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="deployments">
                <Card v-if="site.deployments && site.deployments.length > 0">
                    <CardHeader>
                        <CardTitle>Deployments</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-2">
                        <Link
                            v-for="deploy in site.deployments"
                            :key="deploy.id"
                            :href="showDeployment(deploy.id)"
                            class="flex items-center justify-between rounded-md p-2 text-sm hover:bg-muted"
                        >
                            <span class="flex items-center gap-2">
                                <Badge
                                    :variant="deploymentVariant[deploy.status]"
                                >
                                    {{ capitalize(deploy.status) }}
                                </Badge>
                                <span v-if="deploy.commit_sha">{{
                                    deploy.commit_sha.slice(0, 7)
                                }}</span>
                                <span class="text-muted-foreground">{{
                                    deploy.commit_message
                                }}</span>
                            </span>
                            <span class="text-muted-foreground">{{
                                deploy.triggered_by_user?.name ?? 'webhook'
                            }}</span>
                        </Link>
                    </CardContent>
                </Card>
                <p v-else class="text-sm text-muted-foreground">
                    No deployments yet.
                </p>
            </TabsContent>

            <TabsContent value="domain">
                <Card v-if="site.git_connection">
                    <CardHeader>
                        <CardTitle>Git repository</CardTitle>
                        <CardDescription>
                            {{ site.git_connection.repository }} ({{
                                site.git_connection.branch
                            }})
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="site.git_connection.deploy_key">
                            <p class="mb-2 text-sm text-muted-foreground">
                                Add this deploy key to your repository
                                (read-only access is enough):
                            </p>
                            <pre
                                class="overflow-x-auto rounded-md bg-muted p-4 text-xs"
                                >{{
                                    site.git_connection.deploy_key.public_key
                                }}</pre
                            >
                        </div>
                        <div v-if="webhookUrl && webhookSecret">
                            <p class="mb-2 text-sm text-muted-foreground">
                                To push-to-deploy, add a webhook in your
                                repository settings with this payload URL and
                                secret (content type: application/json):
                            </p>
                            <pre
                                class="overflow-x-auto rounded-md bg-muted p-4 text-xs"
                            >
URL: {{ webhookUrl }}
Secret: {{ webhookSecret }}</pre
                            >
                        </div>
                    </CardContent>
                </Card>

                <Card v-if="site.status === 'active'">
                    <CardHeader>
                        <CardTitle>SSL certificate</CardTitle>
                        <CardDescription>
                            Issue a free Let's Encrypt certificate for this
                            domain
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            v-bind="SiteSslController.store.form(site.id)"
                            v-slot="{ processing }"
                        >
                            <Button
                                :disabled="
                                    processing ||
                                    site.ssl_status === 'pending' ||
                                    site.ssl_status === 'active'
                                "
                            >
                                {{
                                    site.ssl_status === 'active'
                                        ? 'Certificate active'
                                        : 'Issue certificate'
                                }}
                            </Button>
                        </Form>
                    </CardContent>
                </Card>
            </TabsContent>

            <TabsContent value="settings">
                <Card v-if="site.status === 'active' && canManageEnvironment">
                    <CardHeader>
                        <CardTitle>Environment</CardTitle>
                        <CardDescription
                            >The .env file deployed with this
                            site</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <Form
                            v-bind="
                                SiteEnvironmentController.update.form(site.id)
                            "
                            class="space-y-4"
                            v-slot="{ processing }"
                        >
                            <Textarea
                                name="env"
                                rows="10"
                                class="font-mono text-xs"
                                placeholder="APP_NAME=Example"
                                :default-value="env ?? ''"
                            />
                            <Button :disabled="processing"
                                >Save environment</Button
                            >
                        </Form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Deploy script</CardTitle>
                        <CardDescription
                            >Runs on every deployment</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <Button variant="outline" as-child>
                            <Link :href="editSite(site.id)">
                                <Pencil class="mr-2 size-4" />
                                Edit deploy script
                            </Link>
                        </Button>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Delete site</CardTitle>
                        <CardDescription
                            >Removes the site from this server. This does not
                            affect the machine itself.</CardDescription
                        >
                    </CardHeader>
                    <CardContent>
                        <DeleteSiteDialog :site="site" />
                    </CardContent>
                </Card>
            </TabsContent>
        </Tabs>
    </div>
</template>
