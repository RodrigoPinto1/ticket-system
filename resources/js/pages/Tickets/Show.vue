<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { useRoute } from '@/composables/useRoute'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import TicketActivityLog from '@/components/TicketActivityLog.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Separator } from '@/components/ui/separator'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Label } from '@/components/ui/label'
import axios from 'axios'
import { Paperclip } from 'lucide-vue-next'

const route = useRoute()

defineOptions({
  layout: AppLayout,
})

interface User {
  id: number
  name: string
  email: string
}

interface Entity {
  id: number
  name: string
}

interface Inbox {
  id: number
  name: string
}

interface TicketStatus {
  id: number
  name: string
}

interface TicketType {
  id: number
  name: string
}

interface Contact {
  id: number
  name: string
  email: string
}

interface Attachment {
  id: number
  file_name: string
  file_path: string
  mime_type: string
  file_size: number
}

interface Message {
  id: number
  content: string
  is_internal: boolean
  cc?: string[]
  user?: User
  contact?: Contact
  attachments?: Attachment[]
  created_at: string
}

interface Activity {
  id: number
  action: string
  field?: string
  old_value?: string
  new_value?: string
  description?: string
  metadata?: Record<string, any>
  user?: User
  created_at: string
}

interface Ticket {
  id: number
  ticket_number: string
  subject: string
  inbox?: Inbox
  requester?: User
  assignee?: User
  entity?: Entity
  type?: TicketType
  status?: TicketStatus
  contact?: Contact
  known_emails?: string[]
  messages?: Message[]
  activities?: Activity[]
  created_at: string
  updated_at: string
  closed_at?: string
}

const props = defineProps<{
  ticket: Ticket
}>()

const showReplyModal = ref(false)
const replyProcessing = ref(false)
const replyContent = ref('')
const replyCc = ref('')
const replyInternal = ref(false)
const replyErrors = ref<Record<string, string[]>>({})
const replyAttachments = ref<File[]>([])

const replyAttachmentError = computed(() => {
  const key = Object.keys(replyErrors.value).find((k) => k.startsWith('attachments'))
  return key ? replyErrors.value[key][0] : null
})

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const getUserInitials = (name: string) => {
  return name
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}

const formatFileSize = (bytes: number) => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const submitReply = async () => {
  replyProcessing.value = true
  replyErrors.value = {}

  try {
    const formData = new FormData()
    formData.append('content', replyContent.value)
    formData.append('is_internal', replyInternal.value ? '1' : '0')

    const ccList = replyCc.value
      ? replyCc.value
          .split(',')
          .map((s) => s.trim())
          .filter((s) => s.length > 0)
      : []

    ccList.forEach((email) => formData.append('cc[]', email))
    replyAttachments.value.forEach((file) => formData.append('attachments[]', file))

    await axios.post(route('tickets.reply', props.ticket.id), formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    replyContent.value = ''
    replyCc.value = ''
    replyInternal.value = false
    replyAttachments.value = []
    showReplyModal.value = false

    router.reload({ only: ['ticket'], preserveScroll: true })
  } catch (error: any) {
    if (error?.response?.status === 422) {
      replyErrors.value = error.response.data.errors || {}
    }
  } finally {
    replyProcessing.value = false
  }
}

const onReplyFilesChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (!target.files) return
  replyAttachments.value = Array.from(target.files)
}

const removeAttachment = (index: number) => {
  replyAttachments.value.splice(index, 1)
}
</script>

<template>
  <div class="space-y-6 px-6 py-4">
    <Head :title="`Ticket ${props.ticket.ticket_number}`" />

      <!-- Header -->
      <div class="flex items-start justify-between">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <Button variant="ghost" size="sm" as-child>
              <Link :href="route('tickets.index')">
                ← Voltar
              </Link>
            </Button>
          </div>
          <Heading :title="props.ticket.ticket_number" :description="props.ticket.subject" />
        </div>

        <div class="flex gap-2">
          <Button variant="outline" as-child>
            <Link :href="route('tickets.edit', props.ticket.id)">
              Editar Ticket
            </Link>
          </Button>
          <Button @click="showReplyModal = true">
            Adicionar Resposta
          </Button>
        </div>
      </div>

      <!-- Ticket Info Cards -->
      <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <!-- Status & Type -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Informações</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div>
              <div class="text-sm font-medium text-muted-foreground">Estado</div>
              <Badge v-if="props.ticket.status" class="mt-1">
                {{ props.ticket.status.name }}
              </Badge>
              <span v-else class="text-sm text-muted-foreground">Não definido</span>
            </div>

            <div>
              <div class="text-sm font-medium text-muted-foreground">Tipo</div>
              <Badge v-if="props.ticket.type" variant="secondary" class="mt-1">
                {{ props.ticket.type.name }}
              </Badge>
              <span v-else class="text-sm text-muted-foreground">Não definido</span>
            </div>

            <div>
              <div class="text-sm font-medium text-muted-foreground">Inbox</div>
              <Badge v-if="props.ticket.inbox" variant="outline" class="mt-1">
                {{ props.ticket.inbox.name }}
              </Badge>
              <span v-else class="text-sm text-muted-foreground">Não definido</span>
            </div>
          </CardContent>
        </Card>

        <!-- People -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Pessoas</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div>
              <div class="text-sm font-medium text-muted-foreground">Requerente</div>
              <div v-if="props.ticket.requester" class="mt-1 flex items-center gap-2">
                <Avatar class="h-6 w-6">
                  <AvatarFallback class="text-xs">
                    {{ getUserInitials(props.ticket.requester.name) }}
                  </AvatarFallback>
                </Avatar>
                <div>
                  <div class="text-sm font-medium">{{ props.ticket.requester.name }}</div>
                  <div class="text-xs text-muted-foreground">{{ props.ticket.requester.email }}</div>
                </div>
              </div>
            </div>

            <div>
              <div class="text-sm font-medium text-muted-foreground">Operador Atribuído</div>
              <div v-if="props.ticket.assignee" class="mt-1 flex items-center gap-2">
                <Avatar class="h-6 w-6">
                  <AvatarFallback class="text-xs">
                    {{ getUserInitials(props.ticket.assignee.name) }}
                  </AvatarFallback>
                </Avatar>
                <div class="text-sm font-medium">{{ props.ticket.assignee.name }}</div>
              </div>
              <span v-else class="text-sm text-muted-foreground">Não atribuído</span>
            </div>
          </CardContent>
        </Card>

        <!-- Additional Info -->
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Detalhes</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div>
              <div class="text-sm font-medium text-muted-foreground">Entidade</div>
              <div v-if="props.ticket.entity" class="text-sm">{{ props.ticket.entity.name }}</div>
              <span v-else class="text-sm text-muted-foreground">Não definido</span>
            </div>

            <div>
              <div class="text-sm font-medium text-muted-foreground">Criado</div>
              <div class="text-sm">{{ formatDate(props.ticket.created_at) }}</div>
            </div>

            <div v-if="props.ticket.closed_at">
              <div class="text-sm font-medium text-muted-foreground">Fechado</div>
              <div class="text-sm">{{ formatDate(props.ticket.closed_at) }}</div>
            </div>

            <div v-if="props.ticket.known_emails && props.ticket.known_emails.length > 0">
              <div class="text-sm font-medium text-muted-foreground">CC</div>
              <div class="mt-1 flex flex-wrap gap-1">
                <Badge
                  v-for="(email, idx) in props.ticket.known_emails"
                  :key="idx"
                  variant="outline"
                  class="text-xs"
                >
                  {{ email }}
                </Badge>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Messages -->
      <Card>
        <CardHeader>
          <CardTitle>Mensagens</CardTitle>
        </CardHeader>
        <CardContent>
          <div v-if="!props.ticket.messages || props.ticket.messages.length === 0" class="text-center text-muted-foreground py-8">
            Nenhuma mensagem ainda
          </div>

          <div v-else class="space-y-6">
            <div
              v-for="(message, idx) in props.ticket.messages"
              :key="message.id"
            >
              <div class="flex gap-4">
                <Avatar class="h-10 w-10">
                  <AvatarFallback>
                    {{ message.user ? getUserInitials(message.user.name) : (message.contact ? getUserInitials(message.contact.name) : '?') }}
                  </AvatarFallback>
                </Avatar>

                <div class="flex-1 space-y-2">
                  <div class="flex items-start justify-between">
                    <div>
                      <div class="font-medium">
                        {{ message.user?.name || message.contact?.name || 'Desconhecido' }}
                      </div>
                      <div class="text-xs text-muted-foreground">
                        {{ message.user?.email || message.contact?.email }}
                      </div>
                    </div>

                    <div class="flex items-center gap-2">
                      <Badge v-if="message.is_internal" variant="secondary">
                        Nota Interna
                      </Badge>
                      <span class="text-sm text-muted-foreground">
                        {{ formatDate(message.created_at) }}
                      </span>
                    </div>
                  </div>

                  <div class="rounded-lg bg-muted p-4">
                    <div class="whitespace-pre-wrap text-sm">{{ message.content }}</div>
                  </div>

                  <!-- Attachments -->
                  <div
                    v-if="message.attachments && message.attachments.length > 0"
                    class="space-y-2"
                  >
                    <div class="text-sm font-medium">Anexos:</div>
                    <div class="flex flex-wrap gap-2">
                      <a
                        v-for="attachment in message.attachments"
                        :key="attachment.id"
                        :href="`/storage/${attachment.file_path}`"
                        target="_blank"
                        rel="noreferrer"
                        class="inline-flex items-center gap-1 rounded-md border px-3 py-1 text-sm hover:bg-accent"
                      >
                        📎 {{ attachment.file_name }}
                        <span class="ml-1 text-xs text-muted-foreground">
                          ({{ formatFileSize(attachment.file_size) }})
                        </span>
                      </a>
                    </div>
                  </div>

                  <!-- CC -->
                  <div v-if="message.cc && message.cc.length > 0" class="text-xs text-muted-foreground">
                    CC: {{ message.cc.join(', ') }}
                  </div>
                </div>
              </div>

              <Separator v-if="idx < props.ticket.messages.length - 1" class="my-6" />
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Activity Log -->
      <TicketActivityLog v-if="props.ticket.activities" :activities="props.ticket.activities" />

      <!-- Modal: Add Reply -->
      <div
        v-if="showReplyModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
      >
        <Card class="w-full max-w-xl shadow-xl border border-border/60">
          <CardHeader>
            <CardTitle class="text-lg font-semibold">Adicionar Resposta</CardTitle>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="submitReply" class="space-y-5">
              <div class="space-y-2">
                <Label for="reply_content">Mensagem *</Label>
                <Textarea
                  id="reply_content"
                  v-model="replyContent"
                  placeholder="Escreva a resposta"
                  class="min-h-[200px] border-muted bg-muted/40 focus-visible:ring-2 focus-visible:ring-primary"
                />
                <p v-if="replyErrors.content" class="text-sm text-destructive">{{ replyErrors.content[0] }}</p>
              </div>

              <div class="space-y-2">
                <Label for="reply_cc">CC (separar por vírgulas)</Label>
                <Input
                  id="reply_cc"
                  v-model="replyCc"
                  type="text"
                  placeholder="ex: foo@bar.com, baz@bar.com"
                />
                <p v-if="replyErrors['cc.0']" class="text-sm text-destructive">{{ replyErrors['cc.0'][0] }}</p>
              </div>

              <div class="space-y-2">
                <Label for="reply_attachments">Anexos (imagens ou ficheiros, até 20MB cada)</Label>
                <div class="relative">
                  <Paperclip class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                  <Input
                    id="reply_attachments"
                    type="file"
                    multiple
                    class="pl-10 cursor-pointer border-dashed"
                    @change="onReplyFilesChange"
                  />
                </div>
                <p v-if="replyAttachmentError" class="text-sm text-destructive">{{ replyAttachmentError }}</p>

                <div v-if="replyAttachments.length" class="space-y-2 rounded-md border p-3 bg-muted/50">
                  <div class="text-sm font-medium flex items-center gap-2">
                    <Paperclip class="h-4 w-4" />
                    Selecionados
                  </div>
                  <div class="space-y-1 text-sm">
                    <div
                      v-for="(file, idx) in replyAttachments"
                      :key="file.name + idx"
                      class="flex items-center justify-between gap-2 rounded-md border bg-background px-3 py-2 shadow-sm"
                    >
                      <span class="truncate">{{ file.name }} ({{ formatFileSize(file.size) }})</span>
                      <Button variant="ghost" size="sm" type="button" @click="removeAttachment(idx)">
                        Remover
                      </Button>
                    </div>
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between flex-wrap gap-3">
                <label class="inline-flex items-center gap-2 text-sm">
                  <input type="checkbox" v-model="replyInternal" class="h-4 w-4" />
                  Marcar como interno
                </label>

                <div class="flex gap-2">
                  <Button type="button" variant="outline" @click="showReplyModal = false">
                    Cancelar
                  </Button>
                  <Button type="submit" :disabled="replyProcessing">
                    {{ replyProcessing ? 'A enviar...' : 'Enviar' }}
                  </Button>
                </div>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
  </div>
</template>
