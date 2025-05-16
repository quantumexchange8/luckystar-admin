<script setup>
import {Card} from "primevue";
</script>

<template>
    <div class="w-full flex flex-col md:flex-row gap-5 self-stretch">
        <Card class="w-full"></Card>

        <div class="w-full flex flex-col gap-5 md:max-w-[262px] xl:max-w-[358px] 2xl:max-w-[560px]">
            <Card></Card>
            <Card></Card>
        </div>
    </div>
</template>
