document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('tlc-chat-toggle');
    const close = document.getElementById('tlc-chat-close');
    const windowElement = document.getElementById('tlc-chat-window');
    const form = document.getElementById('tlc-chat-form');
    const input = document.getElementById('tlc-chat-input');
    const messages = document.getElementById('tlc-chat-messages');
    const pollInterval = 5000;

    let conversationId = null;
    let lastMessageId = 0;
    let pollingTimer = null;
    let pollingAttempts = 0;
    let isLoadingHistory = false;
    let isPolling = false;

    if (!toggle || !windowElement || !form || !input || !messages) {
        return;
    }

    const visitorId = getVisitorId();

    updateAdminStatus();

    toggle.addEventListener('click', async () => {
        windowElement.hidden = !windowElement.hidden;
        toggle.setAttribute('aria-expanded', String(!windowElement.hidden));

        if (windowElement.hidden) {
            stopPolling();
            return;
        }

        input.focus();
        await loadHistory();
        startPolling();
    });

    close.addEventListener('click', () => {
        windowElement.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.focus();
        stopPolling();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();

        const message = input.value.trim();

        if (!message || isLoadingHistory) {
            return;
        }

        const optimisticMessage = addMessage(message, 'user');
        input.value = '';

        try {
            const result = await sendMessage(message);

            if (result.conversation_id) {
                conversationId = Number(result.conversation_id);
            }

            if (result.message_id) {
                lastMessageId = Math.max(
                    lastMessageId,
                    Number(result.message_id),
                );
            }

            startPolling();
        } catch (error) {
            console.error('TLC send message error:', error);
            optimisticMessage.remove();
            addMessage(
                TLC_DATA.errorMessage ||
                    'Message could not be sent. Please try again.',
                'system',
            );
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            stopPolling();
        } else if (!windowElement.hidden) {
            startPolling();
        }
    });

    function getVisitorId() {
        const storageKey = 'tlc_visitor_id';
        let id = localStorage.getItem(storageKey);

        if (!id) {
            if (
                window.crypto &&
                typeof window.crypto.randomUUID === 'function'
            ) {
                id = window.crypto.randomUUID();
            } else {
                id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(
                    /[xy]/g,
                    character => {
                        const random = Math.floor(Math.random() * 16);
                        const value =
                            character === 'x' ? random : (random & 0x3) | 0x8;

                        return value.toString(16);
                    },
                );
            }

            localStorage.setItem(storageKey, id);
        }

        return id;
    }

    function updateAdminStatus() {
        const status = TLC_DATA.status || 'offline';
        const statusText = document.getElementById('tlc-chat-status-text');
        const statusElement = document.getElementById('tlc-chat-status');

        if (!statusText || !statusElement) {
            return;
        }

        const online = status === 'online';
        statusText.textContent = online ? 'Online' : 'Offline';
        statusElement.classList.toggle('tlc-status-online', online);
        statusElement.classList.toggle('tlc-status-offline', !online);
    }

    async function sendMessage(message) {
        const response = await fetch(`${TLC_DATA.restUrl}messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': TLC_DATA.nonce,
            },
            body: JSON.stringify({
                visitor_id: visitorId,
                message,
                page_url: window.location.href,
            }),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return response.json();
    }

    async function loadHistory() {
        if (isLoadingHistory) {
            return;
        }

        isLoadingHistory = true;

        try {
            if (!conversationId) {
                const url = buildRestUrl('conversations/by-visitor', {
                    visitor_id: visitorId,
                    _: Date.now(),
                });
                const response = await fetch(url, {
                    cache: 'no-store',
                    headers: {
                        'Cache-Control': 'no-cache',
                        Pragma: 'no-cache',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const result = await response.json();
                conversationId =
                    result && result.conversation_id
                        ? Number(result.conversation_id)
                        : null;
            }

            if (!conversationId) {
                lastMessageId = 0;
                return;
            }

            const result = await requestMessages(0);

            if (!result || !Array.isArray(result.messages)) {
                return;
            }

            messages.innerHTML = '';
            lastMessageId = 0;
            appendMessages(result.messages, true);
        } catch (error) {
            console.error('TLC history load error:', error);
        } finally {
            isLoadingHistory = false;
        }
    }

    async function requestMessages(afterId) {
        const url = buildRestUrl(`conversations/${conversationId}/messages`, {
            visitor_id: visitorId,
            after_id: afterId,
            _: Date.now(),
        });

        const response = await fetch(url, {
            cache: 'no-store',
            headers: {
                'Cache-Control': 'no-cache',
                Pragma: 'no-cache',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return response.json();
    }

    function buildRestUrl(endpoint, parameters = {}) {
        const url = new URL(
            `${TLC_DATA.restUrl}${endpoint}`,
            window.location.origin,
        );

        Object.entries(parameters).forEach(([key, value]) => {
            url.searchParams.set(key, String(value));
        });

        return url.toString();
    }

    function startPolling() {
        if (
            !conversationId ||
            windowElement.hidden ||
            document.hidden ||
            pollingTimer ||
            isPolling
        ) {
            return;
        }

        schedulePoll(0);
    }

    function stopPolling() {
        if (pollingTimer) {
            clearTimeout(pollingTimer);
            pollingTimer = null;
        }

        pollingAttempts = 0;
    }

    function schedulePoll(delay) {
        if (
            !conversationId ||
            windowElement.hidden ||
            document.hidden ||
            pollingTimer
        ) {
            return;
        }

        pollingTimer = window.setTimeout(async () => {
            pollingTimer = null;
            await pollMessages();
        }, delay);
    }

    async function pollMessages() {
        if (
            isPolling ||
            !conversationId ||
            windowElement.hidden ||
            document.hidden
        ) {
            return;
        }

        isPolling = true;

        try {
            const result = await requestMessages(lastMessageId);

            if (result && Array.isArray(result.messages)) {
                appendMessages(result.messages, false);
            }

            pollingAttempts = 0;
            schedulePoll(pollInterval);
        } catch (error) {
            console.error('TLC polling error:', error);
            pollingAttempts += 1;
            schedulePoll(Math.min(pollInterval * 2 ** pollingAttempts, 30000));
        } finally {
            isPolling = false;
        }
    }

    function appendMessages(items, includeVisitorMessages) {
        items.forEach(item => {
            const id = Number(item.id);

            if (!id || id <= lastMessageId) {
                return;
            }

            lastMessageId = id;

            if (
                includeVisitorMessages ||
                item.sender === 'admin' ||
                item.sender === 'system'
            ) {
                addMessage(item.message, item.sender);
            }
        });
    }

    function addMessage(message, sender) {
        const element = document.createElement('div');
        element.className = `tlc-message tlc-message-${sender}`;
        element.textContent = message;
        messages.appendChild(element);
        messages.scrollTop = messages.scrollHeight;

        return element;
    }
});
