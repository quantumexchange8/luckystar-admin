<script setup>
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import { isDark, toggleDarkMode } from "@/Composables/index.js";
import { IconMoon, IconSun, IconLanguage } from "@tabler/icons-vue";
import {Button, Menu, Card} from "primevue";
import {loadLanguageAsync} from "laravel-vue-i18n";
import { ref } from "vue";
import AuthHeader from "@/Components/Auth/AuthHeader.vue";
import { Link } from '@inertiajs/vue3';

const menu = ref();
const locales = ref([
    {
        label: 'English',
        command: () => {
            changeLanguage('en');
        }
    },
    {
        label: '中文',
        command: () => {
            changeLanguage('cn')
        }
    }
]);

const toggle = (event) => {
    menu.value.toggle(event);
};

const changeLanguage = async (langVal) => {
    try {
        await loadLanguageAsync(langVal);
        await axios.get(`/locale/${langVal}`);
    } catch (error) {
        console.error('Error changing locale:', error);
    }
};
</script>

<template>
    <div class="flex flex-col min-h-screen bg-surface-100 dark:bg-surface-950">
        <!-- Top Buttons -->
        <div class="flex items-center justify-end gap-2 p-5 shrink-0">
            <Button
                severity="secondary"
                outlined
                aria-label="Mode"
                class="min-w-12"
                @click="() => { toggleDarkMode() }"
            >
                <template #icon>
                    <IconSun v-if="!isDark" size="16" />
                    <IconMoon v-if="isDark" size="16" />
                </template>
            </Button>

            <Button
                severity="secondary"
                outlined
                aria-label="Mode"
                class="min-w-12"
                @click="toggle"
            >
                <template #icon>
                    <IconLanguage size="16" stroke-width="1.5" />
                </template>
            </Button>
        </div>

        <div
            class="flex flex-col gap-8 items-center pt-6 sm:justify-center"
        >
            <div class="flex flex-col items-center">
                <Link href="/">
                    <ApplicationLogo class="h-20 w-20 fill-current text-surface-500" />
                </Link>
                <AuthHeader
                    :header="$t('public.welcome_to_luckystar')"
                    :caption="$t('public.admin_portal')"
                />
            </div>

            <Card class="w-full max-w-[300px] sm:max-w-md">
                <template #content>
                    <slot />
                </template>
            </Card>
        </div>
    </div>

    <Menu
        ref="menu"
        id="overlay_menu"
        :model="locales"
        :popup="true"
        class="w-32"
    />
</template>
