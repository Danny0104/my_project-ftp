# Organization ↔ Student Messaging Architecture

## Overview

The messaging UI is a **real-time-ready chat workspace** built on Yii2 Advanced. Today it uses the existing **`notification` table** as the delivery channel—not a threaded chat backend. The frontend is structured so WebSocket/Pusher/Echo integration can be added without redesigning the UI.

## Current backend (honest capabilities)

| Feature | Status | Implementation |
|---------|--------|----------------|
| List inbox | ✅ | `NotificationController::actionIndex` |
| Unread count (poll) | ✅ | `notification/unread-count` |
| Mark read / delete | ✅ | `DashboardController` JSON actions |
| Org → student message | ✅ | `notification/send-applicant-message` → `Notification::createFromOrganization()` |
| Student reply | ❌ | Composer read-only until `message` table + API |
| Live delivery / typing / read receipts | ❌ | Disabled in `StatusPolicy` |
| Attachments | ❌ | UI placeholder only |

## Frontend module structure

```
frontend/web/js/messaging/
├── event-bus.js          # Pub/sub (conversation:changed, message:*, unread:updated)
├── status-policy.js      # Capability flags — never fake unsupported states
├── transport.js          # HttpNotificationTransport + NullRealtimeTransport
├── message-renderer.js   # Bubbles, date dividers, scroll (infinite-scroll ready)
├── composer.js           # Drafts (localStorage), validation, Enter-to-send
├── conversation-store.js # Active conversation + outbound queue
├── hub.js                # Main controller (PJAX-safe mount)
└── bootstrap.js          # DOMContentLoaded + pjax:end
```

### Yii2 assets

- `MessagingCoreAsset` — core modules + `messaging-core.css`
- `OrganizationMessagesAsset` — org context panel + counters
- `StudentMessagesHubAsset` — student detail panel hooks

### View contract

Hub root element:

```html
<div class="sp-messages-hub"
     data-messaging-hub
     data-active-id="…"
     data-messaging-config="{…}">
```

Config is rendered from `frontend/views/notification/_messaging_config.php`.

## UX rules (do not mislead users)

`StatusPolicy` defaults:

```javascript
realtime: false
typingIndicators: false
onlinePresence: false
readReceipts: false
deliveryReceipts: false
```

Outbound messages from recruiters show **"Sent via notification"** after API success, or **"Sending…" / "Failed"** during request—not fake "Read" or "Delivered".

Applicant threads do **not** show fabricated auto-reply bubbles.

## Org recruiter composer flow

1. Select applicant thread (`data-conv-source="application"`).
2. Composer enables; drafts saved per `localStorage` key `msg-draft:{conversationId}`.
3. Send → `POST notification/send-applicant-message` with `application_id`, `message`, CSRF.
4. Optimistic bubble (optional) → confirmed or failed based on JSON response.
5. Student receives row in `notification` inbox (existing mechanism).

## Student experience

- Read-only composer.
- Thread renders single inbound notification body.
- Mark read on open (silent POST).
- Unread badge polls every 45s (`pollUnreadMs`).

## Future WebSocket integration

### 1. Backend

Add tables (recommended):

- `conversation` — `id`, `organization_id`, `student_user_id`, `application_id` (nullable)
- `message` — `id`, `conversation_id`, `sender_user_id`, `body`, `created_at`, `read_at`

Bridge: optionally mirror new messages into `notification` for nav badges during migration.

### 2. Transport swap

Implement `PusherRealtimeTransport` (or Echo) in `transport.js`:

```javascript
PusherRealtimeTransport.prototype.subscribeConversation = function (id) {
  channel.bind('message.created', (payload) => {
    this.bus.emit('message:inbound', payload);
  });
};
```

In `_messaging_config.php` set `capabilities.realtime: true` when server supports it.

### 3. Hub patches

In `hub.js` `_bindStore`:

```javascript
this.bus.on('message:inbound', function (msg) {
  self.renderer.appendBubble(self.threadBody, msg);
});
```

Enable `typingIndicators` only when server emits `typing` events.

### 4. Channel naming (example)

- `private-user.{userId}` — badge updates
- `private-conversation.{conversationId}` — thread messages

Authorize subscriptions in Yii2 (Pusher auth endpoint or Socket.IO middleware).

### 5. PJAX

`bootstrap.js` listens for `pjax:end` / `yii:pjax:end` and calls `Hub.mountAll()`. On container replace, call `Hub.destroyAll()` before PJAX navigation if needed.

## API roadmap (optional REST)

`frontend/config/api.php` lists notification routes without a controller. Either implement `api\NotificationController` or remove stale rules. Align mobile clients with the same `message` resources when added.

## Permissions

`OrgTeamMember::ROLE_RECRUITER` includes `'messages'` — gate `actionSendApplicantMessage` with team permission checks when multi-user org accounts are enforced.

## Testing checklist

- [ ] Org: select applicant → compose → send → student sees notification
- [ ] Org: platform inbox thread → mark read / delete
- [ ] Student: open conversation → mark read, no fake typing
- [ ] Draft persists on conversation switch
- [ ] Mobile sidebar opens/closes
- [ ] No fake online dots or read receipts on application threads
