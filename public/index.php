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

    <!-- Banner d’estat -->
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

  <!-- JS principal separat -->
  <script src="assets/app.js"></script>
</body>
</html>
