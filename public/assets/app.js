/* Organització Casa – JS principal */

/* ==== helpers base ==== */
function $(id){ return document.getElementById(id); }
function log(){ try{ console.log.apply(console, arguments); }catch(e){} }

/* Estat i constants */
const QUALITY_MULT = {1:0.6, 2:0.8, 3:1.0, 4:1.2, 5:1.5};
let state = { members:[], tasks:[], entries:[] };

/* Banner d’estat (opcional) */
const statusEl = $('status');
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
  let data = {};
  try { data = text ? JSON.parse(text) : {}; }
  catch(e){ showErr('Resposta no vàlida de l’API: '+text.slice(0,200)); throw e; }
  if(!res.ok || data.error){ showErr('Error API: '+(data.error||('HTTP '+res.status))); throw new Error(data.error||('HTTP '+res.status)); }
  clearStatus(); return data;
}

/* ==== cicle de vida ==== */
document.addEventListener('DOMContentLoaded', init);

async function init(){
  const dateInput = $('dateISO');
  if (dateInput) dateInput.value = toLocalInputNow();

  try{
    await api(new URLSearchParams({action:'bootstrap'}), 'GET');
    await loadAll();
    bindForms();
    renderAll();
  }catch(e){ console.error(e); }
}

/* ==== dades ==== */
async function loadAll(){
  const data = await api(new URLSearchParams({action:'list_all'}), 'GET');
  state = data;
  if(state.members.length===0 || state.tasks.length===0){
    showErr('No hi ha membres o tasques. Afegeix-ne a Configuració.');
  }
}

/* ==== formularis ==== */
function bindForms(){
  const entryForm = $('entryForm');
  if (entryForm) {
    entryForm.addEventListener('submit', async (e)=>{
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
      }catch(err){ console.error(err); }
    });
  }

  const memberForm = $('memberForm');
  if (memberForm) {
    memberForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const name = $('memberName').value.trim();
      const role = $('memberRole').value.trim();
      if(!name){ showErr('Cal un nom de membre.'); return; }
      try{
        await api(new URLSearchParams({action:'add_member', name, role}), 'POST');
        $('memberName').value=''; $('memberRole').value='';
        await loadAll(); renderAll(); showOK('Membre afegit.');
      }catch(err){ console.error(err); }
    });
  }

  const taskForm = $('taskForm');
  if (taskForm) {
    taskForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      const name = $('taskName').value.trim();
      const base_points = parseInt($('taskPoints').value||'10',10);
      const category = $('taskCategory').value.trim();
      const emoji = $('taskEmoji') ? $('taskEmoji').value.trim() : '';
      const file = $('taskIconImg').files[0];

      if(!name || base_points<=0){ showErr('Nom de tasca i punts base > 0'); return; }

      try{
        // enviem com multipart per poder incloure imatge
        const fd = new FormData();
        fd.append('action','add_task');
        fd.append('name', name);
        fd.append('base_points', String(base_points));
        if (category) fd.append('category', category);
        if (emoji) fd.append('icon', emoji);
        if (file) fd.append('icon_img', file);

        const res = await fetch('api.php', { method:'POST', body: fd });
        const data = await res.json();
        if (data.error) throw new Error(data.error);

        // reseteja
        $('taskName').value=''; $('taskPoints').value='10';
        if ($('taskCategory')) $('taskCategory').value='';
        if ($('taskEmoji')) $('taskEmoji').value='';
        if ($('taskIconImg')) $('taskIconImg').value='';
        await loadAll(); renderAll(); showOK('Tasca afegida.');
      }catch(err){ console.error(err); showErr('Error creant tasca: '+err.message); }
    });
  }

  // >>>>>> IMPORTANT: listener separat per al formulari de FOTO (no dins d’altres submits)
  const photoForm = $('photoForm');
  if (photoForm) {
    photoForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      try{
        const id = $('photoMemberId')?.value;
        const f  = $('photoFile')?.files?.[0];
        if(!id){ showErr('Escull un membre'); return; }
        if(!f){ showErr('Escull una imatge'); return; }

        const fd = new FormData();
        fd.append('action','upload_member_photo');
        fd.append('member_id', id);
        fd.append('photo', f);

        log('ENVIANT upload_member_photo…');
        const res = await fetch('api.php', { method:'POST', body: fd });
        const data = await res.json();
        log('RESP upload_member_photo', data);
        if(data.error) throw new Error(data.error);

        await loadAll(); renderAll(); showOK('Foto pujada.');
        $('photoFile').value='';
      }catch(err){ console.error(err); showErr('Error pujant foto: '+err.message); }
    });
  }

  const search = $('search');
  if (search) search.addEventListener('input', renderAll);
  const range = $('range');
  if (range) range.addEventListener('change', renderAll);

 // ----- Selector d’estrelles (★/☆) -----
  const starsWrap = $('qualityStars');
  const stars = starsWrap ? starsWrap.querySelectorAll('.star') : [];
  const qualityInput = $('quality');
  const qualityLabel = $('qualityLabel');

  function paintStars(val){
    stars.forEach(s=>{
      const n = parseInt(s.dataset.val,10);
      const active = n <= val;
      s.textContent = active ? '★' : '☆';
      s.classList.toggle('active', active);
    });
    if (qualityLabel) qualityLabel.textContent = `${val}/5`;
  }

  if (stars.length){
    // inicialitza
    paintStars(parseInt(qualityInput.value||'3',10));

    stars.forEach(star=>{
      star.addEventListener('mouseenter', ()=>{
        paintStars(parseInt(star.dataset.val,10));
      });
      star.addEventListener('mouseleave', ()=>{
        paintStars(parseInt(qualityInput.value||'3',10));
      });
      star.addEventListener('click', ()=>{
        const v = parseInt(star.dataset.val,10);
        qualityInput.value = v;
        paintStars(v);
      });
    });
  }

  // ---- Task Picker (modal) ----
  const picker = $('taskPicker');
  const openBtn = $('openTaskPicker');
  const closeBtn = $('taskPickerClose');
  const cancelBtn = $('taskPickerCancel');
  const searchInput = $('taskPickerSearch');
  const content = $('taskPickerContent');
  const preview = $('taskSelectedPreview');

  function openPicker(){
    renderPickerCards('');
    if (typeof picker.showModal === 'function') picker.showModal();
    else picker.setAttribute('open','');
    searchInput.value='';
    searchInput.focus();
  }
  function closePicker(){
    if (typeof picker.close === 'function') picker.close();
    else picker.removeAttribute('open');
  }
  if (openBtn) openBtn.addEventListener('click', openPicker);
  if (closeBtn) closeBtn.addEventListener('click', closePicker);
  if (cancelBtn) cancelBtn.addEventListener('click', closePicker);
  if (picker) picker.addEventListener('click', (e)=>{ if(e.target===picker) closePicker(); });
  if (searchInput) searchInput.addEventListener('input', ()=>renderPickerCards(searchInput.value.trim().toLowerCase()));

  function renderPickerCards(q){
    // agrupem per categoria
    const groups = {};
    state.tasks.forEach(t=>{
      if(q){
        const hay = (t.name+' '+(t.category||'')).toLowerCase();
        if(!hay.includes(q)) return;
      }
      const cat = t.category || 'Altres';
      if(!groups[cat]) groups[cat]=[];
      groups[cat].push(t);
    });

    const parts = [];
    Object.keys(groups).sort().forEach(cat=>{
      parts.push(`<h4 style="grid-column:1/-1;margin:.5rem 0 .25rem">${escapeHtml(cat)}</h4>`);
      groups[cat].forEach(t=>{
        const img = t.icon_img ? `<img src="${escapeHtml(t.icon_img)}" alt="">` :
          `<div style="font-size:2.2rem;margin:.25rem 0">${t.icon?escapeHtml(t.icon):'🧩'}</div>`;
        parts.push(`
          <div class="task-card" data-id="${t.id}" title="${escapeHtml(t.name)}">
            ${img}
            <span class="title">${escapeHtml(t.name)}</span>
            <span class="cat muted">${t.base_points}p</span>
          </div>
        `);
      });
    });

    content.innerHTML = parts.join('') || `<p class="muted" style="grid-column:1/-1">No hi ha tasques que coincideixin.</p>`;

    // click handler de cada targeta
    content.querySelectorAll('.task-card').forEach(card=>{
      card.addEventListener('click', ()=>{
        const id = card.getAttribute('data-id');
        const t = state.tasks.find(x=> String(x.id)===String(id));
        if (!t) return;
        $('taskId').value = t.id;
        const label = t.icon_img ? `<img src="${escapeHtml(t.icon_img)}" alt="" style="width:22px;height:22px;object-fit:cover;border-radius:6px;vertical-align:middle;margin-right:.35rem">` :
                      (t.icon ? `<span style="font-size:1.2rem;vertical-align:middle;margin-right:.25rem">${escapeHtml(t.icon)}</span>` : '');
        preview.innerHTML = `${label}<strong>${escapeHtml(t.name)}</strong> · <span class="muted">${t.base_points}p</span>`;
        closePicker();
      });
    });
  }

}

/* ==== render ==== */
function renderAll(){
  // SELECTS
  if ($('memberId')) $('memberId').innerHTML =
    state.members.map(m => `<option value="${m.id}">${escapeHtml(m.name)}</option>`).join('');

  if ($('taskId')) $('taskId').innerHTML =
    state.tasks.map(t => {
      const ico = t.icon ? (escapeHtml(t.icon)+' ') : '';
      return `<option value="${t.id}">${ico}${escapeHtml(t.name)} · ${t.base_points}p</option>`;
    }).join('');

  // select per al formulari de fotos
  const photoSel = $('photoMemberId');
  if (photoSel) {
    photoSel.innerHTML = state.members.map(m => `<option value="${m.id}">${escapeHtml(m.name)}</option>`).join('');
  }

  // CONFIG: membres
  if ($('membersList')) $('membersList').innerHTML = state.members.map(m => {
    const img = m.photo
      ? `<img src="${escapeHtml(m.photo)}" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;margin-right:.5rem">`
      : `<div style="width:32px;height:32px;border-radius:50%;background:#ddd;display:inline-flex;align-items:center;justify-content:center;margin-right:.5rem">👤</div>`;
    return `<div class="card" style="padding:.5rem;display:flex;justify-content:space-between;align-items:center">
      <div style="display:flex;align-items:center">${img}<div><strong>${escapeHtml(m.name)}</strong><br/><span class="muted">${escapeHtml(m.role||'')}</span></div></div>
      <button class="secondary" onclick="delMember(${m.id})">Esborra</button></div>`;
  }).join('');

  // CONFIG: tasques
  if ($('tasksList')) $('tasksList').innerHTML = state.tasks.map(t => {
    const hasImg = !!t.icon_img;
    const media = hasImg
      ? `<img src="${escapeHtml(t.icon_img)}" alt="">`
      : `<div style="font-size:2rem">${t.icon?escapeHtml(t.icon):'🧩'}</div>`;
    return `
      <div class="card" style="padding:.5rem;display:flex;justify-content:space-between;align-items:center">
        <div style="display:flex;align-items:center;gap:.75rem">
          ${media}
          <div>
            <strong>${escapeHtml(t.name)}</strong>
            <br><span class="muted">${t.base_points} punts base${t.category? ' · '+escapeHtml(t.category):''}</span>
          </div>
        </div>
        <button class="secondary" onclick="delTask(${t.id})">Esborra</button>
      </div>`;
  }).join('');

  // Llista d'entrades (una línia + paperera)
  const q = ($('search')?.value||'').toLowerCase();
  const range = $('range')?.value || 'setmana';
  if ($('rangeLabel')) $('rangeLabel').textContent = (range==='setmana')?'(setmana)':(range==='mes')?'(mes)':'(tot)';

  const filtered = state.entries
    .filter(e => withinRange(e.date_iso, range))
    .filter(e => {
      if(!q) return true;
      const m = (state.members.find(x=>x.id==e.member_id)||{}).name||'';
      const t = (state.tasks.find(x=>x.id==e.task_id)||{}).name||'';
      const n = (e.notes||'');
      return m.toLowerCase().includes(q) || t.toLowerCase().includes(q) || n.toLowerCase().includes(q);
    });

  if ($('entriesList')) $('entriesList').innerHTML = filtered.map(e=>{
    const m = state.members.find(x=>x.id==e.member_id);
    const t = state.tasks.find(x=>x.id==e.task_id);
    const mult = QUALITY_MULT[e.quality] || 1;
    const pts = Math.round((t ? t.base_points : 0) * mult);
    const taskLabelPlain = (t ? ((t.icon? t.icon+' ' : '') + t.name) : '');
    const leftText = `
      <strong>${escapeHtml(m?m.name:'')}</strong>
      · ${escapeHtml(taskLabelPlain)}
      · <span class="muted">${fmtDate(e.date_iso)}</span>
      · <strong>+${pts}p</strong>
      · ${e.quality}⭐
    `.replace(/\s+/g,' ').trim();

    return `
      <div class="card" style="padding:.5rem">
        <div style="display:flex;align-items:center;gap:.5rem;justify-content:space-between">
          <div style="min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"
               title="${escapeHtml(e.notes||'')}">${leftText}</div>
          <button class="secondary" style="padding:.25rem .45rem;font-size:1rem"
                  onclick="delEntry(${e.id})" aria-label="Esborra">🗑️</button>
        </div>
      </div>`;
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
  if ($('leaderboard')) $('leaderboard').innerHTML = rows.map((r,idx)=>{
    const m = state.members.find(x=>x.id==r.memberId);
    return `<div class="card" style="padding:.5rem;display:flex;justify-content:space-between;align-items:center">
      <div><span class="badge" style="${idx===0?'background:#ffd54f':''}">${idx+1}</span> <strong>${m?escapeHtml(m.name):''}</strong></div>
      <div><strong>${r.pts} punts</strong></div></div>`;
  }).join('') || '<p class="muted">Encara no hi ha punts.</p>';
}

/* ==== utils ==== */
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

/* Mostra banner d'instal·lació si cal */
async function checkInstall(){
  try {
    await api(new URLSearchParams({action:'list_all'}), 'GET');
    if ($('installBanner')) $('installBanner').style.display = 'none';
  } catch(e) {
    const banner = $('installBanner');
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
