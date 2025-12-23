<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Mail, Edit, Eye } from 'lucide-vue-next'
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

defineProps<{
  templates: NotificationTemplate[]
}>()

const showPreview = ref(false)
const previewHtml = ref('')
const previewSubject = ref('')
const previewLoading = ref(false)

const loadPreview = async (templateId: number) => {
  previewLoading.value = true
  try {
    const response = await axios.post(`/notification-templates/${templateId}/preview`)
    previewSubject.value = response.data.subject
    previewHtml.value = response.data.html
    showPreview.value = true
  } catch (error) {
    console.error('Failed to load preview:', error)
  } finally {
    previewLoading.value = false
  }
}

const getTemplateIcon = (slug: string) => {
  if (slug.includes('created')) return '🎫'
  if (slug.includes('replied')) return '💬'
  return '📧'
}

const getTemplateColor = (slug: string) => {
  if (slug.includes('created')) return 'from-blue-500 to-cyan-500'
  if (slug.includes('replied')) return 'from-purple-500 to-pink-500'
  return 'from-gray-500 to-slate-500'
}
</script>

<template>
  <div class="space-y-6 px-6 py-4">
    <Head title="Templates de E-mail" />

    <div class="flex items-start justify-between">
      <Heading
        title="Templates de Notificação"
        description="Gerir templates de e-mail enviados pelo sistema"
      />
    </div>

    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <Card
        v-for="template in templates"
        :key="template.id"
        class="group overflow-hidden border border-border/60 bg-card/80 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:shadow-xl"
      >
        <CardHeader class="pb-4 bg-transparent">
          <CardTitle class="flex items-center gap-3 text-base">
            <div
              class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15 text-lg shadow-inner"
            >
              {{ getTemplateIcon(template.slug) }}
            </div>
            <div class="flex-1 min-w-0 space-y-0.5">
              <div class="text-[11px] uppercase tracking-[0.18em] opacity-80">{{ template.slug }}</div>
              <div class="text-sm font-semibold leading-tight line-clamp-2">{{ template.subject }}</div>
            </div>
          </CardTitle>
        </CardHeader>

        <CardContent class="space-y-3 pt-4">
          <div class="space-y-2">
            <div class="flex items-center justify-between text-xs text-muted-foreground">
              <span class="flex items-center gap-2">
                <Badge v-if="template.enabled" variant="default" class="text-[11px] px-2 py-0.5">
                  Ativo
                </Badge>
                <Badge v-else variant="secondary" class="text-[11px] px-2 py-0.5">
                  Desativado
                </Badge>
                <span class="px-2 py-0.5 rounded-full bg-muted/60 text-muted-foreground text-[11px]">
                  {{ template.locale || 'pt-PT' }}
                </span>
              </span>
              <span>Atualizado {{ new Date(template.updated_at).toLocaleDateString('pt-PT') }}</span>
            </div>

            <div class="rounded-lg border bg-muted/40 px-3 py-2 text-xs text-muted-foreground line-clamp-3">
              {{ template.body_html?.replace(/<[^>]+>/g, '') || '—' }}
            </div>
          </div>

          <div class="flex gap-2">
            <Button variant="outline" size="sm" class="flex-1" @click="loadPreview(template.id)">
              <Eye class="mr-1 h-3.5 w-3.5" />
              Preview
            </Button>
            <Button size="sm" as-child class="flex-1">
              <Link :href="`/notification-templates/${template.id}/edit`">
                <Edit class="mr-1 h-3.5 w-3.5" />
                Editar
              </Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>

    <Card v-if="!templates || templates.length === 0">
      <CardContent class="py-12 text-center text-muted-foreground">
        <Mail class="mx-auto mb-4 h-16 w-16 opacity-20" />
        <p class="font-medium">Nenhum template encontrado</p>
        <p class="text-sm">Execute as seeds para criar templates padrão</p>
      </CardContent>
    </Card>

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
          <div v-if="previewLoading" class="flex items-center justify-center p-12">
            <div class="text-muted-foreground">A carregar...</div>
          </div>
          <iframe
            v-else
            :srcdoc="previewHtml"
            class="w-full h-full min-h-[600px] border-0"
          ></iframe>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
