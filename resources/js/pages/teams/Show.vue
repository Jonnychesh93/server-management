<script setup lang="ts">
import { Form, Head, Link, router, usePage } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import GithubInstallationController from '@/actions/App/Http/Controllers/GithubInstallationController';
import TeamController from '@/actions/App/Http/Controllers/Teams/TeamController';
import TeamInvitationController from '@/actions/App/Http/Controllers/Teams/TeamInvitationController';
import TeamMemberController from '@/actions/App/Http/Controllers/Teams/TeamMemberController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { install as installGithub } from '@/routes/github';
import type { Team, TeamInvitation, TeamMember, TeamRole } from '@/types';

const { team, canManage } = defineProps<{
    team: Team & { users: TeamMember[]; invitations: TeamInvitation[] };
    canManage: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: team.name, href: '' }],
    },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth.user.id);

const isOwner = computed(
    () =>
        team.users.find((member) => member.id === currentUserId.value)?.pivot
            .role === 'owner',
);

const roleLabels: Record<TeamRole, string> = {
    owner: 'Owner',
    admin: 'Admin',
    member: 'Member',
};

const assignableRoles: TeamRole[] = ['admin', 'member'];

const inviteRole = ref<TeamRole>('member');

function updateMemberRole(member: TeamMember, role: TeamRole): void {
    router.put(TeamMemberController.update.url([team.id, member.id]), {
        role,
    });
}
</script>

<template>
    <Head :title="team.name" />

    <div class="flex flex-col space-y-8 p-4 md:p-6">
        <Heading :title="team.name" description="Team settings and members" />

        <Card v-if="canManage">
            <CardHeader>
                <CardTitle>Team name</CardTitle>
                <CardDescription
                    >Update your team's display name</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Form
                    v-bind="TeamController.update.form(team.id)"
                    class="flex max-w-md items-start gap-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="flex-1 space-y-2">
                        <Input
                            name="name"
                            :default-value="team.name"
                            required
                        />
                        <InputError :message="errors.name" />
                    </div>
                    <Button :disabled="processing">Save</Button>
                </Form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Members</CardTitle>
                <CardDescription
                    >People with access to this team's servers</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-for="member in team.users"
                    :key="member.id"
                    class="flex items-center justify-between gap-4"
                >
                    <div>
                        <p class="font-medium">{{ member.name }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ member.email }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Badge
                            v-if="member.pivot.role === 'owner' || !canManage"
                            variant="secondary"
                        >
                            {{ roleLabels[member.pivot.role] }}
                        </Badge>
                        <Select
                            v-else
                            :model-value="member.pivot.role"
                            @update:model-value="
                                (role) =>
                                    updateMemberRole(member, role as TeamRole)
                            "
                        >
                            <SelectTrigger class="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in assignableRoles"
                                    :key="role"
                                    :value="role"
                                >
                                    {{ roleLabels[role] }}
                                </SelectItem>
                            </SelectContent>
                        </Select>

                        <Dialog
                            v-if="canManage && member.pivot.role !== 'owner'"
                        >
                            <DialogTrigger as-child>
                                <Button variant="ghost" size="icon">
                                    <Trash2 class="size-4" />
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <Form
                                    v-bind="
                                        TeamMemberController.destroy.form([
                                            team.id,
                                            member.id,
                                        ])
                                    "
                                    v-slot="{ processing }"
                                >
                                    <DialogHeader class="space-y-3">
                                        <DialogTitle
                                            >Remove
                                            {{ member.name }}?</DialogTitle
                                        >
                                        <DialogDescription>
                                            They will immediately lose access to
                                            this team.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter class="mt-6 gap-2">
                                        <DialogClose as-child>
                                            <Button variant="secondary"
                                                >Cancel</Button
                                            >
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            :disabled="processing"
                                        >
                                            Remove
                                        </Button>
                                    </DialogFooter>
                                </Form>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card v-if="canManage">
            <CardHeader>
                <CardTitle>Invite a member</CardTitle>
                <CardDescription
                    >Send an email invitation to join this team</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-4">
                <div
                    v-for="invitation in team.invitations"
                    :key="invitation.id"
                    class="flex items-center justify-between gap-4 text-sm"
                >
                    <div>
                        <span class="font-medium">{{ invitation.email }}</span>
                        <span class="ml-2 text-muted-foreground"
                            >{{ roleLabels[invitation.role] }} &middot;
                            invited</span
                        >
                    </div>
                    <Link
                        :href="
                            TeamInvitationController.destroy.url([
                                team.id,
                                invitation.id,
                            ])
                        "
                        method="delete"
                        as="button"
                        class="text-sm text-muted-foreground underline hover:text-foreground"
                    >
                        Revoke
                    </Link>
                </div>

                <Separator v-if="team.invitations.length > 0" />

                <Form
                    v-bind="TeamInvitationController.store.form(team.id)"
                    reset-on-success
                    class="flex flex-wrap items-start gap-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="min-w-48 flex-1 space-y-2">
                        <Label for="invite-email" class="sr-only">Email</Label>
                        <Input
                            id="invite-email"
                            name="email"
                            type="email"
                            placeholder="teammate@example.com"
                            required
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="space-y-2">
                        <Select v-model="inviteRole" name="role">
                            <SelectTrigger class="w-32">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="role in assignableRoles"
                                    :key="role"
                                    :value="role"
                                >
                                    {{ roleLabels[role] }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.role" />
                    </div>

                    <Button :disabled="processing">Send invite</Button>
                </Form>
            </CardContent>
        </Card>

        <Card v-if="canManage">
            <CardHeader>
                <CardTitle>GitHub</CardTitle>
                <CardDescription>
                    Connect a GitHub account to deploy repositories without
                    managing your own deploy keys
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    v-if="team.github_installation"
                    class="flex items-center justify-between"
                >
                    <p class="text-sm">
                        Connected as
                        <span class="font-medium">{{
                            team.github_installation.account_login
                        }}</span>
                    </p>
                    <Form
                        v-bind="GithubInstallationController.destroy.form()"
                        v-slot="{ processing }"
                    >
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="processing"
                        >
                            Disconnect
                        </Button>
                    </Form>
                </div>
                <Button v-else as-child>
                    <a :href="installGithub.url()">Connect GitHub</a>
                </Button>
            </CardContent>
        </Card>

        <Card v-if="isOwner" class="border-destructive/50">
            <CardHeader>
                <CardTitle>Delete team</CardTitle>
                <CardDescription
                    >Permanently delete this team and its
                    servers</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Dialog>
                    <DialogTrigger as-child>
                        <Button variant="destructive">Delete team</Button>
                    </DialogTrigger>
                    <DialogContent>
                        <Form
                            v-bind="TeamController.destroy.form(team.id)"
                            v-slot="{ processing }"
                        >
                            <DialogHeader class="space-y-3">
                                <DialogTitle
                                    >Delete {{ team.name }}?</DialogTitle
                                >
                                <DialogDescription>
                                    This permanently deletes the team, its
                                    members, and every server recorded under it.
                                    This cannot be undone.
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
                                    Delete team
                                </Button>
                            </DialogFooter>
                        </Form>
                    </DialogContent>
                </Dialog>
            </CardContent>
        </Card>
    </div>
</template>
