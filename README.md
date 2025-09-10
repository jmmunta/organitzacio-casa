# organitzacio-casa
Aplicació per gamificar les tasques de la casa i els hàbits del dia a dia

# Organització Casa · Tasques familiars (PHP + MySQL)

Aplicació senzilla per gestionar les tasques domèstiques d’una família (5 membres per defecte), registrar qui fa què, quan i com de bé, i veure un rànquing de punts. Dissenyada perquè la pugui usar canalla (icones per a tasques).

## ✨ Funcionalitats
- Alta de **membres** (nom + rol/edat).
- Alta de **tasques** amb **punts base** i **icona** (emoji).
- **Registre** de tasques: membre, tasca, data/hora, qualitat (1–5), notes.
- **Classificació** per període (setmana/mes/tot).
- **Instal·lador web** (`migrate.php`) per crear taules i dades inicials.
- Frontend simple amb [Pico.css](https://picocss.com/), sense dependències pesades.

## 🧱 Stack
- **PHP 8+**, **PDO MySQL**
- **MySQL/MariaDB**
- HTML/CSS/JS (vanilla)

## 📦 Estructura

projecte/
├─ public/
│ ├─ index.php
│ ├─ api.php
│ ├─ db.php
│ ├─ config.sample.php ← exemple; copia'l a config.php
│ ├─ config.php ← NO el posis al repo (afegit a .gitignore)
│ ├─ migrate.php ← instal·lador web (elimina'l després)
│ └─ assets/
│ └─ app.js
├─ sql/
│ ├─ schema.sql ← taules
│ └─ seed_tasks.sql ← tasques base + icones
└─ docs/
└─ INSTALL.md


## 🚀 Instal·lació ràpida
1. **Configura la BD**
   - Copia `public/config.sample.php` a `public/config.php` i edita:
     ```php
     $servername = "localhost";
     $username   = "USUARI";
     $password   = "CONTRASENYA";
     $dbname     = "NOM_BASE_DADES";
     ```
2. **Crea taules i dades**
   - Obre al navegador `migrate.php` (ex: `https://EL_TEU_DOMINI/migrate.php`) i tria **both** (schema + seed).
   - Quan acabi **ELIMINA `migrate.php`** per seguretat.
3. **Entra a l’app**
   - `index.php` → configura membres/tasques i registra activitats.

> Guia detallada a `docs/INSTALL.md`.

## 🧩 Ús bàsic
- **Configuració**: afegeix membres + tasques (pots posar icones/emoji a cada tasca).
- **Registrar tasca**: tria membre, tasca, data/hora, qualitat i notes.
- **Rànquing**: canvia el filtre (setmana/mes/tot).

## 🗺️ Roadmap (properes versions)
1. **Accés amb contrasenya** i rols (pare/mare admin, cada família aïllada).
2. **Agrupacions de tasques** i selector més visual (categories → tasca).
3. **Rànquing percentual**: objectius setmanals/diaris per membre i % assolit.

Vegeu `docs/roadmap.md` (quan estigui disponible) i els *issues* del repo.

## 🤝 Contribuir
- Fes *fork* i obre un **Pull Request** amb canvis petits i clars.
- No incloguis credencials reals ni fitxers secrets.
- Si canvies esquema, afegeix una migració a `sql/` i actualitza `schema.sql`.

## 🔒 Seguretat
- **No facis commit** de `public/config.php`. Mantén-lo al `.gitignore`.
- Si per error s’ha publicat, **rota contrasenyes** i neteja l’historial (mira avall).

## 📄 Llicència
[MIT](LICENSE)
