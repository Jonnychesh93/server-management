<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import ServerController from '@/actions/App/Http/Controllers/ServerController';
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
import { Textarea } from '@/components/ui/textarea';
import { index as serversIndex } from '@/routes/servers';
import type { BootstrapCredentialType, ServerOsOption } from '@/types';

const { operatingSystems, credentialTypes } = defineProps<{
    operatingSystems: ServerOsOption[];
    credentialTypes: BootstrapCredentialType[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Servers', href: serversIndex() },
            { title: 'Add server', href: '' },
        ],
    },
});

const credentialLabels: Record<BootstrapCredentialType, string> = {
    password: 'Root password',
    private_key: 'Private key',
};

const os = ref(operatingSystems[0]?.value ?? '');
const credentialType = ref(credentialTypes[0] ?? 'password');
</script>

<template>
    <Head title="Add server" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <Heading
            title="Add a server"
            description="Connect a server so it can be provisioned and managed here"
        />

        <Form
            v-bind="ServerController.store.form()"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Server name</Label>
                <Input id="name" name="name" placeholder="web-1" required />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="ip_address">IP address</Label>
                <Input
                    id="ip_address"
                    name="ip_address"
                    placeholder="203.0.113.10"
                    required
                />
                <InputError :message="errors.ip_address" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="grid gap-2">
                    <Label for="ssh_port">SSH port</Label>
                    <Input
                        id="ssh_port"
                        name="ssh_port"
                        type="number"
                        default-value="22"
                        required
                    />
                    <InputError :message="errors.ssh_port" />
                </div>

                <div class="grid gap-2">
                    <Label for="ssh_user">SSH user</Label>
                    <Input
                        id="ssh_user"
                        name="ssh_user"
                        default-value="root"
                        required
                    />
                    <InputError :message="errors.ssh_user" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="os">Operating system</Label>
                <Select v-model="os" name="os">
                    <SelectTrigger id="os" class="w-full">
                        <SelectValue placeholder="Select an operating system" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in operatingSystems"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.os" />
            </div>

            <div class="grid gap-2">
                <Label for="bootstrap_credential_type">
                    How should we authenticate for the first connection?
                </Label>
                <Select
                    v-model="credentialType"
                    name="bootstrap_credential_type"
                >
                    <SelectTrigger
                        id="bootstrap_credential_type"
                        class="w-full"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="type in credentialTypes"
                            :key="type"
                            :value="type"
                        >
                            {{ credentialLabels[type] }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.bootstrap_credential_type" />
            </div>

            <div class="grid gap-2">
                <Label for="bootstrap_credential">
                    {{ credentialLabels[credentialType] }}
                </Label>
                <Textarea
                    id="bootstrap_credential"
                    name="bootstrap_credential"
                    rows="4"
                    :placeholder="
                        credentialType === 'password'
                            ? 'The server\'s root password'
                            : '-----BEGIN OPENSSH PRIVATE KEY-----'
                    "
                    required
                />
                <p class="text-sm text-muted-foreground">
                    Used once to install our own SSH key on the server, then
                    discarded.
                </p>
                <InputError :message="errors.bootstrap_credential" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Add server</Button>
            </div>
        </Form>
    </div>
</template>
