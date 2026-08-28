<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
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
import type { Site } from '@/types';

const { site } = defineProps<{
    site: Site;
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
                v-bind="SiteController.destroy.form(site.uuid)"
                v-slot="{ processing }"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>Remove {{ site.domain }}?</DialogTitle>
                    <DialogDescription>
                        This removes the site from the dashboard. It does not
                        remove any files from the server.
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
                        Remove site
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
