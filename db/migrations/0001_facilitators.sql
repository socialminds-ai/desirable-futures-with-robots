-- Facilitators and passwordless auth tokens.
-- Scale target ~100-200 facilitators: plain tables, basic indexes.

CREATE TABLE facilitators (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(200)  NOT NULL,
    email           VARCHAR(320)  NOT NULL,
    institution     VARCHAR(200)  NULL,
    country         VARCHAR(100)  NULL,
    continent       VARCHAR(32)   NULL,
    -- City-level location, deliberately coarse (2 decimals ~= 1.1 km) for
    -- data minimization. NULL when the facilitator did not place a pin.
    lat             DECIMAL(5,2)  NULL,
    lng             DECIMAL(6,2)  NULL,
    location_label  VARCHAR(200)  NULL,
    -- Map visibility: anonymous dot by default; identity is a separate opt-in.
    show_on_map     TINYINT(1)    NOT NULL DEFAULT 1,
    show_identity   TINYINT(1)    NOT NULL DEFAULT 0,
    -- 'pending' until the double opt-in email is confirmed.
    status          ENUM('pending','active') NOT NULL DEFAULT 'pending',
    consent_at      DATETIME      NULL,
    consent_version VARCHAR(20)   NULL,
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE auth_tokens (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    facilitator_id INT          NOT NULL,
    token_hash     CHAR(64)     NOT NULL,           -- sha256 hex of the raw token
    purpose        ENUM('verify','login') NOT NULL,
    expires_at     DATETIME     NOT NULL,
    used_at        DATETIME     NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_token_hash (token_hash),
    CONSTRAINT fk_token_facilitator
        FOREIGN KEY (facilitator_id) REFERENCES facilitators (id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
