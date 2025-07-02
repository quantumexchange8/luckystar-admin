<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import {
    InputText,
    Password,
    Checkbox,
    Button,
} from "primevue";
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <form
            @submit.prevent="submit"
            class="flex flex-col gap-5 self-stretch"
        >
            <div class="flex flex-col gap-1 items-start self-stretch">
                <InputLabel for="email" value="Email" />

                <InputText
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    autofocus
                    autocomplete="username"
                    placeholder="Enter your email"
                    :invalid="!!form.errors.email"
                />

                <InputError :message="form.errors.email" />
            </div>

            <div class="flex flex-col gap-1 items-start self-stretch">
                <InputLabel for="password" value="Password" />

                <Password
                    id="password"
                    class="block w-full"
                    v-model="form.password"
                    toggleMask
                    :inputStyle="{'width': '100%'}"
                    :style="{'width': '100%'}"
                    :invalid="!!form.errors.password"
                    :feedback="false"
                />

                <InputError :message="form.errors.password" />
            </div>

            <label class="flex items-center">
                <Checkbox name="remember" v-model:checked="form.remember" :binary="true"/>
                <span class="ms-2 text-sm text-surface-600 dark:text-surface-400"
                >Remember me
                        </span>
            </label>

            <div class="flex flex-col gap-1 items-center self-stretch">
                <Button
                    type="submit"
                    :class="[
                        'w-full',
                        { 'opacity-25': form.processing }
                    ]"
                    :disabled="form.processing"
                    :label="'Login'"
                />
            </div>
        </form>
    </GuestLayout>
</template>
