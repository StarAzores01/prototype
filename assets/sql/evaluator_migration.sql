-- ============================================================
-- PAThrive – Evaluator Role Migration
-- Run this against pathrive_db
-- ============================================================

-- 1. Add 'evaluator' to users.role ENUM
ALTER TABLE users
  MODIFY COLUMN role ENUM('extension_coordinator','trainer','evaluator') NOT NULL DEFAULT 'trainer';

-- 2. Evaluator whitelist (pre-approved by EC, same pattern as trainer_whitelist)
CREATE TABLE IF NOT EXISTS evaluator_whitelist (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  NOT NULL,
  department    VARCHAR(120) NOT NULL,
  id_number     VARCHAR(20)  NULL,
  is_registered TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Impact Assessment forms (submitted by evaluators to EC)
CREATE TABLE IF NOT EXISTS impact_assessments (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  evaluator_id    INT          NOT NULL,
  training_id     INT          NULL,
  title           VARCHAR(200) NOT NULL,
  description     TEXT         NULL,
  file_name       VARCHAR(255) NULL,
  original_name   VARCHAR(255) NULL,
  file_type       VARCHAR(20)  NULL,
  file_size       INT          NULL DEFAULT 0,
  status          ENUM('Draft','Submitted','Reviewed') NOT NULL DEFAULT 'Submitted',
  submitted_at    DATETIME     NULL,
  reviewed_at     DATETIME     NULL,
  ec_notes        TEXT         NULL,
  created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (evaluator_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (training_id)  REFERENCES trainings(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Allow notifications to target evaluators
ALTER TABLE notifications
  MODIFY COLUMN role ENUM('trainer','beneficiary','evaluator','all') NOT NULL DEFAULT 'all';

-- ============================================================
-- PAThrive – Impact Assessment Participant Survey Migration
-- Run this against pathrive_db (after evaluator_migration.sql)
-- ============================================================

-- 5. Impact Assessment Forms (sent by EC to participants)
CREATE TABLE IF NOT EXISTS impact_assessment_forms (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  training_id INT NOT NULL,
  title       VARCHAR(200) NOT NULL DEFAULT 'Impact Assessment Survey',
  fields      JSON NOT NULL,
  created_by  INT NOT NULL,
  sent_at     DATETIME NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Impact Assessment Responses (submitted by beneficiaries/participants)
CREATE TABLE IF NOT EXISTS impact_assessment_responses (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  form_id        INT NOT NULL,
  training_id    INT NOT NULL,
  beneficiary_id INT NOT NULL,
  responses      JSON NOT NULL,
  submitted_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ia_resp (form_id, beneficiary_id),
  FOREIGN KEY (form_id)        REFERENCES impact_assessment_forms(id) ON DELETE CASCADE,
  FOREIGN KEY (training_id)    REFERENCES trainings(id)               ON DELETE CASCADE,
  FOREIGN KEY (beneficiary_id) REFERENCES beneficiaries(id)           ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Allow notifications to target beneficiaries for impact assessments
--    (already handled by existing notifications table structure)
