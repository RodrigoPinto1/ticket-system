<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import TicketFilters from '@/components/TicketFilters.vue'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Pagination,
  PaginationContent,
  PaginationFirst,
  PaginationLast,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination'
import {
  Ticket as TicketIcon,
  Plus,
  Inbox,
  User,
  Building2,
  Tag,
  Clock,
  Eye,
  Mail
} from 'lucide-vue-next'

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
  created_at: string
  updated_at: string
}

interface PaginationData {
  current_page: number
  last_page: number
  per_page: number
  total: number
  data: Ticket[]
  links: Array<{
    url: string | null
    label: string
    active: boolean
  }>
}

interface FilterOptions {
  inboxes: Array<{ id: number; name: string }>
  statuses: Array<{ id: number; name: string }>
  types: Array<{ id: number; name: string }>
  entities: Array<{ id: number; name: string }>
  operators: Array<{ id: number; name: string; email: string }>
}

interface FilterParams {
  inbox_id?: number | null
  status_id?: number | null
  type_id?: number | null
  entity_id?: number | null
  assigned_to?: number | null
  search?: string
}

defineProps<{
  tickets: PaginationData
  filters: FilterOptions
  queryParams: FilterParams
}>()

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('pt-PT', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date)
}

const getStatusBadgeClass = (statusName?: string) => {
  if (!statusName) return ''
  const name = statusName.toLowerCase()
  if (name.includes('aberto') || name.includes('novo')) return 'bg-blue-500 hover:bg-blue-600 text-white border-blue-600'
  if (name.includes('processo') || name.includes('andamento')) return 'bg-amber-500 hover:bg-amber-600 text-white border-amber-600'
  if (name.includes('fechado') || name.includes('resolvido')) return 'bg-green-500 hover:bg-green-600 text-white border-green-600'
  return ''
}
</script>

<template>
  <Head title="Gestão de Tickets" />

  <div class="space-y-6 px-6 py-4">
      <!-- Hero Header -->
      <div class="relative w-full px-1 py-2">
        <div class="flex items-center justify-between w-full">
          <div class="space-y-2">
            <div class="flex items-center gap-3">
              <div class="rounded-lg bg-primary/10 p-2">
                <TicketIcon class="h-8 w-8 text-primary" />
              </div>
              <div>
                <h1 class="text-3xl font-bold text-foreground">Gestão de Tickets</h1>
                <p class="text-muted-foreground">Gerencie e acompanhe todos os tickets do sistema</p>
              </div>
            </div>
          </div>
          <Button size="lg" class="shadow-lg" as-child>
            <Link href="/tickets/create" class="flex items-center gap-2">
              <Plus class="h-5 w-5" />
              Criar Novo Ticket
            </Link>
          </Button>
        </div>
      </div>

      <!-- Filters -->
      <TicketFilters :filters="filters" :query-params="queryParams" />

      <!-- Tickets Table -->
      <div class="overflow-hidden rounded-xl border bg-card shadow-md">
        <Table>
          <TableHeader>
            <TableRow class="bg-muted/50">
              <TableHead class="font-semibold">
                <div class="flex items-center gap-2">
                  <TicketIcon class="h-4 w-4" />
                  Nº Ticket
                </div>
              </TableHead>
              <TableHead class="font-semibold">Assunto</TableHead>
              <TableHead class="font-semibold">
                <div class="flex items-center gap-2">
                  <Inbox class="h-4 w-4" />
                  Inbox
                </div>
              </TableHead>
              <TableHead class="font-semibold">
                <div class="flex items-center gap-2">
                  <Mail class="h-4 w-4" />
                  Requerente
                </div>
              </TableHead>
              <TableHead class="font-semibold">
                <div class="flex items-center gap-2">
                  <User class="h-4 w-4" />
                  Operador
                </div>
              </TableHead>
              <TableHead class="font-semibold">
                <div class="flex items-center gap-2">
                  <Building2 class="h-4 w-4" />
                  Entidade
                </div>
              </TableHead>
              <TableHead class="font-semibold">
                <div class="flex items-center gap-2">
                  <Tag class="h-4 w-4" />
                  Tipo
                </div>
              </TableHead>
              <TableHead class="font-semibold">Estado</TableHead>
              <TableHead class="font-semibold">
                <div class="flex items-center gap-2">
                  <Clock class="h-4 w-4" />
                  Criado
                </div>
              </TableHead>
              <TableHead class="font-semibold">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="tickets.data.length === 0">
              <TableCell colspan="10" class="h-32 text-center">
                <div class="flex flex-col items-center justify-center gap-2 text-muted-foreground">
                  <TicketIcon class="h-12 w-12 opacity-20" />
                  <p class="font-medium">Nenhum ticket encontrado</p>
                  <p class="text-sm">Tente ajustar os filtros ou criar um novo ticket</p>
                </div>
              </TableCell>
            </TableRow>
            <TableRow
              v-for="ticket in tickets.data"
              :key="ticket.id"
              class="group cursor-pointer transition-colors hover:bg-muted/50"
            >
              <TableCell class="font-mono font-semibold">
                <Link
                  :href="`/tickets/${ticket.id}`"
                  class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 hover:underline"
                >
                  <TicketIcon class="h-4 w-4" />
                  {{ ticket.ticket_number }}
                </Link>
              </TableCell>
              <TableCell>
                <div class="max-w-xs truncate font-medium">
                  {{ ticket.subject }}
                </div>
              </TableCell>
              <TableCell>
                <Badge v-if="ticket.inbox" variant="outline" class="gap-1.5">
                  <Inbox class="h-3 w-3" />
                  {{ ticket.inbox.name }}
                </Badge>
                <span v-else class="text-muted-foreground">-</span>
              </TableCell>
              <TableCell>
                <div v-if="ticket.requester" class="flex items-center gap-2">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-xs font-semibold text-blue-700">
                    {{ ticket.requester.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="space-y-0.5">
                    <div class="font-medium">{{ ticket.requester.name }}</div>
                    <div class="text-xs text-muted-foreground">
                      {{ ticket.requester.email }}
                    </div>
                  </div>
                </div>
                <span v-else class="text-muted-foreground">-</span>
              </TableCell>
              <TableCell>
                <div v-if="ticket.assignee" class="flex items-center gap-2">
                  <div class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                    {{ ticket.assignee.name.charAt(0).toUpperCase() }}
                  </div>
                  <div class="font-medium">{{ ticket.assignee.name }}</div>
                </div>
                <Badge v-else variant="secondary" class="gap-1.5">
                  <User class="h-3 w-3" />
                  Não atribuído
                </Badge>
              </TableCell>
              <TableCell>
                <div v-if="ticket.entity" class="flex items-center gap-1.5">
                  <Building2 class="h-4 w-4 text-muted-foreground" />
                  <span class="font-medium">{{ ticket.entity.name }}</span>
                </div>
                <span v-else class="text-muted-foreground">-</span>
              </TableCell>
              <TableCell>
                <Badge v-if="ticket.type" variant="secondary" class="gap-1.5">
                  <Tag class="h-3 w-3" />
                  {{ ticket.type.name }}
                </Badge>
                <span v-else class="text-muted-foreground">-</span>
              </TableCell>
              <TableCell>
                <Badge
                  v-if="ticket.status"
                  :class="getStatusBadgeClass(ticket.status.name)"
                  class="font-medium shadow-sm"
                >
                  {{ ticket.status.name }}
                </Badge>
                <span v-else class="text-muted-foreground">-</span>
              </TableCell>
              <TableCell class="text-sm text-muted-foreground">
                <div class="flex items-center gap-1.5">
                  <Clock class="h-3.5 w-3.5" />
                  {{ formatDate(ticket.created_at) }}
                </div>
              </TableCell>
              <TableCell>
                <Button
                  variant="ghost"
                  size="sm"
                  class="gap-2 transition-colors"
                  as-child
                >
                  <Link :href="`/tickets/${ticket.id}`">
                    <Eye class="h-4 w-4" />
                    Ver Detalhes
                  </Link>
                </Button>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>

        <!-- Pagination -->
        <div
          v-if="tickets.last_page > 1"
          class="flex items-center justify-between border-t bg-muted/30 px-6 py-4"
        >
          <div class="flex items-center gap-2 text-sm font-medium text-muted-foreground">
            <TicketIcon class="h-4 w-4" />
            Mostrando {{ (tickets.current_page - 1) * tickets.per_page + 1 }} a
            {{
              Math.min(
                tickets.current_page * tickets.per_page,
                tickets.total
              )
            }}
            de <span class="font-bold text-foreground">{{ tickets.total }}</span> tickets
          </div>

          <Pagination
            :total="tickets.total"
            :sibling-count="1"
            show-edges
            :default-page="tickets.current_page"
            :items-per-page="tickets.per_page"
          >
            <PaginationContent>
              <PaginationFirst as-child>
                <Link
                  :href="tickets.links[0].url || '#'"
                  preserve-scroll
                  preserve-state
                />
              </PaginationFirst>

              <PaginationPrevious as-child>
                <Link
                  :href="
                    tickets.links.find((l) => l.label === '&laquo; Previous')
                      ?.url || '#'
                  "
                  preserve-scroll
                  preserve-state
                />
              </PaginationPrevious>

              <PaginationNext as-child>
                <Link
                  :href="
                    tickets.links.find((l) => l.label === 'Next &raquo;')
                      ?.url || '#'
                  "
                  preserve-scroll
                  preserve-state
                />
              </PaginationNext>

              <PaginationLast as-child>
                <Link
                  :href="tickets.links[tickets.links.length - 1].url || '#'"
                  preserve-scroll
                  preserve-state
                />
              </PaginationLast>
            </PaginationContent>
          </Pagination>
        </div>
      </div>
    </div>
</template>
