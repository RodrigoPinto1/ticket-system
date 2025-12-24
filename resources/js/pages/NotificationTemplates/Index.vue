<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { Copy, Edit, Eye, Mail, Plus } from 'lucide-vue-next';
import { computed, ref } from 'vue';

defineOptions({
    layout: AppLayout,
});

interface NotificationTemplate {
    id: number;
    slug: string;
    subject: string;
    body_html: string;
    locale: string | null;
    enabled: boolean;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    templates: NotificationTemplate[];
}>();

const createdTemplates = computed(() =>
    props.templates.filter((t) => t.slug === 'ticket_created'),
);

const repliedTemplates = computed(() =>
    props.templates.filter((t) => t.slug === 'ticket_replied'),
);

const otherTemplates = computed(() =>
    props.templates.filter(
        (t) => t.slug !== 'ticket_created' && t.slug !== 'ticket_replied',
    ),
);

const showPreview = ref(false);
const previewHtml = ref('');
const previewSubject = ref('');
const previewLoading = ref(false);

const loadPreview = async (templateId: number) => {
    previewLoading.value = true;
    try {
        const response = await axios.post(
            `/notification-templates/${templateId}/preview`,
        );
        previewSubject.value = response.data.subject;
        previewHtml.value = response.data.html;
        showPreview.value = true;
    } catch (error) {
        console.error('Failed to load preview:', error);
    } finally {
        previewLoading.value = false;
    }
};

const getTemplateIcon = (slug: string) => {
    if (slug.includes('created')) return '🎫';
    if (slug.includes('replied')) return '💬';
    return '📧';
};

const getTemplateColor = (slug: string) => {
    if (slug.includes('created')) return 'from-blue-500 to-cyan-500';
    if (slug.includes('replied')) return 'from-purple-500 to-pink-500';
    return 'from-gray-500 to-slate-500';
};

const useTemplate = async (template: NotificationTemplate) => {
    try {
        await axios.post(`/notification-templates/${template.id}/activate`);
        // Reload list to reflect active state
        router.get('/notification-templates', {}, { preserveScroll: true });
    } catch (e) {
        console.error('Falha ao ativar template:', e);
    }
};
</script>

<template>
    <div class="space-y-6 px-6 py-4">
        <Head title="Templates de E-mail" />

        <div class="flex items-start justify-between">
            <Heading
                title="Templates de Notificação"
                description="Gerir templates de e-mail enviados pelo sistema"
            />
            <Button as-child>
                <Link href="/notification-templates/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Novo Template
                </Link>
            </Button>
        </div>

        <!-- Ticket Created Templates -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">
                    🎫 Templates de Criação de Ticket
                </h2>
                <Badge variant="outline" class="text-xs">ticket_created</Badge>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="template in createdTemplates"
                    :key="template.id"
                    class="group overflow-hidden border border-border/60 bg-card/80 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl"
                    :class="{ 'ring-2 ring-blue-500': template.enabled }"
                >
                    <CardHeader class="bg-transparent pb-4">
                        <CardTitle class="flex items-center gap-3 text-base">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-500/15 text-lg shadow-inner"
                            >
                                {{ getTemplateIcon(template.slug) }}
                            </div>
                            <div class="min-w-0 flex-1 space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="text-[11px] tracking-[0.18em] uppercase opacity-80"
                                    >
                                        {{ template.slug }}
                                    </div>
                                    <Badge
                                        v-if="template.enabled"
                                        variant="default"
                                        class="px-1.5 py-0 text-[10px]"
                                        >ATIVO</Badge
                                    >
                                </div>
                                <div
                                    class="line-clamp-2 text-sm leading-tight font-semibold"
                                >
                                    {{ template.subject }}
                                </div>
                            </div>
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="space-y-3 pt-4">
                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between text-xs text-muted-foreground"
                            >
                                <span class="flex items-center gap-2">
                                    <Badge
                                        v-if="template.enabled"
                                        variant="default"
                                        class="px-2 py-0.5 text-[11px]"
                                    >
                                        Ativo
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="secondary"
                                        class="px-2 py-0.5 text-[11px]"
                                    >
                                        Desativado
                                    </Badge>
                                    <span
                                        class="rounded-full bg-muted/60 px-2 py-0.5 text-[11px] text-muted-foreground"
                                    >
                                        {{ template.locale || 'pt-PT' }}
                                    </span>
                                </span>
                                <span
                                    >Atualizado
                                    {{
                                        new Date(
                                            template.updated_at,
                                        ).toLocaleDateString('pt-PT')
                                    }}</span
                                >
                            </div>

                            <div
                                class="line-clamp-3 rounded-lg border bg-muted/40 px-3 py-2 text-xs text-muted-foreground"
                            >
                                {{
                                    template.body_html?.replace(
                                        /<[^>]+>/g,
                                        '',
                                    ) || '—'
                                }}
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="flex-1"
                                @click="useTemplate(template)"
                            >
                                <Copy class="mr-1 h-3.5 w-3.5" />
                                Usar
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                class="flex-1"
                                @click="loadPreview(template.id)"
                            >
                                <Eye class="mr-1 h-3.5 w-3.5" />
                                Preview
                            </Button>
                            <Button size="sm" as-child class="flex-1">
                                <Link
                                    :href="`/notification-templates/${template.id}/edit`"
                                >
                                    <Edit class="mr-1 h-3.5 w-3.5" />
                                    Editar
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Ticket Replied Templates -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">
                    💬 Templates de Resposta a Ticket
                </h2>
                <Badge variant="outline" class="text-xs">ticket_replied</Badge>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="template in repliedTemplates"
                    :key="template.id"
                    class="group overflow-hidden border border-border/60 bg-card/80 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl"
                    :class="{ 'ring-2 ring-green-500': template.enabled }"
                >
                    <CardHeader class="bg-transparent pb-4">
                        <CardTitle class="flex items-center gap-3 text-base">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-green-500/15 text-lg shadow-inner"
                            >
                                {{ getTemplateIcon(template.slug) }}
                            </div>
                            <div class="min-w-0 flex-1 space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="text-[11px] tracking-[0.18em] uppercase opacity-80"
                                    >
                                        {{ template.slug }}
                                    </div>
                                    <Badge
                                        v-if="template.enabled"
                                        variant="default"
                                        class="bg-green-600 px-1.5 py-0 text-[10px]"
                                        >ATIVO</Badge
                                    >
                                </div>
                                <div
                                    class="line-clamp-2 text-sm leading-tight font-semibold"
                                >
                                    {{ template.subject }}
                                </div>
                            </div>
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="space-y-3 pt-4">
                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between text-xs text-muted-foreground"
                            >
                                <span class="flex items-center gap-2">
                                    <Badge
                                        v-if="template.enabled"
                                        variant="default"
                                        class="px-2 py-0.5 text-[11px]"
                                    >
                                        Ativo
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="secondary"
                                        class="px-2 py-0.5 text-[11px]"
                                    >
                                        Desativado
                                    </Badge>
                                    <span
                                        class="rounded-full bg-muted/60 px-2 py-0.5 text-[11px] text-muted-foreground"
                                    >
                                        {{ template.locale || 'pt-PT' }}
                                    </span>
                                </span>
                                <span
                                    >Atualizado
                                    {{
                                        new Date(
                                            template.updated_at,
                                        ).toLocaleDateString('pt-PT')
                                    }}</span
                                >
                            </div>

                            <div
                                class="line-clamp-3 rounded-lg border bg-muted/40 px-3 py-2 text-xs text-muted-foreground"
                            >
                                {{
                                    template.body_html?.replace(
                                        /<[^>]+>/g,
                                        '',
                                    ) || '—'
                                }}
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="flex-1"
                                @click="useTemplate(template)"
                            >
                                <Copy class="mr-1 h-3.5 w-3.5" />
                                Usar
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                class="flex-1"
                                @click="loadPreview(template.id)"
                            >
                                <Eye class="mr-1 h-3.5 w-3.5" />
                                Preview
                            </Button>
                            <Button size="sm" as-child class="flex-1">
                                <Link
                                    :href="`/notification-templates/${template.id}/edit`"
                                >
                                    <Edit class="mr-1 h-3.5 w-3.5" />
                                    Editar
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Other Templates -->
        <div v-if="otherTemplates.length > 0" class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">📧 Outros Templates</h2>
            </div>
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="template in otherTemplates"
                    :key="template.id"
                    class="group overflow-hidden border border-border/60 bg-card/80 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl"
                >
                    <CardHeader class="bg-transparent pb-4">
                        <CardTitle class="flex items-center gap-3 text-base">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-lg shadow-inner"
                            >
                                {{ getTemplateIcon(template.slug) }}
                            </div>
                            <div class="min-w-0 flex-1 space-y-0.5">
                                <div
                                    class="text-[11px] tracking-[0.18em] uppercase opacity-80"
                                >
                                    {{ template.slug }}
                                </div>
                                <div
                                    class="line-clamp-2 text-sm leading-tight font-semibold"
                                >
                                    {{ template.subject }}
                                </div>
                            </div>
                        </CardTitle>
                    </CardHeader>

                    <CardContent class="space-y-3 pt-4">
                        <div class="space-y-2">
                            <div
                                class="flex items-center justify-between text-xs text-muted-foreground"
                            >
                                <span class="flex items-center gap-2">
                                    <Badge
                                        v-if="template.enabled"
                                        variant="default"
                                        class="px-2 py-0.5 text-[11px]"
                                    >
                                        Ativo
                                    </Badge>
                                    <Badge
                                        v-else
                                        variant="secondary"
                                        class="px-2 py-0.5 text-[11px]"
                                    >
                                        Desativado
                                    </Badge>
                                    <span
                                        class="rounded-full bg-muted/60 px-2 py-0.5 text-[11px] text-muted-foreground"
                                    >
                                        {{ template.locale || 'pt-PT' }}
                                    </span>
                                </span>
                                <span
                                    >Atualizado
                                    {{
                                        new Date(
                                            template.updated_at,
                                        ).toLocaleDateString('pt-PT')
                                    }}</span
                                >
                            </div>

                            <div
                                class="line-clamp-3 rounded-lg border bg-muted/40 px-3 py-2 text-xs text-muted-foreground"
                            >
                                {{
                                    template.body_html?.replace(
                                        /<[^>]+>/g,
                                        '',
                                    ) || '—'
                                }}
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                class="flex-1"
                                @click="useTemplate(template)"
                            >
                                <Copy class="mr-1 h-3.5 w-3.5" />
                                Usar
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                class="flex-1"
                                @click="loadPreview(template.id)"
                            >
                                <Eye class="mr-1 h-3.5 w-3.5" />
                                Preview
                            </Button>
                            <Button size="sm" as-child class="flex-1">
                                <Link
                                    :href="`/notification-templates/${template.id}/edit`"
                                >
                                    <Edit class="mr-1 h-3.5 w-3.5" />
                                    Editar
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Card v-if="!templates || templates.length === 0">
            <CardContent class="py-12 text-center text-muted-foreground">
                <Mail class="mx-auto mb-4 h-16 w-16 opacity-20" />
                <p class="font-medium">Nenhum template encontrado</p>
                <p class="text-sm">
                    Execute as seeds para criar templates padrão
                </p>
            </CardContent>
        </Card>

        <!-- Preview Modal -->
        <div
            v-if="showPreview"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
            @click.self="showPreview = false"
        >
            <Card
                class="max-h-[90vh] w-full max-w-4xl overflow-hidden shadow-xl"
            >
                <CardHeader class="border-b">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex flex-1 items-center gap-3">
                            <CardTitle class="text-base"
                                >Preview do email</CardTitle
                            >
                            <span class="text-sm text-muted-foreground">•</span>
                            <span class="truncate text-sm font-semibold">{{
                                previewSubject
                            }}</span>
                        </div>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="showPreview = false"
                        >
                            Fechar
                        </Button>
                    </div>
                </CardHeader>
                <CardContent class="max-h-[calc(90vh-120px)] overflow-auto p-0">
                    <div
                        v-if="previewLoading"
                        class="flex items-center justify-center p-12"
                    >
                        <div class="text-muted-foreground">A carregar...</div>
                    </div>
                    <iframe
                        v-else
                        :srcdoc="previewHtml"
                        class="h-full min-h-[600px] w-full border-0"
                    ></iframe>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
