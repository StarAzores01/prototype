-- ============================================================
-- PAThrive Database Schema
-- Database: pathrive_db
-- System: PAThrive – Extension Training Management &
--         Skills Utilization Tracking System
-- College of Industrial Technology, SLSU
-- ============================================================

CREATE DATABASE IF NOT EXISTS pathrive_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pathrive_db;

-- ============================================================
-- USERS (Extension Coordinators, Trainers)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(60)  NOT NULL UNIQUE,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  NOT NULL,
  email         VARCHAR(120) NOT NULL UNIQUE,
  id_number     VARCHAR(40)  NOT NULL UNIQUE,
  position      ENUM('Professor','Assistant Professor','Instructor') NOT NULL,
  role          ENUM('extension_coordinator','trainer') NOT NULL DEFAULT 'trainer',
  password_hash VARCHAR(255) NOT NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  reset_token   VARCHAR(100) NULL,
  reset_expires DATETIME     NULL,
  last_login    DATETIME     NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TRAININGS
-- ============================================================
CREATE TABLE IF NOT EXISTS trainings (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  title           VARCHAR(200) NOT NULL,
  area            VARCHAR(120) NOT NULL,
  description     TEXT         NULL,
  date_start      DATE         NULL,
  date_end        DATE         NULL,
  status          ENUM('Proposed','Approved','Ongoing','Completed') NOT NULL DEFAULT 'Proposed',
  trainer_id      INT          NULL,
  target_participants INT      NOT NULL DEFAULT 0,
  created_by      INT          NOT NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (trainer_id)  REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PARTICIPANTS / BENEFICIARIES
-- ============================================================
CREATE TABLE IF NOT EXISTS participants (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(160) NOT NULL,
  id_number     VARCHAR(40)  NULL,
  training_id   INT          NOT NULL,
  barangay      VARCHAR(120) NULL,
  municipality  VARCHAR(120) NULL,
  beneficiary_type ENUM('Out-of-School Youth','Displaced Worker','Solo Parent','Farmer','Retiree','Other') NOT NULL DEFAULT 'Other',
  attendance    VARCHAR(20)  NULL DEFAULT '0/0',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- DOCUMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS documents (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  file_name     VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  file_type     VARCHAR(20)  NOT NULL,
  file_size     INT          NOT NULL DEFAULT 0,
  training_id   INT          NULL,
  uploaded_by   INT          NOT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE SET NULL,
  FOREIGN KEY (uploaded_by) REFERENCES users(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- EVALUATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS evaluations (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  training_id   INT          NOT NULL,
  participant_id INT         NOT NULL,
  rating        TINYINT      NULL CHECK (rating BETWEEN 1 AND 5),
  feedback      TEXT         NULL,
  submitted_at  DATETIME     NULL,
  status        ENUM('Pending','Submitted') NOT NULL DEFAULT 'Pending',
  FOREIGN KEY (training_id)   REFERENCES trainings(id)   ON DELETE CASCADE,
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SKILLS UTILIZATION
-- ============================================================
CREATE TABLE IF NOT EXISTS skills_utilization (
  id                  INT AUTO_INCREMENT PRIMARY KEY,
  training_id         INT            NOT NULL,
  personal_use_pct    DECIMAL(5,2)   NOT NULL DEFAULT 0,
  income_gen_pct      DECIMAL(5,2)   NOT NULL DEFAULT 0,
  employment_pct      DECIMAL(5,2)   NOT NULL DEFAULT 0,
  nc2_cert_pct        DECIMAL(5,2)   NOT NULL DEFAULT 0,
  recorded_at         DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TRAINER WHITELIST (pre-approved trainers by superadmin)
-- ============================================================
CREATE TABLE IF NOT EXISTS trainer_whitelist (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  NOT NULL,
  specialization VARCHAR(120) NOT NULL,
  id_number     VARCHAR(20)  NULL,
  is_registered TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================
-- Default extension coordinator (password: Admin@1234)
INSERT INTO users (username, first_name, last_name, email, id_number, position, role, password_hash, is_active)
VALUES ('anacruz', 'Ana', 'Cruz', 'ana.cruz@slsu.edu.ph', 'EC-2024-001', 'Professor', 'extension_coordinator',
        '$2y$12$eImiTXuWVxfM37uY4JANjOe5XceXjYguwuwnoJDR0o.OtZnomeRHm', 1);

-- Pre-approved trainer whitelist
INSERT INTO trainer_whitelist (first_name, last_name, specialization) VALUES
('Jerwin',  'Campita',   'Mechanical Technology'),
('Angelito','Mangubat',  'Automotive Technology'),
('Reynaldo','Danganan',  'Computer Technology'),
('Jose',    'Sanvictores','Electronics Technology'),
('Aurita',  'Laguador',  'Culinary Technology'),
('Maricel', 'Lingatong', 'Apparel and Fashion Technology'),
('Lendel',  'Racelis',   'Print Media Technology'),
('Devie',   'Bello',     'Information Technology');

-- ============================================================
-- BENEFICIARIES (self-registered participants)
-- ============================================================
CREATE TABLE IF NOT EXISTS beneficiaries (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(60)  NOT NULL UNIQUE,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  NOT NULL,
  email         VARCHAR(120) NOT NULL UNIQUE,
  contact       VARCHAR(30)  NULL,
  barangay      VARCHAR(120) NULL,
  municipality  VARCHAR(120) NULL,
  password_hash VARCHAR(255) NOT NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Link beneficiaries to participants records
ALTER TABLE participants ADD COLUMN IF NOT EXISTS beneficiary_id INT NULL,
  ADD FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE SET NULL;

-- ============================================================
-- BENEFICIARIES table update — add age, sex, address, phone
-- ============================================================
ALTER TABLE beneficiaries
  ADD COLUMN IF NOT EXISTS phone   VARCHAR(30)  NULL AFTER email,
  ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS age     TINYINT      NULL AFTER address,
  ADD COLUMN IF NOT EXISTS sex     ENUM('Male','Female','Other') NULL AFTER age;

-- PARTICIPANTS table update — add phone, address, age, sex
ALTER TABLE participants
  ADD COLUMN IF NOT EXISTS phone   VARCHAR(30)  NULL AFTER id_number,
  ADD COLUMN IF NOT EXISTS address VARCHAR(255) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS age     TINYINT      NULL AFTER address,
  ADD COLUMN IF NOT EXISTS sex     ENUM('Male','Female','Other') NULL AFTER age;

-- ============================================================
-- EVALUATION FORMS (form builder by EC)
-- ============================================================
CREATE TABLE IF NOT EXISTS eval_forms (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  training_id INT NOT NULL,
  title       VARCHAR(200) NOT NULL DEFAULT 'Training Evaluation Form',
  fields      JSON NOT NULL,
  created_by  INT NOT NULL,
  sent_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE IF NOT EXISTS notifications (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NULL,
  role        ENUM('trainer','beneficiary','all') NOT NULL DEFAULT 'all',
  training_id INT NULL,
  message     TEXT NOT NULL,
  link        VARCHAR(255) NULL,
  is_read     TINYINT(1) NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Add visibility column to documents if not present
ALTER TABLE documents ADD COLUMN IF NOT EXISTS visibility ENUM('private','ec_trainer','public') NOT NULL DEFAULT 'public';

-- Add id_number to trainer_whitelist for auto-generated TR IDs
ALTER TABLE trainer_whitelist ADD COLUMN IF NOT EXISTS id_number VARCHAR(20) NULL AFTER specialization;

-- ============================================================
-- ATTENDANCE
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  training_id    INT          NOT NULL,
  participant_id INT          NOT NULL,
  session_date   DATE         NOT NULL,
  status         ENUM('Present','Absent','Late') NOT NULL DEFAULT 'Absent',
  time_in        TIME         NULL,
  time_out       TIME         NULL,
  recorded_by    INT          NOT NULL,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attendance (training_id, participant_id, session_date),
  FOREIGN KEY (training_id)    REFERENCES trainings(id)    ON DELETE CASCADE,
  FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
  FOREIGN KEY (recorded_by)    REFERENCES users(id)        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SKILLS FORMS (form builder by trainers)
-- ============================================================
CREATE TABLE IF NOT EXISTS skills_forms (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  training_id INT NOT NULL,
  title       VARCHAR(200) NOT NULL DEFAULT 'Skills Utilization Survey',
  fields      JSON NOT NULL,
  created_by  INT NOT NULL,
  sent_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- EVALUATION RESPONSES (beneficiary answers to eval forms)
-- ============================================================
CREATE TABLE IF NOT EXISTS eval_responses (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  form_id        INT NOT NULL,
  training_id    INT NOT NULL,
  beneficiary_id INT NOT NULL,
  responses      JSON NOT NULL,
  submitted_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_eval_resp (form_id, beneficiary_id),
  FOREIGN KEY (form_id)        REFERENCES eval_forms(id)    ON DELETE CASCADE,
  FOREIGN KEY (training_id)    REFERENCES trainings(id)     ON DELETE CASCADE,
  FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SKILLS RESPONSES (beneficiary answers to skills forms)
-- ============================================================
CREATE TABLE IF NOT EXISTS skills_responses (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  form_id        INT NOT NULL,
  training_id    INT NOT NULL,
  beneficiary_id INT NOT NULL,
  responses      JSON NOT NULL,
  submitted_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_skills_resp (form_id, beneficiary_id),
  FOREIGN KEY (form_id)        REFERENCES skills_forms(id)  ON DELETE CASCADE,
  FOREIGN KEY (training_id)    REFERENCES trainings(id)     ON DELETE CASCADE,
  FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- TRAINING DOCUMENTATION PHOTOS (uploaded by trainers)
-- ============================================================
CREATE TABLE IF NOT EXISTS training_docs (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  training_id INT NOT NULL,
  caption     VARCHAR(255) NULL,
  file_name   VARCHAR(255) NOT NULL,
  file_type   VARCHAR(20)  NOT NULL,
  uploaded_by INT NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- CONTACT MESSAGES (from public contact form)
-- ============================================================
CREATE TABLE IF NOT EXISTS contact_messages (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  name       VARCHAR(160) NOT NULL,
  email      VARCHAR(120) NOT NULL,
  subject    VARCHAR(200) NOT NULL,
  message    TEXT         NOT NULL,
  is_read    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
