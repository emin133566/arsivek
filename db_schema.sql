CREATE TABLE IF NOT EXISTS news (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title TEXT NOT NULL,
    summary TEXT NOT NULL,
    content LONGTEXT,
    category VARCHAR(255) NOT NULL,
    image TEXT,
    video TEXT,
    hero VARCHAR(255),
    featured TINYINT(1) NOT NULL DEFAULT 0,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    date DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
