-- Добавляем колонки логина и хеша пароля в таблицу заявок.
-- Выполнить один раз: mysql -uuXXXXX -p uXXXXX < db_update.sql

ALTER TABLE application
    ADD COLUMN login         VARCHAR(64)  NULL DEFAULT NULL AFTER agreed,
    ADD COLUMN password_hash VARCHAR(255) NULL DEFAULT NULL AFTER login,
    ADD UNIQUE KEY uq_login (login);
