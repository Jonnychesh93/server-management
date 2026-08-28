<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import DaemonController from '@/actions/App/Http/Controllers/DaemonController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Server } from '@/types';

const { server } = defineProps<{
    server: Server;
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button variant="outline">
                <Plus class="mr-2 size-4" />
                Add daemon
            </Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="DaemonController.store.form(server.uuid)"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>Add a daemon</DialogTitle>
                    <DialogDescription>
                        A persistent background process, managed by Supervisor.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4 space-y-4">
                    <div class="grid gap-2">
                        <Label for="daemon-command">Command</Label>
                        <Input
                            id="daemon-command"
                            name="command"
                            placeholder="php artisan queue:work"
                            required
                        />
                        <InputError :message="errors.command" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="daemon-directory">Directory</Label>
                        <Input
                            id="daemon-directory"
                            name="directory"
                            default-value="/home/appuser"
                            required
                        />
                        <InputError :message="errors.directory" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="daemon-user">User</Label>
                            <Input
                                id="daemon-user"
                                name="user"
                                default-value="appuser"
                                required
                            />
                            <InputError :message="errors.user" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="daemon-processes">Processes</Label>
                            <Input
                                id="daemon-processes"
                                name="processes"
                                type="number"
                                default-value="1"
                                required
                            />
                            <InputError :message="errors.processes" />
                        </div>
                    </div>
                </div>

                <DialogFooter class="mt-6 gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        Add daemon
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
