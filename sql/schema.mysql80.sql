-- Organització Casa – Esquema per MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS entries;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS members;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS families;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================
--  FAMÍLIES
-- =========================
CREATE TABLE families (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
--  USUARIS
-- =========================
CREATE TABLE users (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  family_id      INT NOT NULL,
  email          VARCHAR(190) NOT NULL,             -- UNIQUE segur amb utf8mb4
  password_hash  VARCHAR(255) NOT NULL,
  role           ENUM('admin','member') NOT NULL DEFAULT 'admin',
  created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_family
    FOREIGN KEY (family_id) REFERENCES families(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_family (family_id),
  CHECK (role IN ('admin','member'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
--  MEMBRES
-- =========================
CREATE TABLE members (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  family_id  INT NOT NULL,
  user_id    INT NULL,
  name       VARCHAR(100) NOT NULL,
  role       VARCHAR(100) NULL,
  photo      VARCHAR(255) NULL,      -- ruta (uploads/members/...)
  created_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_members_family
    FOREIGN KEY (family_id) REFERENCES families(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_members_user
    FOREIGN KEY (user_id)   REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  KEY idx_members_family (family_id),
  KEY idx_members_user    (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
--  TASQUES
-- =========================
CREATE TABLE tasks (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  family_id    INT NOT NULL,
  name         VARCHAR(150) NOT NULL,
  base_points  INT NOT NULL DEFAULT 10,
  icon         VARCHAR(16) NULL,     -- emoji opcional
  icon_img     VARCHAR(255) NULL,    -- imatge (uploads/tasks/t_123.png)
  category     VARCHAR(80) NULL,     -- ex: 'Cuina / residus'
  created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tasks_family
    FOREIGN KEY (family_id) REFERENCES families(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE KEY uq_tasks_family_name (family_id, name),
  KEY idx_tasks_family   (family_id),
  KEY idx_tasks_category (category),
  CHECK (base_points >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================
--  REGISTRES (ENTRADES)
-- =========================
CREATE TABLE entries (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  family_id  INT NOT NULL,
  member_id  INT NOT NULL,
  task_id    INT NOT NULL,
  date_iso   DATETIME NOT NULL,
  quality    TINYINT  NOT NULL DEFAULT 3,  -- 1..5
  notes      TEXT NULL,
  created_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_entries_family
    FOREIGN KEY (family_id) REFERENCES families(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_entries_member
    FOREIGN KEY (member_id) REFERENCES members(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_entries_task
    FOREIGN KEY (task_id)   REFERENCES tasks(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  KEY idx_entries_family (family_id),
  KEY idx_entries_member (member_id),
  KEY idx_entries_task   (task_id),
  KEY idx_entries_date   (date_iso),
  CHECK (quality BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
