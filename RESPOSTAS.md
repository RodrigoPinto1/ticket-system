# Sistema de Respostas - Documentação

## Visão Geral

Sistema completo de respostas para tickets com notificações automáticas por email.

## Funcionalidades Implementadas

### 1. **Respostas com Notificações Automáticas**

Cada resposta a um ticket gera notificações automáticas para:
- ✅ Criador do ticket (requester)
- ✅ Todos os contatos em conhecimento (CC) do ticket
- ✅ Contatos em CC específicos da mensagem
- ✅ Usuário responsável (assignee), se atribuído

### 2. **Suporte a Múltiplos Tipos de Conteúdo**

As respostas suportam:
- ✅ **Texto** - conteúdo de texto formatado
- ✅ **Imagens** - anexadas como arquivos
- ✅ **Anexos** - qualquer tipo de arquivo (até 10MB por arquivo)

### 3. **Mensagens Internas**

- Respostas podem ser marcadas como `is_internal` para comunicação interna
- Mensagens internas **não** geram notificações por email

## Arquivos Criados/Modificados

### Novos Arquivos

1. **`app/Observers/TicketMessageObserver.php`**
   - Observer que detecta quando uma nova mensagem é criada
   - Envia notificações automáticas para todos os interessados
   - Respeita delays configurados para evitar rate limits

2. **`app/Mail/TicketReplied.php`**
   - Mailable para emails de notificação de resposta
   - Contém informações do ticket e da mensagem

3. **`resources/views/emails/ticket/replied.blade.php`**
   - Template HTML responsivo para emails de resposta
   - Exibe conteúdo da mensagem, anexos e informações do ticket

4. **`scripts/test_ticket_reply.php`**
   - Script de teste para validar a funcionalidade
   - Cria ticket e respostas de teste

### Arquivos Modificados

1. **`app/Http/Controllers/TicketController.php`**
   - Adicionado método `reply()` para criar respostas
   - Suporte para upload de anexos
   - Validação de dados de entrada

2. **`app/Providers/AppServiceProvider.php`**
   - Registrado `TicketMessageObserver` para observar o modelo `TicketMessage`

3. **`routes/web.php`**
   - Adicionada rota `POST /tickets/{ticket}/reply` para criar respostas

## Estrutura de Dados

### Modelo TicketMessage

```php
[
    'ticket_id' => integer,      // ID do ticket
    'user_id' => integer|null,   // ID do usuário que responde
    'contact_id' => integer|null,// ID do contato externo (se aplicável)
    'content' => string,         // Conteúdo da resposta
    'cc' => array,              // Emails em CC para esta mensagem
    'is_internal' => boolean,   // Se é uma nota interna
]
```

### Modelo MessageAttachment

```php
[
    'message_id' => integer,     // ID da mensagem
    'file_name' => string,       // Nome original do arquivo
    'file_path' => string,       // Caminho no storage
    'mime_type' => string,       // Tipo MIME do arquivo
    'file_size' => integer,      // Tamanho em bytes
]
```

## Como Usar

### 1. Criar uma Resposta via API

```php
POST /tickets/{ticket}/reply

// Dados do formulário
{
    "content": "Texto da resposta",
    "cc": ["email1@example.com", "email2@example.com"], // Opcional
    "is_internal": false,                                 // Opcional
    "attachments": [File, File, ...]                      // Opcional
}
```

### 2. Criar uma Resposta via Código

```php
use App\Models\TicketMessage;
use App\Models\Ticket;

$ticket = Ticket::find(1);

$message = TicketMessage::create([
    'ticket_id' => $ticket->id,
    'user_id' => auth()->id(),
    'content' => 'Minha resposta ao ticket',
    'cc' => ['outro@example.com'],
    'is_internal' => false,
]);

// Notificações são enviadas automaticamente pelo Observer!
```

### 3. Anexar Arquivos

```php
// Através do controller (automático)
// Os arquivos são salvos em storage/app/public/ticket-attachments/{ticket_id}/

// Ou manualmente:
$message->attachments()->create([
    'file_name' => 'documento.pdf',
    'file_path' => 'ticket-attachments/1/documento.pdf',
    'mime_type' => 'application/pdf',
    'file_size' => 12345,
]);
```

## Fluxo de Notificações

1. **Nova resposta é criada** → `TicketMessage::create()`
2. **Observer detecta** → `TicketMessageObserver::created()`
3. **Observer coleta destinatários:**
   - Criador do ticket
   - CCs do ticket
   - CCs da mensagem
   - Responsável (assignee)
4. **Observer enfileira emails** com delays incrementais
5. **Queue processa emails** → `TicketReplied` Mailable
6. **Emails são enviados** via configuração de mail

## Configurações

### Variáveis de Ambiente

```env
# Delay entre envios de email (em milissegundos)
MAIL_SEND_DELAY_MS=1000

# Configuração de email
MAIL_MAILER=log  # ou smtp, sendmail, etc.

# Para desenvolvimento
APP_ENV=local
```

### Processamento de Fila

Para processar as notificações enfileiradas:

```bash
php artisan queue:work
```

## Testes

### Executar Script de Teste

```bash
php scripts/test_ticket_reply.php
```

Este script:
- Cria um usuário de teste
- Cria um ticket com CCs
- Adiciona duas respostas
- Mostra os destinatários esperados

### Verificar Logs

Se `MAIL_MAILER=log`, verifique:

```bash
tail -f storage/logs/laravel.log
```

## Segurança

- ✅ Rotas protegidas com middleware `auth`
- ✅ Validação de dados de entrada
- ✅ Limite de tamanho de arquivo (10MB)
- ✅ Sanitização de conteúdo nos emails (uso de `e()` no Blade)
- ✅ Armazenamento seguro de anexos

## Políticas de Acesso

A rota de resposta pode ser combinada com políticas (Policy) para controlar:
- Quem pode responder a tickets
- Permissões baseadas em papel (inbox member, assignee, etc.)

Ver: `app/Policies/TicketPolicy.php` → método `reply()`

## Melhorias Futuras

- [ ] Suporte a respostas via email (inbound parsing)
- [ ] Notificações em tempo real (WebSockets/Pusher)
- [ ] Templates de resposta personalizáveis
- [ ] Assinaturas automáticas de usuários
- [ ] Editor rico (WYSIWYG) para respostas
- [ ] Menções (@usuário) em respostas
- [ ] Reações e likes em mensagens

## Suporte

Para dúvidas ou problemas, verifique:
1. Logs do Laravel: `storage/logs/laravel.log`
2. Configuração de email: `config/mail.php`
3. Status da fila: `php artisan queue:failed`
