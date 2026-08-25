<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import ServerController from '@/actions/App/Http/Controllers/ServerController';
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
import type { Server } from '@/types';

const { server } = defineProps<{
    server: Server;
}>();
</script>

<template>
    <Dialog>
        <DialogTrigger as-child>
            <Button variant="destructive">
                <Trash2 class="mr-2 size-4" />
                Remove
            </Button>
        </DialogTrigger>
        <DialogContent>
            <Form
                v-bind="ServerController.destroy.form(server.id)"
                v-slot="{ processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>Remove {{ server.name }}?</DialogTitle>
                    <DialogDescription>
                        This removes the server from your team. It does not
                        affect the machine itself.
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter class="mt-6 gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        variant="destructive"
                        :disabled="processing"
                    >
                        Remove server
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
