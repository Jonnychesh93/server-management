<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus, Settings } from '@lucide/vue';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { update as switchTeam } from '@/routes/current-team';
import { create as createTeam, show as showTeam } from '@/routes/teams';

const page = usePage();
const currentTeam = computed(() => page.props.currentTeam);
const teams = computed(() => page.props.teams);
const { isMobile, state } = useSidebar();

function selectTeam(teamId: number): void {
    if (teamId === currentTeam.value?.id) {
        return;
    }

    router.put(switchTeam.url(teamId));
}
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                    >
                        <span class="truncate font-medium">{{
                            currentTeam?.name ?? 'Select team'
                        }}</span>
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'left'
                              : 'bottom'
                    "
                    align="start"
                    :side-offset="4"
                >
                    <DropdownMenuLabel class="text-xs text-muted-foreground">
                        Teams
                    </DropdownMenuLabel>
                    <DropdownMenuItem
                        v-for="team in teams"
                        :key="team.id"
                        class="cursor-pointer"
                        @click="selectTeam(team.id)"
                    >
                        <Check
                            :class="[
                                'mr-2 size-4',
                                team.id === currentTeam?.id
                                    ? 'opacity-100'
                                    : 'opacity-0',
                            ]"
                        />
                        {{ team.name }}
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem v-if="currentTeam" as-child>
                        <Link
                            class="cursor-pointer"
                            :href="showTeam(currentTeam.id)"
                        >
                            <Settings class="mr-2 size-4" />
                            Team settings
                        </Link>
                    </DropdownMenuItem>
                    <DropdownMenuItem as-child>
                        <Link class="cursor-pointer" :href="createTeam()">
                            <Plus class="mr-2 size-4" />
                            Create team
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
