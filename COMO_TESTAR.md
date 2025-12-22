# Guia de Testes - Sistema de Respostas

## 📋 Visão Geral

Este guia mostra como testar o sistema de respostas a tickets com notificações automáticas.

---

## 🚀 Método 1: Script PHP Automático (Recomendado)

### Passo 1: Configurar o Ambiente

Edite o arquivo `.env` e configure:

```env
# Para ver emails no log
MAIL_MAILER=log

# Para enfileirar emails (recomendado)
QUEUE_CONNECTION=database

# Delay entre emails (1 segundo = 1000ms)
MAIL_SEND_DELAY_MS=1000
```

### Passo 2: Preparar a Base de Dados

```bash
# Garantir que as migrações estão aplicadas
php artisan migrate

# (Opcional) Criar as tabelas de jobs se usar queue=database
php artisan queue:table
php artisan migrate
```

### Passo 3: Executar o Script de Teste

```bash
php scripts/test_ticket_reply.php
```

**O que o script faz:**
1. ✅ Cria um usuário de teste (`test@example.com`)
2. ✅ Cria uma inbox de teste
3. ✅ Cria um ticket com 2 contatos em CC
4. ✅ Adiciona 2 respostas ao ticket
5. ✅ Enfileira notificações automáticas

**Saída esperada:**
```
=== Testing Ticket Reply System ===

1. Creating/getting test user...
   User: Test User (test@example.com)

2. Creating/getting test inbox...
   Inbox: Test Inbox

3. Creating ticket with CC contacts...
   Ticket created: TC-000001
   Subject: Test Ticket - Reply System
   CC Contacts: cc1@example.com, cc2@example.com

4. Creating reply to ticket...
   Reply created (ID: 1)
   By: Test User
   Additional CC: cc3@example.com

5. Creating second reply...
   Second reply created (ID: 2)

=== Expected Notification Recipients ===
...
```

### Passo 4: Processar a Fila de Emails

```bash
# Processar todos os jobs enfileirados
php artisan queue:work --once

# OU processar continuamente (Ctrl+C para parar)
php artisan queue:work
```

### Passo 5: Verificar os Emails Enviados

```bash
# Ver os últimos emails no log
Get-Content storage/logs/laravel.log -Tail 100

# OU no Linux/Mac
tail -f storage/logs/laravel.log
```

**O que procurar:**
- Linhas com "Local: MAIL sent to"
- Assunto: "[Ticket Reply] TC-000001"
- Destinatários: test@example.com, cc1@example.com, cc2@example.com, cc3@example.com

---

## 🌐 Método 2: Teste via HTTP/API

### Passo 1: Iniciar o Servidor

```bash
php artisan serve
```

Servidor rodando em: `http://localhost:8000`

### Passo 2: Criar um Ticket (via rota dev)

```bash
# Windows PowerShell
Invoke-WebRequest -Uri "http://localhost:8000/dev/create-ticket?subject=Teste&known_emails=maria@example.com,joao@example.com" -Method GET

# OU abrir no navegador
http://localhost:8000/dev/create-ticket?subject=Teste&known_emails=maria@example.com,joao@example.com
```

**Resposta esperada:**
```json
{
  "ok": true,
  "ticket": {
    "id": 1,
    "ticket_number": "TC-000001",
    "subject": "Teste",
    "known_emails": ["maria@example.com", "joao@example.com"]
  }
}
```

### Passo 3: Fazer Login

Acesse `http://localhost:8000/login` e faça login com:
- Email: `test@example.com` (ou o usuário criado)
- Password: `password`

### Passo 4: Criar uma Resposta via API

```bash
# Windows PowerShell
$body = @{
    content = "Esta é uma resposta de teste com notificações"
    cc = @("novoemail@example.com")
    is_internal = $false
} | ConvertTo-Json

Invoke-WebRequest -Uri "http://localhost:8000/tickets/1/reply" `
    -Method POST `
    -ContentType "application/json" `
    -Body $body `
    -Headers @{
        "Accept" = "application/json"
        "X-CSRF-TOKEN" = "seu-token-aqui"
    }
```

**Ou usando um cliente HTTP como Postman/Insomnia:**

```
POST http://localhost:8000/tickets/1/reply
Content-Type: application/json

{
  "content": "Esta é uma resposta de teste",
  "cc": ["novoemail@example.com"],
  "is_internal": false
}
```

### Passo 5: Verificar Notificações

```bash
# Ver jobs enfileirados
php artisan queue:work --once

# Ver emails no log
Get-Content storage/logs/laravel.log -Tail 50
```

---

## 🧪 Método 3: Teste com Anexos

### Criar uma Resposta com Anexos

Crie um arquivo HTML para testar upload:

```html
<!-- test_upload.html -->
<!DOCTYPE html>
<html>
<body>
<h1>Teste de Resposta com Anexo</h1>
<form action="http://localhost:8000/tickets/1/reply" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="SEU_CSRF_TOKEN">
    
    <label>Mensagem:</label><br>
    <textarea name="content" rows="5" cols="50">Esta é uma resposta com anexos</textarea><br><br>
    
    <label>Anexos:</label><br>
    <input type="file" name="attachments[]" multiple><br><br>
    
    <label>CC (separado por vírgulas):</label><br>
    <input type="text" name="cc[]" value="teste@example.com"><br><br>
    
    <button type="submit">Enviar Resposta</button>
</form>
</body>
</html>
```

---

## 🔍 Método 4: Teste via Tinker

```bash
php artisan tinker
```

```php
// Criar usuário
$user = User::first();

// Criar ticket
$ticket = Ticket::create([
    'inbox_id' => 1,
    'requester_id' => $user->id,
    'subject' => 'Ticket via Tinker',
    'known_emails' => ['cc@example.com'],
]);

// Criar resposta
$message = TicketMessage::create([
    'ticket_id' => $ticket->id,
    'user_id' => $user->id,
    'content' => 'Resposta de teste via Tinker',
    'cc' => ['outro@example.com'],
    'is_internal' => false,
]);

// Verificar que foi criado
$message->id; // Deve retornar um número

// Ver jobs enfileirados (se QUEUE_CONNECTION=database)
DB::table('jobs')->count(); // Deve mostrar jobs pendentes

exit
```

Depois execute:
```bash
php artisan queue:work --once
```

---

## ✅ Checklist de Verificação

Após executar qualquer teste, verifique:

### 1. ✅ Resposta foi criada?
```bash
php artisan tinker
>>> TicketMessage::latest()->first()
>>> exit
```

### 2. ✅ Jobs foram enfileirados?
```bash
php artisan tinker
>>> DB::table('jobs')->count()
>>> exit
```

### 3. ✅ Emails foram "enviados"?
```bash
# Ver log
Get-Content storage/logs/laravel.log | Select-String "MAIL sent"
```

### 4. ✅ Destinatários corretos?
Procure no log por:
- Email do criador do ticket
- Emails em CC do ticket (known_emails)
- Emails em CC da mensagem

### 5. ✅ Anexos foram salvos?
```bash
# Ver arquivos
Get-ChildItem storage/app/public/ticket-attachments -Recurse
```

---

## 🐛 Troubleshooting

### Problema: "Queue is empty" mas não vejo emails

**Solução:**
```bash
# Verificar configuração
php artisan config:clear
php artisan cache:clear

# Verificar se QUEUE_CONNECTION está correto no .env
# Se for 'sync', os jobs executam imediatamente
```

### Problema: Erro "Class TicketMessageObserver not found"

**Solução:**
```bash
composer dump-autoload
php artisan config:clear
```

### Problema: Não vejo emails no log

**Solução:**
Verifique o `.env`:
```env
MAIL_MAILER=log
LOG_LEVEL=debug
```

### Problema: CSRF token mismatch

**Solução:**
Para testes com API, use rotas de teste ou desabilite CSRF temporariamente em `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'tickets/*/reply', // Apenas para testes locais!
];
```

---

## 📊 Exemplo Completo de Teste

```bash
# 1. Limpar ambiente
php artisan migrate:fresh

# 2. Executar script de teste
php scripts/test_ticket_reply.php

# 3. Processar fila
php artisan queue:work --once

# 4. Ver resultados
Get-Content storage/logs/laravel.log -Tail 100 | Select-String "Ticket Reply"
```

**Resultado esperado:** 
- 4-5 emails enfileirados por resposta
- Todos com assunto "[Ticket Reply] TC-000001"
- Destinatários únicos (sem duplicatas)

---

## 💡 Dicas

1. **Modo de Desenvolvimento:** Use `MAIL_MAILER=log` para ver emails sem enviá-los
2. **Modo de Produção:** Use `MAIL_MAILER=smtp` com credenciais reais
3. **Queue em Produção:** Use `QUEUE_CONNECTION=redis` ou `database` + supervisor
4. **Testes Automatizados:** Crie testes PHPUnit para CI/CD
5. **Monitoramento:** Use `php artisan queue:monitor` para acompanhar a fila

---

## 📞 Comandos Úteis

```bash
# Ver fila
php artisan queue:work --once       # Processar 1 job
php artisan queue:work              # Processar continuamente
php artisan queue:listen            # Processar com reload automático
php artisan queue:failed            # Ver jobs falhados
php artisan queue:retry all         # Retentar jobs falhados
php artisan queue:flush             # Limpar jobs falhados

# Ver logs
Get-Content storage/logs/laravel.log -Tail 50
Get-Content storage/logs/laravel.log | Select-String "error"

# Limpar caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```
