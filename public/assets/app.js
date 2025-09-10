/* Organització Casa – JS principal (moure des d'index.php) */

/* Estat i constants */
const QUALITY_MULT = {1:0.6, 2:0.8, 3:1.0, 4:1.2, 5:1.5};
let state = { members:[], tasks:[], entries:[] };

const $ = (id)=>document.getElementById(id);

/* Banner d’estat */
const statusEl = document.getElementById('status');
function showOK(msg){ if(!statusEl) return; statusEl.className='ok'; statusEl.textContent=msg; statusEl.style.display='block'; }
function showErr(msg){ if(!statusEl) return; statusEl.className='err'; statusEl.textContent=msg; statusEl.style.display='block'; }
function clearStatus(){ if(!statusEl) return; statusEl.className=''; statusEl.textContent=''; statusEl.style.display='none'; }

/* Helpers */
function fmtDate(d){ const dt = new Date(d); return dt.toLocaleString('ca-ES'); }
function toLocalInputNow(){ const d=new Date(); const local=new Date(Date.now()-d.getTimezoneOffset()*60000); return local.toISOString().slice(0,16); }
function escapeHtml(s){
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

/* Client API robust */
async function api(params, method='GET'){
  const url = method==='GET' ? 'api.php?'+params.toString() : 'api.php';
  const res = await fetch(url, { method, ...(method==='POST'?{body:params}:{}) });
  const text = await res.text();
  let data;
  try { data = text ? JSON.parse(text) : {}; }
  catch(e){ showErr('Resposta no vàlida de l’API: '+text.slice(0,200)); throw e; }
  if(!res.ok || data.error){ showErr('Error API: '+(data.error||('HTTP '+res.status))); throw new Error(data.error||('HTTP '+res.status)); }
  clearStatus(); return data;
}

/* Cicle de vida */
document.addEventListener('DOMContentLoaded', async ()=>{
  const dateInput = $('dateISO');
  if (dateInput) dateInput.value = toLocalInputNow();

  try{
    await api(new URLSearchParams({action:'bootstrap'}), 'GET');
    await loadAll();
    bindForms();
    renderAll();
  }catch(e){ console.error(e); }
});

/* Dades */
async function loadAll(){
  const data = await api(new URLSearchParams({action:'list_all'}), 'GET');
  state = data;
  if(state.members.length===0 || state.tasks.length===0){
    showErr('No hi ha membres o tasques. Afegeix-ne a Configuració.');
  }
}

/* Formularis */
function bindForms(){
  $('entryForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const member_id = $('memberId').value;
    const task_id   = $('taskId').value;
    if(!member_id || !task_id){ showErr('Tria membre i tasca'); return; }

    const d = new Date($('dateISO').value);
    const iso = new Date(d.getTime()-d.getTimezoneOffset()*60000).toISOString().slice(0,19).replace('T',' ');

    const payload = new URLSearchParams({
      action:'add_entry',
      member_id, task_id,
      date_iso: iso,
      quality: $('quality').value || '3',
      notes: $('notes').value || ''
    });
    try{
      await api(payload, 'POST');
      await loadAll(); renderAll();
      $('notes').value=''; showOK('Registre afegit!');
    }catch(e){ console.error(e); }
  });

  $('memberForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const name = $('memberName').value.trim();
    const role = $('memberRole').value.trim();
    if(!name){ showErr('Cal un nom de membre.'); return; }
    try{
      await api(new URLSearchParams({action:'add_member', name, role}), 'POST');
      $('memberName').value=''; $('memberRole').value='';
      await loadAll(); renderAll(); showOK('Membre afegit.');
    }catch(e){ console.error(e); }
  });

  $('taskForm').addEventListener('submit', async (e)=>{
    e.preventDefault();
    const name = $('taskName').value.trim();
    const base_points = parseInt($('taskPoints').value||'10',10);
    const icon = $('taskIcon') ? $('taskIcon').value.trim() : '';
    if(!name || base_points<=0){ showErr('Nom de tasca i punts base > 0'); return; }
    try{
      await api(new URLSearchParams({action:'add_task', name, base_points, icon}), 'POST');
      $('taskName').value=''; $('taskPoints').value='10'; if($('taskIcon')) $('taskIcon').value='';
      await loadAll(); renderAll(); showOK('Tasca afegida.');
    }catch(e){ console.error(e); }
  });

  $('search').addEventListener('input', renderAll);
  $('range').addEventListener('change', renderAll);
}

/* Render */
function renderAll(){
  // SELECTS
  $('memberId').innerHTML = state.members.map(m => '<option value="'+m.id+'">'+escapeHtml(m.name)+'</option>').join('');
  $('taskId').innerHTML = state.tasks.map(t => {
    const ico = t.icon ? (escapeHtml(t.icon)+' ') : '';
    return '<option value="'+t.id+'">'+ico+escapeHtml(t.name)+' · '+t.base_points+'p</option>';
  }).join('');

  // CONFIG: membres
  $('membersList').innerHTML = state.members.map(m =>
    '<div class="card" style="padding:.5rem;display:flex;justify-content:space-between;align-items:center">'
    + '<div><strong>'+escapeHtml(m.name)+'</strong><br/><span class="muted">'+escapeHtml(m.role||'')+'</span></div>'
    + '<button class="secondary" onclick="delMember('+m.id+')">Esborra</button></div>'
  ).join('');

  // CONFIG: tasques (amb icona)
  $('tasksList').innerHTML = state.tasks.map(t =>
    '<div class="card" style="padding:.5rem;display:flex;justify-content:space-between;align-items:center">'
    + '<div><strong>'+(t.icon?escapeHtml(t.icon)+' ':'')+escapeHtml(t.name)+'</strong>'
    + '<br/><span class="muted">'+t.base_points+' punts base</span></div>'
    + '<button class="secondary" onclick="delTask('+t.id+')">Esborra</button></div>'
  ).join('');

  // Llista d'entrades
  const q = ($('search').value||'').toLowerCase();
  const range = $('range').value;
  $('rangeLabel').textContent = (range==='setmana')?'(setmana)':(range==='mes')?'(mes)':'(tot)';

  const filtered = state.entries
    .filter(e => withinRange(e.date_iso, range))
    .filter(e => {
      if(!q) return true;
      const m = (state.members.find(x=>x.id==e.member_id)||{}).name||'';
      const t = (state.tasks.find(x=>x.id==e.task_id)||{}).name||'';
      const n = (e.notes||'');
      return m.toLowerCase().includes(q) || t.toLowerCase().includes(q) || n.toLowerCase().includes(q);
    });

  $('entriesList').innerHTML = filtered.map(e=>{
    const m = state.members.find(x=>x.id==e.member_id);
    const t = state.tasks.find(x=>x.id==e.task_id);
    const mult = QUALITY_MULT[e.quality] || 1;
    const pts = Math.round((t ? t.base_points : 0) * mult);
    const notesHtml = e.notes ? '<div class="muted" style="margin-top:.25rem">'+escapeHtml(e.notes)+'</div>' : '';
    const taskLabel = (t && t.icon ? '<span style="margin-right:.25rem">'+escapeHtml(t.icon)+'</span>' : '')
                      + (t ? escapeHtml(t.name) : '');

    return ''
      + '<div class="card" style="padding:.75rem">'
      + '<div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:.5rem;align-items:center">'
      + '<div><span class="badge">'+(m?escapeHtml(m.name):'')+'</span></div>'
      + '<div><span class="badge" style="background:#f3f3f3">'+taskLabel+'</span></div>'
      + '<div class="muted">'+fmtDate(e.date_iso)+'</div>'
      + '<div style="text-align:right"><strong>+'+pts+'p</strong> · '+e.quality+'⭐</div>'
      + '</div>' + notesHtml
      + '<div style="text-align:right;margin-top:.25rem"><button class="secondary" onclick="delEntry('+e.id+')">Esborra</button></div>'
      + '</div>';
  }).join('') || '<p class="muted">Encara no hi ha registres en aquest període.</p>';

  // Rànquing
  const score = {};
  for(const e of filtered){
    const t = state.tasks.find(x=>x.id==e.task_id);
    if(!t) continue;
    const pts = Math.round(t.base_points * (QUALITY_MULT[e.quality]||1));
    score[e.member_id] = (score[e.member_id]||0) + pts;
  }
  const rows = Object.entries(score).map(([memberId,pts])=>({memberId:parseInt(memberId,10), pts})).sort((a,b)=>b.pts-a.pts);
  $('leaderboard').innerHTML = rows.map((r,idx)=>{
    const m = state.members.find(x=>x.id==r.memberId);
    return '<div class="card" style="padding:.5rem;display:flex;justify-content:space-between;align-items:center">'
         + '<div><span class="badge" style="'+(idx===0?'background:#ffd54f':'')+'">'+(idx+1)+'</span> <strong>'+(m?escapeHtml(m.name):'')+'</strong></div>'
         + '<div><strong>'+r.pts+' punts</strong></div></div>';
  }).join('') || '<p class="muted">Encara no hi ha punts.</p>';
}

/* Utils */
function withinRange(dateStr, range){
  const d = new Date(dateStr);
  const now = new Date();
  const day = (x)=> new Date(x.getFullYear(), x.getMonth(), x.getDate());
  if(range==='setmana'){
    const dayOfWeek = (now.getDay()+6)%7; // dilluns=0
    const start = new Date(day(now)); start.setDate(start.getDate()-dayOfWeek);
    const end = new Date(start); end.setDate(end.getDate()+6); end.setHours(23,59,59,999);
    return d>=start && d<=end;
  }
  if(range==='mes'){
    const start = new Date(now.getFullYear(), now.getMonth(), 1);
    const end = new Date(now.getFullYear(), now.getMonth()+1, 0, 23,59,59,999);
    return d>=start && d<=end;
  }
  return true;
}

/* Detecta si falta la BD (mostra banner d'instal·lació) */
async function checkInstall(){
  try {
    await api(new URLSearchParams({action:'list_all'}), 'GET');
    document.getElementById('installBanner').style.display = 'none';
  } catch(e) {
    console.warn('Error API: potser falta inicialitzar la BD', e);
    const banner = document.getElementById('installBanner');
    if (banner) banner.style.display = 'block';
  }
}

document.addEventListener('DOMContentLoaded', checkInstall);


/* Accions d’esborrar */
window.delMember = async function(id){
  if(!confirm('Segur que vols esborrar aquest membre? També s\'esborraran les seves entrades.')) return;
  try{ await api(new URLSearchParams({action:'delete_member', id}), 'POST'); await loadAll(); renderAll(); showOK('Membre esborrat.'); }catch(e){}
};
window.delTask = async function(id){
  if(!confirm('Segur que vols esborrar aquesta tasca? També s\'esborraran les entrades associades.')) return;
  try{ await api(new URLSearchParams({action:'delete_task', id}), 'POST'); await loadAll(); renderAll(); showOK('Tasca esborrada.'); }catch(e){}
};
window.delEntry = async function(id){
  if(!confirm('Esborrar aquest registre?')) return;
  try{ await api(new URLSearchParams({action:'delete_entry', id}), 'POST'); await loadAll(); renderAll(); showOK('Registre esborrat.'); }catch(e){}
};
