-- Organització Casa – Esquema compatible MySQL 5.6/5.7

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS entries;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS families;

SET FOREIGN_KEY_CHECKS = 1;

-- FAMÍLIES
CREATE TABLE families (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(150) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- USUARIS
CREATE TABLE users (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  family_id      INT NOT NULL,
  email          VARCHAR(190) NOT NULL,           -- 190 per UNIQUE en utf8mb4
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('admin','member') NOT NULL DEFAULT 'admin',
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_family (family_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MEMBRES
CREATE TABLE members (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  family_id  INT NOT NULL,
  user_id    INT NULL,
  name       VARCHAR(100) NOT NULL,
  role       VARCHAR(100) NULL,
  photo      VARCHAR(255) NULL,
  KEY idx_members_family (family_id),
  KEY idx_members_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TASQUES (emoji + imatge; es pot tenir una, l'altra o totes dues)
CREATE TABLE tasks (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  family_id    INT NOT NULL,
  name         VARCHAR(150) NOT NULL,
  base_points  INT NOT NULL DEFAULT 10,
  icon         VARCHAR(10) NULL,       -- emoji
  icon_img     VARCHAR(255) NULL,      -- imatge (ruta relativa)
  category     VARCHAR(80) NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tasks_family (family_id),
  KEY idx_tasks_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- REGISTRES
CREATE TABLE entries (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  family_id  INT NOT NULL,
  member_id  INT NOT NULL,
  task_id    INT NOT NULL,
  date_iso   DATETIME NOT NULL,
  quality    TINYINT NOT NULL DEFAULT 3,
  notes      TEXT NULL,
  KEY idx_entries_family (family_id),
  KEY idx_entries_member (member_id),
  KEY idx_entries_task (task_id),
  KEY idx_entries_date (date_iso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FKs (fora dels CREATE per compatibilitat)
ALTER TABLE users
  ADD CONSTRAINT fk_users_family
  FOREIGN KEY (family_id) REFERENCES families(id)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE members
  ADD CONSTRAINT fk_members_family
  FOREIGN KEY (family_id) REFERENCES families(id)
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_members_user
  FOREIGN KEY (user_id) REFERENCES users(id)
  ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE tasks
  ADD CONSTRAINT fk_tasks_family
  FOREIGN KEY (family_id) REFERENCES families(id)
  ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE entries
  ADD CONSTRAINT fk_entries_family
  FOREIGN KEY (family_id) REFERENCES families(id)
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_entries_member
  FOREIGN KEY (member_id) REFERENCES members(id)
  ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_entries_task
  FOREIGN KEY (task_id) REFERENCES tasks(id)
  ON DELETE CASCADE ON UPDATE CASCADE;

-- Unicitat de nom de tasca dins la família (composite UNIQUE segur en 5.6)
ALTER TABLE tasks
  ADD UNIQUE KEY uq_tasks_family_name (family_id, name);
