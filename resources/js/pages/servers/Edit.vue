<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import ServerController from '@/actions/App/Http/Controllers/ServerController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as serversIndex, show as showServer } from '@/routes/servers';
import type { Server } from '@/types';

const { server } = defineProps<{
    server: Server;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Servers', href: serversIndex() },
        { title: server.name, href: showServer(server.uuid) },
        { title: 'Edit', href: '' },
    ],
});
</script>

<template>
    <Head :title="`Edit ${server.name}`" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <Heading
            title="Edit server"
            description="Update this server's connection details"
        />

        <Form
            v-bind="ServerController.update.form(server.uuid)"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Server name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="server.name"
                    required
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="ip_address">IP address</Label>
                <Input
                    id="ip_address"
                    name="ip_address"
                    :default-value="server.ip_address"
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
                        :default-value="server.ssh_port"
                        required
                    />
                    <InputError :message="errors.ssh_port" />
                </div>

                <div class="grid gap-2">
                    <Label for="ssh_user">SSH user</Label>
                    <Input
                        id="ssh_user"
                        name="ssh_user"
                        :default-value="server.ssh_user"
                        required
                    />
                    <InputError :message="errors.ssh_user" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Save</Button>
            </div>
        </Form>
    </div>
</template>
