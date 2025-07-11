-- Tạo database
CREATE DATABASE IF NOT EXISTS qlbv CHARACTER SET utf8 COLLATE utf8_general_ci;

-- Tạo user
CREATE USER IF NOT EXISTS 'qlbv_user'@'%' IDENTIFIED BY 'qlbv_password';
GRANT ALL PRIVILEGES ON qlbv.* TO 'qlbv_user'@'%';
FLUSH PRIVILEGES; 