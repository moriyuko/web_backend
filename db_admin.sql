-- Таблица администраторов. Выполнить один раз:
-- mysql -uuXXXXX -p uXXXXX < db_admin.sql

CREATE TABLE IF NOT EXISTS admin (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    login         VARCHAR(64)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_admin_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Добавляем администратора по умолчанию: логин admin, пароль admin123
-- md5('admin123') = 0192023a7bbd73250516f069df18b500
-- Смените пароль после первого входа!
INSERT INTO admin (login, password_hash)
VALUES ('admin', MD5('admin123'))
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);
