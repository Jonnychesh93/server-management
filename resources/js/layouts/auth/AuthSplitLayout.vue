<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Rocket, Server, ShieldCheck } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();

const highlights = [
    {
        icon: Server,
        text: 'Bootstrap Ubuntu servers over SSH — nginx, PHP, MySQL, and Redis, fully automated.',
    },
    {
        icon: Rocket,
        text: 'Zero-downtime deployments, triggered by a push or a click.',
    },
    {
        icon: ShieldCheck,
        text: 'SSH keys and secrets encrypted at rest, scoped to your team.',
    },
];
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-2">
        <div
            class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-600 via-indigo-700 to-indigo-950 p-10 text-white lg:flex"
        >
            <div
                class="absolute -top-24 -right-24 size-96 rounded-full bg-white/10 blur-3xl"
            />
            <div
                class="absolute -bottom-32 -left-16 size-96 rounded-full bg-indigo-400/20 blur-3xl"
            />

            <Link
                :href="home()"
                class="relative z-10 flex items-center gap-2 text-lg font-semibold"
            >
                <AppLogoIcon class="size-8" />
                {{ name }}
            </Link>

            <div class="relative z-10 space-y-6">
                <h2 class="text-2xl leading-snug font-semibold">
                    Provision servers, ship deployments, sleep easy.
                </h2>
                <ul class="space-y-4 text-sm text-indigo-100">
                    <li
                        v-for="highlight in highlights"
                        :key="highlight.text"
                        class="flex items-start gap-3"
                    >
                        <component
                            :is="highlight.icon"
                            class="mt-0.5 size-5 shrink-0"
                        />
                        <span>{{ highlight.text }}</span>
                    </li>
                </ul>
            </div>

            <p class="relative z-10 text-xs text-indigo-200/70">
                &copy; {{ new Date().getFullYear() }} {{ name }}
            </p>
        </div>

        <div
            class="flex flex-1 items-center justify-center overflow-y-auto bg-background p-6 lg:p-10"
        >
            <div class="w-full max-w-sm space-y-6">
                <Link
                    :href="home()"
                    class="mx-auto mb-2 flex items-center justify-center gap-2 font-semibold lg:hidden"
                >
                    <AppLogoIcon class="size-7 text-primary" />
                    {{ name }}
                </Link>

                <div class="space-y-1.5 text-center lg:text-left">
                    <h1
                        v-if="title"
                        class="text-2xl font-semibold tracking-tight"
                    >
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
