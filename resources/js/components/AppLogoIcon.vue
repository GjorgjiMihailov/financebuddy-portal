<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps<{ className?: HTMLAttributes['class'] }>();
</script>

<template>
    <img
        src="https://i.imgur.com/fEiVbXj.png"
        alt="FinanceBuddy.mk"
        :class="className"
        v-bind="$attrs"
    />
</template>