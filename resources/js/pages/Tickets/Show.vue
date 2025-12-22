<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { useRoute } from '@/composables/useRoute'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import TicketActivityLog from '@/components/TicketActivityLog.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'

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
          <Button variant="outline">
            Editar Ticket
          </Button>
          <Button>
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
                      <Badge
                        v-for="attachment in message.attachments"
                        :key="attachment.id"
                        variant="outline"
                        class="cursor-pointer hover:bg-accent"
                      >
                        📎 {{ attachment.file_name }}
                        <span class="ml-1 text-xs text-muted-foreground">
                          ({{ formatFileSize(attachment.file_size) }})
                        </span>
                      </Badge>
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
  </div>
</template>
