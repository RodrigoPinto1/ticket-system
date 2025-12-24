<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { useRoute } from '@/composables/useRoute';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Eye } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

defineOptions({
    layout: AppLayout,
});

interface Inbox {
    id: number;
    name: string;
}

const props = defineProps<{
    inboxes: Inbox[];
    defaultBodyHtml: string;
    slug?: string;
    subject?: string;
    body_html?: string;
    locale?: string;
}>();

const form = useForm({
    slug: props.slug || '',
    subject: props.subject || '',
    body_html: props.body_html || props.defaultBodyHtml,
    locale: props.locale || 'pt-PT',
    inbox_id: null as number | null,
    enabled: true,
});

const selectedInboxId = ref('_global');
const selectedSlug = ref(props.slug || '');
const route = useRoute();
const showPreview = ref(true);
const templateType = ref<'created' | 'replied' | 'custom'>('custom');
const previewFrame = ref<HTMLIFrameElement | null>(null);

// Sync selectedSlug with form.slug if provided via props
onMounted(() => {
    if (props.slug === 'ticket_created') {
        templateType.value = 'created';
        form.slug = 'ticket_created';
    } else if (props.slug === 'ticket_replied') {
        templateType.value = 'replied';
        form.slug = 'ticket_replied';
    } else if (props.slug) {
        templateType.value = 'custom';
        form.slug = props.slug;
    }
});

// Sample data for preview
const previewData = {
    ticket: {
        number: '#12345',
        subject: 'Problema com o sistema',
        content: 'Tenho um problema que precisa de ser resolvido.',
        url: 'https://example.com/tickets/12345',
    },
    message: {
        content: 'Esta é uma resposta de exemplo ao ticket.',
    },
    app: {
        name: 'Sistema de Tickets',
    },
};

const renderedPreview = computed(() => {
    let html = form.body_html;

    // Replace variables with sample data
    html = html.replace(/\{\{ticket\.number\}\}/g, previewData.ticket.number);
    html = html.replace(/\{\{ticket\.subject\}\}/g, previewData.ticket.subject);
    html = html.replace(/\{\{ticket\.content\}\}/g, previewData.ticket.content);
    html = html.replace(/\{\{ticket\.url\}\}/g, previewData.ticket.url);
    html = html.replace(
        /\{\{message\.content\}\}/g,
        previewData.message.content,
    );
    html = html.replace(/\{\{app\.name\}\}/g, previewData.app.name);

    return html;
});

// Function to update iframe content
const updateIframeContent = () => {
    if (!previewFrame.value) {
        console.log('Iframe ref not ready yet');
        return;
    }

    try {
        const doc =
            previewFrame.value.contentDocument ||
            previewFrame.value.contentWindow?.document;
        if (doc) {
            doc.open();
            doc.write(renderedPreview.value);
            doc.close();
            console.log('Preview updated successfully');
        }
    } catch (error) {
        console.error('Error updating iframe:', error);
    }
};

// Update iframe content when preview changes
watch(renderedPreview, () => {
    // Small delay to ensure iframe is ready
    setTimeout(updateIframeContent, 100);
});

// Initial load - wait for iframe to be ready
onMounted(() => {
    // Give the iframe time to mount
    setTimeout(updateIframeContent, 200);
});

const submit = () => {
    // Convert string back to number or null
    form.inbox_id =
        selectedInboxId.value === '_global' || selectedInboxId.value === ''
            ? null
            : Number(selectedInboxId.value);
    // Map type to slug if not custom
    if (templateType.value === 'created') {
        form.slug = 'ticket_created';
    } else if (templateType.value === 'replied') {
        form.slug = 'ticket_replied';
    } // else 'custom' keeps form.slug as entered
    form.post(route('notification-templates.store'));
};
</script>

<template>
    <div class="space-y-6 px-6 py-4">
        <Head title="Criar Template" />

        <div class="flex items-start justify-between">
            <Heading
                title="Criar Novo Template"
                description="Criar um template de notificação personalizado"
            />
            <Button variant="outline" as-child>
                <Link href="/notification-templates">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Voltar
                </Link>
            </Button>
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-6 lg:grid-cols-[3fr_2fr]">
                <!-- Form Section -->
                <Card>
                    <CardHeader>
                        <CardTitle>Detalhes do Template</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-2">
                                <Label for="type">Tipo de Template</Label>
                                <Select v-model="templateType">
                                    <SelectTrigger id="type">
                                        <SelectValue
                                            placeholder="Selecione o tipo"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="created"
                                            >Criação de Ticket</SelectItem
                                        >
                                        <SelectItem value="replied"
                                            >Resposta ao Ticket</SelectItem
                                        >
                                        <SelectItem value="custom"
                                            >Personalizado</SelectItem
                                        >
                                    </SelectContent>
                                </Select>
                                <div
                                    v-if="templateType !== 'custom'"
                                    class="text-xs text-muted-foreground"
                                >
                                    Slug utilizado:
                                    <code
                                        class="rounded bg-muted px-1.5 py-0.5"
                                        >{{
                                            templateType === 'created'
                                                ? 'ticket_created'
                                                : 'ticket_replied'
                                        }}</code
                                    >
                                </div>
                                <div v-else class="space-y-2">
                                    <Label for="slug">Slug personalizado</Label>
                                    <Input
                                        id="slug"
                                        v-model="form.slug"
                                        placeholder="ex: ticket-assigned"
                                        :disabled="form.processing"
                                        required
                                    />
                                </div>
                                <p
                                    v-if="form.errors.slug"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.slug }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="locale">Idioma</Label>
                                <Input
                                    id="locale"
                                    v-model="form.locale"
                                    placeholder="pt-PT"
                                    :disabled="form.processing"
                                />
                                <p
                                    v-if="form.errors.locale"
                                    class="text-sm text-destructive"
                                >
                                    {{ form.errors.locale }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="inbox">Inbox (opcional)</Label>
                            <Select v-model="selectedInboxId">
                                <SelectTrigger id="inbox">
                                    <SelectValue
                                        placeholder="Selecione uma inbox ou deixe vazio para global"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="_global"
                                        >Global (todas as inboxes)</SelectItem
                                    >
                                    <SelectItem
                                        v-for="inbox in inboxes"
                                        :key="inbox.id"
                                        :value="String(inbox.id)"
                                    >
                                        {{ inbox.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="form.errors.inbox_id"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.inbox_id }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="subject">Assunto do E-mail</Label>
                            <Input
                                id="subject"
                                v-model="form.subject"
                                placeholder="Ex: Novo ticket criado: {{ticket.number}}"
                                :disabled="form.processing"
                                required
                            />
                            <p
                                v-if="form.errors.subject"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.subject }}
                            </p>
                            <div class="mt-2 rounded-md bg-muted/50 p-3">
                                <p
                                    class="mb-1 text-sm font-medium text-foreground"
                                >
                                    💡 Variáveis disponíveis:
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <code
                                        class="inline-flex items-center rounded bg-primary/10 px-2 py-1 font-mono text-xs text-primary"
                                    >
                                        &#123;&#123;ticket.number&#125;&#125;
                                    </code>
                                    <code
                                        class="inline-flex items-center rounded bg-primary/10 px-2 py-1 font-mono text-xs text-primary"
                                    >
                                        &#123;&#123;ticket.subject&#125;&#125;
                                    </code>
                                    <code
                                        class="inline-flex items-center rounded bg-primary/10 px-2 py-1 font-mono text-xs text-primary"
                                    >
                                        &#123;&#123;app.name&#125;&#125;
                                    </code>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="body_html"
                                >Corpo do E-mail (HTML)</Label
                            >
                            <Textarea
                                id="body_html"
                                v-model="form.body_html"
                                rows="20"
                                :disabled="form.processing"
                                required
                                class="font-mono text-sm"
                            />
                            <p
                                v-if="form.errors.body_html"
                                class="text-sm text-destructive"
                            >
                                {{ form.errors.body_html }}
                            </p>
                            <div class="mt-2 rounded-lg border bg-muted/50 p-4">
                                <p
                                    class="mb-2 text-sm font-semibold text-foreground"
                                >
                                    💡 Variáveis disponíveis para o template:
                                </p>
                                <div
                                    class="grid grid-cols-1 gap-2 md:grid-cols-2"
                                >
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="inline-flex items-center rounded-md bg-primary/10 px-3 py-1.5 font-mono text-sm text-primary"
                                        >
                                            &#123;&#123;ticket.number&#125;&#125;
                                        </code>
                                        <span
                                            class="mt-1 text-xs text-muted-foreground"
                                            >Número</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="inline-flex items-center rounded-md bg-primary/10 px-3 py-1.5 font-mono text-sm text-primary"
                                        >
                                            &#123;&#123;ticket.subject&#125;&#125;
                                        </code>
                                        <span
                                            class="mt-1 text-xs text-muted-foreground"
                                            >Assunto</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="inline-flex items-center rounded-md bg-primary/10 px-3 py-1.5 font-mono text-sm text-primary"
                                        >
                                            &#123;&#123;ticket.content&#125;&#125;
                                        </code>
                                        <span
                                            class="mt-1 text-xs text-muted-foreground"
                                            >Conteúdo</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="inline-flex items-center rounded-md bg-primary/10 px-3 py-1.5 font-mono text-sm text-primary"
                                        >
                                            &#123;&#123;ticket.url&#125;&#125;
                                        </code>
                                        <span
                                            class="mt-1 text-xs text-muted-foreground"
                                            >Link</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="inline-flex items-center rounded-md bg-primary/10 px-3 py-1.5 font-mono text-sm text-primary"
                                        >
                                            &#123;&#123;message.content&#125;&#125;
                                        </code>
                                        <span
                                            class="mt-1 text-xs text-muted-foreground"
                                            >Mensagem</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="inline-flex items-center rounded-md bg-primary/10 px-3 py-1.5 font-mono text-sm text-primary"
                                        >
                                            &#123;&#123;app.name&#125;&#125;
                                        </code>
                                        <span
                                            class="mt-1 text-xs text-muted-foreground"
                                            >App</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">
                                {{
                                    form.processing
                                        ? 'A criar...'
                                        : 'Criar Template'
                                }}
                            </Button>
                            <Button variant="outline" as-child type="button">
                                <Link href="/notification-templates"
                                    >Cancelar</Link
                                >
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Preview Section -->
                <Card
                    class="flex max-h-[calc(100vh-100px)] flex-col lg:sticky lg:top-6"
                >
                    <CardHeader class="flex-shrink-0">
                        <div class="flex items-center justify-between">
                            <CardTitle>Pré-visualização</CardTitle>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="showPreview = !showPreview"
                            >
                                <Eye class="mr-2 h-4 w-4" />
                                {{ showPreview ? 'Esconder' : 'Mostrar' }}
                            </Button>
                        </div>
                    </CardHeader>
                    <CardContent
                        class="flex-1 overflow-y-auto"
                        :class="{ hidden: !showPreview }"
                    >
                        <div class="space-y-4">
                            <div>
                                <Label class="text-xs text-muted-foreground"
                                    >Assunto:</Label
                                >
                                <p class="text-sm font-medium">
                                    {{ form.subject || 'Sem assunto' }}
                                </p>
                            </div>
                            <div class="border-t pt-4">
                                <Label class="text-xs text-muted-foreground"
                                    >Corpo do E-mail:</Label
                                >
                                <div class="mt-2 rounded-md border bg-white">
                                    <iframe
                                        ref="previewFrame"
                                        class="w-full rounded-md"
                                        style="
                                            height: 500px;
                                            transform: scale(0.8);
                                            transform-origin: top left;
                                            width: 125%;
                                            height: 625px;
                                        "
                                        frameborder="0"
                                    />
                                </div>
                            </div>
                            <div class="border-t pt-4 text-xs">
                                <p class="mb-3 font-medium text-foreground">
                                    Variáveis disponíveis:
                                </p>
                                <div class="space-y-2">
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs whitespace-nowrap"
                                            >&#123;&#123;ticket.number&#125;&#125;</code
                                        >
                                        <span class="text-muted-foreground"
                                            >- Número do ticket (ex:
                                            #12345)</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs whitespace-nowrap"
                                            >&#123;&#123;ticket.subject&#125;&#125;</code
                                        >
                                        <span class="text-muted-foreground"
                                            >- Assunto/título do ticket</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs whitespace-nowrap"
                                            >&#123;&#123;ticket.content&#125;&#125;</code
                                        >
                                        <span class="text-muted-foreground"
                                            >- Conteúdo/descrição do
                                            ticket</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs whitespace-nowrap"
                                            >&#123;&#123;ticket.url&#125;&#125;</code
                                        >
                                        <span class="text-muted-foreground"
                                            >- Link direto para o ticket</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs whitespace-nowrap"
                                            >&#123;&#123;message.content&#125;&#125;</code
                                        >
                                        <span class="text-muted-foreground"
                                            >- Conteúdo da
                                            mensagem/resposta</span
                                        >
                                    </div>
                                    <div class="flex items-start gap-2">
                                        <code
                                            class="rounded bg-muted px-2 py-0.5 font-mono text-xs whitespace-nowrap"
                                            >&#123;&#123;app.name&#125;&#125;</code
                                        >
                                        <span class="text-muted-foreground"
                                            >- Nome da aplicação</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </form>
    </div>
</template>
