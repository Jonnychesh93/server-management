<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import CronController from '@/actions/App/Http/Controllers/CronController';
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
                Add cron job
            </Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="CronController.store.form(server.uuid)"
                reset-on-success
                v-slot="{ errors, processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>Add a cron job</DialogTitle>
                    <DialogDescription>
                        Written to /etc/cron.d on the server.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4 space-y-4">
                    <div class="grid gap-2">
                        <Label for="cron-schedule">Schedule</Label>
                        <Input
                            id="cron-schedule"
                            name="schedule"
                            placeholder="* * * * *"
                            default-value="* * * * *"
                            required
                        />
                        <InputError :message="errors.schedule" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cron-command">Command</Label>
                        <Input
                            id="cron-command"
                            name="command"
                            placeholder="php artisan schedule:run"
                            required
                        />
                        <InputError :message="errors.command" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="cron-user">User</Label>
                        <Input
                            id="cron-user"
                            name="user"
                            default-value="appuser"
                            required
                        />
                        <InputError :message="errors.user" />
                    </div>
                </div>

                <DialogFooter class="mt-6 gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        Add cron job
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
