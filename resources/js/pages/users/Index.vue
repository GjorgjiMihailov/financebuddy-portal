<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Users } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { ManagedUser, PaginatedUsers, UserRole } from '@/types';
import { USER_ROLE_LABELS, USER_ROLE_VARIANT } from '@/types';

defineProps<{
    users: PaginatedUsers;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Корисници', href: '/users' }],
    },
});

const deleteTarget = ref<ManagedUser | null>(null);

function doDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/users/${deleteTarget.value.id}`, {
        onFinish: () => { deleteTarget.value = null; },
    });
}
</script>

<template>
    <Head title="Корисници" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Корисници</h1>
                <p class="text-sm text-muted-foreground">
                    {{ users.total }}
                    {{ users.total === 1 ? 'корисник' : 'корисници' }}
                </p>
            </div>
            <Button as-child>
                <Link href="/users/create">
                    <Plus class="mr-2 size-4" />
                    Нов корисник
                </Link>
            </Button>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Корисник</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Улога</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Компании</th>
                        <th class="w-24 px-3 py-1.5" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="users.data.length === 0">
                        <td colspan="4" class="py-16 text-center text-muted-foreground">
                            <Users class="mx-auto mb-3 size-10 opacity-30" />
                            <p class="font-medium">Нема корисници</p>
                            <p class="text-xs">Додај нов корисник со копчето горе</p>
                        </td>
                    </tr>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="border-b last:border-0"
                    >
                        <td class="px-3 py-1.5">
                            <div class="font-medium">{{ user.name }}</div>
                            <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                        </td>
                        <td class="px-3 py-1.5">
                            <Badge
                                v-if="user.role"
                                :variant="USER_ROLE_VARIANT[user.role as UserRole]"
                            >
                                {{ USER_ROLE_LABELS[user.role as UserRole] }}
                            </Badge>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                        <td class="px-3 py-1.5">
                            <span v-if="user.companies.length === 0" class="text-muted-foreground">—</span>
                            <div v-else class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="company in user.companies"
                                    :key="company.id"
                                    variant="outline"
                                    class="font-normal"
                                >
                                    {{ company.name }}
                                </Badge>
                            </div>
                        </td>
                        <td class="px-3 py-1.5">
                            <div class="flex items-center justify-end gap-1">
                                <Button variant="ghost" size="icon" as-child>
                                    <Link :href="`/users/${user.id}/edit`">
                                        <Pencil class="size-4" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:text-destructive"
                                    @click="deleteTarget = user"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="users.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in users.links"
                :key="link.label"
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
                :disabled="!link.url"
                v-html="link.label"
                @click="link.url && router.visit(link.url, { preserveScroll: true })"
            />
        </div>
    </div>

    <Dialog :open="!!deleteTarget" @update:open="(v) => { if (!v) deleteTarget = null }">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Избриши корисник</DialogTitle>
                <DialogDescription>
                    Дали сте сигурни дека сакате да го избришете корисникот
                    <strong>{{ deleteTarget?.name }}</strong>?
                    Оваа акција не може да се врати.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="deleteTarget = null">Откажи</Button>
                <Button variant="destructive" @click="doDelete">Избриши</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
