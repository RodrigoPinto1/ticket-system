<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useRoute } from '@/composables/useRoute';
import axios from 'axios';
import { Shield, Users } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

defineOptions({
    layout: AppLayout,
});

type Role = 'owner' | 'operator' | 'client';

interface UserSummary {
    id: number;
    name: string;
    email: string;
}

interface RoleAssignment {
    id: number;
    inbox_id: number;
    user_id: number;
    role: Role;
    user: UserSummary;
}

interface InboxSummary {
    id: number;
    name: string;
    slug: string;
    roles: RoleAssignment[];
}

type Props = {
    inboxes: InboxSummary[];
    users: UserSummary[];
    canManage: boolean;
    allowedInboxIds: number[];
    canEdit: boolean;
};

const props = defineProps<Props>();
const canManage = computed(() => props.canManage !== false);
const route = useRoute();

// Local copy so we can update reactively after saves
const inboxes = ref(
    props.inboxes.map((inbox) => ({
        ...inbox,
        roles: [...inbox.roles],
    })),
);

const selectedInboxId = ref(
    inboxes.value[0] ? String(inboxes.value[0].id) : '',
);
const selectedInbox = computed(() => {
    const id = Number(selectedInboxId.value);
    return inboxes.value.find((inbox) => inbox.id === id) || null;
});

const canManageSelectedInbox = computed(() => {
    if (!props.canEdit) return false;
    const id = Number(selectedInboxId.value);
    return props.allowedInboxIds?.includes(id) ?? false;
});

const form = reactive({
    user_id: '',
    role: 'operator' as Role,
    processing: false,
    errors: {} as Record<string, string[]>,
});

const roleLabels: Record<Role, string> = {
    owner: 'Owner',
    operator: 'Operador',
    client: 'Cliente',
};



// Search support for users (filters by name or email)
const userSearch = ref('');
const filteredUsers = computed(() => {
    const q = userSearch.value.trim().toLowerCase();
    if (!q) return props.users;
    return props.users.filter(
        (u) => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q),
    );
});

const submit = async () => {
    if (!selectedInbox.value) return;
    form.processing = true;
    form.errors = {};
    try {
        const { data } = await axios.post(
            route('inboxes.roles.assign', selectedInbox.value.id),
            {
                user_id: form.user_id,
                role: form.role,
            },
        );

        const assignment: RoleAssignment = data.assignment;
        const currentInbox = selectedInbox.value;
        const idx = currentInbox.roles.findIndex(
            (r) => r.user_id === assignment.user_id,
        );
        if (idx >= 0) {
            currentInbox.roles[idx] = assignment;
        } else {
            currentInbox.roles.push(assignment);
        }
    } catch (e: any) {
        if (e?.response?.status === 422) {
            form.errors = e.response.data.errors || {};
        }
    } finally {
        form.processing = false;
    }
};

// Inline update for existing assignment rows
const updateRowRole = async (assignment: RoleAssignment, newRole: Role) => {
    if (!selectedInbox.value) return;
    // Optimistic UI update
    const prevRole = assignment.role;
    assignment.role = newRole;
    try {
        await axios.post(route('inboxes.roles.assign', selectedInbox.value.id), {
            user_id: assignment.user_id,
            role: newRole,
        });
    } catch (e: any) {
        // revert on failure
        assignment.role = prevRole;
        if (e?.response?.status === 422) {
            form.errors = e.response.data.errors || {};
        }
    }
};
</script>

<template>
    <div class="space-y-6 px-6 py-4">
        <Head title="Gestão de Cargos" />

        <div class="flex items-start justify-between gap-4">
            <Heading
                title="Gestão de cargos"
                description="Defina o cargo (owner, operador, cliente) dos utilizadores por inbox"
            />
        </div>

        <Card v-if="canManage" class="border border-border/70 bg-card/80 shadow-sm">
            <CardHeader class="space-y-2">
                <CardTitle class="flex items-center gap-2 text-base font-semibold">
                    <Shield class="h-4 w-4" />
                    Selecionar inbox
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                    <Label for="inbox">Inbox</Label>
                    <Select v-model="selectedInboxId">
                        <SelectTrigger id="inbox">
                            <SelectValue placeholder="Escolha a inbox" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="inbox in inboxes" :key="inbox.id" :value="String(inbox.id)">
                                {{ inbox.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="selectedInbox && !canManageSelectedInbox && !props.canEdit" class="mt-1 text-xs text-muted-foreground">
                        Apenas owners podem alterar cargos. Pode ver mas não editar.
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="user">Utilizador</Label>
                    <Select v-model="form.user_id">
                        <SelectTrigger id="user">
                            <SelectValue placeholder="Escolha a pessoa" />
                        </SelectTrigger>
                        <SelectContent>
                            <div class="sticky top-0 z-10 border-b bg-card px-2 py-2">
                                <Input id="userSearch" v-model="userSearch" placeholder="Nome ou email..." />
                            </div>
                            <template v-if="filteredUsers.length > 0">
                                <SelectItem v-for="user in filteredUsers" :key="user.id" :value="String(user.id)">
                                    <div class="flex flex-col">
                                        <span class="font-medium">{{ user.name }}</span>
                                        <span class="text-xs text-muted-foreground">{{ user.email }}</span>
                                    </div>
                                </SelectItem>
                            </template>
                            <div v-else class="px-3 py-2 text-xs text-muted-foreground">
                                Nenhum utilizador corresponde à pesquisa.
                            </div>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.user_id" class="text-sm text-destructive">
                        {{ form.errors.user_id[0] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="role">Cargo</Label>
                    <Select v-model="form.role">
                        <SelectTrigger id="role">
                            <SelectValue placeholder="Escolha o cargo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="owner">Owner</SelectItem>
                            <SelectItem value="operator">Operador</SelectItem>
                            <SelectItem value="client">Cliente</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.role" class="text-sm text-destructive">{{ form.errors.role[0] }}</p>
                </div>

                <div class="md:col-span-3">
                    <Button class="w-full" :disabled="!selectedInbox || !canManageSelectedInbox || form.processing" @click="submit">
                        Guardar cargo
                    </Button>
                </div>
            </CardContent>
        </Card>

        <Card
            v-if="canManage && selectedInbox"
            class="border border-border/70 bg-card/80 shadow-sm"
        >
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle
                    class="flex items-center gap-2 text-base font-semibold"
                >
                    <Users class="h-4 w-4" />
                    Utilizadores da inbox {{ selectedInbox.name }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nome</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Cargo</TableHead>
                            <TableHead class="w-[140px]">Alterar</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="selectedInbox.roles.length === 0">
                            <TableCell
                                colspan="3"
                                class="text-sm text-muted-foreground"
                            >
                                Ainda não existem utilizadores nesta inbox.
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-for="assignment in selectedInbox.roles"
                            :key="assignment.id"
                        >
                            <TableCell class="font-medium">{{
                                assignment.user.name
                            }}</TableCell>
                            <TableCell class="text-muted-foreground">{{
                                assignment.user.email
                            }}</TableCell>
                            <TableCell>
                                <Badge variant="outline">{{
                                    roleLabels[assignment.role]
                                }}</Badge>
                            </TableCell>
                            <TableCell>
                                <Select
                                    :disabled="!canManageSelectedInbox || form.processing"
                                    :model-value="assignment.role"
                                    @update:model-value="(val: Role) => updateRowRole(assignment, val)"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Escolha" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="owner">Owner</SelectItem>
                                        <SelectItem value="operator">Operador</SelectItem>
                                        <SelectItem value="client">Cliente</SelectItem>
                                    </SelectContent>
                                </Select>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
