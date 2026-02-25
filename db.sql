-- Удалить таблицы если существуют (порядок важен из-за внешних ключей)
DROP TABLE IF EXISTS application_language;
DROP TABLE IF EXISTS language;
DROP TABLE IF EXISTS application;

-- Таблица заявок
CREATE TABLE application (
    id          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(150)     NOT NULL DEFAULT '',
    phone       VARCHAR(20)      NOT NULL DEFAULT '',
    email       VARCHAR(255)     NOT NULL DEFAULT '',
    birthdate   DATE             NOT NULL,
    gender      ENUM('male','female') NOT NULL,
    biography   TEXT             NOT NULL,
    agreed      TINYINT(1)       NOT NULL DEFAULT 0,
    created_at  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица языков программирования (справочник)
CREATE TABLE language (
    id   INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(64)      NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Заполняем справочник языков
INSERT INTO language (name) VALUES
    ('Pascal'),
    ('C'),
    ('C++'),
    ('JavaScript'),
    ('PHP'),
    ('Python'),
    ('Java'),
    ('Haskell'),
    ('Clojure'),
    ('Prolog'),
    ('Scala'),
    ('Go');

-- Таблица связи заявка ↔ язык (1 ко многим)
CREATE TABLE application_language (
    application_id INT(10) UNSIGNED NOT NULL,
    language_id    INT(10) UNSIGNED NOT NULL,
    PRIMARY KEY (application_id, language_id),
    CONSTRAINT fk_app  FOREIGN KEY (application_id) REFERENCES application(id) ON DELETE CASCADE,
    CONSTRAINT fk_lang FOREIGN KEY (language_id)    REFERENCES language(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
