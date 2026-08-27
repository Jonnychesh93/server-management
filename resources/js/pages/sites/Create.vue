<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import GithubRepositoryController from '@/actions/App/Http/Controllers/GithubRepositoryController';
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
import type { GithubInstallation, GithubRepository, Server } from '@/types';

const { server, phpVersions, githubInstallation, repositories } =
    defineProps<{
        server: Server;
        phpVersions: string[];
        githubInstallation: GithubInstallation | null;
        repositories: GithubRepository[];
    }>();

setLayoutProps({
    breadcrumbs: [
        { title: server.name, href: showServer(server.id) },
        { title: 'Add site', href: '' },
    ],
});

const phpVersion = ref(phpVersions[phpVersions.length - 1] ?? phpVersions[0]);

const selectedRepository = ref('');
const selectedBranch = ref('');
const branches = ref<string[]>([]);
const loadingBranches = ref(false);

watch(selectedRepository, async (fullName) => {
    branches.value = [];
    selectedBranch.value = '';

    if (!fullName) {
        return;
    }

    const repository = repositories.find((r) => r.full_name === fullName);
    selectedBranch.value = repository?.default_branch ?? '';

    loadingBranches.value = true;

    try {
        const [owner, repo] = fullName.split('/');
        const response = await fetch(
            GithubRepositoryController.branches.url({ owner, repo }),
        );
        const data = await response.json();
        branches.value = data.branches ?? [];
    } finally {
        loadingBranches.value = false;
    }
});
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
                <Select
                    v-model="selectedRepository"
                    name="github_repository"
                >
                    <SelectTrigger id="github_repository" class="w-full">
                        <SelectValue placeholder="Select a repository" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="repository in repositories"
                            :key="repository.full_name"
                            :value="repository.full_name"
                        >
                            {{ repository.full_name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p class="text-sm text-muted-foreground">
                    Any repository {{ githubInstallation.account_login }} has
                    granted this app access to. Deploys automatically on push,
                    no deploy key needed.
                </p>
                <InputError :message="errors.github_repository" />
            </div>

            <div v-if="selectedRepository" class="grid gap-2">
                <Label for="branch">Branch</Label>
                <Select v-model="selectedBranch" name="branch">
                    <SelectTrigger id="branch" class="w-full">
                        <SelectValue
                            :placeholder="
                                loadingBranches
                                    ? 'Loading branches…'
                                    : 'Select a branch'
                            "
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="branch in branches"
                            :key="branch"
                            :value="branch"
                        >
                            {{ branch }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.branch" />
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

            <div v-if="!selectedRepository" class="grid gap-2">
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
