const JOURNEYS_API = 'http://localhost/IAmStillHere/backend/journeys';
let journeysCache = [];
let activeJourneyId = null;
let journeyPrivacyWidget = null;

function jEl(tag, className = '', text = '') { const n = document.createElement(tag); if (className) n.className = className; if (text !== '') n.textContent = text; return n; }
function jDateRange(j) { return [j.start_date, j.end_date].filter(Boolean).join(' - ') || 'No dates'; }

function initJourneysFeature() {
  document.getElementById('journey-create-btn')?.addEventListener('click', () => openJourneyModal());
  document.getElementById('journeyForm')?.addEventListener('submit', saveJourney);
  document.getElementById('journey-invite-search')?.addEventListener('input', loadJourneyInvitees);
  document.getElementById('journey-item-save')?.addEventListener('click', saveJourneyItem);
  if (document.getElementById('journeys-container')) loadJourneys();
}

async function loadJourneys() {
  const box = document.getElementById('journeys-container'); if (!box || !profileUserId) return;
  box.innerHTML = '<div class="text-muted">Loading journeys...</div>';
  try {
    const res = await fetch(`${JOURNEYS_API}/list.php?user_id=${encodeURIComponent(profileUserId)}&limit=25`);
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'Unable to load journeys.');
    journeysCache = data.data.journeys || [];
    renderJourneys(journeysCache);
  } catch (e) { box.innerHTML = `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`; }
}

function renderJourneys(rows) {
  const box = document.getElementById('journeys-container'); box.innerHTML = '';
  const create = document.getElementById('journey-create-btn');
  if (create) create.style.display = currentUser && String(currentUser.id) === String(profileUserId) ? '' : 'none';
  if (!rows.length) { box.appendChild(jEl('div','text-muted','No shared journeys yet.')); return; }
  rows.forEach(j => {
    const col = jEl('div','col-md-6'); const card = jEl('div','card h-100'); const body = jEl('div','card-body');
    body.appendChild(jEl('h6','mb-1',j.title));
    body.appendChild(jEl('div','small text-muted mb-2',`${jDateRange(j)} Ã‚Â· ${j.participant_count} participants Ã‚Â· ${j.status}`));
    body.appendChild(jEl('p','small mb-2',j.description || ''));
    const actions = jEl('div','d-flex flex-wrap gap-2');
    if (j.participant_status === 'pending') { const accept=jEl('button','btn btn-success btn-sm','Accept'); accept.type='button'; accept.addEventListener('click',()=>respondJourney(j.id,'accept')); actions.appendChild(accept); const reject=jEl('button','btn btn-outline-danger btn-sm','Reject'); reject.type='button'; reject.addEventListener('click',()=>respondJourney(j.id,'reject')); actions.appendChild(reject); } else { const view = jEl('button','btn btn-outline-primary btn-sm','Open'); view.type='button'; view.addEventListener('click',()=>openJourney(j.id)); actions.appendChild(view); }
    if (j.can_manage) {
      const edit=jEl('button','btn btn-outline-secondary btn-sm','Edit'); edit.type='button'; edit.addEventListener('click',()=>openJourneyModal(j)); actions.appendChild(edit);
      const inv=jEl('button','btn btn-outline-secondary btn-sm','Invite'); inv.type='button'; inv.addEventListener('click',()=>openJourneyInvite(j.id)); actions.appendChild(inv);
      const del=jEl('button','btn btn-outline-danger btn-sm','Delete'); del.type='button'; del.addEventListener('click',()=>deleteJourney(j.id)); actions.appendChild(del);
    }
    if (j.can_contribute) { const add=jEl('button','btn btn-outline-success btn-sm','Add item'); add.type='button'; add.addEventListener('click',()=>openJourneyItem(j.id)); actions.appendChild(add); }
    body.appendChild(actions); card.appendChild(body); col.appendChild(card); box.appendChild(col);
  });
}

async function openJourneyModal(journey = null) {
  document.getElementById('journeyForm').reset(); document.getElementById('journey-error').textContent='';
  document.getElementById('journey-id').value = journey?.id || '';
  document.getElementById('journey-title').value = journey?.title || '';
  document.getElementById('journey-description').value = journey?.description || '';
  document.getElementById('journey-start').value = journey?.start_date || '';
  document.getElementById('journey-end').value = journey?.end_date || '';
  document.getElementById('journey-status').value = journey?.status || 'draft';
  if (!journeyPrivacyWidget) { journeyPrivacyWidget = privacyComponent('journey', currentUser.id); document.getElementById('journey-privacy').appendChild(journeyPrivacyWidget); }
  journeyPrivacyWidget.querySelector('.privacy-type').value = journey?.privacy_level || 'private';
  if (journey?.id) await journeyPrivacyWidget.loadRule('journey', journey.id);
  bootstrap.Modal.getOrCreateInstance(document.getElementById('journeyModal')).show();
}

async function saveJourney(e) {
  e.preventDefault(); const error = document.getElementById('journey-error'); error.textContent='';
  const id = Number(document.getElementById('journey-id').value || 0); const rule = journeyPrivacyWidget ? journeyPrivacyWidget.getRule() : {visibility_type:'private'};
  const payload = { journey_id:id, title:document.getElementById('journey-title').value.trim(), description:document.getElementById('journey-description').value, start_date:document.getElementById('journey-start').value, end_date:document.getElementById('journey-end').value, status:document.getElementById('journey-status').value, privacy_level:rule.visibility_type, csrf_token:csrfToken };
  try {
    const res = await fetch(`${JOURNEYS_API}/${id ? 'update' : 'create'}.php`, {method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken}, body:JSON.stringify(payload)});
    const data = await res.json(); if (!data.success) throw new Error(data.message || 'Unable to save journey.');
    const savedId = id || data.data.journey.id;
    try { await savePrivacyRule(csrfToken, 'journey', savedId, rule); } catch (pe) { throw new Error('Journey saved, but privacy failed: ' + pe.message); }
    bootstrap.Modal.getInstance(document.getElementById('journeyModal')).hide(); loadJourneys();
  } catch (err) { error.textContent = err.message; }
}

async function openJourney(id) {
  activeJourneyId = id; const wrap=document.getElementById('journey-detail'); const body=wrap.querySelector('.card-body'); wrap.classList.remove('d-none'); body.innerHTML='<div class="text-muted">Loading...</div>';
  const res=await fetch(`${JOURNEYS_API}/view.php?journey_id=${encodeURIComponent(id)}`); const data=await res.json();
  if(!data.success){body.innerHTML=`<div class="alert alert-danger">${escapeHtml(data.message||'Unable to load journey.')}</div>`;return;}
  const j=data.data.journey; body.innerHTML=''; body.appendChild(jEl('h5','mb-1',j.title)); body.appendChild(jEl('div','small text-muted mb-2',jDateRange(j))); body.appendChild(jEl('p','',j.description||''));
  body.appendChild(jEl('div','small mb-3','Participants: '+(data.data.participants||[]).map(p=>p.full_name).join(', ')));
  const list=jEl('div','journey-timeline'); const items=data.data.items||[]; if(!items.length) list.appendChild(jEl('div','text-muted','No approved journey items yet.'));
  items.forEach(item=>{ const row=jEl('div','border-start ps-3 mb-3'); row.appendChild(jEl('div','fw-semibold',item.title)); row.appendChild(jEl('div','small text-muted',`${item.item_date || 'No date'} Ã‚Â· ${item.item_type} Ã‚Â· ${item.contributor_name} Ã‚Â· ${item.status}`)); if(item.description)row.appendChild(jEl('div','small',item.description)); if(j.can_manage && item.status==='pending'){ const ap=jEl('button','btn btn-success btn-sm me-2 mt-1','Approve'); ap.onclick=()=>reviewJourneyItem(item.id,'approve'); const rj=jEl('button','btn btn-outline-danger btn-sm mt-1','Reject'); rj.onclick=()=>reviewJourneyItem(item.id,'reject'); row.append(ap,rj); } list.appendChild(row); }); body.appendChild(list); wrap.scrollIntoView({behavior:'smooth',block:'start'});
}

async function openJourneyInvite(id) { activeJourneyId=id; document.getElementById('journey-invite-search').value=''; document.getElementById('journey-invite-results').innerHTML='<div class="text-muted">Type to search.</div>'; bootstrap.Modal.getOrCreateInstance(document.getElementById('journeyInviteModal')).show(); }
async function loadJourneyInvitees() { const q=document.getElementById('journey-invite-search').value.trim(); const box=document.getElementById('journey-invite-results'); const res=await fetch(`${JOURNEYS_API}/invitees.php?q=${encodeURIComponent(q)}`); const data=await res.json(); box.innerHTML=''; (data.data?.users||[]).forEach(u=>{const b=jEl('button','list-group-item list-group-item-action',u.full_name||u.username); b.onclick=()=>inviteToJourney(u.id); box.appendChild(b);}); if(!box.children.length)box.appendChild(jEl('div','text-muted','No invitees found.')); }
async function inviteToJourney(userId){ const res=await fetch(`${JOURNEYS_API}/invite.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({journey_id:activeJourneyId,user_id:userId})}); const data=await res.json(); showAlert(data.message||'Done',data.success?'success':'danger'); if(data.success)bootstrap.Modal.getInstance(document.getElementById('journeyInviteModal')).hide(); }
async function openJourneyItem(id){ activeJourneyId=id; const sel=document.getElementById('journey-item-select'); sel.innerHTML='<option value="event:0">New event note</option>'; const res=await fetch(`${JOURNEYS_API}/content.php`); const data=await res.json(); (data.data?.items||[]).forEach(item=>{const o=document.createElement('option'); o.value=`${item.item_type}:${item.id}`; o.textContent=`${item.item_type}: ${item.title}`; sel.appendChild(o);}); bootstrap.Modal.getOrCreateInstance(document.getElementById('journeyItemModal')).show(); }
async function saveJourneyItem(){ const error=document.getElementById('journey-item-error'); error.textContent=''; const [type,id]=document.getElementById('journey-item-select').value.split(':'); const payload={journey_id:activeJourneyId,item_type:type,source_id:Number(id),title:document.getElementById('journey-item-title').value.trim(),description:document.getElementById('journey-item-description').value,item_date:document.getElementById('journey-item-date').value,csrf_token:csrfToken}; try{const res=await fetch(`${JOURNEYS_API}/add_item.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify(payload)});const data=await res.json(); if(!data.success)throw new Error(data.message||'Unable to add item.'); bootstrap.Modal.getInstance(document.getElementById('journeyItemModal')).hide(); openJourney(activeJourneyId);}catch(e){error.textContent=e.message;} }
async function reviewJourneyItem(itemId, action){ const res=await fetch(`${JOURNEYS_API}/review_item.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({item_id:itemId,action})}); const data=await res.json(); showAlert(data.message||'Done',data.success?'success':'danger'); if(data.success)openJourney(activeJourneyId); }
async function respondJourney(id, action){ const res=await fetch(`${JOURNEYS_API}/respond.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({journey_id:id,action})}); const data=await res.json(); showAlert(data.message||'Done',data.success?'success':'danger'); if(data.success)loadJourneys(); }
async function deleteJourney(id){ if(!confirm('Delete this journey?'))return; const res=await fetch(`${JOURNEYS_API}/delete.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrfToken},body:JSON.stringify({journey_id:id})}); const data=await res.json(); showAlert(data.message||'Done',data.success?'success':'danger'); if(data.success)loadJourneys(); }

document.addEventListener('DOMContentLoaded',()=>setTimeout(initJourneysFeature,400));

