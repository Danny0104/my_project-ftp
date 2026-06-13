/**
 * Conversation state — ready for WebSocket patch events.
 * @module Messaging.ConversationStore
 */
(function (global) {
    'use strict';

    function ConversationStore(bus) {
        this.bus = bus;
        this.conversations = new Map();
        this.activeId = null;
        this.outboundQueue = [];
    }

    ConversationStore.prototype.registerFromDom = function (item) {
        var id = item.getAttribute('data-conversation-id');
        if (!id) return;
        var ctx = {};
        try {
            ctx = JSON.parse(item.getAttribute('data-context-json') || '{}');
        } catch (e) { /* */ }
        var source = item.getAttribute('data-conv-source') || 'notification';
        var chatId = parseInt(item.getAttribute('data-chat-conversation-id') || ctx.conversationId || (source === 'chat' ? id : 0), 10);
        this.conversations.set(id, {
            id: id,
            source: source,
            chatConversationId: chatId > 0 ? chatId : null,
            chatEnabled: source === 'chat' || source === 'application' || !!ctx.chatEnabled,
            filterTags: item.getAttribute('data-conv-filter-tags') || '',
            senderType: item.getAttribute('data-sender-type') || '',
            title: item.querySelector('.sp-conv-title')?.textContent || '',
            preview: item.querySelector('.sp-conv-preview')?.textContent || '',
            time: item.querySelector('.sp-conv-time')?.textContent || '',
            actionUrl: item.getAttribute('data-action-url') || '',
            actionText: item.getAttribute('data-action-text') || '',
            isUnread: item.classList.contains('unread'),
            ctx: ctx,
            element: item,
        });
    };

    ConversationStore.prototype.setActive = function (id) {
        this.activeId = id;
        this.bus.emit('conversation:changed', this.getActive());
    };

    ConversationStore.prototype.getActive = function () {
        return this.activeId ? this.conversations.get(this.activeId) : null;
    };

    ConversationStore.prototype.addOutboundPreview = function (msg) {
        this.outboundQueue.push(msg);
        this.bus.emit('message:outbound-preview', msg);
    };

    ConversationStore.prototype.confirmOutbound = function (tempId, serverMsg) {
        this.bus.emit('message:outbound-confirmed', { tempId: tempId, server: serverMsg });
    };

    global.Messaging = global.Messaging || {};
    global.Messaging.ConversationStore = ConversationStore;
})(typeof window !== 'undefined' ? window : this);
