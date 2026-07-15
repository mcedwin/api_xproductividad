-- ============================================================
-- TABLAS DEL SISTEMA LARAVEL + SANCTUM
-- EJECUTAR DIRECTAMENTE EN MYSQL
-- ============================================================

-- 1. Personal Access Tokens (Sanctum) -- ESENCIAL para auth
CREATE TABLE IF NOT EXISTS personal_access_tokens (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tokenable_type      VARCHAR(255) NOT NULL,
    tokenable_id        BIGINT UNSIGNED NOT NULL,
    name                VARCHAR(255) NOT NULL,
    token               VARCHAR(64) NOT NULL UNIQUE,
    abilities           TEXT NULL,
    last_used_at        TIMESTAMP NULL,
    expires_at          TIMESTAMP NULL,
    created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_tokenable (tokenable_type, tokenable_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Sessions (SESSION_DRIVER=database)
CREATE TABLE IF NOT EXISTS sessions (
    id                  VARCHAR(255) PRIMARY KEY,
    user_id             BIGINT UNSIGNED NULL,
    ip_address          VARCHAR(45) NULL,
    user_agent          TEXT NULL,
    payload             LONGTEXT NOT NULL,
    last_activity       INT NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Cache (CACHE_STORE=database)
CREATE TABLE IF NOT EXISTS cache (
    key                 VARCHAR(255) PRIMARY KEY,
    value               MEDIUMTEXT NOT NULL,
    expiration          INT NOT NULL,
    INDEX idx_expiration (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cache_locks (
    key                 VARCHAR(255) PRIMARY KEY,
    owner               VARCHAR(255) NOT NULL,
    expiration          INT NOT NULL,
    INDEX idx_expiration (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Jobs (QUEUE_CONNECTION=database)
CREATE TABLE IF NOT EXISTS jobs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    queue               VARCHAR(255) NOT NULL,
    payload             LONGTEXT NOT NULL,
    attempts            TINYINT UNSIGNED NOT NULL,
    reserved_at         INT UNSIGNED NULL,
    available_at        INT UNSIGNED NOT NULL,
    created_at          INT UNSIGNED NOT NULL,
    INDEX idx_queue (queue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_batches (
    id                  VARCHAR(255) PRIMARY KEY,
    name                VARCHAR(255) NOT NULL,
    total_jobs          INT NOT NULL,
    pending_jobs        INT NOT NULL,
    failed_jobs         INT NOT NULL,
    failed_job_ids      LONGTEXT NOT NULL,
    options             MEDIUMTEXT NULL,
    cancelled_at        INT NULL,
    created_at          INT NOT NULL,
    finished_at         INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS failed_jobs (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid                VARCHAR(255) NOT NULL UNIQUE,
    connection          TEXT NOT NULL,
    queue               TEXT NOT NULL,
    payload             LONGTEXT NOT NULL,
    exception           LONGTEXT NOT NULL,
    failed_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Password Reset Tokens (opcional, buena práctica)
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email               VARCHAR(255) PRIMARY KEY,
    token               VARCHAR(255) NOT NULL,
    created_at          TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Auth Accounts (usada por AuthController para login con password)
CREATE TABLE IF NOT EXISTS auth_accounts (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             BIGINT UNSIGNED NOT NULL,
    provider            VARCHAR(50) NOT NULL COMMENT 'password, google',
    provider_id         VARCHAR(255) NOT NULL,
    password_hash       VARCHAR(255) NULL,
    created_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_auth_provider (provider, provider_id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
