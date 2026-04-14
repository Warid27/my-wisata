-- Add missing kategori table
CREATE TABLE kategori (
    id_kategori INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add id_kategori column to event table
ALTER TABLE event ADD COLUMN id_kategori INT NULL AFTER id_venue;

-- Add foreign key constraint
ALTER TABLE event ADD FOREIGN KEY (id_kategori) REFERENCES kategori(id_kategori) ON DELETE SET NULL;

-- Insert sample categories
INSERT INTO kategori (nama_kategori, deskripsi) VALUES 
('Konser Musik', 'Acara musik live dengan artis ternama'),
('Olahraga', 'Pertandingan dan kompetisi olahraga'),
('Seminar', 'Konferensi dan pelatihan profesional'),
('Festival', 'Acara festival dan pameran'),
('Teater', 'Pertunjukan seni dan drama'),
('Workshop', 'Pelatihan keterampilan dan kursus');

-- Update existing events to have categories (optional)
UPDATE event SET id_kategori = 1 WHERE id_event IN (1, 2, 3);
UPDATE event SET id_kategori = 2 WHERE id_event IN (4, 5);
UPDATE event SET id_kategori = 3 WHERE id_event IN (6, 7);
