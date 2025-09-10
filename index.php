<?php /* index.php */ ?>
<!doctype html>
<html lang="ca">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Organització Casa · Tasques familiars</title>
  <link rel="stylesheet" href="https://unpkg.com/@picocss/pico@2/css/pico.min.css" />
  <style>
    .badge {display:inline-block;padding:.25rem .6rem;border-radius:999px;background:#eee;margin-right:.25rem}
    .grid {display:grid;gap:1rem}
    @media (min-width: 900px){.grid-2{grid-template-columns:1fr 1fr}.grid-3{grid-template-columns:1fr 1fr 1fr}}
    .card{border:1px solid #e7e7e7;border-radius:12px;padding:1rem}
    .muted{color:#666}
    #status{margin:.75rem 0;padding:.5rem .75rem;border-radius:8px;display:none}
    #status.err{display:block;background:#ffe8e8;border:1px solid #ffb3b3;color:#8a1f1f}
    #status.ok{display:block;background:#e8fff0;border:1px solid #b3ffd1;color:#1f8a4a}
  </style>
</head>
<body>
  <main class="container">
    <h1>Organització Casa · Tasques familiars</h1>
    <p class="muted">Registra qui fa què, quan i com de bé. Suma punts i mireu el rànquing setmanal o mensual.</p>

    <div id="status"></div>

    <section class="grid grid-2">
      <article class="card">
        <h3>Registrar tasca feta</h3>
        <form id="entryForm">
          <label>Membre
            <select id="memberId"></select>
          </label>
          <label>Tasca
            <select id="taskId"></select>
          </label>
          <label>Data i hora
            <input type="datetime-local" id="dateISO" />
          </label>
          <label>Qualitat (1–5)
            <input type="number" id="quality" min="1" max="5" value="3" />
          </label>
          <label>Notes
            <textarea id="notes" rows="2" placeholder="Detalls, incidències…"></textarea>
          </label>
          <button type="submit">Afegir registre</button>
        </form>
      </article>

      <article class="card">
        <h3>Configuració</h3>
        <details open>
          <summary>Membres</summary>
          <form id="memberForm" class="grid">
            <input id="memberName" placeholder="Nom" />
            <input id="memberRole" placeholder="Rol/edat" />
            <button>Afegeix membre</button>
          </form>
          <div id="membersList" style="margin-top:.5rem"></div>
        </details>
        <details open>
          <summary>Tasques</summary>
          <form id="taskForm" class="grid">
            <input id="taskName" placeholder="Nom de la tasca" />
            <input id="taskPoints" type="number" min="1" value="10" />
            <input id="taskIcon" placeholder="Icona (ex: 🧹)" maxlength="8" />
            <button>Afegeix tasca</button>
          </form>
          <div id="tasksList" style="margin-top:.5rem"></div>
        </details>
      </article>
    </section>

    <section class="grid">
      <article class="card">
        <header class="grid grid-3" style="align-items:end">
          <h3 style="margin:0">Tasques registrades</h3>
          <input id="search" placeholder="Cerca per membre, tasca o nota…" />
          <select id="range">
            <option value="setmana">Aquesta setmana</option>
            <option value="mes">Aquest mes</option>
            <option value="sempre">Sempre</option>
          </select>
        </header>
        <div id="entriesList" style="margin-top:1rem"></div>
      </article>

      <article class="card">
        <h3>Classificació <span id="rangeLabel" class="muted"></span></h3>
        <div id="leaderboard"></div>
      </article>
    </section>
  </main>

<script>
const QUALITY_MULT = {1:0.6, 2:0.8, 3:1.0, 4:1.2, 5:1.5};
let state = { members:[], tasks:[], entries:[] };
const $ = (id)=>document.getElementById(id);
const statusEl = $('status');

function showOK(msg){ statusEl.className='ok'; statusEl.textContent=msg; }
function showErr(msg){ statusEl.className='err'; statusEl.textContent=msg; }
function clearStatus(){ statusEl.className=''; statusEl.style.display='none'; statusEl.textContent=''; }
new MutationObserver(()=>{ statusEl.style.display = statusEl.textContent ? 'block' : 'none'; }).observe(statusEl, {childList:true});

function fmtDate(d){ const dt = new Date(d); return dt.toLocaleString('ca-ES'); }
function toLocalInputNow(){ const d=new Date(); const local=new Date(Date.now()-d.getTimezoneOffset()*60000); return local.toISOString().slice(0,16); }
$('dateISO').value = toLocalInputNow();

/* Helper API */
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

afterLoad();

async function afterLoad(){
  try{
    await api(new URLSearchParams({action:'bootstrap'}), 'GET');
    await loadAll();
    bindForms();
    renderAll();
  }catch(e){ console.error(e); }
}
async function loadAll(){
  const data = await api(new URLSearchParams({action:'list_all'}), 'GET');
  state = data;
  if(state.members.length===0 || state.tasks.length===0){
    showErr('No hi ha membres o tasques. Afegeix-ne a Configuració.');
  }
}

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
    const icon = $('taskIcon').value.trim();
    if(!name || base_points<=0){ showErr('Nom de tasca i punts base > 0'); return; }
    try{
      await api(new URLSearchParams({action:'add_task', name, base_points, icon}), 'POST');
      $('taskName').value=''; $('taskPoints').value='10'; $('taskIcon').value='';
      await loadAll(); renderAll(); showOK('Tasca afegida.');
    }catch(e){ console.error(e); }
  });

  $('search').addEventListener('input', renderAll);
  $('range').addEventListener('change', renderAll);
}

function escapeHtml(s){
  if (s == null) return '';
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

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

// accions d'esborrar
async function delMember(id){
  if(!confirm('Segur que vols esborrar aquest membre? També s\'esborraran les seves entrades.')) return;
  try{ await api(new URLSearchParams({action:'delete_member', id}), 'POST'); await loadAll(); renderAll(); showOK('Membre esborrat.'); }catch(e){}
}
async function delTask(id){
  if(!confirm('Segur que vols esborrar aquesta tasca? També s\'esborraran les entrades associades.')) return;
  try{ await api(new URLSearchParams({action:'delete_task', id}), 'POST'); await loadAll(); renderAll(); showOK('Tasca esborrada.'); }catch(e){}
}
async function delEntry(id){
  if(!confirm('Esborrar aquest registre?')) return;
  try{ await api(new URLSearchParams({action:'delete_entry', id}), 'POST'); await loadAll(); renderAll(); showOK('Registre esborrat.'); }catch(e){}
}
</script>
</body>
</html>
