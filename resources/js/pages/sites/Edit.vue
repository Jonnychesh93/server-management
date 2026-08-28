<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import SiteController from '@/actions/App/Http/Controllers/SiteController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { show as showSite } from '@/routes/sites';
import type { Site } from '@/types';

const { site } = defineProps<{
    site: Site;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: site.domain, href: showSite(site.uuid) },
        { title: 'Deploy script', href: '' },
    ],
});
</script>

<template>
    <Head :title="`${site.domain} deploy script`" />

    <div class="flex flex-col space-y-6 p-4 md:p-6">
        <Heading
            title="Deploy script"
            description="Runs on every deployment, from the site's release directory"
        />

        <Form
            v-bind="SiteController.update.form(site.uuid)"
            class="max-w-2xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Textarea
                    name="deploy_script"
                    rows="12"
                    class="font-mono text-xs"
                    :default-value="site.deploy_script"
                    required
                />
                <InputError :message="errors.deploy_script" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">Save</Button>
            </div>
        </Form>
    </div>
</template>
