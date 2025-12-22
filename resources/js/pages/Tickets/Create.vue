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
import { ArrowLeft, Plus } from 'lucide-vue-next'
import { ref } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'

const route = useRoute()

defineOptions({
  layout: AppLayout,
})

interface SelectOption {
  id: number
  name: string
}

interface Operator extends SelectOption {
  email: string
}

interface Props {
  inboxes: SelectOption[]
  statuses: SelectOption[]
  types: SelectOption[]
  entities: SelectOption[]
  operators: Operator[]
}

const props = defineProps<Props>()

const form = useForm({
  inbox_id: '',
  subject: '',
  content: '',
  assigned_to: '',
  known_emails: [] as string[],
})

const emailInput = ref('')

const addEmail = () => {
  if (emailInput.value && emailInput.value.includes('@')) {
    form.known_emails.push(emailInput.value)
    emailInput.value = ''
  }
}

const removeEmail = (index: number) => {
  form.known_emails.splice(index, 1)
}

const submit = () => {
  form.post(route('tickets.store'), {
    onSuccess: () => {
      form.reset()
    },
  })
}
</script>

<template>
  <div class="space-y-6 px-6 py-4">
    <Head title="Criar Novo Ticket" />

    <!-- Header -->
    <div class="space-y-2">
      <Button variant="ghost" size="sm" as-child>
        <Link :href="route('tickets.index')">
          <ArrowLeft class="h-4 w-4 mr-2" />
          Voltar
        </Link>
      </Button>
      <div class="flex items-center gap-3">
        <div class="rounded-lg bg-primary/10 p-2">
          <Plus class="h-8 w-8 text-primary" />
        </div>
        <div>
          <Heading title="Criar Novo Ticket" description="Preencha os dados para criar um novo ticket de suporte" />
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
            <Select v-model="form.inbox_id">
              <SelectTrigger>
                <SelectValue placeholder="Selecione um inbox" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="inbox in props.inboxes"
                  :key="inbox.id"
                  :value="String(inbox.id)"
                >
                  {{ inbox.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.inbox_id" class="text-sm text-destructive">
              {{ form.errors.inbox_id }}
            </p>
          </div>

          <!-- Subject -->
          <div class="space-y-2">
            <Label for="subject">Assunto *</Label>
            <Input
              id="subject"
              v-model="form.subject"
              type="text"
              placeholder="Digite o assunto do ticket"
              required
            />
            <p v-if="form.errors.subject" class="text-sm text-destructive">
              {{ form.errors.subject }}
            </p>
          </div>

          <!-- Content -->
          <div class="space-y-2">
            <Label for="content">Descrição</Label>
            <Textarea
              id="content"
              v-model="form.content"
              placeholder="Digite a descrição do ticket"
              class="min-h-[150px]"
            />
            <p v-if="form.errors.content" class="text-sm text-destructive">
              {{ form.errors.content }}
            </p>
          </div>

          <!-- Assigned To -->
          <div class="space-y-2">
            <Label for="assigned_to">Operador Responsável</Label>
            <Select v-model="form.assigned_to">
              <SelectTrigger>
                <SelectValue placeholder="Selecione um operador (opcional)" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="operator in props.operators"
                  :key="operator.id"
                  :value="String(operator.id)"
                >
                  {{ operator.name }} ({{ operator.email }})
                </SelectItem>
              </SelectContent>
            </Select>
            <p v-if="form.errors.assigned_to" class="text-sm text-destructive">
              {{ form.errors.assigned_to }}
            </p>
          </div>

          <!-- Known Emails -->
          <div class="space-y-2">
            <Label>Emails para CC</Label>
            <div class="flex gap-2">
              <Input
                v-model="emailInput"
                type="email"
                placeholder="Digite um email para adicionar em CC"
                @keyup.enter="addEmail"
              />
              <Button type="button" variant="outline" @click="addEmail">
                Adicionar
              </Button>
            </div>
            <div v-if="form.known_emails.length > 0" class="mt-3 flex flex-wrap gap-2">
              <div
                v-for="(email, idx) in form.known_emails"
                :key="idx"
                class="flex items-center gap-2 bg-secondary px-3 py-1 rounded-full text-sm"
              >
                <span>{{ email }}</span>
                <button
                  type="button"
                  @click="removeEmail(idx)"
                  class="ml-1 hover:text-destructive"
                >
                  ✕
                </button>
              </div>
            </div>
            <p v-if="form.errors.known_emails" class="text-sm text-destructive">
              {{ form.errors.known_emails }}
            </p>
          </div>

          <!-- Submit Button -->
          <div class="flex gap-2 pt-4">
            <Button type="submit" :disabled="form.processing">
              {{ form.processing ? 'Criando...' : 'Criar Ticket' }}
            </Button>
            <Button type="button" variant="outline" as-child>
              <Link :href="route('tickets.index')">
                Cancelar
              </Link>
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>
