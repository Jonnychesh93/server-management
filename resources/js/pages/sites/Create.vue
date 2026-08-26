<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { show as showServer } from '@/routes/servers';
import type { GithubInstallation, Server } from '@/types';

const { server, phpVersions, githubInstallation } = defineProps<{
    server: Server;
    phpVersions: string[];
    githubInstallation: GithubInstallation | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: server.name, href: showServer(server.id) },
            { title: 'Add site', href: '' },
        ],
    },
});

const phpVersion = ref(phpVersions[phpVersions.length - 1] ?? phpVersions[0]);
</script>

<template>
    <Head title="Add site" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <Heading
            title="Add a site"
            :description="`Add a site to ${server.name}`"
        />

        <Form
            v-bind="SiteController.store.form(server.id)"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="domain">Domain</Label>
                <Input
                    id="domain"
                    name="domain"
                    placeholder="example.com"
                    required
                />
                <InputError :message="errors.domain" />
            </div>

            <div class="grid gap-2">
                <Label for="php_version">PHP version</Label>
                <Select v-model="phpVersion" name="php_version">
                    <SelectTrigger id="php_version" class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="version in phpVersions"
                            :key="version"
                            :value="version"
                        >
                            PHP {{ version }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.php_version" />
            </div>

            <div v-if="githubInstallation" class="grid gap-2">
                <Label for="github_repository">
                    GitHub repository
                    <span class="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="github_repository"
                    name="github_repository"
                    placeholder="acme/example"
                />
                <p class="text-sm text-muted-foreground">
                    Any repository {{ githubInstallation.account_login }} has
                    granted this app access to. Deploys automatically on push,
                    no deploy key needed.
                </p>
                <InputError :message="errors.github_repository" />
            </div>

            <div class="grid gap-2">
                <Label for="repository">
                    Git repository
                    <span class="text-muted-foreground">(optional)</span>
                </Label>
                <Input
                    id="repository"
                    name="repository"
                    placeholder="git@github.com:acme/example.git"
                />
                <p class="text-sm text-muted-foreground">
                    We'll generate a deploy key you can add to this repository.
                    {{
                        githubInstallation
                            ? 'Leave the GitHub repository field above empty to use this instead.'
                            : ''
                    }}
                </p>
                <InputError :message="errors.repository" />
            </div>

            <div class="grid gap-2">
                <Label for="branch">Branch</Label>
                <Input id="branch" name="branch" placeholder="main" />
                <InputError :message="errors.branch" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Add site</Button>
            </div>
        </Form>
    </div>
</template>
