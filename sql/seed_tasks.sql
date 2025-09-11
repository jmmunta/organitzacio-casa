-- SEED/UPDATE de tasques amb categories i emoji
-- Crea una família de prova si no n'hi ha cap
INSERT INTO families(name)
SELECT 'Família de prova'
WHERE NOT EXISTS (SELECT 1 FROM families);

-- (Opcional però recomanat) Evitar duplicats: nom únic per família
-- CREA primer aquest índex si no existeix al teu esquema:
-- ALTER TABLE tasks ADD UNIQUE KEY uq_tasks_family_name (family_id, name);

-- Helper: per fer UPDATE si ja existeix i INSERT si no, repetim patró per a cada tasca.

-- ========= CUINA / RESIDUS =========
-- Parar taula (6) 🍽️
UPDATE tasks t
JOIN families f ON 1=1
SET t.base_points=6, t.category='Cuina / residus', t.icon='🍽️'
WHERE t.family_id=f.id AND t.name='Parar taula';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Parar taula',6,'🍽️','Cuina / residus'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Parar taula');

-- Desparar taula (4) 🍽️
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=4, t.category='Cuina / residus', t.icon='🍽️'
WHERE t.family_id=f.id AND t.name='Desparar taula';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Desparar taula',4,'🍽️','Cuina / residus'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Desparar taula');

-- Posar rentavaixelles (10) 🧽
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Cuina / residus', t.icon='🧽'
WHERE t.family_id=f.id AND t.name='Posar rentavaixelles';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Posar rentavaixelles',10,'🧽','Cuina / residus'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Posar rentavaixelles');

-- Treure rentavaixelles (10) 🧽
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Cuina / residus', t.icon='🧽'
WHERE t.family_id=f.id AND t.name='Treure rentavaixelles';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Treure rentavaixelles',10,'🧽','Cuina / residus'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Treure rentavaixelles');

-- Endreçar cuina (10) 🧼
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Cuina / residus', t.icon='🧼'
WHERE t.family_id=f.id AND t.name='Endreçar cuina';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Endreçar cuina',10,'🧼','Cuina / residus'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Endreçar cuina');

-- Treure la brossa (10) 🗑️
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Cuina / residus', t.icon='🗑️'
WHERE t.family_id=f.id AND t.name='Treure la brossa';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Treure la brossa',10,'🗑️','Cuina / residus'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Treure la brossa');

-- Preparar esmorzars (10) 🥪
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Cuina / residus', t.icon='🥪'
WHERE t.family_id=f.id AND t.name='Preparar esmorzars';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Preparar esmorzars',10,'🥪','Cuina / residus'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Preparar esmorzars');

-- Cuinar (14) 🥪
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=14, t.category='Cuina / residus', t.icon='🥪'
WHERE t.family_id=f.id AND t.name='Cuinar';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Cuinar',14,'🥪','Cuina / residus'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Cuinar');

-- Fer la compra (14) 🛒
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=14, t.category='Cuina / residus', t.icon='🛒'
WHERE t.family_id=f.id AND t.name='Fer la compra';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Fer la compra',14,'🛒','Cuina / residus'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Fer la compra');

-- ========= NETEJA GENERAL =========
-- Aspirar (12) 🧹
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=12, t.category='Neteja general (casa)', t.icon='🧹'
WHERE t.family_id=f.id AND t.name='Aspirar';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Aspirar',12,'🧹','Neteja general (casa)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Aspirar');

-- Fregar el terra (14) 🪣
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=14, t.category='Neteja general (casa)', t.icon='🪣'
WHERE t.family_id=f.id AND t.name='Fregar el terra';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Fregar el terra',14,'🪣','Neteja general (casa)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Fregar el terra');

-- Treure la pols (10) 🧴
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Neteja general (casa)', t.icon='🧴'
WHERE t.family_id=f.id AND t.name='Treure la pols';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Treure la pols',10,'🧴','Neteja general (casa)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Treure la pols');

-- Netejar bany (lavabo) (14) 🚽
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=14, t.category='Neteja general (casa)', t.icon='🚽'
WHERE t.family_id=f.id AND t.name='Netejar bany (lavabo)';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Netejar bany (lavabo)',14,'🚽','Neteja general (casa)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Netejar bany (lavabo)');

-- Netejar dutxa/banyera (14) 🛁
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=14, t.category='Neteja general (casa)', t.icon='🛁'
WHERE t.family_id=f.id AND t.name='Netejar dutxa/banyera';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Netejar dutxa/banyera',14,'🛁','Neteja general (casa)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Netejar dutxa/banyera');

-- Netejar miralls (8) 🪞
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=8, t.category='Neteja general (casa)', t.icon='🪞'
WHERE t.family_id=f.id AND t.name='Netejar miralls';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Netejar miralls',8,'🪞','Neteja general (casa)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Netejar miralls');

-- Canviar tovalloles (6) 🧻
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=6, t.category='Neteja general (casa)', t.icon='🧻'
WHERE t.family_id=f.id AND t.name='Canviar tovalloles';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Canviar tovalloles',6,'🧻','Neteja general (casa)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Canviar tovalloles');

-- Canviar sabó (4) 🧴
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=4, t.category='Neteja general (casa)', t.icon='🧴'
WHERE t.family_id=f.id AND t.name='Canviar sabó';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Canviar sabó',4,'🧴','Neteja general (casa)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Canviar sabó');

-- ========= ROBA =========
-- Canviar llençols (4) 🛏️
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=4, t.category='Roba (bugada)', t.icon='🛏️'
WHERE t.family_id=f.id AND t.name='Canviar llençols';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Canviar llençols',4,'🛏️','Roba (bugada)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Canviar llençols');

-- Posar rentadora (6) 🧺
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=6, t.category='Roba (bugada)', t.icon='🧺'
WHERE t.family_id=f.id AND t.name='Posar rentadora';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Posar rentadora',6,'🧺','Roba (bugada)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Posar rentadora');

-- Estendre la roba (10) 🧦
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Roba (bugada)', t.icon='🧦'
WHERE t.family_id=f.id AND t.name='Estendre la roba';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Estendre la roba',10,'🧦','Roba (bugada)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Estendre la roba');

-- Plegar la roba (10) 👕
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Roba (bugada)', t.icon='👕'
WHERE t.family_id=f.id AND t.name='Plegar la roba';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Plegar la roba',10,'👕','Roba (bugada)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Plegar la roba');

-- Guardar la roba (10) 🧩
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Roba (bugada)', t.icon='🧩'
WHERE t.family_id=f.id AND t.name='Guardar la roba';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Guardar la roba',10,'🧩','Roba (bugada)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Guardar la roba');

-- ========= HABITACIONS / ESCOLA =========
-- Fer el llit (6) 🛏️
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=6, t.category='Habitacions / escola', t.icon='🛏️'
WHERE t.family_id=f.id AND t.name='Fer el llit';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Fer el llit',6,'🛏️','Habitacions / escola'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Fer el llit');

-- Endreçar habitació (9) 🧸
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=9, t.category='Habitacions / escola', t.icon='🧸'
WHERE t.family_id=f.id AND t.name='Endreçar habitació';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Endreçar habitació',9,'🧸','Habitacions / escola'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Endreçar habitació');

-- Endreçar joguines (8) 🧸
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=8, t.category='Habitacions / escola', t.icon='🧸'
WHERE t.family_id=f.id AND t.name='Endreçar joguines';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Endreçar joguines',8,'🧸','Habitacions / escola'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Endreçar joguines');

-- Motxilla escola preparada (8) 🎒
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=8, t.category='Habitacions / escola', t.icon='🎒'
WHERE t.family_id=f.id AND t.name='Motxilla escola preparada';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Motxilla escola preparada',8,'🎒','Habitacions / escola'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Motxilla escola preparada');

-- Deures fets (12) ✏️
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=12, t.category='Habitacions / escola', t.icon='✏️'
WHERE t.family_id=f.id AND t.name='Deures fets';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Deures fets',12,'✏️','Habitacions / escola'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Deures fets');

-- Lectura 15 minuts (6) 📚
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=6, t.category='Habitacions / escola', t.icon='📚'
WHERE t.family_id=f.id AND t.name='Lectura 15 minuts';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Lectura 15 minuts',6,'📚','Habitacions / escola'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Lectura 15 minuts');

-- ========= HÀBITS PERSONALS =========
-- Dutxa (6) 🚿
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=6, t.category='Hàbits personals', t.icon='🚿'
WHERE t.family_id=f.id AND t.name='Dutxa';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Dutxa',6,'🚿','Hàbits personals'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Dutxa');

-- Raspallar dents (4) 🪥
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=4, t.category='Hàbits personals', t.icon='🪥'
WHERE t.family_id=f.id AND t.name='Raspallar dents';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Raspallar dents',4,'🪥','Hàbits personals'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Raspallar dents');

-- Pentinar-se (3) 💇
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=3, t.category='Hàbits personals', t.icon='💇'
WHERE t.family_id=f.id AND t.name='Pentinar-se';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Pentinar-se',3,'💇','Hàbits personals'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Pentinar-se');

-- Preparar la roba de demà (6) 👚
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=6, t.category='Hàbits personals', t.icon='👚'
WHERE t.family_id=f.id AND t.name='Preparar la roba de demà';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Preparar la roba de demà',6,'👚','Hàbits personals'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Preparar la roba de demà');

-- Recollir roba bruta (5) 🧦
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=5, t.category='Hàbits personals', t.icon='🧦'
WHERE t.family_id=f.id AND t.name='Recollir roba bruta';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Recollir roba bruta',5,'🧦','Hàbits personals'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Recollir roba bruta');

-- ========= MASCOTA (GAT) =========
-- Donar menjar al gat (6) 🐱
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=6, t.category='Mascota (gat)', t.icon='🐱'
WHERE t.family_id=f.id AND t.name='Donar menjar al gat';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Donar menjar al gat',6,'🐱','Mascota (gat)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Donar menjar al gat');

-- Canviar aigua del gat (5) 💧
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=5, t.category='Mascota (gat)', t.icon='💧'
WHERE t.family_id=f.id AND t.name='Canviar aigua del gat';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Canviar aigua del gat',5,'💧','Mascota (gat)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Canviar aigua del gat');

-- Netejar sorral del gat (10) 🧴
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Mascota (gat)', t.icon='🧴'
WHERE t.family_id=f.id AND t.name='Netejar sorral del gat';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Netejar sorral del gat',10,'🧴','Mascota (gat)'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Netejar sorral del gat');

-- ========= PLANTES / EXTERIOR =========
-- Regar plantes (6) 🪴
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=6, t.category='Plantes / exterior', t.icon='🪴'
WHERE t.family_id=f.id AND t.name='Regar plantes';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Regar plantes',6,'🪴','Plantes / exterior'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Regar plantes');

-- Endreçar balcó/terrassa (10) 🏡
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=10, t.category='Plantes / exterior', t.icon='🏡'
WHERE t.family_id=f.id AND t.name='Endreçar balcó/terrassa';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Endreçar balcó/terrassa',10,'🏡','Plantes / exterior'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Endreçar balcó/terrassa');

-- ========= ALTRES =========
-- Ajudar un germà/una germana (6) 🤝
UPDATE tasks t JOIN families f ON 1=1
SET t.base_points=6, t.category='Altres', t.icon='🤝'
WHERE t.family_id=f.id AND t.name='Ajudar un germà/una germana';
INSERT INTO tasks(family_id,name,base_points,icon,category)
SELECT f.id,'Ajudar un germà/una germana',6,'🤝','Altres'
FROM families f
WHERE NOT EXISTS (SELECT 1 FROM tasks t WHERE t.family_id=f.id AND t.name='Ajudar un germà/una germana');
