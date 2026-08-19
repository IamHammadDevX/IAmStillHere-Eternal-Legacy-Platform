const AI_AVATAR_API = '/backend/ai';
let aiAvatarConversationId = null;
let aiAvatarBusy = false;
let aiAvatarSourcesCache = [];
let aiAvatarSourceFilter = 'all';

function initAiAvatar() {
    const form = document.getElementById('ai-avatar-form');
    if (!form || form.dataset.aiAvatarReady === '1') return;
    form.dataset.aiAvatarReady = '1';
    form.addEventListener('submit', sendAiAvatarMessage);
    document.getElementById('ai-avatar-delete')?.addEventListener('click', deleteAiAvatarConversation);
    document.getElementById('ai-avatar-build')?.addEventListener('click', buildAiAvatarKnowledge);
    document.getElementById('ai-avatar-source-search')?.addEventListener('input', renderAiAvatarSources);
    document.getElementById('ai-avatar-select-visible')?.addEventListener('click', selectVisibleAiAvatarSources);
    document.querySelectorAll('#ai-avatar-source-filters [data-filter]').forEach(btn => btn.addEventListener('click', () => setAiAvatarSourceFilter(btn)));
    syncAiAvatarProfilePhoto();
    loadAiAvatarSources();
    loadAiAvatarConversations();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAiAvatar);
} else {
    initAiAvatar();
}

function aiAvatarEscape(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function aiAvatarProfilePhotoUrl() {
    const profilePhoto = document.getElementById('profile-image');
    return profilePhoto?.currentSrc || profilePhoto?.src || '/data/uploads/photos/default-profile.png';
}

function syncAiAvatarProfilePhoto() {
    const avatar = document.getElementById('ai-avatar-photo');
    const profilePhoto = document.getElementById('profile-image');
    if (!avatar) return;
    avatar.src = aiAvatarProfilePhotoUrl();
    avatar.onerror = () => { avatar.src = '/data/uploads/photos/default-profile.png'; };
    profilePhoto?.addEventListener('load', () => { avatar.src = aiAvatarProfilePhotoUrl(); });
}


async function loadAiAvatarSources() {
    const box = document.getElementById('ai-avatar-sources');
    const summary = document.getElementById('ai-avatar-source-summary');
    if (!box) return;
    try {
        const response = await fetch(`${AI_AVATAR_API}/sources.php`, {cache: 'no-store'});
        const data = await response.json();
        if (!data || data.success !== true) throw new Error((data && data.message) || 'Unable to load sources.');
        aiAvatarSourcesCache = Array.isArray(data.data && data.data.sources) ? data.data.sources : [];
        renderAiAvatarSources();
    } catch (error) {
        box.innerHTML = `<div class="text-danger">${aiAvatarEscape(error && error.message ? error.message : 'Unable to load sources.')}</div>`;
        if (summary) summary.textContent = '';
    }
}
function setAiAvatarSourceFilter(button) {
    aiAvatarSourceFilter = button.dataset.filter || 'all';
    document.querySelectorAll('#ai-avatar-source-filters [data-filter]').forEach(btn => btn.classList.toggle('active', btn === button));
    renderAiAvatarSources();
}

function filteredAiAvatarSources() {
    const query = (document.getElementById('ai-avatar-source-search')?.value || '').trim().toLowerCase();
    const cache = Array.isArray(aiAvatarSourcesCache) ? aiAvatarSourcesCache : [];
    return cache.filter(source => {
        const typeOk = aiAvatarSourceFilter === 'all' || source.resource_type === aiAvatarSourceFilter;
        const text = `${source.resource_type} ${source.title} ${source.ingestion_status || ''}`.toLowerCase();
        return typeOk && (!query || text.includes(query));
    });
}

function renderAiAvatarSources() {
    const box = document.getElementById('ai-avatar-sources');
    const summary = document.getElementById('ai-avatar-source-summary');
    if (!box) return;
    const cache = Array.isArray(aiAvatarSourcesCache) ? aiAvatarSourcesCache : [];
    const shown = Array.isArray(filteredAiAvatarSources()) ? filteredAiAvatarSources() : [];
    const totalCount = cache.length || 0;
    const shownCount = shown.length || 0;
    const indexedCount = cache.reduce((count, source) => count + (source && source.ingestion_status === 'indexed' ? 1 : 0), 0);
    if (summary) summary.textContent = `${totalCount} total, ${indexedCount} indexed, ${shownCount} shown`;
    if (totalCount === 0) {
        box.innerHTML = '<div class="text-muted p-2">Create a post, memory, or milestone first.</div>';
        return;
    }
    if (shownCount === 0) {
        box.innerHTML = '<div class="text-muted p-2">No sources match this filter.</div>';
        return;
    }
    box.innerHTML = shown.map(source => {
        source = source || {};
        const type = source.resource_type || 'source';
        const id = Number(source.resource_id || 0);
        const title = source.title || 'Untitled';
        const key = `${type}:${id}`;
        const status = source.ingestion_status === 'indexed' ? 'indexed' : 'not enabled';
        const checked = source.ingestion_status === 'indexed' ? 'checked' : '';
        return `<label class="ai-source-row" data-source-type="${aiAvatarEscape(type)}">
            <input class="form-check-input ai-avatar-source-check" type="checkbox" ${checked} value="${aiAvatarEscape(key)}" data-type="${aiAvatarEscape(type)}" data-id="${id}">
            <span class="badge bg-secondary ai-source-type">${aiAvatarEscape(type)}</span>
            <span class="ai-source-title">${aiAvatarEscape(title)}</span>
            <span class="ai-source-status ${status === 'indexed' ? 'text-success' : 'text-muted'}">${aiAvatarEscape(status)}</span>
        </label>`;
    }).join('');
}
function selectVisibleAiAvatarSources() {
    document.querySelectorAll('#ai-avatar-sources .ai-avatar-source-check').forEach(input => { input.checked = true; });
}
async function buildAiAvatarKnowledge() {
    const status = document.getElementById('ai-avatar-status');
    const button = document.getElementById('ai-avatar-build');
    const checked = Array.from(document.querySelectorAll('.ai-avatar-source-check:checked'));
    if (!checked.length) {
        if (status) status.textContent = 'Select at least one source first.';
        return;
    }
    const sources = checked.map(item => ({resource_type: item.dataset.type, resource_id: Number(item.dataset.id)}));
    if (button) button.disabled = true;
    if (status) status.textContent = 'Building AI knowledge...';
    try {
        const response = await fetch(`${AI_AVATAR_API}/build_avatar.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
            body: JSON.stringify({sources, csrf_token: csrfToken})
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unable to build AI knowledge.');
        const worker = data?.data?.worker || {};
        if (status) status.textContent = `Knowledge ready. Processed ${worker.processed || 0}, failed ${worker.failed || 0}.`;
        await loadAiAvatarSources();
    } catch (error) {
        if (status) status.textContent = error.message;
    } finally {
        if (button) button.disabled = false;
    }
}
async function loadAiAvatarConversations() {
    if (!window.profileUserId && typeof profileUserId === 'undefined') return;
    const owner = typeof profileUserId !== 'undefined' ? profileUserId : window.profileUserId;
    const status = document.getElementById('ai-avatar-status');
    try {
        const response = await fetch(`${AI_AVATAR_API}/conversations.php?owner_id=${encodeURIComponent(owner)}&limit=1`);
        const data = await response.json();
        if (!data.success) {
            if (status) status.textContent = data.message || 'AI Avatar unavailable.';
            return;
        }
        const conversation = (data.data.conversations || [])[0];
        if (conversation) {
            aiAvatarConversationId = conversation.id;
            await loadAiAvatarMessages(aiAvatarConversationId);
        } else {
            renderAiAvatarEmpty();
        }
    } catch (error) {
        if (status) status.textContent = 'Unable to load AI Avatar.';
    }
}

async function loadAiAvatarMessages(conversationId) {
    const response = await fetch(`${AI_AVATAR_API}/messages.php?conversation_id=${encodeURIComponent(conversationId)}`);
    const data = await response.json();
    if (!data.success) throw new Error(data.message || 'Unable to load messages.');
    renderAiAvatarMessages(data.data.messages || []);
}

function renderAiAvatarEmpty() {
    const box = document.getElementById('ai-avatar-messages');
    if (!box) return;
    box.innerHTML = '<div class="text-muted text-center py-4">Ask about memories, milestones, posts, or shared journeys that were approved for AI.</div>';
}

function renderAiAvatarMessages(messages) {
    const box = document.getElementById('ai-avatar-messages');
    if (!box) return;
    if (!messages.length) return renderAiAvatarEmpty();
    box.innerHTML = messages.map(message => aiAvatarMessageMarkup(
        message.role === 'user' ? 'user' : 'assistant',
        message.message_text,
        message.sources || []
    )).join('');
    box.scrollTop = box.scrollHeight;
}

function aiAvatarMessageMarkup(role, text, sources = []) {
    const mine = role === 'user';
    const avatar = mine
        ? `<img src="${aiAvatarEscape(aiAvatarProfilePhotoUrl())}" alt="You" onerror="this.src='/data/uploads/photos/default-profile.png'">`
        : '<i class="bi bi-robot" aria-hidden="true"></i>';
    const references = mine ? '' : renderAiAvatarReferences(sources);
    return `<div class="ai-avatar-message ai-avatar-message-${mine ? 'user' : 'assistant'}">
        <div class="ai-avatar-chat-avatar ${mine ? 'ai-avatar-chat-avatar-user' : 'ai-avatar-chat-avatar-ai'}">${avatar}</div>
        <div class="ai-avatar-bubble ${mine ? 'ai-avatar-user' : 'ai-avatar-answer'}">
            <div>${aiAvatarEscape(text)}</div>${references}
        </div>
    </div>`;
}

function appendAiAvatarMessage(role, text, sources = []) {
    const box = document.getElementById('ai-avatar-messages');
    if (!box) return;
    if (box.textContent.includes('Ask about memories')) box.innerHTML = '';
    const message = document.createElement('div');
    message.innerHTML = aiAvatarMessageMarkup(role, text, sources);
    box.appendChild(message.firstElementChild);
    box.scrollTop = box.scrollHeight;
}

function renderAiAvatarReferences(sources) {
    sources = Array.isArray(sources) ? sources : [];
    if (!sources.length) return '';
    return `<div class="small text-muted mt-2">Sources: ${sources.map(source => aiAvatarEscape(`${source.type}: ${source.title}`)).join(', ')} <span class="ai-inline-disclaimer">AI-generated &middot; may be inaccurate</span></div>`;
}

async function sendAiAvatarMessage(event) {
    event.preventDefault();
    if (aiAvatarBusy) return;
    const input = document.getElementById('ai-avatar-question');
    const status = document.getElementById('ai-avatar-status');
    const send = document.getElementById('ai-avatar-send');
    const owner = typeof profileUserId !== 'undefined' ? profileUserId : window.profileUserId;
    const question = (input?.value || '').trim();
    if (!question) return;
    aiAvatarBusy = true;
    if (send) send.disabled = true;
    if (status) status.textContent = 'Thinking...';
    appendAiAvatarMessage('user', question);
    input.value = '';
    try {
        const response = await fetch(`${AI_AVATAR_API}/chat.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
            body: JSON.stringify({owner_id: Number(owner), conversation_id: aiAvatarConversationId, question, csrf_token: csrfToken})
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'AI Avatar unavailable.');
        aiAvatarConversationId = data.data.conversation_id;
        appendAiAvatarMessage('assistant', data.data.answer, data.data.sources || []);
        if (status) status.textContent = '';
    } catch (error) {
        if (status) status.textContent = error.message;
    } finally {
        aiAvatarBusy = false;
        if (send) send.disabled = false;
    }
}

async function deleteAiAvatarConversation() {
    if (!csrfToken) return;
    const status = document.getElementById('ai-avatar-status');
    try {
        const response = await fetch(`${AI_AVATAR_API}/delete_conversation.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
            body: JSON.stringify({conversation_id: aiAvatarConversationId, owner_id: Number(typeof profileUserId !== 'undefined' ? profileUserId : window.profileUserId), csrf_token: csrfToken})
        });
        const data = await response.json();
        if (!data.success) throw new Error(data.message || 'Unable to delete conversation.');
        aiAvatarConversationId = null;
        renderAiAvatarEmpty();
        if (status) status.textContent = 'Conversation deleted.';
    } catch (error) {
        if (status) status.textContent = error.message;
    }
}

function forceAiAvatarSourceLoad() {
    const box = document.getElementById('ai-avatar-sources');
    if (box && box.textContent.trim().toLowerCase().includes('loading sources')) {
        loadAiAvatarSources();
    }
}

window.initAiAvatar = initAiAvatar;
window.loadAiAvatarSources = loadAiAvatarSources;
window.addEventListener('load', () => setTimeout(forceAiAvatarSourceLoad, 250));
setTimeout(forceAiAvatarSourceLoad, 800);
