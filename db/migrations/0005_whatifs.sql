-- Community "what-if" bank: questions plus one-vote-per-facilitator favourites.
-- New questions are visible immediately; admins hide them post-hoc.

CREATE TABLE whatifs (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    prompt                VARCHAR(280) NOT NULL,
    author_facilitator_id INT NULL,
    status                ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    hidden_at             DATETIME NULL,
    hidden_by             INT NULL,
    KEY idx_status (status),
    CONSTRAINT fk_whatif_author
        FOREIGN KEY (author_facilitator_id) REFERENCES facilitators (id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE whatif_votes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    whatif_id      INT NOT NULL,
    facilitator_id INT NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_vote (whatif_id, facilitator_id),
    CONSTRAINT fk_vote_whatif
        FOREIGN KEY (whatif_id) REFERENCES whatifs (id) ON DELETE CASCADE,
    CONSTRAINT fk_vote_facilitator
        FOREIGN KEY (facilitator_id) REFERENCES facilitators (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Canonical starter questions (no author; part of the shared kit).
INSERT INTO whatifs (prompt, status) VALUES
('What if robots didn''t always say yes, but questioned you?', 'visible'),
('What if a robot was clumsy, and sometimes needed our help?', 'visible'),
('What if a robot didn''t try to save your time, but to waste it well?', 'visible'),
('What if a robot brought you something you never asked for?', 'visible');
