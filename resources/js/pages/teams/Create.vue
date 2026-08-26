<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Create team', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Create team" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <Heading
            title="Create a team"
            description="Teams have their own servers, sites, and members"
        />

        <Form
            v-bind="TeamController.store.form()"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Team name</Label>
                <Input id="name" name="name" placeholder="Acme Inc" required />
                <InputError :message="errors.name" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Create team</Button>
            </div>
        </Form>
    </div>
</template>
