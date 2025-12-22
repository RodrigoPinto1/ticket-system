<script setup lang="ts">
import { format } from 'date-fns'
import { ptBR } from 'date-fns/locale'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import {
  Activity,
  FileText,
  Edit3,
  MessageSquare,
  StickyNote,
  UserPlus,
  RefreshCw
} from 'lucide-vue-next'

interface User {
  id: number
  name: string
  email: string
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

defineProps<{
  activities: Activity[]
}>()

const getActivityIcon = (action: string) => {
  const icons: Record<string, any> = {
    created: FileText,
    updated: Edit3,
    replied: MessageSquare,
    internal_note_added: StickyNote,
    assigned: UserPlus,
    status_changed: RefreshCw,
  }
  return icons[action] || Activity
}

const getActivityColor = (action: string) => {
  const colors: Record<string, string> = {
    created: 'bg-gradient-to-br from-green-500 to-emerald-600',
    updated: 'bg-gradient-to-br from-blue-500 to-cyan-600',
    replied: 'bg-gradient-to-br from-purple-500 to-pink-600',
    internal_note_added: 'bg-gradient-to-br from-yellow-500 to-orange-600',
    assigned: 'bg-gradient-to-br from-indigo-500 to-blue-600',
    status_changed: 'bg-gradient-to-br from-orange-500 to-red-600',
  }
  return colors[action] || 'bg-gradient-to-br from-gray-500 to-slate-600'
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  return format(date, "d 'de' MMMM 'às' HH:mm", { locale: ptBR })
}

const getUserInitials = (name: string) => {
  return name
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase()
}
</script>

<template>
  <Card class="shadow-md">
    <CardHeader class="bg-gradient-to-r from-muted/50 to-muted/20">
      <CardTitle class="flex items-center gap-2">
        <div class="rounded-lg bg-primary/10 p-2">
          <Activity class="h-5 w-5 text-primary" />
        </div>
        Histórico de Atividades
      </CardTitle>
    </CardHeader>
    <CardContent class="pt-6">
      <div v-if="activities.length === 0" class="flex flex-col items-center justify-center gap-3 py-12 text-center text-muted-foreground">
        <Activity class="h-16 w-16 opacity-20" />
        <div>
          <p class="font-medium">Nenhuma atividade registrada</p>
          <p class="text-sm">As atividades do ticket aparecerão aqui</p>
        </div>
      </div>

      <div v-else class="relative space-y-6">
        <!-- Timeline line -->
        <div class="absolute left-7 top-0 bottom-0 w-0.5 bg-gradient-to-b from-primary/20 via-primary/10 to-transparent" />

        <!-- Activity items -->
        <div
          v-for="activity in activities"
          :key="activity.id"
          class="relative flex gap-4 group"
        >
          <!-- Icon -->
          <div
            class="relative z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-xl border-4 border-background shadow-lg transition-transform group-hover:scale-110"
            :class="getActivityColor(activity.action)"
          >
            <component :is="getActivityIcon(activity.action)" class="h-6 w-6 text-white" />
          </div>

          <!-- Content -->
          <div class="flex-1 space-y-2 pb-6">
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1 space-y-2">
                <p class="font-semibold text-foreground leading-relaxed">
                  {{ activity.description }}
                </p>

                <div v-if="activity.field && (activity.old_value || activity.new_value)" class="rounded-lg bg-muted/50 p-3 text-sm space-y-1.5 border">
                  <div v-if="activity.old_value" class="flex items-start gap-2">
                    <Badge variant="outline" class="shrink-0">Anterior</Badge>
                    <span class="text-muted-foreground">{{ activity.old_value }}</span>
                  </div>
                  <div v-if="activity.new_value" class="flex items-start gap-2">
                    <Badge variant="default" class="shrink-0">Novo</Badge>
                    <span class="font-medium">{{ activity.new_value }}</span>
                  </div>
                </div>

                <div class="flex items-center gap-3 text-sm text-muted-foreground">
                  <Avatar v-if="activity.user" class="h-6 w-6 border-2 border-background shadow-sm">
                    <AvatarFallback class="text-xs font-semibold bg-gradient-to-br from-blue-500 to-indigo-600 text-white">
                      {{ getUserInitials(activity.user.name) }}
                    </AvatarFallback>
                  </Avatar>
                  <span v-if="activity.user" class="font-medium text-foreground">
                    {{ activity.user.name }}
                  </span>
                  <span class="text-muted-foreground/50">•</span>
                  <time :datetime="activity.created_at" class="flex items-center gap-1.5">
                    <Activity class="h-3.5 w-3.5" />
                    {{ formatDate(activity.created_at) }}
                  </time>
                </div>
              </div>

              <Badge
                variant="secondary"
                class="shrink-0 font-mono text-xs shadow-sm"
              >
                {{ activity.action }}
              </Badge>
            </div>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
