<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import DatabaseController from '@/actions/App/Http/Controllers/DatabaseController';
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

const open = ref(false);
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogTrigger as-child>
            <Button variant="outline">
                <Plus class="mr-2 size-4" />
                Add database
            </Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="DatabaseController.store.form(server.uuid)"
                reset-on-success
                @success="open = false"
                v-slot="{ errors, processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>Add a database</DialogTitle>
                    <DialogDescription>
                        Creates a MySQL database and a dedicated user on this
                        server. The password is generated for you.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4 space-y-4">
                    <div class="grid gap-2">
                        <Label for="database-name">Database name</Label>
                        <Input
                            id="database-name"
                            name="name"
                            placeholder="my_app"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="database-username">Username</Label>
                        <Input
                            id="database-username"
                            name="username"
                            placeholder="my_app"
                            required
                        />
                        <InputError :message="errors.username" />
                    </div>
                </div>

                <DialogFooter class="mt-6 gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        Add database
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
