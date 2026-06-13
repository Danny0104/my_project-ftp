# Live chat setup (Socket.IO + Yii2)

## 1. Database

```bash
cd c:\xampp\htdocs\my_project
php yii migrate
```

Creates: `chat_conversation`, `chat_participant`, `chat_message`, `chat_message_status`, `chat_presence`, `chat_typing`.

## 2. Realtime server

```bash
cd realtime/chat-server
npm install
npm start
```

Default port: **3001**. Health check: `http://127.0.0.1:3001/health`

## 3. Yii params

In `common/config/params.php` (or `params-local.php`):

```php
'chat.websocketUrl' => 'http://127.0.0.1:3001',
'chat.broadcastUrl' => 'http://127.0.0.1:3001/broadcast',
'chat.pollIntervalMs' => 2500,
```

## 4. Usage

- **Organizations:** Messages → select applicant or org notification thread → compose and send.
- **Students:** Messages → select organization thread → reply.
- **Admin/system notifications:** read-only (no composer).

If Socket.IO is offline, HTTP polling still delivers messages every ~2.5s.

## 5. API endpoints

| Action | Route |
|--------|--------|
| Open thread | `GET message/ensure?application_id=` or `?notification_id=` |
| History | `GET message/thread?conversation_id=` |
| Send | `POST message/send` |
| Poll | `GET message/poll?conversation_id=&since_id=` |
| Typing | `POST message/typing` |
| Presence | `POST message/heartbeat` |

Attachments: PDF, PNG, JPEG, GIF, WebP (max 8MB) → `frontend/web/uploads/chat/`.
