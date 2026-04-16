-- Database: event_tiket
CREATE DATABASE IF NOT EXISTS event_tiket;
USE event_tiket;

-- Table: users
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: venue
CREATE TABLE venue (
    id_venue INT AUTO_INCREMENT PRIMARY KEY,
    nama_venue VARCHAR(100) NOT NULL,
    alamat TEXT NOT NULL,
    kapasitas INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: event
CREATE TABLE event (
    id_event INT AUTO_INCREMENT PRIMARY KEY,
    nama_event VARCHAR(150) NOT NULL,
    tanggal DATE NOT NULL,
    id_venue INT NOT NULL,
    deskripsi TEXT,
    gambar VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_venue) REFERENCES venue(id_venue) ON DELETE CASCADE
);

-- Table: tiket
CREATE TABLE tiket (
    id_tiket INT AUTO_INCREMENT PRIMARY KEY,
    id_event INT NOT NULL,
    nama_tiket VARCHAR(50) NOT NULL,
    harga INT NOT NULL,
    kuota INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_event) REFERENCES event(id_event) ON DELETE CASCADE
);

-- Table: voucher
CREATE TABLE voucher (
    id_voucher INT AUTO_INCREMENT PRIMARY KEY,
    kode_voucher VARCHAR(20) NOT NULL UNIQUE,
    potongan INT NOT NULL,
    kuota INT NOT NULL DEFAULT 0,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: orders
CREATE TABLE orders (
    id_order INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    tanggal_order DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total INT NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') NOT NULL DEFAULT 'pending',
    id_voucher INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_voucher) REFERENCES voucher(id_voucher) ON DELETE SET NULL
);

-- Table: order_detail
CREATE TABLE order_detail (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_order INT NOT NULL,
    id_tiket INT NOT NULL,
    qty INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE,
    FOREIGN KEY (id_tiket) REFERENCES tiket(id_tiket) ON DELETE CASCADE
);

-- Table: attendee
CREATE TABLE attendee (
    id_attendee INT AUTO_INCREMENT PRIMARY KEY,
    id_detail INT NOT NULL,
    kode_tiket VARCHAR(50) NOT NULL UNIQUE,
    status_checkin ENUM('belum', 'sudah') NOT NULL DEFAULT 'belum',
    waktu_checkin DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_detail) REFERENCES order_detail(id_detail) ON DELETE CASCADE
);

-- Indexes for performance
CREATE INDEX idx_event_tanggal ON event(tanggal);
CREATE INDEX idx_tiket_event ON tiket(id_event);
CREATE INDEX idx_orders_user ON orders(id_user);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_order_detail_order ON order_detail(id_order);
CREATE INDEX idx_attendee_kode ON attendee(kode_tiket);
CREATE INDEX idx_voucher_kode ON voucher(kode_voucher);

-- Insert sample admin user
INSERT INTO users (nama, email, password, role) VALUES 
('Admin', 'admin@event.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample venues
INSERT INTO venue (nama_venue, alamat, kapasitas) VALUES 
('Stadion Utama', 'Jl. Sudirman No. 1, Jakarta', 50000),
('Convention Center', 'Jl. Gatot Subroto No. 10, Jakarta', 10000),
('Arena Balap', 'Jl. Pahlawan No. 25, Bandung', 25000);

-- Insert sample vouchers
INSERT INTO voucher (kode_voucher, potongan, kuota, status) VALUES 
('EARLY2024', 20000, 100, 'aktif'),
('DISCOUNT50', 50000, 50, 'aktif'),
('SPECIAL10', 10000, 200, 'aktif');
