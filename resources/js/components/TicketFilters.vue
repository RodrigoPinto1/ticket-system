<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash-es'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import {
  Search,
  Filter,
  X,
  Inbox,
  Tag,
  Building2,
  User,
  CheckCircle2
} from 'lucide-vue-next'

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

const props = defineProps<{
  filters: FilterOptions
  queryParams: FilterParams
}>()

const inboxId = ref<number | null>(props.queryParams.inbox_id || null)
const statusId = ref<number | null>(props.queryParams.status_id || null)
const typeId = ref<number | null>(props.queryParams.type_id || null)
const entityId = ref<number | null>(props.queryParams.entity_id || null)
const assignedTo = ref<number | null>(props.queryParams.assigned_to || null)
const search = ref<string>(props.queryParams.search || '')

const applyFilters = () => {
  const params: Record<string, any> = {}

  if (inboxId.value) params.inbox_id = inboxId.value
  if (statusId.value) params.status_id = statusId.value
  if (typeId.value) params.type_id = typeId.value
  if (entityId.value) params.entity_id = entityId.value
  if (assignedTo.value) params.assigned_to = assignedTo.value
  if (search.value) params.search = search.value

  router.get('/tickets', params, {
    preserveState: true,
    preserveScroll: true,
  })
}

const clearFilters = () => {
  inboxId.value = null
  statusId.value = null
  typeId.value = null
  entityId.value = null
  assignedTo.value = null
  search.value = ''

  router.get('/tickets', {}, {
    preserveState: true,
    preserveScroll: true,
  })
}

// Debounced search
const debouncedSearch = debounce(() => {
  applyFilters()
}, 500)

watch(search, () => {
  debouncedSearch()
})

watch([inboxId, statusId, typeId, entityId, assignedTo], () => {
  applyFilters()
})
</script>

<template>
  <div class="space-y-4 rounded-xl border bg-gradient-to-br from-card to-muted/20 p-6 shadow-md">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <div class="rounded-lg bg-primary/10 p-2">
          <Filter class="h-5 w-5 text-primary" />
        </div>
        <h3 class="text-lg font-semibold">Filtros de Pesquisa</h3>
      </div>
      <Button
        variant="ghost"
        size="sm"
        @click="clearFilters"
        class="gap-2 text-muted-foreground hover:text-destructive"
      >
        <X class="h-4 w-4" />
        Limpar Filtros
      </Button>
    </div>

    <!-- Search Bar -->
    <div class="space-y-2">
      <Label for="search" class="flex items-center gap-2 text-sm font-medium">
        <Search class="h-4 w-4" />
        Pesquisa Rápida
      </Label>
      <div class="relative">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          id="search"
          v-model="search"
          type="text"
          placeholder="Nº Ticket, Assunto, Email ou Entidade..."
          class="pl-10 shadow-sm"
        />
      </div>
    </div>

    <!-- Filter Grid -->
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <!-- Inbox Filter -->
      <div class="space-y-2">
        <Label for="inbox" class="flex items-center gap-2 text-sm font-medium">
          <Inbox class="h-4 w-4 text-blue-600" />
          Inbox
        </Label>
        <Select v-model="inboxId">
          <SelectTrigger id="inbox" class="shadow-sm">
            <SelectValue placeholder="Todos os inboxes" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">Todos os inboxes</SelectItem>
            <SelectItem
              v-for="inbox in filters.inboxes"
              :key="inbox.id"
              :value="inbox.id"
            >
              {{ inbox.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Status Filter -->
      <div class="space-y-2">
        <Label for="status" class="flex items-center gap-2 text-sm font-medium">
          <CheckCircle2 class="h-4 w-4 text-green-600" />
          Estado
        </Label>
        <Select v-model="statusId">
          <SelectTrigger id="status" class="shadow-sm">
            <SelectValue placeholder="Todos os estados" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">Todos os estados</SelectItem>
            <SelectItem
              v-for="status in filters.statuses"
              :key="status.id"
              :value="status.id"
            >
              {{ status.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Type Filter -->
      <div class="space-y-2">
        <Label for="type" class="flex items-center gap-2 text-sm font-medium">
          <Tag class="h-4 w-4 text-purple-600" />
          Tipo
        </Label>
        <Select v-model="typeId">
          <SelectTrigger id="type" class="shadow-sm">
            <SelectValue placeholder="Todos os tipos" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">Todos os tipos</SelectItem>
            <SelectItem
              v-for="type in filters.types"
              :key="type.id"
              :value="type.id"
            >
              {{ type.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Entity Filter -->
      <div class="space-y-2">
        <Label for="entity" class="flex items-center gap-2 text-sm font-medium">
          <Building2 class="h-4 w-4 text-orange-600" />
          Entidade
        </Label>
        <Select v-model="entityId">
          <SelectTrigger id="entity" class="shadow-sm">
            <SelectValue placeholder="Todas as entidades" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">Todas as entidades</SelectItem>
            <SelectItem
              v-for="entity in filters.entities"
              :key="entity.id"
              :value="entity.id"
            >
              {{ entity.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Operator Filter -->
      <div class="space-y-2">
        <Label for="operator" class="flex items-center gap-2 text-sm font-medium">
          <User class="h-4 w-4 text-indigo-600" />
          Operador
        </Label>
        <Select v-model="assignedTo">
          <SelectTrigger id="operator" class="shadow-sm">
            <SelectValue placeholder="Todos os operadores" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">Todos os operadores</SelectItem>
            <SelectItem
              v-for="operator in filters.operators"
              :key="operator.id"
              :value="operator.id"
            >
              {{ operator.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>
    </div>
  </div>
</template>
