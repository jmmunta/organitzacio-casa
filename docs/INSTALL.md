# Instal·lació · Organització Casa (PHP + MySQL)

Guia pas a pas per instal·lar l’aplicació en qualsevol hosting amb PHP i MySQL.

---

## Requisits

* **PHP 8.0+** amb extensió **PDO MySQL** activada.
* **MySQL/MariaDB** (permís per crear taules).
* Accés via **FTP** o **git** al servidor.
* Opcional: compte a **GitHub** per clonar/actualitzar el codi.

---

## Estructura de carpetes

```
projecte/
├─ public/
│  ├─ index.php
│  ├─ api.php
│  ├─ db.php
│  ├─ config.php               ← credencials de la BD
│  ├─ migrate.php              ← instal·lador via web (elimina’l després!)
│  └─ assets/ (opcional)
│     ├─ app.js
│     └─ styles.css
├─ sql/
│  ├─ schema.sql               ← crea taules
│  └─ seed_tasks.sql           ← dades inicials (tasques + icones)
└─ docs/
   └─ INSTALL.md               ← aquest document
```

> **Seguretat:** no pugis mai `config.php` amb claus reals a un repo públic.

---

## 1) Obtenir el codi

**Opció A – GitHub (recomanat)**

1. Crea/obre el repo a GitHub.
2. `git clone https://github.com/<usuari>/<repo>.git`
3. Entra a la carpeta del projecte.

**Opció B – ZIP**

1. Descarrega el ZIP de GitHub.
2. Descomprimeix i puja’l via FTP al servidor.

---

## 2) Configurar la base de dades

Edita `public/config.php` amb les teves credencials:

```php
<?php
$servername = "localhost";
$username   = "USUARI";
$password   = "CONTRASENYA";
$dbname     = "NOM_BASE_DADES";
```

---

## 3) Crear taules i dades inicials

Hi ha **dues formes**. Tria’n una.

### A) Instal·lador via web (fàcil)

1. Assegura’t que `migrate.php` és a `public/`.
2. Obre al navegador: `https://EL_TEU_DOMINI/migrate.php`.
3. Tria una opció i prem **Executa**:

   * *schema* → crea taules
   * *seed* → insereix tasques inicials
   * *both* → totes dues
4. **Elimina `migrate.php`** del servidor un cop acabat.

### B) Manual (phpMyAdmin / consola)

1. Importa `sql/schema.sql`.
2. (Opcional) Importa `sql/seed_tasks.sql` per tenir tasques amb icones.

---

## 4) Provar l’aplicació

1. Obre `https://EL_TEU_DOMINI/index.php`.
2. A l’apartat **Configuració**:

   * Afegeix **membres** (nom + rol/edat).
   * Afegeix **tasques** (nom, punts base i **icona** opcional — ex. `🧹`).
3. A **Registrar tasca feta**, tria membre, tasca, data, qualitat i notes.
4. Revisa el **rànquing** i la llista de registres.

---

## 5) Actualitzacions del codi

* Si uses **git** al servidor: `git pull` a la carpeta del projecte.
* Si uses **FTP**: puja només els fitxers que han canviat.
* Canvis d’esquema vindran com a fitxers a `sql/` (migracions). S’indicarà quan calgui executar-los.

---

## 6) Problemes freqüents

**Pantalla buida o JSON invàlid**

* Revisa `public/api.php` i `public/index.php` estan actualitzats.
* Comprova que **PDO MySQL** està actiu al servidor.

**No crea taules / error de connexió**

* Credencials a `public/config.php`.
* Prova `migrate.php` i llegeix el missatge d’error.

**No es desen membres/tasques**

* Mira el banner d’estat a dalt de `index.php` (vermell = error API).
* Assegura’t que la taula `tasks` té la columna `icon` si l’API l’està usant.

**Emojis no es veuen bé**

* Comprova que el fitxer i la pàgina estan en **UTF-8** (capçaleres i arxius).

---

## 7) Seguretat

* **Elimina `migrate.php`** després de l’ús.
* Mantén el servidor amb **HTTPS**.
* Fes **backups** periòdics de la base de dades (`members`, `tasks`, `entries`).

---

## 8) Desinstal·lar

1. Esborra la carpeta del projecte del servidor.
2. (Opcional) Esborra les taules `members`, `tasks`, `entries` de la base de dades.

---

## 9) Contribuir (opcional)

* Fes *fork* del repo i obre **Pull Requests** amb canvis petits i descrits.
* Afegeix proves manuals (què has fet, com provar-ho).
* No incloguis claus reals a les *commits*.

---

## Annex: Execució SQL per consola (exemple)

```bash
# Crear taules
mysql -u USUARI -p NOM_BASE_DADES < sql/schema.sql

# Dades inicials (tasques + icones)
mysql -u USUARI -p NOM_BASE_DADES < sql/seed_tasks.sql
```

---

> Dubtes o errors? Obre una *issue* al repositori o comparteix el missatge d’error i t’ajudem a resoldre-ho.
