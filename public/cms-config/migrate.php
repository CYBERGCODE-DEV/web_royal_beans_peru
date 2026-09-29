<?php
declare(strict_types=1);

function restore_known_media_aliases(PDO $db): int
{
    $catalogPath = __DIR__ . '/media-aliases.json';
    $catalog = is_file($catalogPath) ? json_decode((string) file_get_contents($catalogPath), true) : [];
    if (!is_array($catalog)) $catalog = [];
    $save = $db->prepare("INSERT INTO media_aliases(local_path,remote_path,content_hash) VALUES(?,?,?) ON DUPLICATE KEY UPDATE remote_path=IF(remote_path='' OR remote_path NOT REGEXP '^https?://',VALUES(remote_path),remote_path),content_hash=COALESCE(NULLIF(content_hash,''),VALUES(content_hash))");
    $restored = 0;
    foreach ($catalog as $localPath => $remotePath) {
        if (!is_string($localPath) || !str_starts_with($localPath, '/') || !is_string($remotePath) || !preg_match('#^https?://#i', $remotePath)) continue;
        $hash = preg_match('#/by-hash/([a-f0-9]{64})\.[a-z0-9]+(?:\?.*)?$#i', $remotePath, $match) ? strtolower($match[1]) : null;
        $save->execute([$localPath, $remotePath, $hash]);
        if ($save->rowCount() > 0) $restored++;
    }
    $findRemote = $db->prepare("SELECT path,content_hash FROM media WHERE LOWER(original_name)=? AND path REGEXP '^https?://' ORDER BY id DESC LIMIT 1");
    foreach (['/images/standards/fda.png','/images/standards/senasa.png','/images/standards/haccp.png','/images/standards/marca-peru.png'] as $localPath) {
        $findRemote->execute([strtolower(basename($localPath))]);
        $remote = $findRemote->fetch();
        if (!$remote) continue;
        $save->execute([$localPath, $remote['path'], $remote['content_hash'] ?: null]);
        if ($save->rowCount() > 0) $restored++;
    }
    return $restored;
}

function repair_known_media_references(PDO $db): int
{
    $repaired = 0;
    foreach ([
        ['products','image_path'],
        ['product_gallery','media_path'],
        ['product_packages','image_path'],
        ['content_fields','value_es'],
        ['content_fields','value_en'],
        ['cms_collections','logo_path'],
        ['cms_collections','cover_path'],
        ['cms_collection_media','media_path'],
        ['home_announcements','image_path'],
    ] as [$table, $column]) {
        $repaired += $db->exec("UPDATE $table target INNER JOIN media_aliases alias ON target.$column=alias.local_path SET target.$column=alias.remote_path");
    }
    $repaired += $db->exec("UPDATE cms_collections target INNER JOIN media_aliases alias ON alias.local_path=CASE UPPER(TRIM(target.title_es)) WHEN 'FDA' THEN '/images/standards/fda.png' WHEN 'SENASA' THEN '/images/standards/senasa.png' WHEN 'HACCP INTERNO' THEN '/images/standards/haccp.png' WHEN 'MARCA PERÚ' THEN '/images/standards/marca-peru.png' ELSE '' END SET target.logo_path=alias.remote_path WHERE target.module_key='standards' AND (target.logo_path='' OR target.logo_path=alias.local_path)");
    return $repaired;
}

function migrate_cms(PDO $db): void
{
    $migrationVersion = '20260928_1';
    $migrationMarker = $db->prepare("SELECT value_text FROM settings WHERE setting_key='cms_migration_version' LIMIT 1");
    $migrationMarker->execute();
    $previousVersion = $migrationMarker->fetchColumn();
    if ($previousVersion === $migrationVersion) return;
    if ($previousVersion === '20260923_1') {
        $homeCopy = $db->prepare("UPDATE content_fields SET value_es=?,value_en=?,label=? WHERE page_key='inicio' AND section_key=? AND field_key=?");
        $homeCopy->execute(['Granos, legumbres y especias del Perú para el mundo.','Peruvian grains, pulses and spices for the world.','Mensaje principal del Home','hero','title']);
        $homeCopy->execute(['Seleccionamos productos peruanos para mercados nacionales e internacionales.','We select Peruvian products for domestic and international markets.','Descripción principal del Home','hero','description']);
        $homeCopy->execute(['Ver Línea a Granel','Explore Bulk Line','Botón Línea a Granel','inicio','a-button-button-lime-1']);
        $db->exec("UPDATE product_categories SET name_es='Granos y semillas',name_en='Grains & seeds' WHERE slug='grains'");
        $db->prepare("INSERT INTO settings(setting_key,value_text,is_public) VALUES('cms_migration_version',?,0) ON DUPLICATE KEY UPDATE value_text=VALUES(value_text),is_public=0")->execute([$migrationVersion]);
        return;
    }
    if ($previousVersion === '20260919_6') {
        $db->exec("UPDATE product_lines SET name_es='Línea a Granel',name_en='Bulk Line' WHERE slug='conventional'");
        $db->exec("UPDATE content_fields SET value_es=REPLACE(REPLACE(value_es,'Línea Convencional','Línea a Granel'),'Línea convencional','Línea a granel'),value_en=REPLACE(REPLACE(value_en,'Conventional Line','Bulk Line'),'Conventional line','Bulk line'),label=REPLACE(REPLACE(label,'Línea Convencional','Línea a Granel'),'Línea convencional','Línea a granel') WHERE value_es LIKE '%Línea Convencional%' OR value_en LIKE '%Conventional Line%' OR label LIKE '%Línea Convencional%'");
        $db->exec("INSERT IGNORE INTO content_fields(page_key,section_key,field_key,field_type,label,value_es,value_en) VALUES('nosotros','about-video','source','url','Video institucional','','')");
        $db->prepare("INSERT INTO settings(setting_key,value_text,is_public) VALUES('cms_migration_version',?,0) ON DUPLICATE KEY UPDATE value_text=VALUES(value_text),is_public=0")->execute([$migrationVersion]);
        return;
    }

    $hasColumn = static function (string $table, string $column) use ($db): bool {
        $statement = $db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
        $statement->execute([$table, $column]);
        return (bool) $statement->fetchColumn();
    };
    $hasIndex = static function (string $table, string $index) use ($db): bool {
        $statement = $db->prepare('SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name=? AND index_name=?');
        $statement->execute([$table, $index]);
        return (bool) $statement->fetchColumn();
    };
    if (!$hasColumn('admin_users', 'permissions_json')) {
        $db->exec('ALTER TABLE admin_users ADD permissions_json JSON NULL AFTER role');
    }
    if (!$hasColumn('product_packages', 'image_path')) $db->exec("ALTER TABLE product_packages ADD image_path VARCHAR(500) NOT NULL DEFAULT '' AFTER material_en");
    if (!$hasColumn('product_packages', 'is_available')) $db->exec('ALTER TABLE product_packages ADD is_available TINYINT(1) NOT NULL DEFAULT 1 AFTER image_path');
    if (!$hasColumn('content_fields', 'style_json')) $db->exec('ALTER TABLE content_fields ADD style_json JSON NULL AFTER value_en');
    if (!$hasColumn('inquiries', 'participant_type')) $db->exec("ALTER TABLE inquiries ADD participant_type VARCHAR(80) NOT NULL DEFAULT '' AFTER id");
    if (!$hasColumn('inquiries', 'phone')) $db->exec("ALTER TABLE inquiries ADD phone VARCHAR(80) NOT NULL DEFAULT '' AFTER email");
    if (!$hasColumn('inquiries', 'product_line')) $db->exec("ALTER TABLE inquiries ADD product_line VARCHAR(40) NOT NULL DEFAULT '' AFTER country");
    if (!$hasColumn('inquiries', 'product_names')) $db->exec('ALTER TABLE inquiries ADD product_names TEXT NULL AFTER product_name');
    if (!$hasColumn('media', 'content_hash')) $db->exec('ALTER TABLE media ADD content_hash CHAR(64) NULL AFTER size_bytes');
    $db->exec('DELETE duplicate_media FROM media duplicate_media JOIN media canonical_media ON canonical_media.path=duplicate_media.path AND canonical_media.id<duplicate_media.id');
    $hasMediaPathIndex = (int) $db->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='media' AND index_name='uq_media_path'")->fetchColumn();
    if (!$hasMediaPathIndex) $db->exec('ALTER TABLE media ADD UNIQUE KEY uq_media_path (path)');
    $hasMediaHashIndex = (int) $db->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='media' AND index_name='idx_media_content_hash'")->fetchColumn();
    if (!$hasMediaHashIndex) $db->exec('ALTER TABLE media ADD INDEX idx_media_content_hash (content_hash)');
    $db->exec("CREATE TABLE IF NOT EXISTS media_aliases (local_path VARCHAR(500) PRIMARY KEY,remote_path VARCHAR(1000) NOT NULL,content_hash CHAR(64) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_media_alias_hash (content_hash)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS home_announcements (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(190) NOT NULL,image_path VARCHAR(1000) NOT NULL,alt_es VARCHAR(255) NOT NULL DEFAULT '',alt_en VARCHAR(255) NOT NULL DEFAULT '',is_permanent TINYINT(1) NOT NULL DEFAULT 1,expires_at DATETIME NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_home_announcements_public (is_active,is_permanent,expires_at,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("ALTER TABLE product_harvest MODIFY availability ENUM('none','planting','harvest','available','limited') NOT NULL DEFAULT 'none'");
    $db->exec("CREATE TABLE IF NOT EXISTS cms_collections (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,module_key ENUM('presentation','impact') NOT NULL,entry_type ENUM('event','presence') NOT NULL,title_es VARCHAR(190) NOT NULL,title_en VARCHAR(190) NOT NULL DEFAULT '',description_es TEXT NULL,description_en TEXT NULL,event_date DATE NULL,location_es VARCHAR(190) NOT NULL DEFAULT '',location_en VARCHAR(190) NOT NULL DEFAULT '',logo_path VARCHAR(500) NOT NULL DEFAULT '',cover_path VARCHAR(500) NOT NULL DEFAULT '',is_active TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_collection_public (module_key,entry_type,is_active,sort_order)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("ALTER TABLE cms_collections MODIFY module_key ENUM('presentation','impact','standards') NOT NULL, MODIFY entry_type ENUM('event','presence','standard') NOT NULL");
    $db->exec("CREATE TABLE IF NOT EXISTS cms_collection_media (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,collection_id BIGINT UNSIGNED NOT NULL,media_path VARCHAR(500) NOT NULL,caption_es VARCHAR(255) NOT NULL DEFAULT '',caption_en VARCHAR(255) NOT NULL DEFAULT '',sort_order INT NOT NULL DEFAULT 0,CONSTRAINT fk_collection_media FOREIGN KEY (collection_id) REFERENCES cms_collections(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("CREATE TABLE IF NOT EXISTS contact_channels (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,channel_type ENUM('email','phone','whatsapp','address','other') NOT NULL DEFAULT 'other',label_es VARCHAR(160) NOT NULL,label_en VARCHAR(160) NOT NULL DEFAULT '',value_text VARCHAR(500) NOT NULL,link_url VARCHAR(500) NOT NULL DEFAULT '',is_active TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $db->exec("ALTER TABLE contact_channels MODIFY channel_type ENUM('email','phone','whatsapp','address','facebook','instagram','youtube','linkedin','tiktok','other') NOT NULL DEFAULT 'other'");
    $db->exec("UPDATE product_lines SET name_es='Línea a Granel',name_en='Bulk Line' WHERE slug='conventional'");
    $db->exec("UPDATE content_fields SET value_es=REPLACE(REPLACE(value_es,'Línea Convencional','Línea a Granel'),'Línea convencional','Línea a granel'),value_en=REPLACE(REPLACE(value_en,'Conventional Line','Bulk Line'),'Conventional line','Bulk line'),label=REPLACE(REPLACE(label,'Línea Convencional','Línea a Granel'),'Línea convencional','Línea a granel') WHERE value_es LIKE '%Línea Convencional%' OR value_en LIKE '%Conventional Line%' OR label LIKE '%Línea Convencional%'");
    $db->exec("INSERT IGNORE INTO content_fields(page_key,section_key,field_key,field_type,label,value_es,value_en) VALUES('nosotros','about-video','source','url','Video institucional','','')");
    $db->exec("UPDATE content_fields SET value_es=REPLACE(value_es,'/images/expo-team.webp','/images/expo-connection.webp'),value_en=REPLACE(value_en,'/images/expo-team.webp','/images/expo-connection.webp') WHERE value_es LIKE '%/images/expo-team.webp%' OR value_en LIKE '%/images/expo-team.webp%'");
    $db->exec("UPDATE cms_collections SET logo_path=REPLACE(logo_path,'/images/expo-team.webp','/images/expo-connection.webp'),cover_path=REPLACE(cover_path,'/images/expo-team.webp','/images/expo-connection.webp') WHERE logo_path='/images/expo-team.webp' OR cover_path='/images/expo-team.webp'");
    $db->exec("UPDATE cms_collection_media SET media_path='/images/expo-connection.webp' WHERE media_path='/images/expo-team.webp'");
    restore_known_media_aliases($db);
    repair_known_media_references($db);
    foreach ([
        ['media','idx_media_type_id','mime_type,id'],
        ['product_gallery','idx_gallery_product_sort','product_id,sort_order,id'],
        ['product_packages','idx_packages_product_sort','product_id,sort_order,id'],
        ['product_certifications','idx_certifications_product_sort','product_id,sort_order,id'],
        ['product_relations','idx_relations_product_sort','product_id,sort_order,related_product_id'],
        ['cms_collection_media','idx_collection_media_sort','collection_id,sort_order,id'],
        ['contact_channels','idx_contact_public','is_active,sort_order,id'],
    ] as [$table, $index, $columns]) {
        if (!$hasIndex($table, $index)) $db->exec("ALTER TABLE $table ADD INDEX $index ($columns)");
    }

    $insertLegalField = $db->prepare('INSERT IGNORE INTO content_fields(page_key,section_key,field_key,field_type,label,value_es,value_en) VALUES(?,?,?,?,?,?,?)');
    foreach ([
        ['privacidad','legal-intro','back','text','Volver a Royal Beans Perú','← Royal Beans Perú','← Royal Beans Perú'],
        ['privacidad','legal-intro','title','text','Título de privacidad','Política de privacidad','Privacy policy'],
        ['privacidad','legal-intro','summary','textarea','Resumen de privacidad','Explicamos qué datos recibimos, para qué los usamos y cómo puedes ejercer tus derechos.','This policy explains what data we receive, why we use it and how you can exercise your rights.'],
        ['privacidad','privacy-controller','title','text','Responsable','Responsable','Controller'],
        ['privacidad','privacy-controller','body','textarea','Información del responsable','Royal Beans Perú S.A.C. es responsable del tratamiento de los datos enviados por este sitio. Puedes contactarnos en administracion@royalbeansperu.com.','Royal Beans Perú S.A.C. is responsible for personal data submitted through this website. Contact us at administracion@royalbeansperu.com.'],
        ['privacidad','privacy-data','title','text','Datos recopilados','Datos que recopilamos','Data we collect'],
        ['privacidad','privacy-data','body','textarea','Detalle de datos recopilados','Registramos los datos que completas en el formulario: tipo de participante, nombre, empresa, país, correo, teléfono, línea y productos de interés, y mensaje. También conservamos un registro técnico de seguridad asociado al envío.','We record the details entered in the form: participant type, name, company, country, email, phone number, product line and products of interest, and message. We also keep a technical security record associated with the submission.'],
        ['privacidad','privacy-purpose','title','text','Finalidad','Para qué los usamos','How we use it'],
        ['privacidad','privacy-purpose','body','textarea','Uso de los datos','Usamos los datos para responder consultas, preparar cotizaciones, dar seguimiento comercial y prevenir envíos abusivos. No los usamos para publicidad sin una autorización adicional.','We use the data to answer enquiries, prepare quotations, provide commercial follow-up and prevent abusive submissions. We do not use it for advertising without additional permission.'],
        ['privacidad','privacy-sharing','title','text','Acceso y terceros','Acceso y terceros','Access and third parties'],
        ['privacidad','privacy-sharing','body','textarea','Acceso de terceros','El acceso se limita al personal autorizado y a proveedores de infraestructura necesarios para operar el sitio. No vendemos datos. Si eliges WhatsApp u otro enlace externo, ese proveedor aplicará su propia política.','Access is limited to authorised staff and infrastructure providers required to operate the website. We do not sell personal data. If you choose WhatsApp or another external link, that provider\'s own policy applies.'],
        ['privacidad','privacy-retention','title','text','Conservación','Conservación','Retention'],
        ['privacidad','privacy-retention','body','textarea','Tiempo de conservación','Conservamos la información mientras sea necesaria para atender la consulta y cumplir obligaciones comerciales o legales. Después se elimina o anonimiza de forma segura.','We retain information while it is needed to handle the enquiry and meet commercial or legal obligations. It is then securely deleted or anonymised.'],
        ['privacidad','privacy-rights','title','text','Derechos ARCO','Tus derechos','Your rights'],
        ['privacidad','privacy-rights','body','textarea','Ejercicio de derechos','Puedes solicitar acceso, rectificación, cancelación u oposición al tratamiento de tus datos. Envía tu solicitud e identificación a administracion@royalbeansperu.com. La atenderemos dentro de los plazos legales.','You may request access, rectification, cancellation or object to the processing of your personal data. Send your request and identification to administracion@royalbeansperu.com. We will respond within the legal time limits.'],
        ['privacidad','privacy-cookies','title','text','Cookies','Cookies y servicios externos','Cookies and external services'],
        ['privacidad','privacy-cookies','body','textarea','Cookies y servicios externos','La web pública no usa cookies publicitarias. Puede guardar preferencias técnicas en tu navegador. Mapas, videos, WhatsApp y redes sociales pueden aplicar sus propias cookies o políticas cuando los utilizas.','The public website does not use advertising cookies. It may store technical preferences in your browser. Maps, videos, WhatsApp and social networks may apply their own cookies or policies when you use them.'],
        ['terminos','legal-intro','back','text','Volver a Royal Beans Perú','← Royal Beans Perú','← Royal Beans Perú'],
        ['terminos','legal-intro','title','text','Título de términos','Términos y condiciones','Terms and conditions'],
        ['terminos','legal-intro','summary','textarea','Resumen de términos','Reglas claras para usar el sitio y solicitar información comercial.','Clear rules for using the website and requesting commercial information.'],
        ['terminos','terms-scope','title','text','Alcance','Alcance','Scope'],
        ['terminos','terms-scope','body','textarea','Alcance de los términos','Estas condiciones regulan el uso del sitio público de Royal Beans Perú S.A.C. Al navegar o enviar una consulta, aceptas estas reglas.','These terms govern use of the Royal Beans Perú S.A.C. public website. By browsing or submitting an enquiry, you accept these rules.'],
        ['terminos','terms-information','title','text','Información y cotizaciones','Información y cotizaciones','Information and quotations'],
        ['terminos','terms-information','body','textarea','Condición de la información','El contenido es informativo. No constituye una oferta ni confirma una venta. Solo una cotización o contrato emitido por Royal Beans Perú establece condiciones comerciales vinculantes.','Website content is informational. It is not an offer and does not confirm a sale. Only a quotation or contract issued by Royal Beans Perú establishes binding commercial terms.'],
        ['terminos','terms-products','title','text','Productos y operaciones','Productos y operaciones','Products and transactions'],
        ['terminos','terms-products','body','textarea','Condiciones comerciales','Disponibilidad, calidad, especificaciones, certificaciones, precios, cantidades, entrega y pago se confirman para cada operación en su documentación comercial.','Availability, quality, specifications, certifications, prices, quantities, delivery and payment are confirmed for each transaction in its commercial documents.'],
        ['terminos','terms-use','title','text','Uso permitido','Uso permitido','Permitted use'],
        ['terminos','terms-use','body','textarea','Reglas de uso','Puedes consultar el contenido y contactar a la empresa. Está prohibido interferir con el sitio, acceder a áreas restringidas, introducir código malicioso, suplantar identidades o usar la información con fines ilícitos.','You may consult the content and contact the company. You must not disrupt the website, access restricted areas, introduce malicious code, impersonate others or use information for unlawful purposes.'],
        ['terminos','terms-intellectual','title','text','Propiedad intelectual','Propiedad intelectual','Intellectual property'],
        ['terminos','terms-intellectual','body','textarea','Uso del contenido','Marcas, fotografías, diseños, textos y archivos pertenecen a Royal Beans Perú o se usan con autorización. No está permitida su reproducción o explotación comercial sin autorización escrita.','Trademarks, photographs, designs, text and files belong to Royal Beans Perú or are used with permission. Reproduction or commercial exploitation without written authorisation is not permitted.'],
        ['terminos','terms-external','title','text','Servicios externos','Servicios externos','External services'],
        ['terminos','terms-external','body','textarea','Servicios de terceros','Los enlaces a WhatsApp, mapas, videos o redes sociales llevan a servicios de terceros. Cada proveedor es responsable de su servicio, disponibilidad y políticas.','Links to WhatsApp, maps, videos or social networks lead to third-party services. Each provider is responsible for its service, availability and policies.'],
        ['terminos','terms-liability','title','text','Disponibilidad y responsabilidad','Disponibilidad y responsabilidad','Availability and liability'],
        ['terminos','terms-liability','body','textarea','Límite de responsabilidad','Procuramos mantener el sitio actualizado y disponible, pero puede contener errores o interrupciones. Royal Beans Perú no responde por el uso indebido del sitio ni por fallas de servicios externos, dentro de los límites legales.','We seek to keep the website accurate and available, but it may contain errors or interruptions. Within legal limits, Royal Beans Perú is not responsible for misuse of the website or failures of external services.'],
        ['terminos','terms-changes','title','text','Cambios y ley aplicable','Cambios y ley aplicable','Changes and applicable law'],
        ['terminos','terms-changes','body','textarea','Actualización y contacto','Podemos actualizar estas condiciones cuando cambie el sitio o la normativa. Se aplica la legislación peruana. Para consultas, escribe a administracion@royalbeansperu.com.','We may update these terms when the website or applicable rules change. Peruvian law applies. For enquiries, contact administracion@royalbeansperu.com.'],
    ] as $field) $insertLegalField->execute($field);

    $legacySettings = [];
    foreach ($db->query("SELECT setting_key,value_text FROM settings WHERE setting_key IN ('company_email','company_phone','whatsapp','instagram','facebook','address_es','address_en')") as $item) {
        $legacySettings[$item['setting_key']] = $item['value_text'];
    }
    $contactSeeds = [
        ['email','Correo','Email',$legacySettings['company_email'] ?? 'administracion@royalbeansperu.com','mailto:' . ($legacySettings['company_email'] ?? 'administracion@royalbeansperu.com'),10],
        ['phone','Teléfono','Phone',$legacySettings['company_phone'] ?? '+51 961 804 500','tel:' . preg_replace('/[^\d+]/', '', $legacySettings['company_phone'] ?? '+51 961 804 500'),20],
        ['whatsapp','WhatsApp','WhatsApp',$legacySettings['whatsapp'] ?? '51961804500','https://wa.me/' . preg_replace('/\D/', '', $legacySettings['whatsapp'] ?? '51961804500'),30],
        ['address','Dirección','Address',$legacySettings['address_es'] ?? 'Chiclayo, Lambayeque, Perú','',40],
        ['facebook','Facebook','Facebook',$legacySettings['facebook'] ?? 'Royal Beans Perú',$legacySettings['facebook'] ?? 'https://www.facebook.com/profile.php?id=61573866174999',50],
        ['instagram','Instagram','Instagram',$legacySettings['instagram'] ?? '@royalbeans_peru',$legacySettings['instagram'] ?? 'https://www.instagram.com/royalbeans_peru/',60],
        ['youtube','YouTube','YouTube','Royal Beans Perú','https://www.youtube.com/@Royalbeansperu',70],
    ];
    $hasContactType = $db->prepare('SELECT COUNT(*) FROM contact_channels WHERE channel_type=?');
    $insertContact = $db->prepare('INSERT INTO contact_channels(channel_type,label_es,label_en,value_text,link_url,is_active,sort_order) VALUES(?,?,?,?,?,1,?)');
    foreach ($contactSeeds as $contact) {
        $hasContactType->execute([$contact[0]]);
        if (!(int) $hasContactType->fetchColumn()) $insertContact->execute($contact);
    }

    $setting = $db->prepare('INSERT IGNORE INTO settings (setting_key,value_text,is_public) VALUES (?,?,1)');
    foreach ([
        'theme_palette' => 'royal',
        'theme_font' => 'bricolage-dm',
        'theme_background' => 'paper',
        'media_storage' => 'r2',
        'r2_account_id' => '',
        'r2_access_key_id' => '',
        'r2_secret_access_key' => '',
        'r2_bucket' => '',
        'r2_public_url' => '',
    ] as $key => $value) {
        $setting->execute([$key, $value]);
    }

    $contentSeedPath = __DIR__ . '/content.seed.json';
    if (is_file($contentSeedPath)) {
        $contentSeed = json_decode((string) file_get_contents($contentSeedPath), true);
        if (is_array($contentSeed)) {
            $contentStatement = $db->prepare("INSERT INTO content_fields(page_key,section_key,field_key,field_type,label,value_es,value_en) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE field_type=VALUES(field_type),label=VALUES(label),value_es=IF(value_es IS NULL OR value_es='',VALUES(value_es),value_es),value_en=IF(value_en IS NULL OR value_en='',VALUES(value_en),value_en)");
            foreach ($contentSeed as $field) {
                if (!is_array($field)) continue;
                $contentStatement->execute([$field['page_key']??'',$field['section_key']??'',$field['field_key']??'',$field['field_type']??'text',$field['label']??'Contenido',$field['value_es']??null,$field['value_en']??null]);
            }
        }
    }

    $seedMarker = $db->prepare("SELECT value_text FROM settings WHERE setting_key='collections_seed_20260915'");
    $seedMarker->execute();
    if ($seedMarker->fetchColumn() === false) {
        $db->beginTransaction();
        try {
            $insertCollection = $db->prepare('INSERT INTO cms_collections(module_key,entry_type,title_es,title_en,description_es,description_en,event_date,location_es,location_en,logo_path,cover_path,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $insertMedia = $db->prepare('INSERT INTO cms_collection_media(collection_id,media_path,caption_es,caption_en,sort_order) VALUES(?,?,?,?,?)');

            $moduleCount = $db->prepare('SELECT COUNT(*) FROM cms_collections WHERE module_key=?');
            $moduleCount->execute(['presentation']);
            if ((int) $moduleCount->fetchColumn() === 0) {
                $insertCollection->execute(['presentation','event','Expoalimentaria 2026','Expoalimentaria 2026','Del 23 al 25 de septiembre de 2026','September 23–25, 2026','2026-09-23','Lima, Perú','Lima, Peru','/images/events/expoalimentaria-2026-logo.png','/images/events/expo-2026-hero.png',1,10]);
                foreach ([
                    ['2025','Seguimos conectando el fruto del Perú con nuevos mercados.','We continued connecting Peru’s harvest with new markets.',6],
                    ['2024','Nuevos lazos para un futuro más sostenible.','New relationships for a more sustainable future.',13],
                ] as [$year, $descriptionEs, $descriptionEn, $photoCount]) {
                    $coverExtension = $year === '2025' ? 'jpeg' : 'jpg';
                    $sortOrder = $year === '2025' ? 10 : 20;
                    $insertCollection->execute(['presentation','presence',"Expoalimentaria {$year}","Expoalimentaria {$year}",$descriptionEs,$descriptionEn,"{$year}-01-01",'','','/images/events/expoalimentaria-'.$year.'-logo.png',"/images/events/expo-{$year}-04.{$coverExtension}",1,$sortOrder]);
                    $collectionId = (int) $db->lastInsertId();
                    for ($index = 1; $index <= $photoCount; $index++) {
                        $extension = $year === '2025' ? ($index <= 3 ? 'jpg' : 'jpeg') : ($index <= 5 ? 'jpg' : 'jpeg');
                        $path = sprintf('/images/events/expo-%s-%02d.%s', $year, $index, $extension);
                        $insertMedia->execute([$collectionId,$path,"Expoalimentaria {$year}","Expoalimentaria {$year}",$index]);
                    }
                }
            }

            $moduleCount->execute(['impact']);
            if ((int) $moduleCount->fetchColumn() === 0) {
                $impactPhotos = ['/images/impact-crop.webp','/images/hero-tablet.webp','/images/about-field.webp','/images/hero-desktop.webp','/images/expo-connection.webp'];
                $insertCollection->execute(['impact','presence','Oportunidades que transforman','Opportunities that transform','Historias que se construyen juntas.','Stories built together.',null,'','','',$impactPhotos[0],1,10]);
                $collectionId = (int) $db->lastInsertId();
                foreach ($impactPhotos as $index => $path) {
                    $insertMedia->execute([$collectionId,$path,'Historia de impacto','Impact story',$index + 1]);
                }
            }

            $db->prepare('INSERT INTO settings(setting_key,value_text,is_public) VALUES(?,?,0)')->execute(['collections_seed_20260915','1']);
            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    $standardCount = (int) $db->query("SELECT COUNT(*) FROM cms_collections WHERE module_key='standards'")->fetchColumn();
    if ($standardCount === 0) {
        $insertStandard = $db->prepare("INSERT INTO cms_collections(module_key,entry_type,title_es,title_en,description_es,description_en,logo_path,is_active,sort_order) VALUES('standards','standard',?,?,?,?,?,1,?)");
        foreach ([
            ['FDA','FDA','Registro internacional','International registration','/images/standards/fda.png',10],
            ['SENASA','SENASA','Certificación sanitaria','Sanitary certification','/images/standards/senasa.png',20],
            ['HACCP INTERNO','INTERNAL HACCP','Control preventivo','Preventive control','/images/standards/haccp.png',30],
            ['MARCA PERÚ','PERU BRAND','Identidad y origen peruano','Peruvian identity and origin','/images/standards/marca-peru.png',40],
        ] as $standard) $insertStandard->execute($standard);
    }

    $saveMigrationVersion = $db->prepare("INSERT INTO settings(setting_key,value_text,is_public) VALUES('cms_migration_version',?,0) ON DUPLICATE KEY UPDATE value_text=VALUES(value_text),is_public=0");
    $saveMigrationVersion->execute([$migrationVersion]);
}
