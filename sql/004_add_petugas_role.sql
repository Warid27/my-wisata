-- Add 'petugas' to role enum in users table
ALTER TABLE users MODIFY COLUMN role ENUM('admin','user','petugas') NOT NULL DEFAULT 'user';

-- Insert petugas user (nama: Petugas, email: petugas@mywisata.com, password: petugas123)
INSERT INTO users (nama, email, password, role, created_at) 
VALUES ('Petugas', 'petugas@mywisata.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'petugas', NOW())
ON DUPLICATE KEY UPDATE role = 'petugas';
