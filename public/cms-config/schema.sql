CREATE TABLE IF NOT EXISTS admin_users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  name_es VARCHAR(140) NOT NULL,
  name_en VARCHAR(140) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  name_es VARCHAR(140) NOT NULL,
  name_en VARCHAR(140) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(500) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  size_bytes INT UNSIGNED NOT NULL DEFAULT 0,
  alt_es VARCHAR(255) NOT NULL DEFAULT '',
  alt_en VARCHAR(255) NOT NULL DEFAULT '',
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_media_user FOREIGN KEY (uploaded_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  line_id BIGINT UNSIGNED NOT NULL,
  category_id BIGINT UNSIGNED NOT NULL,
  image_path VARCHAR(500) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  featured_home TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  deleted_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_products_public (line_id,is_active,deleted_at,sort_order),
  CONSTRAINT fk_products_line FOREIGN KEY (line_id) REFERENCES product_lines(id),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES product_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_translations (
  product_id BIGINT UNSIGNED NOT NULL,
  locale ENUM('es','en') NOT NULL,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(210) NOT NULL,
  short_description VARCHAR(500) NOT NULL DEFAULT '',
  description TEXT NULL,
  seo_title VARCHAR(190) NOT NULL DEFAULT '',
  seo_description VARCHAR(320) NOT NULL DEFAULT '',
  PRIMARY KEY (product_id,locale),
  UNIQUE KEY uq_product_slug_locale (locale,slug),
  CONSTRAINT fk_translation_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_specs (
  product_id BIGINT UNSIGNED PRIMARY KEY,
  scientific_name VARCHAR(190) NOT NULL DEFAULT '',
  tariff_code VARCHAR(80) NOT NULL DEFAULT '',
  caliber_es VARCHAR(190) NOT NULL DEFAULT '',
  caliber_en VARCHAR(190) NOT NULL DEFAULT '',
  destinations_es VARCHAR(500) NOT NULL DEFAULT '',
  destinations_en VARCHAR(500) NOT NULL DEFAULT '',
  technical_sheet_path VARCHAR(500) NOT NULL DEFAULT '',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_specs_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_gallery (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  media_type ENUM('image','video') NOT NULL DEFAULT 'image',
  media_path VARCHAR(500) NOT NULL,
  alt_es VARCHAR(255) NOT NULL DEFAULT '',
  alt_en VARCHAR(255) NOT NULL DEFAULT '',
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_gallery_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_packages (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  weight_primary VARCHAR(80) NOT NULL,
  weight_secondary VARCHAR(80) NOT NULL DEFAULT '',
  material_es VARCHAR(190) NOT NULL DEFAULT '',
  material_en VARCHAR(190) NOT NULL DEFAULT '',
  package_type ENUM('bag','sack','big-bag','other') NOT NULL DEFAULT 'sack',
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_packages_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_harvest (
  product_id BIGINT UNSIGNED NOT NULL,
  month_number TINYINT UNSIGNED NOT NULL,
  availability ENUM('none','harvest','available','limited') NOT NULL DEFAULT 'none',
  PRIMARY KEY (product_id,month_number),
  CONSTRAINT fk_harvest_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_certifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(160) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_certification_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_relations (
  product_id BIGINT UNSIGNED NOT NULL,
  related_product_id BIGINT UNSIGNED NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  PRIMARY KEY (product_id,related_product_id),
  CONSTRAINT fk_relation_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_relation_related FOREIGN KEY (related_product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS content_fields (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  page_key VARCHAR(80) NOT NULL,
  section_key VARCHAR(100) NOT NULL,
  field_key VARCHAR(100) NOT NULL,
  field_type ENUM('text','textarea','image','url') NOT NULL DEFAULT 'text',
  label VARCHAR(190) NOT NULL,
  value_es MEDIUMTEXT NULL,
  value_en MEDIUMTEXT NULL,
  updated_by BIGINT UNSIGNED NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_content_field (page_key,section_key,field_key),
  CONSTRAINT fk_content_user FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  value_text MEDIUMTEXT NULL,
  is_public TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inquiries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  company VARCHAR(180) NOT NULL DEFAULT '',
  email VARCHAR(190) NOT NULL,
  country VARCHAR(120) NOT NULL DEFAULT '',
  product_name VARCHAR(190) NOT NULL DEFAULT '',
  message TEXT NOT NULL,
  locale ENUM('es','en') NOT NULL DEFAULT 'es',
  status ENUM('new','contacted','closed','spam') NOT NULL DEFAULT 'new',
  ip_hash CHAR(64) NOT NULL DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_inquiries_status_created (status,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id VARCHAR(80) NOT NULL DEFAULT '',
  details_json JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO product_lines (slug,name_es,name_en,sort_order) VALUES
('conventional','Línea Convencional','Conventional Line',10),
('retail','Línea Retail','Retail Line',20);

INSERT IGNORE INTO product_categories (slug,name_es,name_en,sort_order) VALUES
('pulses','Legumbres','Pulses',10),
('grains','Granos y semillas','Grains & seeds',20),
('corn','Maíces','Corn',30),
('spices','Especias','Spices',40);

INSERT IGNORE INTO settings (setting_key,value_text,is_public) VALUES
('company_email','administracion@royalbeansperu.com',1),
('company_phone','+51 961 804 500',1),
('whatsapp','51961804500',1),
('site_name','Royal Beans Perú',1);

INSERT IGNORE INTO content_fields (page_key,section_key,field_key,field_type,label) VALUES
('inicio','hero','title','text','Título principal'),
('inicio','hero','description','textarea','Descripción principal'),
('inicio','about','title','text','Título de quiénes somos'),
('inicio','about','description','textarea','Descripción de quiénes somos'),
('inicio','about','image','image','Imagen de quiénes somos'),
('inicio','cta','title','text','Título de llamada a la acción'),
('nosotros','hero','title','text','Título principal'),
('nosotros','hero','description','textarea','Descripción principal'),
('nosotros','hero','image','image','Imagen principal'),
('productos','hero','title','text','Título principal'),
('productos','hero','description','textarea','Descripción principal'),
('productos-conventional','hero','title','text','Título de Línea Convencional'),
('productos-conventional','hero','description','textarea','Descripción de Línea Convencional'),
('productos-retail','hero','title','text','Título de Línea Retail'),
('productos-retail','hero','description','textarea','Descripción de Línea Retail'),
('participacion','hero','title','text','Título principal'),
('participacion','hero','description','textarea','Descripción principal'),
('presencia','hero','title','text','Título principal'),
('presencia','hero','description','textarea','Descripción principal'),
('impacto','hero','title','text','Título principal'),
('impacto','hero','description','textarea','Descripción principal'),
('contacto','hero','title','text','Título principal'),
('contacto','hero','description','textarea','Descripción principal');
