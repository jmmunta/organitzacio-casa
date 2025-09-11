<?php /* index.php */ session_start(); if(empty($_SESSION['user'])){ header('Location: login.php'); exit; } $me=$_SESSION['user']; ?>
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
    @media (min-width: 900px){
      .grid-2{grid-template-columns:1fr 1fr}
      .right-col{grid-template-columns:1fr} /* apilat a la columna dreta */
    }
    .card{border:1px solid #e7e7e7;border-radius:12px;padding:1rem}
    .muted{color:#666}
    #status{margin:.75rem 0;padding:.5rem .75rem;border-radius:8px;display:none}
    #status.err{display:block;background:#ffe8e8;border:1px solid #ffb3b3;color:#8a1f1f}
    #status.ok{display:block;background:#e8fff0;border:1px solid #b3ffd1;color:#1f8a4a}
    .stars { user-select:none; }
    .stars .star{ font-size:1.8rem; line-height:1; cursor:pointer; color:#bbb; }
    .stars .star.active{ color:#f5c518; } /* groc per a seleccionades */

    .task-card{
      border:1px solid #e7e7e7;border-radius:12px;padding:.5rem;text-align:center;cursor:pointer;
    }
    .task-card img{ width:72px;height:72px;object-fit:cover;border-radius:12px;display:block;margin:.25rem auto; }
    .task-card .title{ display:block;margin-top:.25rem;font-weight:600 }
    .task-card .cat{ display:block;font-size:.85rem;color:#666 }
    .task-card.selected{ outline:2px solid #4caf50; }

 
  </style>
</head>
<body>
  <header class="container" style="display:flex;justify-content:space-between;align-items:center;margin-top:1rem">
    <div><strong>Organització Casa</strong></div>
    <nav>
      <span class="muted" style="margin-right:.5rem"><?=htmlspecialchars($me['email'])?> (<?=htmlspecialchars($me['role'])?>)</span>
      <a href="api.php?action=logout" role="button" class="secondary">Sortir</a>
    </nav>
  </header>
  <main class="container">
    <h1>Organització Casa · Tasques familiars</h1>
    <p class="muted">Registra qui fa què, quan i com de bé. Suma punts i mireu el rànquing setmanal o mensual.</p>

    <!-- Banner d’instal·lació (es mostra si l’API falla) -->
    <div id="installBanner" style="display:none;margin:1rem 0;padding:.75rem 1rem;border-radius:8px;
         background:#fff4e5;border:1px solid #ffcd94;color:#663c00">
      ⚠️ Sembla que la base de dades encara no està inicialitzada.
      Ves a <a href="migrate.php"><strong>migrate.php</strong></a> per crear les taules i omplir les tasques.
    </div>

    <!-- Banner d’estat de l’app -->
    <div id="status"></div>

    <!-- FILA SUPERIOR: esquerra registre / dreta classificació + llista -->
    <section class="grid grid-2">
      <!-- Esquerra: Registrar -->
      <article class="card">
        <h3>Registrar tasca feta</h3>
        <form id="entryForm">
          <label>Membre
            <select id="memberId"></select>
          </label>
          <label>Tasca</label>
          <div style="display:flex;gap:.5rem;align-items:center">
            <button id="openTaskPicker" type="button">Selecciona tasca</button>
            <span id="taskSelectedPreview" class="muted">Cap tasca seleccionada</span>
          </div>
          <input type="hidden" id="taskId" />

          <label>Data i hora
            <input type="datetime-local" id="dateISO" />
          </label>
          <label>Qualitat</label>
            <div style="display:flex;align-items:center;gap:.5rem">
              <div id="qualityStars" class="stars" data-value="3" aria-label="Qualitat 1 a 5">
                <span class="star" data-val="1">☆</span>
                <span class="star" data-val="2">☆</span>
                <span class="star" data-val="3">☆</span>
                <span class="star" data-val="4">☆</span>
                <span class="star" data-val="5">☆</span>
              </div>
              <small id="qualityLabel" class="muted">3/5</small>
            </div>
            <input type="hidden" id="quality" value="3">

          <label>Notes
            <textarea id="notes" rows="2" placeholder="Detalls, incidències…"></textarea>
          </label>
          <button type="submit">Afegir registre</button>
        </form>
      </article>

      <!-- Dreta: Classificació (a dalt) + Tasques registrades (a sota) -->
      <div class="grid right-col">
        <article class="card">
          <h3>Classificació <span id="rangeLabel" class="muted"></span></h3>
          <div id="leaderboard"></div>
        </article>

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
      </div>
    </section>

    <!-- ÚLTIM BLOC: Configuració (a baix de tot, 100% ample) -->
    <section class="grid">
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
          <form id="photoForm" class="grid" enctype="multipart/form-data" style="margin-top:.5rem">
            <select id="photoMemberId" required></select>
            <input type="file" id="photoFile" accept="image/*" required />
            <button>Puja foto</button>
          </form>
          <p class="muted">Si no hi ha foto, es mostra un avatar per defecte.</p>
        </details>
        <details open>
          <summary>Tasques</summary>
          <form id="taskForm" class="grid" enctype="multipart/form-data">
            <input id="taskName" name="name" placeholder="Nom de la tasca" required />
            <input id="taskCategory" name="category" placeholder="Categoria (ex: Cuina, Neteja…)" />
            <input id="taskPoints" name="base_points" type="number" min="1" value="10" />
            <input id="taskEmoji" name="icon" placeholder="Emoji (opcional) ex: 🧹" maxlength="8" />
            <input id="taskIconImg" name="icon_img" type="file" accept="image/*" />
            <button>Crea tasca</button>
          </form>
          <div id="tasksList" style="margin-top:.5rem"></div>
        </details>

      </article>
    </section>
    <!-- Modal selector de tasques -->
    <dialog id="taskPicker">
      <article style="max-width:820px">
        <header style="display:flex;justify-content:space-between;align-items:center">
          <strong>Selecciona una tasca</strong>
          <button id="taskPickerClose" class="secondary" aria-label="Tanca">✕</button>
        </header>

        <input id="taskPickerSearch" placeholder="Cerca…" style="width:100%;margin:.5rem 0">

        <div id="taskPickerContent" class="grid" style="grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:.75rem"></div>

        <footer style="display:flex;justify-content:flex-end;margin-top:.5rem">
          <button id="taskPickerCancel" class="secondary">Cancel·la</button>
        </footer>
      </article>
    </dialog>

  </main>

  <!-- JS principal separat -->
  <script src="assets/app.js"></script>

  <!-- petit script per mostrar el banner d'instal·lació quan cal -->
  <script>
    async function checkInstall(){
      try {
        const r = await fetch('api.php?action=list_all', { headers:{'Accept':'application/json'} });
        const txt = await r.text();
        JSON.parse(txt);
        document.getElementById('installBanner').style.display = 'none';
      } catch(e) {
        const b = document.getElementById('installBanner');
        if (b) b.style.display = 'block';
      }
    }
    document.addEventListener('DOMContentLoaded', checkInstall);
  </script>
</body>
</html>
