# Sistema de Gestão e Visualização de Tickets

## Funcionalidades Implementadas

### 1. **Filtros Avançados**

O sistema agora possui filtros completos para facilitar a gestão de tickets:

- **Filtro por Inbox**: Filtre tickets por caixa de entrada específica
- **Filtro por Estado**: Visualize tickets por status (aberto, em processo, fechado, etc.)
- **Filtro por Operador**: Veja tickets atribuídos a operadores específicos
- **Filtro por Tipo**: Filtre por tipo de ticket
- **Filtro por Entidade**: Filtre por entidade associada ao ticket

#### Como usar:
- Acesse a página de listagem de tickets em `/tickets`
- Use os dropdowns de filtro no topo da página
- Os filtros são aplicados automaticamente ao selecionar
- Use o botão "Limpar Filtros" para resetar

### 2. **Pesquisa Rápida**

Sistema de pesquisa inteligente que permite encontrar tickets por:

- **Número do Ticket**: Ex: TC-000001
- **Assunto**: Busca no título do ticket
- **Email**: Busca no email do requerente ou emails em CC
- **Entidade**: Busca pelo nome da entidade

#### Como usar:
- Digite na barra de pesquisa no topo dos filtros
- A pesquisa é executada automaticamente após 500ms (debounced)
- A pesquisa procura em múltiplos campos simultaneamente

### 3. **Histórico Completo de Atividades**

Sistema de log que registra automaticamente todas as alterações:

#### Atividades Registradas:
- **Criação de Ticket**: Quando um novo ticket é criado
- **Alterações de Estado**: Mudanças no status do ticket
- **Reatribuições**: Quando o ticket é atribuído a outro operador
- **Alterações de Tipo**: Mudanças no tipo do ticket
- **Alterações de Entidade**: Mudanças na entidade associada
- **Respostas**: Quando alguém responde ao ticket
- **Notas Internas**: Quando notas internas são adicionadas

#### Informações do Log:
- Usuário que realizou a ação
- Data e hora da ação
- Descrição da alteração
- Valores antigos e novos (para alterações)
- Metadados adicionais (anexos, CC, etc.)

### 4. **Visualização Detalhada do Ticket**

Página completa de visualização com:

#### Seção de Informações:
- Estado, Tipo e Inbox do ticket
- Informações do requerente e operador atribuído
- Entidade associada
- Datas de criação e fechamento
- Lista de emails em CC

#### Seção de Mensagens:
- Histórico completo de todas as mensagens
- Identificação do autor (usuário ou contato)
- Anexos com tamanho do arquivo
- Emails em CC por mensagem
- Distinção entre mensagens públicas e notas internas

#### Seção de Atividades:
- Timeline visual de todas as atividades
- Ícones coloridos por tipo de ação
- Detalhes de alterações com valores antigos e novos
- Informações do usuário que realizou cada ação

## Estrutura Técnica

### Backend (Laravel)

#### Novo Modelo:
- `TicketActivity`: Gerencia o log de atividades

#### Controller Atualizado:
- `TicketController::index()`: Listagem com filtros e pesquisa
- `TicketController::show()`: Visualização detalhada com histórico

#### Observers Atualizados:
- `TicketObserver`: Registra criação e alterações de tickets
- `TicketMessageObserver`: Registra respostas e notas internas

#### Migration:
- `create_ticket_activities_table`: Tabela para armazenar o histórico

### Frontend (Vue.js)

#### Novos Componentes:
- `TicketFilters.vue`: Componente de filtros reutilizável
- `TicketActivityLog.vue`: Timeline de atividades com ícones

#### Novas Páginas:
- `Tickets/Index.vue`: Listagem de tickets com filtros e pesquisa
- `Tickets/Show.vue`: Visualização completa do ticket

## Rotas

```php
GET  /tickets              - Lista todos os tickets (com filtros)
GET  /tickets/{ticket}     - Visualiza um ticket específico
POST /tickets              - Cria um novo ticket
POST /tickets/{ticket}/reply - Responde a um ticket
```

## Exemplos de Uso

### Filtrando Tickets:
```
GET /tickets?inbox_id=1&status_id=2&assigned_to=3
```

### Pesquisando Tickets:
```
GET /tickets?search=TC-000001
GET /tickets?search=cliente@example.com
GET /tickets?search=Empresa ABC
```

### Combinando Filtros e Pesquisa:
```
GET /tickets?inbox_id=1&status_id=2&search=urgente
```

## Recursos de UI

- **Responsivo**: Funciona em desktop, tablet e mobile
- **Paginação**: Listagem paginada com 15 tickets por página
- **Badges Coloridos**: Estados e tipos com cores distintas
- **Tooltips**: Informações adicionais ao passar o mouse
- **Loading States**: Indicadores de carregamento
- **Empty States**: Mensagens quando não há dados

## Melhorias Futuras Sugeridas

1. Exportação de relatórios (PDF, Excel)
2. Filtros salvos (favoritos)
3. Notificações em tempo real
4. Métricas e dashboards
5. Bulk actions (ações em massa)
6. Templates de resposta
7. SLA tracking
8. Automações baseadas em regras

## Manutenção

### Para adicionar novos tipos de atividades:

1. Adicione a lógica no Observer correspondente
2. Atualize o método `getActivityIcon()` em `TicketActivityLog.vue`
3. Atualize o método `getActivityColor()` em `TicketActivityLog.vue`

### Para adicionar novos filtros:

1. Adicione o campo no `TicketController::index()`
2. Adicione o filtro no componente `TicketFilters.vue`
3. Adicione a opção de dados no controller

## Suporte

Para questões ou problemas, consulte a documentação do Laravel e Inertia.js, ou entre em contato com a equipe de desenvolvimento.
