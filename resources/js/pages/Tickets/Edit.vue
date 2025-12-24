<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3'
import { useRoute } from '@/composables/useRoute'
import Heading from '@/components/Heading.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import { ArrowLeft, Pencil, Plus } from 'lucide-vue-next'
import { ref, reactive } from 'vue'
import axios from 'axios'
import AppLayout from '@/layouts/AppLayout.vue'

const route = useRoute()

defineOptions({
  layout: AppLayout,
})

interface SelectOption { id: number; name: string }
interface Operator extends SelectOption { email: string }

interface TicketDto {
  id: number
  inbox_id: number
  entity_id: number | null
  requester_id: number
  assigned_to: number | null
  type_id: number | null
  status_id: number | null
  known_emails: string[] | null
  subject: string
  content?: string | null
}

interface Props {
  ticket: TicketDto
  inboxes: SelectOption[]
  statuses: SelectOption[]
  types: SelectOption[]
  entities: SelectOption[]
  operators: Operator[]
  isOperator: boolean
}

const props = defineProps<Props>()

const form = useForm({
  inbox_id: String(props.ticket.inbox_id || ''),
  subject: props.ticket.subject || '',
  content: props.ticket.content || '',
  assigned_to: props.ticket.assigned_to ? String(props.ticket.assigned_to) : '',
  entity_id: props.ticket.entity_id ? String(props.ticket.entity_id) : '',
  type_id: props.ticket.type_id ? String(props.ticket.type_id) : '',
  status_id: props.ticket.status_id ? String(props.ticket.status_id) : '',
  known_emails: (props.ticket.known_emails || []) as string[],
})

const emailInput = ref('')

// Modal states
const showAddInboxModal = ref(false)
const showAddEntityModal = ref(false)
const showAddTypeModal = ref(false)

// New inbox/entity/type forms
const newInbox = reactive({ name: '', description: '' })
const newEntity = reactive({ nif: '', name: '', email: '', phone: '', mobile: '', website: '' })
const newType = reactive({ name: '', description: '' })

const inboxForm = useForm(newInbox)
const entityForm = useForm(newEntity)
const typeForm = useForm(newType)

const inboxes = ref(props.inboxes)
const entities = ref(props.entities)
const types = ref(props.types)
const addInbox = async () => {
  try {
    inboxForm.processing = true
    const { data } = await axios.post(route('inboxes.store'), inboxForm.data())
    const newIb = data.inbox
    inboxes.value.push(newIb)
    form.inbox_id = String(newIb.id)
    showAddInboxModal.value = false
    inboxForm.reset(); inboxForm.errors = {}
  } catch (e: any) {
    if (e?.response?.status === 422) inboxForm.errors = e.response.data.errors || {}
  } finally { inboxForm.processing = false }
}

const addEmail = () => {
  if (emailInput.value && emailInput.value.includes('@')) {
    form.known_emails.push(emailInput.value)
    emailInput.value = ''
  }
}

const removeEmail = (index: number) => { form.known_emails.splice(index, 1) }

const addEntity = async () => {
  try {
    entityForm.processing = true
    const { data } = await axios.post(route('entities.store'), entityForm.data())
    const newEnt = data.entity
    entities.value.push(newEnt)
    form.entity_id = String(newEnt.id)
    showAddEntityModal.value = false
    entityForm.reset(); entityForm.errors = {}
  } catch (e: any) {
    if (e?.response?.status === 422) entityForm.errors = e.response.data.errors || {}
  } finally { entityForm.processing = false }
}

const addType = async () => {
  try {
    typeForm.processing = true
    const { data } = await axios.post(route('ticket-types.store'), typeForm.data())
    const newTp = data.type
    types.value.push(newTp)
    form.type_id = String(newTp.id)
    showAddTypeModal.value = false
    typeForm.reset(); typeForm.errors = {}
  } catch (e: any) {
    if (e?.response?.status === 422) typeForm.errors = e.response.data.errors || {}
  } finally { typeForm.processing = false }
}

const submit = () => {
  form.put(route('tickets.update', props.ticket.id), {
    onSuccess: () => {},
  })
}
</script>

<template>
  <div class="space-y-6 px-6 py-4">
    <Head title="Editar Ticket" />

    <!-- Header -->
    <div class="space-y-2">
      <Button variant="ghost" size="sm" as-child>
        <Link :href="route('tickets.show', props.ticket.id)">
          <ArrowLeft class="h-4 w-4 mr-2" />
          Voltar
        </Link>
      </Button>
      <div class="flex items-center gap-3">
        <div class="rounded-lg bg-primary/10 p-2">
          <Pencil class="h-8 w-8 text-primary" />
        </div>
        <div>
          <Heading title="Editar Ticket" description="Atualize as informações deste ticket" />
        </div>
      </div>
    </div>

    <!-- Form -->
    <Card>
      <CardHeader>
        <CardTitle>Informações do Ticket</CardTitle>
      </CardHeader>
      <CardContent>
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Inbox -->
          <div class="space-y-2">
            <Label for="inbox_id">Inbox *</Label>
            <div class="flex items-center gap-0">
              <Select v-model="form.inbox_id" class="flex-1">
                <SelectTrigger>
                  <SelectValue placeholder="Selecione um inbox" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="inbox in inboxes" :key="inbox.id" :value="String(inbox.id)">
                    {{ inbox.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <button v-if="props.isOperator" type="button" @click="showAddInboxModal = true"
                class="btn btn-sm btn-ghost h-auto py-1 px-2 -ml-px text-sm hover:bg-base-300 hover:text-base-content flex items-center transition-all duration-200 ease-in-out hover:scale-105">
                <Plus class="h-4 w-4" /> <span class="hidden sm:inline">Adicionar</span>
              </button>
            </div>
            <p v-if="form.errors.inbox_id" class="text-sm text-destructive">{{ form.errors.inbox_id }}</p>
          </div>
    <!-- Modal: Add Inbox -->
    <div v-if="showAddInboxModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <Card class="w-full max-w-md">
        <CardHeader><CardTitle>Adicionar Inbox</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="addInbox" class="space-y-4">
            <div class="space-y-2">
              <Label for="inbox_name">Nome *</Label>
              <Input id="inbox_name" v-model="inboxForm.name" type="text" placeholder="Nome do inbox" required />
              <p v-if="inboxForm.errors.name" class="text-sm text-destructive">{{ inboxForm.errors.name }}</p>
            </div>
            <div class="space-y-2">
              <Label for="inbox_description">Descrição</Label>
              <Textarea id="inbox_description" v-model="inboxForm.description" placeholder="Descrição (opcional)" />
              <p v-if="inboxForm.errors.description" class="text-sm text-destructive">{{ inboxForm.errors.description }}</p>
            </div>
            <div class="flex gap-2">
              <Button type="submit" :disabled="inboxForm.processing">{{ inboxForm.processing ? 'Adicionando...' : 'Adicionar' }}</Button>
              <Button type="button" variant="outline" @click="showAddInboxModal = false">Cancelar</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>

          <!-- Entity, Type, Status (Inline) -->
          <div class="grid grid-cols-3 gap-4">
            <!-- Entity -->
            <div class="space-y-1">
              <div class="flex items-center justify-between gap-2 min-h-[36px]">
                <Label for="entity_id">Entidade</Label>
                <button v-if="props.isOperator" type="button" @click="showAddEntityModal = true"
                  class="btn btn-sm btn-ghost gap-1 h-auto py-1 px-2 text-sm hover:bg-base-300 hover:text-base-content flex items-center transition-all duration-200 ease-in-out hover:scale-105">
                  <Plus class="h-4 w-4" /> <span class="hidden sm:inline">Adicionar</span>
                </button>
              </div>
              <Select v-model="form.entity_id">
                <SelectTrigger>
                  <SelectValue placeholder="Selecione uma entidade (opcional)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="entity in entities" :key="entity.id" :value="String(entity.id)">
                    {{ entity.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.entity_id" class="text-sm text-destructive">{{ form.errors.entity_id }}</p>
            </div>

            <!-- Type -->
            <div class="space-y-1">
              <div class="flex items-center justify-between gap-2 min-h-[36px]">
                <Label for="type_id">Tipo</Label>
                <button v-if="props.isOperator" type="button" @click="showAddTypeModal = true"
                  class="btn btn-sm btn-ghost gap-1 h-auto py-1 px-2 text-sm hover:bg-base-300 hover:text-base-content flex items-center transition-all duration-200 ease-in-out hover:scale-105">
                  <Plus class="h-4 w-4" /> <span class="hidden sm:inline">Adicionar</span>
                </button>
              </div>
              <Select v-model="form.type_id">
                <SelectTrigger>
                  <SelectValue placeholder="Selecione um tipo (opcional)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="type in types" :key="type.id" :value="String(type.id)">
                    {{ type.name }}
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.type_id" class="text-sm text-destructive">{{ form.errors.type_id }}</p>
            </div>

            <!-- Status -->
            <div class="space-y-1">
              <div class="flex items-center justify-between gap-2 min-h-[36px]">
                <Label for="status_id">Estado</Label>
                <!-- Placeholder button to align with Entity/Type when operator has add buttons -->
                <button v-if="props.isOperator" type="button"
                  class="btn btn-sm btn-ghost gap-1 h-auto py-1 px-2 text-sm opacity-0 pointer-events-none">
                  <Plus class="h-4 w-4" /> <span class="hidden sm:inline">Adicionar</span>
                </button>
              </div>
              <template v-if="props.isOperator">
                <Select v-model="form.status_id">
                  <SelectTrigger>
                    <SelectValue placeholder="Selecione um estado" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="status in props.statuses" :key="status.id" :value="String(status.id)">
                      {{ status.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </template>
              <template v-else>
                <Select v-model="form.status_id" disabled>
                  <SelectTrigger>
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="status in props.statuses" :key="status.id" :value="String(status.id)">
                      {{ status.name }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </template>
              <p v-if="form.errors.status_id" class="text-sm text-destructive">{{ form.errors.status_id }}</p>
            </div>
          </div>

          <!-- Subject -->
          <div class="space-y-2">
            <Label for="subject">Assunto *</Label>
            <Input id="subject" v-model="form.subject" type="text" placeholder="Digite o assunto" required />
            <p v-if="form.errors.subject" class="text-sm text-destructive">{{ form.errors.subject }}</p>
          </div>

          <!-- Content -->
          <div class="space-y-2">
            <Label for="content">Descrição</Label>
            <div v-if="props.ticket.content" class="rounded-md bg-secondary/30 p-3 text-sm text-muted-foreground whitespace-pre-wrap">
              <div class="text-xs font-medium mb-1">Descrição atual</div>
              {{ props.ticket.content }}
            </div>
            <Textarea id="content" v-model="form.content" placeholder="Atualize a descrição"
              class="min-h-[150px]" />
            <p v-if="form.errors.content" class="text-sm text-destructive">{{ form.errors.content }}</p>
          </div>

          <!-- Assigned To -->
          <div class="space-y-2">
            <Label for="assigned_to">Operador Responsável</Label>
            <Select v-model="form.assigned_to">
              <SelectTrigger>
                <SelectValue placeholder="Selecione um operador (opcional)" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem v-for="operator in props.operators" :key="operator.id" :value="String(operator.id)">
                  {{ operator.name }} ({{ operator.email }})
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.assigned_to" class="text-sm text-destructive">{{ form.errors.assigned_to }}</p>
          </div>

          <!-- Known Emails -->
          <div class="space-y-2">
            <Label>Emails para CC</Label>
            <div class="flex gap-2">
              <Input v-model="emailInput" type="email" placeholder="Digite um email para adicionar em CC"
                @keyup.enter="addEmail" />
              <Button type="button" variant="outline" @click="addEmail">Adicionar</Button>
            </div>
            <div v-if="form.known_emails.length > 0" class="mt-3 flex flex-wrap gap-2">
              <div v-for="(email, idx) in form.known_emails" :key="idx"
                class="flex items-center gap-2 bg-secondary px-3 py-1 rounded-full text-sm">
                <span>{{ email }}</span>
                <button type="button" @click="removeEmail(idx)" class="ml-1 hover:text-destructive">✕</button>
              </div>
            </div>
            <p v-if="form.errors.known_emails" class="text-sm text-destructive">{{ form.errors.known_emails }}</p>
          </div>

          <!-- Submit Button -->
          <div class="flex gap-0 pt-2">
            <Button type="submit" :disabled="form.processing">
              {{ form.processing ? 'A guardar...' : 'Guardar Alterações' }}
            </Button>
            <Button type="button" variant="outline" as-child>
              <Link :href="route('tickets.show', props.ticket.id)">Cancelar</Link>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>

    <!-- Modal: Add Entity -->
    <div v-if="showAddEntityModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <Card class="w-full max-w-md">
        <CardHeader><CardTitle>Adicionar Entidade</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="addEntity" class="space-y-4">
            <div class="space-y-2">
              <Label for="nif">NIF *</Label>
              <Input id="nif" v-model="entityForm.nif" type="text" placeholder="Número fiscal" required />
              <p v-if="entityForm.errors.nif" class="text-sm text-destructive">{{ entityForm.errors.nif }}</p>
            </div>
            <div class="space-y-2">
              <Label for="entity_name">Nome *</Label>
              <Input id="entity_name" v-model="entityForm.name" type="text" placeholder="Nome" required />
              <p v-if="entityForm.errors.name" class="text-sm text-destructive">{{ entityForm.errors.name }}</p>
            </div>
            <div class="space-y-2">
              <Label for="entity_email">Email *</Label>
              <Input id="entity_email" v-model="entityForm.email" type="email" placeholder="Email" required />
              <p v-if="entityForm.errors.email" class="text-sm text-destructive">{{ entityForm.errors.email }}</p>
            </div>
            <div class="space-y-2">
              <Label for="entity_phone">Telefone</Label>
              <Input id="entity_phone" v-model="entityForm.phone" type="text" placeholder="Telefone" />
            </div>
            <div class="space-y-2">
              <Label for="entity_mobile">Telemóvel</Label>
              <Input id="entity_mobile" v-model="entityForm.mobile" type="text" placeholder="Telemóvel" />
            </div>
            <div class="flex gap-0">
              <Button type="submit" :disabled="entityForm.processing">{{ entityForm.processing ? 'Adicionando...' : 'Adicionar' }}</Button>
              <Button type="button" variant="outline" @click="showAddEntityModal = false">Cancelar</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>

    <!-- Modal: Add Type -->
    <div v-if="showAddTypeModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <Card class="w-full max-w-md">
        <CardHeader><CardTitle>Adicionar Tipo</CardTitle></CardHeader>
        <CardContent>
          <form @submit.prevent="addType" class="space-y-4">
            <div class="space-y-2">
              <Label for="type_name">Nome *</Label>
              <Input id="type_name" v-model="typeForm.name" type="text" placeholder="Nome do tipo" required />
              <p v-if="typeForm.errors.name" class="text-sm text-destructive">{{ typeForm.errors.name }}</p>
            </div>
            <div class="space-y-2">
              <Label for="type_description">Descrição</Label>
              <Textarea id="type_description" v-model="typeForm.description" placeholder="Descrição (opcional)" class="min-h-[80px]" />
            </div>
            <div class="flex gap-0">
              <Button type="submit" :disabled="typeForm.processing">{{ typeForm.processing ? 'Adicionando...' : 'Adicionar' }}</Button>
              <Button type="button" variant="outline" @click="showAddTypeModal = false">Cancelar</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
