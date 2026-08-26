<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AcceptTeamInvitationController from '@/actions/App/Http/Controllers/Teams/AcceptTeamInvitationController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import type { TeamInvitation } from '@/types';

const { invitation } = defineProps<{
    invitation: TeamInvitation;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Team invitation', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Team invitation" />

    <div class="p-4 md:p-6">
        <Card class="max-w-md">
            <CardHeader>
                <CardTitle>Join {{ invitation.team?.name }}</CardTitle>
                <CardDescription>
                    {{ invitation.invited_by?.name }} invited you to join as a
                    {{ invitation.role }}.
                </CardDescription>
            </CardHeader>
            <CardContent />
            <CardFooter>
                <Form
                    v-bind="
                        AcceptTeamInvitationController.store.form(
                            invitation.token,
                        )
                    "
                    v-slot="{ processing }"
                >
                    <Button type="submit" :disabled="processing">
                        Accept invitation
                    </Button>
                </Form>
            </CardFooter>
        </Card>
    </div>
</template>
