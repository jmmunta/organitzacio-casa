-- schema.sql (versió robusta: InnoDB + utf8mb4 + ordre segur)

-- Evitem problemes d'FK i netegem taules velles
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS entries;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS members;

SET FOREIGN_KEY_CHECKS = 1;

-- Taula de membres
CREATE TABLE members (
  id   INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  role VARCHAR(100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Taula de tasques
CREATE TABLE tasks (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  name        VARCHAR(150) NOT NULL,
  base_points INT NOT NULL DEFAULT 10,
  icon        VARCHAR(10) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registres (FK -> members, tasks)
CREATE TABLE entries (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  task_id   INT NOT NULL,
  date_iso  DATETIME NOT NULL,
  quality   TINYINT NOT NULL DEFAULT 3,
  notes     TEXT,
  INDEX idx_member (member_id),
  INDEX idx_task (task_id),
  CONSTRAINT fk_entries_member
    FOREIGN KEY (member_id) REFERENCES members(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_entries_task
    FOREIGN KEY (task_id) REFERENCES tasks(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
