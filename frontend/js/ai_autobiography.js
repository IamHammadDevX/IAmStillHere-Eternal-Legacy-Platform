const AI_AUTOBIO_API = '/backend/ai/autobiography';
let aiAutobioState = {autobiography: null, sections: [], timeline: []};
let aiAutobioBusy = false; let aiAutobioSessionReady = false;
let aiAutobioTimelinePage = 1;
const AI_AUTOBIO_TIMELINE_PAGE_SIZE = 10;

function initAiAutobiography() {
    const root = document.getElementById('autobiography-tab');
    if (!root || root.dataset.ready === '1') return;
    root.dataset.ready = '1'; window.addEventListener('profile-session-ready', () => { aiAutobioSessionReady = true; renderAutobiography(); });
    document.getElementById('autobio-generate')?.addEventListener('click', () => generateAutobiography(false));
    document.getElementById('autobio-save')?.addEventListener('click', saveAutobiography);
    document.getElementById('autobio-publish')?.addEventListener('click', toggleAutobiographyPublish);
    loadAutobiography();
}

function autobioOwnerId() {
    return Number(typeof profileUserId !== 'undefined' ? profileUserId : window.profileUserId);
}

function autobioIsOwner() {
    return currentUser && Number(currentUser.id) === autobioOwnerId();
}

function autobioEscape(value) {
    const div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function autobioSetStatus(message, type = 'muted') {
    const box = document.getElementById('autobio-status');
    if (!box) return;
    box.className = `small mb-3 text-${type}`;
    box.textContent = message || '';
}

async function loadAutobiography() {
    const owner = autobioOwnerId();
    if (!owner) return;
    try {
        const response = await fetch(`${AI_AUTOBIO_API}/view.php?owner_id=${encodeURIComponent(owner)}`, {cache: 'no-store'});
        const data = await autobioJson(response);
        if (!data.success) throw new Error(data.message || 'Unable to load autobiography.');
        aiAutobioState = data.data || {autobiography: null, sections: [], timeline: []};
        renderAutobiography();
    } catch (error) {
        autobioSetStatus(error.message || 'Unable to load autobiography.', 'danger');
        renderAutobiography();
    }
}

async function autobioJson(response) {
    const text = await response.text();
    try { return JSON.parse(text); }
    catch (error) {
        const clean = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        throw new Error(clean ? clean.substring(0, 180) : 'Server returned an invalid response.');
    }
}

async function generateAutobiography(overwriteManual) {
    if (aiAutobioBusy || !autobioIsOwner()) return;
    const manual = document.querySelectorAll('.autobio-section-text[data-manual="1"]').length > 0;
    if (manual && !overwriteManual) { if (!confirm('Regenerating will replace your manual edits. Do you want to continue?')) return; overwriteManual = true; }
    aiAutobioBusy = true;
    autobioSetStatus('Generating your autobiography... please wait, this can take 20-60 seconds.', 'primary');
    setAutobioButtons(true);
    try {
        const response = await fetch(`${AI_AUTOBIO_API}/generate.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
            body: JSON.stringify({csrf_token: csrfToken, overwrite_manual: overwriteManual})
        });
        const data = await autobioJson(response);
        if (!data.success) throw new Error(data.message || 'Generation failed.');
        aiAutobioState = data.data;
        renderAutobiography();
        autobioSetStatus('Autobiography generated. Review, edit, then save/publish.', 'success');
    } catch (error) {
        autobioSetStatus(error.message || 'Generation failed.', 'danger');
    } finally {
        aiAutobioBusy = false;
        setAutobioButtons(false);
    }
}

async function regenerateAutobiographySection(sectionKey) {
    if (aiAutobioBusy || !autobioIsOwner()) return;
    const textarea = document.querySelector(`.autobio-section-text[data-section-key="${CSS.escape(sectionKey)}"]`);
    if (textarea?.dataset.manual === '1' && !confirm('This section has manual edits. Regenerate and replace it?')) return;
    aiAutobioBusy = true;
    autobioSetStatus('Regenerating section...', 'muted');
    setAutobioButtons(true);
    try {
        const response = await fetch(`${AI_AUTOBIO_API}/regenerate_section.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
            body: JSON.stringify({csrf_token: csrfToken, section_key: sectionKey})
        });
        const data = await autobioJson(response);
        if (!data.success) throw new Error(data.message || 'Section regeneration failed.');
        aiAutobioState = data.data;
        renderAutobiography();
        autobioSetStatus('Section regenerated.', 'success');
    } catch (error) {
        autobioSetStatus(error.message || 'Section regeneration failed.', 'danger');
    } finally {
        aiAutobioBusy = false;
        setAutobioButtons(false);
    }
}

async function saveAutobiography() {
    if (aiAutobioBusy || !autobioIsOwner()) return;
    const sections = Array.from(document.querySelectorAll('.autobio-section-text')).map(item => ({
        section_key: item.dataset.sectionKey,
        content: item.value
    }));
    aiAutobioBusy = true;
    autobioSetStatus('Saving draft...', 'muted');
    setAutobioButtons(true);
    try {
        const response = await fetch(`${AI_AUTOBIO_API}/save.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
            body: JSON.stringify({csrf_token: csrfToken, title: document.getElementById('autobio-title')?.value || 'My Life Story', sections})
        });
        const data = await autobioJson(response);
        if (!data.success) throw new Error(data.message || 'Save failed.');
        aiAutobioState = data.data;
        renderAutobiography();
        autobioSetStatus('Draft saved.', 'success');
    } catch (error) {
        autobioSetStatus(error.message || 'Save failed.', 'danger');
    } finally {
        aiAutobioBusy = false;
        setAutobioButtons(false);
    }
}

async function toggleAutobiographyPublish() {
    if (aiAutobioBusy || !autobioIsOwner()) return;
    const status = aiAutobioState.autobiography?.status;
    const publish = status !== 'published';
    aiAutobioBusy = true;
    autobioSetStatus(publish ? 'Publishing...' : 'Unpublishing...', 'muted');
    setAutobioButtons(true);
    try {
        const response = await fetch(`${AI_AUTOBIO_API}/publish.php`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken},
            body: JSON.stringify({csrf_token: csrfToken, publish})
        });
        const data = await autobioJson(response);
        if (!data.success) throw new Error(data.message || 'Publish failed.');
        aiAutobioState = data.data;
        renderAutobiography();
        autobioSetStatus(publish ? 'Published.' : 'Unpublished.', 'success');
    } catch (error) {
        autobioSetStatus(error.message || 'Publish failed.', 'danger');
    } finally {
        aiAutobioBusy = false;
        setAutobioButtons(false);
    }
}

function renderAutobiography() {
    const auto = aiAutobioState.autobiography;
    const title = document.getElementById('autobio-title');
    if (title && auto?.title) title.value = auto.title;
    const publish = document.getElementById('autobio-publish');
    const owner = autobioIsOwner();
    document.getElementById('autobio-generate')?.classList.toggle('d-none', !owner);
    document.getElementById('autobio-save')?.classList.toggle('d-none', !owner);
    publish?.classList.toggle('d-none', !owner);
    if (publish) publish.textContent = auto?.status === 'published' ? 'Unpublish' : 'Publish';
    renderAutobiographySections(owner);
    renderAutobiographyTimeline();
}

function renderAutobiographySections(owner) {
    const box = document.getElementById('autobio-sections');
    const sections = Array.isArray(aiAutobioState.sections) ? aiAutobioState.sections : [];
    if (!box) return;
    if (!sections.length) {
        box.innerHTML = '<div class="text-muted text-center py-4">No autobiography sections yet. Build AI knowledge, then click Generate.</div>';
        return;
    }
    box.innerHTML = sections.map(section => {
        const sources = Array.isArray(section.sources) ? section.sources : [];
        const sourceText = sources.slice(0, 4).map(ref => `${ref.type}: ${ref.title}`).join(', ');
        const theme = autobioSectionTheme(section.section_key);
        if (owner) {
            return `<div class="card mb-3 autobio-section-card ${theme}">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-2 mb-2">
                        <h6 class="mb-0">${autobioEscape(section.section_title)}</h6>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="regenerateAutobiographySection('${autobioEscape(section.section_key)}')">Regenerate</button>
                    </div>
                    <textarea class="form-control autobio-section-text" rows="6" data-section-key="${autobioEscape(section.section_key)}" data-manual="${section.manually_edited ? '1' : '0'}">${autobioEscape(section.content)}</textarea>
                    ${sourceText ? `<div class="small text-muted mt-2">Sources: ${autobioEscape(sourceText)} <span class="ai-inline-disclaimer">AI-generated · may be inaccurate</span></div>` : ''}
                </div>
            </div>`;
        }
        return `<div class="card mb-3 autobio-section-card ${theme}"><div class="card-body">
            <h6>${autobioEscape(section.section_title)}</h6>
            <p class="mb-0">${autobioEscape(section.content).replace(/\n/g, '<br>')}</p>
            ${sourceText ? `<div class="small text-muted mt-2">Sources: ${autobioEscape(sourceText)} <span class="ai-inline-disclaimer">AI-generated · may be inaccurate</span></div>` : ''}
        </div></div>`;
    }).join('');
    box.querySelectorAll('.autobio-section-text').forEach(textarea => {
        textarea.addEventListener('input', () => { textarea.dataset.manual = '1'; });
    });
}

function autobioSectionTheme(sectionKey) {
    const key = String(sectionKey || '').toLowerCase();
    if (key.includes('career')) return 'autobio-theme-career';
    if (key.includes('achievement')) return 'autobio-theme-achievements';
    if (key.includes('wisdom') || key.includes('lesson')) return 'autobio-theme-wisdom';
    if (key.includes('legacy')) return 'autobio-theme-legacy';
    if (key.includes('journey') || key.includes('experience')) return 'autobio-theme-journeys';
    if (key.includes('family') || key.includes('relationship')) return 'autobio-theme-family';
    if (key.includes('childhood')) return 'autobio-theme-childhood';
    if (key.includes('early')) return 'autobio-theme-early';
    return 'autobio-theme-early';
}

function renderAutobiographyTimeline() {
    const box = document.getElementById('autobio-timeline');
    const pager = document.getElementById('autobio-timeline-pagination');
    const items = Array.isArray(aiAutobioState.timeline) ? aiAutobioState.timeline : [];
    if (!box) return;
    if (!items.length) {
        box.innerHTML = `<div class="autobio-timeline-empty">
            <i class="bi bi-calendar2-heart" aria-hidden="true"></i>
            <div><strong>Your timeline will appear here.</strong><span>Add dated memories, milestones, posts, or journeys to build your pictograph.</span></div>
        </div>`;
        if (pager) pager.innerHTML = '';
        return;
    }
    const totalPages = Math.max(1, Math.ceil(items.length / AI_AUTOBIO_TIMELINE_PAGE_SIZE));
    aiAutobioTimelinePage = Math.min(Math.max(1, aiAutobioTimelinePage), totalPages);
    const start = (aiAutobioTimelinePage - 1) * AI_AUTOBIO_TIMELINE_PAGE_SIZE;
    const pageItems = items.slice(start, start + AI_AUTOBIO_TIMELINE_PAGE_SIZE);
    box.innerHTML = pageItems.map(item => {
        const thumb = item.thumbnail ? `<img src="${autobioEscape(item.thumbnail)}" alt="" class="autobio-timeline-thumb">` : '<div class="autobio-timeline-dot"><i class="bi bi-stars"></i></div>';
        const date = item.item_date ? new Date(item.item_date).toLocaleDateString() : 'Unknown date';
        return `<div class="autobio-timeline-item">
            ${thumb}
            <div class="autobio-timeline-body">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <strong>${autobioEscape(item.title)}</strong>
                    <span class="badge bg-secondary">${autobioEscape(item.type)}</span>
                    <span class="small text-muted">${autobioEscape(date)}</span>
                </div>
                ${item.description ? `<div class="small text-muted">${autobioEscape(item.description)}</div>` : ''}
            </div>
        </div>`;
    }).join('');
    if (pager) {
        pager.innerHTML = totalPages > 1 ? `<button type="button" class="btn btn-outline-secondary btn-sm" data-autobio-page="${aiAutobioTimelinePage - 1}" ${aiAutobioTimelinePage === 1 ? 'disabled' : ''}>? Previous</button>
            <span class="small text-muted">Showing ${start + 1} : ${Math.min(start + AI_AUTOBIO_TIMELINE_PAGE_SIZE, items.length)} of ${items.length}</span>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-autobio-page="${aiAutobioTimelinePage + 1}" ${aiAutobioTimelinePage === totalPages ? 'disabled' : ''}>Next ?</button>` : `<span class="small text-muted">${items.length} timeline item${items.length === 1 ? '' : 's'}</span>`;
        pager.querySelectorAll('[data-autobio-page]').forEach(button => button.addEventListener('click', () => {
            aiAutobioTimelinePage = Number(button.dataset.autobioPage) || 1;
            renderAutobiographyTimeline();
            document.getElementById('autobio-timeline')?.scrollIntoView({behavior: 'smooth', block: 'start'});
        }));
    }
}

function setAutobioButtons(disabled) {
    ['autobio-generate', 'autobio-save', 'autobio-publish'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = disabled;
    });
    const generate = document.getElementById('autobio-generate');
    if (generate) {
        generate.innerHTML = disabled
            ? '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Generating...'
            : 'Generate';
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAiAutobiography);
else initAiAutobiography();
window.initAiAutobiography = initAiAutobiography;


