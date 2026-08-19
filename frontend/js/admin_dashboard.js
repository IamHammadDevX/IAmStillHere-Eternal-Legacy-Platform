const ADMIN_API='/backend/admin';
let adminCsrf=null;
let adminOverview=null;

function activateAdminPanel(selector, updateHash = false) {
    const target = document.querySelector(selector);
    if (!target) return;
    document.querySelectorAll('.admin-page .admin-panel').forEach((panel) => {
        panel.hidden = panel !== target;
    });
    document.querySelectorAll('.admin-page .admin-tab-button').forEach((button) => {
        const selected = button.dataset.adminTarget === selector;
        button.classList.toggle('active', selected);
        button.setAttribute('aria-selected', selected ? 'true' : 'false');
    });
    if (updateHash) history.replaceState(null, '', selector);
}

document.addEventListener('DOMContentLoaded', () => {
    const requested = location.hash || '#overview-tab';
    const initial = document.querySelector(requested + '.admin-panel') ? requested : '#overview-tab';
    activateAdminPanel(initial);
    document.querySelectorAll('.admin-page .admin-tab-button').forEach((button) => {
        button.addEventListener('click', () => activateAdminPanel(button.dataset.adminTarget, true));
    });
});
function ael(id){return document.getElementById(id);}
function esc(v){return String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));}
function bytes(n){n=Number(n||0);if(n<1024)return n+' B';if(n<1048576)return (n/1024).toFixed(1)+' KB';if(n<1073741824)return (n/1048576).toFixed(1)+' MB';return (n/1073741824).toFixed(1)+' GB';}
function alertAdmin(msg,type='info'){const box=ael('admin-alert');if(box)box.innerHTML=`<div class="alert alert-${type} alert-dismissible fade show">${esc(msg)}<button class="btn-close" data-bs-dismiss="alert"></button></div>`;}
async function adminJson(r){const t=await r.text();try{return JSON.parse(t);}catch(e){throw new Error('Server returned invalid JSON.');}}
async function getCsrf(){const d=await adminJson(await fetch('/backend/auth/csrf_token.php'));adminCsrf=d.data?.csrf_token||null;}
function card(title,value,sub,color='primary'){return `<div class="col-md-3 col-sm-6"><div class="card h-100 shadow-sm"><div class="card-body"><div class="text-muted small">${esc(title)}</div><div class="h4 mb-1 text-${color}">${esc(value)}</div><div class="small text-muted">${esc(sub||'')}</div></div></div></div>`;}
function statList(obj){return `<div class="row g-2">${Object.entries(obj||{}).map(([k,v])=>`<div class="col-md-4"><div class="border rounded p-2"><div class="small text-muted">${esc(k.replaceAll('_',' '))}</div><strong>${esc(v)}</strong></div></div>`).join('')}</div>`;}
async function loadAdminOverview(){const d=await adminJson(await fetch(`${ADMIN_API}/overview.php`,{cache:'no-store'}));if(!d.success)throw new Error(d.message||'Overview failed.');adminOverview=d.data;renderOverview();}
function renderOverview(){const o=adminOverview;const cards=ael('admin-overview-cards');cards.innerHTML=[card('Users',o.users.total,`${o.users.active} active, ${o.users.suspended} suspended`,'primary'),card('Memories',o.memories.total,bytes(o.memories.storage_bytes),'success'),card('Posts / Comments',o.posts.posts,`${o.posts.post_comments+o.posts.memory_comments} comments`,'info'),card('Automations',o.automation.automation_rules,`${o.automation.failed_automations} failed`,'warning'),card('Vault metadata',o.vault.documents,`${bytes(o.vault.storage_bytes)} stored, no file content shown`,'secondary'),card('AI usage',o.ai.sources,`${o.ai.indexed_sources} indexed, ${o.ai.tokens} tokens`,'dark'),card('Journeys',o.journeys.total,`${o.journeys.published} published`,'success'),card('Gift / Phoolwala',o.gifts.orders,o.gifts.integration,o.gifts.integration.startsWith('Configured')?'success':'warning')].join('');
 ael('admin-content-status').innerHTML=statList({...o.memories,...o.posts,...o.social});
 ael('admin-journey-status').innerHTML=statList(o.journeys);
 ael('admin-automation-status').innerHTML=statList(o.automation)+failedAutomations(o.failed.automations);
 ael('admin-ai-status').innerHTML=statList(o.ai)+failedAi(o.failed.ai_jobs);
 ael('admin-system-status').innerHTML=statList({phoolwala:o.gifts.integration,vault_documents:o.vault.documents,vault_permissions:o.vault.permissions,failed_ai_jobs:o.failed.ai_jobs.length,failed_automations:o.failed.automations.length});
 ael('admin-failed-jobs').innerHTML=failedAi(o.failed.ai_jobs)+failedAutomations(o.failed.automations);
 ael('admin-recent-activity').innerHTML=recent(o.recent.activity,o.recent.automation_runs);
 ael('activity-log-body').innerHTML=(o.recent.activity||[]).map(r=>`<tr><td>${esc(r.created_at)}</td><td>${esc(r.username||'System')}</td><td>${esc(r.action)}</td><td>${esc(r.details||'-')}</td></tr>`).join('')||'<tr><td>No activity.</td></tr>';
}
function failedAi(rows){return `<h6>Failed AI Jobs</h6>${(rows||[]).length?(rows||[]).map(r=>`<div class="border rounded p-2 mb-2"><strong>Job #${r.id}</strong> source ${esc(r.source_id)} - ${esc(r.error_code||'failed')} <button class="btn btn-sm btn-outline-primary float-end" onclick="retryJob('ai_ingestion',${r.id})">Retry</button></div>`).join(''):'<div class="text-muted small mb-3">No failed AI jobs.</div>'}`;}
function failedAutomations(rows){return `<h6 class="mt-3">Failed Automations</h6>${(rows||[]).length?(rows||[]).map(r=>`<div class="border rounded p-2 mb-2"><strong>${esc(r.title)}</strong><div class="small text-muted">${esc(r.last_error||'failed')}</div><button class="btn btn-sm btn-outline-primary" onclick="retryJob('automation',${r.id})">Retry</button></div>`).join(''):'<div class="text-muted small">No failed automations.</div>'}`;}
function recent(activity,runs){return `<h6>Activity</h6>${(activity||[]).slice(0,8).map(r=>`<div class="small border-bottom py-1">${esc(r.created_at)} - ${esc(r.username||'System')} - ${esc(r.action)}</div>`).join('')||'<div class="text-muted small">No activity.</div>'}<h6 class="mt-3">Automation Runs</h6>${(runs||[]).slice(0,8).map(r=>`<div class="small border-bottom py-1">#${r.id} rule ${r.rule_id} - ${esc(r.status)} - ${esc(r.started_at)}</div>`).join('')||'<div class="text-muted small">No runs.</div>'}`;}
async function retryJob(type,id){try{const d=await adminJson(await fetch(`${ADMIN_API}/retry_failed_job.php`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':adminCsrf},body:JSON.stringify({csrf_token:adminCsrf,type,id})}));alertAdmin(d.message,d.success?'success':'danger');await loadAdminOverview();}catch(e){alertAdmin(e.message,'danger');}}
async function loadUsers(){const q=encodeURIComponent(ael('admin-user-search')?.value||'');const status=encodeURIComponent(ael('admin-user-status')?.value||'');const d=await adminJson(await fetch(`${ADMIN_API}/users.php?q=${q}&status=${status}&limit=30`,{cache:'no-store'}));const tb=ael('users-table-body');if(!d.success){tb.innerHTML='<tr><td colspan="7">Unable to load users.</td></tr>';return;}tb.innerHTML=(d.data.users||[]).map(u=>`<tr><td>${u.id}</td><td><strong>${esc(u.full_name||u.username)}</strong><div class="small text-muted">${esc(u.username)}</div></td><td>${esc(u.email)}</td><td><span class="badge bg-${u.role==='admin'?'danger':'primary'}">${esc(u.role)}</span></td><td><span class="badge bg-${u.status==='active'?'success':'secondary'}">${esc(u.status)}</span></td><td>${esc((u.created_at||'').slice(0,10))}</td><td>${u.role!=='admin'?`<button class="btn btn-sm btn-outline-success me-1" onclick="setUserStatus(${u.id},'active')">Activate</button><button class="btn btn-sm btn-outline-warning" onclick="setUserStatus(${u.id},'suspended')">Suspend</button>`:'-'}</td></tr>`).join('')||'<tr><td colspan="7">No users.</td></tr>';}
async function setUserStatus(id,status){try{const d=await adminJson(await fetch(`${ADMIN_API}/users.php`,{method:'PUT',headers:{'Content-Type':'application/json','X-CSRF-Token':adminCsrf},body:JSON.stringify({csrf_token:adminCsrf,user_id:id,status})}));alertAdmin(d.message,d.success?'success':'danger');await loadUsers();await loadAdminOverview();}catch(e){alertAdmin(e.message,'danger');}}
document.addEventListener('DOMContentLoaded',async()=>{try{const session=await adminJson(await fetch('/backend/auth/check_session.php'));if(!session.logged_in||session.user?.role!=='admin'){location.href='login.php';return;}await getCsrf();await Promise.all([loadAdminOverview(),loadUsers()]);ael('admin-user-refresh')?.addEventListener('click',loadUsers);ael('admin-user-search')?.addEventListener('input',()=>{clearTimeout(window.adminUserTimer);window.adminUserTimer=setTimeout(loadUsers,300);});ael('admin-user-status')?.addEventListener('change',loadUsers);}catch(e){alertAdmin(e.message,'danger');}});

let adminUsersPage = 1;
let adminActivityPage = 1;
function adminPager(id, page, pages, callback) { const box=ael(id); if(!box)return; box.innerHTML=`<div class="d-flex justify-content-between align-items-center"><span class="small text-muted">Page ${page} of ${pages}</span><div><button class="btn btn-sm btn-outline-secondary me-2" ${page<=1?'disabled':''}>Previous</button><button class="btn btn-sm btn-outline-secondary" ${page>=pages?'disabled':''}>Next</button></div></div>`; const b=box.querySelectorAll('button'); b[0]?.addEventListener('click',()=>callback(page-1)); b[1]?.addEventListener('click',()=>callback(page+1)); }
async function loadUsers(page=1){adminUsersPage=page;const q=encodeURIComponent(ael('admin-user-search')?.value||''),status=encodeURIComponent(ael('admin-user-status')?.value||'');const d=await adminJson(await fetch(`${ADMIN_API}/users.php?q=${q}&status=${status}&limit=10&page=${page}`,{cache:'no-store'}));const tb=ael('users-table-body');if(!d.success){tb.innerHTML='<tr><td colspan="9">Unable to load users.</td></tr>';return;}tb.innerHTML=(d.data.users||[]).map(u=>`<tr><td>${u.id}</td><td><strong>${esc(u.full_name||u.username)}</strong><div class="small text-muted">${esc(u.username)}</div></td><td>${esc(u.email)}</td><td><span class="badge bg-${u.role==='admin'?'danger':'primary'}">${esc(u.role)}</span></td><td><span class="badge bg-${u.status==='active'?'success':'secondary'}">${esc(u.status)}</span></td><td>${esc(u.last_login||'Never')}</td><td>${bytes(u.memory_storage_bytes)}</td><td>${esc((u.created_at||'').slice(0,10))}</td><td>${u.role!=='admin'?`<button class="btn btn-sm btn-outline-success me-1" onclick="setUserStatus(${u.id},'active')">Activate</button><button class="btn btn-sm btn-outline-warning" onclick="setUserStatus(${u.id},'suspended')">Suspend</button>`:'-'}</td></tr>`).join('')||'<tr><td colspan="9">No users.</td></tr>';adminPager('users-pagination',d.data.pagination.page,d.data.pagination.pages,loadUsers);}
async function loadAdminActivity(page=1){adminActivityPage=page;const d=await adminJson(await fetch(`${ADMIN_API}/activity_log.php?page=${page}`,{cache:'no-store'}));const tb=ael('activity-log-body');if(!d.success){tb.innerHTML='<tr><td>Unable to load activity.</td></tr>';return;}tb.innerHTML=(d.logs||[]).map(r=>`<tr><td>${esc(r.created_at)}</td><td>${esc(r.username||'System')}</td><td>${esc(r.action)}</td><td>${esc(r.details||'-')}</td></tr>`).join('')||'<tr><td>No activity.</td></tr>';adminPager('activity-pagination',d.pagination.page,d.pagination.pages,loadAdminActivity);}
document.addEventListener('DOMContentLoaded',()=>{setTimeout(()=>loadAdminActivity(),0);});
