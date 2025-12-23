<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, Save, Eye, Mail, Code, Monitor, Wand2 } from 'lucide-vue-next'
import axios from 'axios'

defineOptions({
  layout: AppLayout,
})

interface NotificationTemplate {
  id: number
  slug: string
  subject: string
  body_html: string
  locale: string | null
  enabled: boolean
  created_at: string
  updated_at: string
}

const props = defineProps<{
  template: NotificationTemplate
}>()

const form = ref({
  subject: props.template.subject,
  body_html: props.template.body_html,
  enabled: props.template.enabled,
})

const processing = ref(false)
const errors = ref<Record<string, string[]>>({})
const showPreview = ref(false)
const previewHtml = ref('')
const previewSubject = ref('')
const showLivePreview = ref(false)
const livePreviewHtml = ref('')

const insertPlaceholder = (key: string) => {
  form.value.body_html = (form.value.body_html || '') + key
}

const formatHtml = () => {
  let html = form.value.body_html

  // Remove extra whitespace
  html = html.replace(/>\s+</g, '><')

  // Add newlines and indentation
  let formatted = ''
  let indent = 0
  const tab = '  '

  html.split(/(<[^>]+>)/g).forEach(part => {
    if (!part.trim()) return

    if (part.startsWith('</')) {
      indent--
      formatted += tab.repeat(Math.max(0, indent)) + part + '\n'
    } else if (part.startsWith('<')) {
      formatted += tab.repeat(indent) + part + '\n'
      if (!part.endsWith('/>') && !part.startsWith('<!')) {
        indent++
      }
    } else {
      formatted += tab.repeat(indent) + part.trim() + '\n'
    }
  })

  form.value.body_html = formatted.trim()
}

const renderLivePreview = () => {
  const sampleData = {
    'ticket.number': 'TKT-12345',
    'ticket.subject': 'Exemplo de ticket para preview',
    'ticket.content': 'Esta é uma descrição de exemplo.',
    'ticket.created_at': new Date().toLocaleString('pt-PT'),
    'ticket.url': window.location.origin + '/tickets/1',
    'message.content': 'Esta é uma resposta de exemplo.',
    'message.created_at': new Date().toLocaleString('pt-PT'),
    'app.name': 'Ticket System',
  }

  let html = form.value.body_html
  for (const [key, value] of Object.entries(sampleData)) {
    html = html.replaceAll(`{{${key}}}`, value)
  }
  livePreviewHtml.value = html
}

watch(() => form.value.body_html, () => {
  if (showLivePreview.value) {
    renderLivePreview()
  }
}, { immediate: true })

watch(showLivePreview, (val) => {
  if (val) renderLivePreview()
})

const submit = () => {
  processing.value = true
  errors.value = {}

  router.put(
    `/notification-templates/${props.template.id}`,
    form.value,
    {
      onSuccess: () => {
        processing.value = false
      },
      onError: (err) => {
        errors.value = err
        processing.value = false
      },
    }
  )
}

const loadPreview = async () => {
  try {
    // Send current form data to preview endpoint
    const response = await axios.post(`/notification-templates/${props.template.id}/preview`, {
      subject: form.value.subject,
      body_html: form.value.body_html,
    })
    previewSubject.value = response.data.subject
    previewHtml.value = response.data.html
    showPreview.value = true
  } catch (error) {
    console.error('Failed to load preview:', error)
  }
}

const placeholders = [
  { key: '{{ticket.number}}', desc: 'Número do ticket' },
  { key: '{{ticket.subject}}', desc: 'Assunto do ticket' },
  { key: '{{ticket.content}}', desc: 'Descrição do ticket' },
  { key: '{{ticket.created_at}}', desc: 'Data de criação' },
  { key: '{{ticket.url}}', desc: 'Link para o ticket' },
  { key: '{{message.content}}', desc: 'Conteúdo da mensagem' },
  { key: '{{message.created_at}}', desc: 'Data da mensagem' },
  { key: '{{app.name}}', desc: 'Nome da aplicação' },
]
</script>

<template>
  <div class="space-y-6 px-6 py-4">
    <Head :title="`Editar Template: ${template.slug}`" />

    <div class="flex items-start justify-between">
      <div class="space-y-1">
        <Button variant="ghost" size="sm" as-child class="mb-2">
          <a href="/notification-templates">
            <ArrowLeft class="mr-1 h-4 w-4" />
            Voltar
          </a>
        </Button>
        <Heading
          :title="`Editar: ${template.slug}`"
          description="Configure o assunto e conteúdo HTML do e-mail"
        />
      </div>

      <div class="flex gap-2">
        <Button
          variant="outline"
          @click="showLivePreview = !showLivePreview"
          :class="{ 'bg-accent': showLivePreview }"
        >
          <Monitor class="mr-1 h-4 w-4" />
          {{ showLivePreview ? 'Ocultar' : 'Mostrar' }} Preview Ao Vivo
        </Button>
        <Button variant="outline" @click="loadPreview">
          <Eye class="mr-1 h-4 w-4" />
          Preview Completo
        </Button>
        <Button @click="submit" :disabled="processing">
          <Save class="mr-1 h-4 w-4" />
          {{ processing ? 'A guardar...' : 'Guardar' }}
        </Button>
      </div>
    </div>

    <div class="grid gap-6" :class="showLivePreview ? 'lg:grid-cols-2' : 'lg:grid-cols-[2fr_1fr]'">
      <!-- Form -->
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <Code class="h-5 w-5" />
              Conteúdo do Template
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="space-y-2">
              <Label for="subject">Assunto do E-mail</Label>
              <Input
                id="subject"
                v-model="form.subject"
                type="text"
                placeholder="ex: Ticket {{ticket.number}} criado"
              />
              <p v-if="errors.subject" class="text-sm text-destructive">
                {{ errors.subject[0] }}
              </p>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <Label for="body_html">HTML do E-mail</Label>
                <div class="flex items-center gap-2">
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="formatHtml"
                    class="h-7"
                  >
                    <Wand2 class="mr-1 h-3 w-3" />
                    Formatar
                  </Button>
                  <Badge variant="outline" class="text-xs font-mono">
                    {{ form.body_html.length }} caracteres
                  </Badge>
                </div>
              </div>
              <Textarea
                id="body_html"
                v-model="form.body_html"
                placeholder="Cole o HTML do template aqui"
                :class="showLivePreview ? 'min-h-[500px]' : 'min-h-[400px]'"
                class="font-mono text-xs leading-relaxed"
              />
              <p v-if="errors.body_html" class="text-sm text-destructive">
                {{ errors.body_html[0] }}
              </p>
            </div>

            <div class="flex items-center gap-2">
              <input
                id="enabled"
                v-model="form.enabled"
                type="checkbox"
                class="h-4 w-4"
              />
              <Label for="enabled" class="cursor-pointer">
                Template ativo (enviar e-mails usando este template)
              </Label>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Live Preview -->
      <div v-if="showLivePreview" class="space-y-6">
        <Card class="sticky top-4">
          <CardHeader class="border-b">
            <CardTitle class="text-base flex items-center gap-2">
              <Monitor class="h-5 w-5" />
              Preview Ao Vivo
            </CardTitle>
          </CardHeader>
          <CardContent class="p-0">
            <div class="max-h-[600px] overflow-auto">
              <iframe
                :srcdoc="livePreviewHtml"
                class="w-full min-h-[500px] border-0"
              ></iframe>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Placeholders -->
      <div v-if="!showLivePreview" class="space-y-6">
        <Card class="sticky top-4">
          <CardHeader>
            <CardTitle class="text-base">Placeholders Disponíveis</CardTitle>
          </CardHeader>
          <CardContent class="space-y-2">
            <div
              v-for="ph in placeholders"
              :key="ph.key"
              class="rounded-md border bg-muted/50 px-3 py-2"
            >
              <div class="flex items-center justify-between gap-2">
                <code class="text-xs font-semibold text-primary">{{ ph.key }}</code>
                <Button variant="ghost" size="sm" class="h-7" @click="insertPlaceholder(ph.key)">
                  Inserir
                </Button>
              </div>
              <div class="text-xs text-muted-foreground mt-0.5">{{ ph.desc }}</div>
            </div>
          </CardContent>
        </Card>

        <Card class="sticky top-72">
          <CardHeader>
            <CardTitle class="text-base">Dicas</CardTitle>
          </CardHeader>
          <CardContent class="space-y-2 text-sm text-muted-foreground">
            <p>• Use inline CSS para garantir compatibilidade com clientes de e-mail</p>
            <p>• Teste com Preview antes de guardar</p>
            <p>• Mantenha o HTML simples e limpo</p>
          </CardContent>
        </Card>
      </div>
    </div>

    <!-- Preview Modal -->
    <div
      v-if="showPreview"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
      @click.self="showPreview = false"
    >
      <Card class="w-full max-w-4xl max-h-[90vh] overflow-hidden shadow-xl">
        <CardHeader class="border-b">
          <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 flex-1">
              <CardTitle class="text-base">Preview do email</CardTitle>
              <span class="text-sm text-muted-foreground">•</span>
              <span class="text-sm font-semibold truncate">{{ previewSubject }}</span>
            </div>
            <Button variant="ghost" size="sm" @click="showPreview = false">
              Fechar
            </Button>
          </div>
        </CardHeader>
        <CardContent class="p-0 overflow-auto max-h-[calc(90vh-120px)]">
          <iframe
            :srcdoc="previewHtml"
            class="w-full h-full min-h-[600px] border-0"
          ></iframe>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
