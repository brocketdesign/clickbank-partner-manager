CREATE TABLE IF NOT EXISTS domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_name VARCHAR(255) NOT NULL UNIQUE,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain_name (domain_name),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS partners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aff_id VARCHAR(100) NOT NULL UNIQUE,
    partner_name VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_aff_id (aff_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_name VARCHAR(255) NOT NULL,
    clickbank_vendor VARCHAR(100) NOT NULL,
    clickbank_hoplink VARCHAR(500) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS redirect_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(255) NOT NULL,
    rule_type ENUM('global', 'domain', 'partner') NOT NULL DEFAULT 'global',
    domain_id INT NULL,
    partner_id INT NULL,
    offer_id INT NOT NULL,
    is_paused TINYINT(1) DEFAULT 0,
    priority INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE CASCADE,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    INDEX idx_rule_type (rule_type),
    INDEX idx_domain_id (domain_id),
    INDEX idx_partner_id (partner_id),
    INDEX idx_is_paused (is_paused),
    INDEX idx_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New partner applications tracking table
CREATE TABLE IF NOT EXISTS partner_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    blog_url VARCHAR(512) NOT NULL,
    traffic_estimate VARCHAR(64),
    notes TEXT,
    consent TINYINT(1) DEFAULT 0,
    status VARCHAR(32) DEFAULT 'pending',
    domain_verification_status VARCHAR(32) DEFAULT 'unchecked',
    domain_verified TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Enhanced partners table for partner registration workflow
CREATE TABLE IF NOT EXISTS partners_new (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id_public VARCHAR(36) UNIQUE NOT NULL COMMENT 'UUID for public snippet',
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    blog_url VARCHAR(512) NOT NULL,
    allowed_domains VARCHAR(512) COMMENT 'CSV list of verified domains',
    status VARCHAR(32) DEFAULT 'pending' COMMENT 'pending, approved, rejected',
    domain_verification_status VARCHAR(32) DEFAULT 'unchecked',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME NULL,
    notes TEXT,
    ip_address VARCHAR(45),
    INDEX idx_status (status),
    INDEX idx_partner_id_public (partner_id_public),
    INDEX idx_email (email),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Creatives (ads) offered to partners
CREATE TABLE IF NOT EXISTS creatives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(32) DEFAULT 'banner' COMMENT 'banner, text, native',
    destination_hoplink VARCHAR(512) NOT NULL COMMENT 'ClickBank hoplink',
    weight INT DEFAULT 100 COMMENT 'distribution weight',
    html TEXT COMMENT 'HTML for banner/native creatives',
    active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners_new(id) ON DELETE CASCADE,
    INDEX idx_partner_id (partner_id),
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Impression tracking (ad views)
CREATE TABLE IF NOT EXISTS impressions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    creative_id INT,
    ip_hash VARCHAR(64),
    ua_hash VARCHAR(64),
    ts DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners_new(id) ON DELETE CASCADE,
    FOREIGN KEY (creative_id) REFERENCES creatives(id) ON DELETE SET NULL,
    INDEX idx_partner_id (partner_id),
    INDEX idx_ts (ts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Click tracking
CREATE TABLE IF NOT EXISTS clicks (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    creative_id INT,
    click_id VARCHAR(64) UNIQUE NOT NULL COMMENT 'UUID for attribution',
    ip_hash VARCHAR(64),
    ua_hash VARCHAR(64),
    referrer TEXT,
    ts DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners_new(id) ON DELETE CASCADE,
    FOREIGN KEY (creative_id) REFERENCES creatives(id) ON DELETE SET NULL,
    INDEX idx_partner_id (partner_id),
    INDEX idx_click_id (click_id),
    INDEX idx_ts (ts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Conversion tracking (sales postbacks)
CREATE TABLE IF NOT EXISTS conversions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    click_id VARCHAR(64),
    external_id VARCHAR(255) COMMENT 'ClickBank transaction ID',
    amount DECIMAL(10,2),
    ts DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_click_id (click_id),
    INDEX idx_external_id (external_id),
    INDEX idx_ts (ts)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Monthly partner payouts
CREATE TABLE IF NOT EXISTS payouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    month VARCHAR(7) COMMENT 'YYYY-MM',
    clicks INT DEFAULT 0,
    amount DECIMAL(10,2),
    badge VARCHAR(32) DEFAULT 'bronze' COMMENT 'bronze, silver, gold',
    status VARCHAR(32) DEFAULT 'pending' COMMENT 'pending, paid',
    paid_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partners_new(id) ON DELETE CASCADE,
    INDEX idx_partner_id (partner_id),
    INDEX idx_month (month),
    INDEX idx_status (status),
    UNIQUE KEY unique_partner_month (partner_id, month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Application log / admin messages for rejected/requested info
CREATE TABLE IF NOT EXISTS application_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_id INT NOT NULL,
    message_type VARCHAR(32) COMMENT 'reject, request_info, approve, payout_notification',
    message_text TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (partner_id) REFERENCES partner_applications(id) ON DELETE CASCADE,
    INDEX idx_partner_id (partner_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS click_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    domain_id INT NULL,
    partner_id INT NULL,
    offer_id INT NULL,
    rule_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referer TEXT,
    redirect_url TEXT,
    clicked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (domain_id) REFERENCES domains(id) ON DELETE SET NULL,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE SET NULL,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL,
    FOREIGN KEY (rule_id) REFERENCES redirect_rules(id) ON DELETE SET NULL,
    INDEX idx_clicked_at (clicked_at),
    INDEX idx_domain_id (domain_id),
    INDEX idx_partner_id (partner_id),
    INDEX idx_offer_id (offer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO admin_users (username, password_hash) VALUES 
('admin', '$2y$12$ozggOSElWoX056SoSHxxdOXDc2Q3wPkEzCc90LRSY0R9dId9DDtQO');
