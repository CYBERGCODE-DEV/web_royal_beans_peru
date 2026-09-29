<?php
declare(strict_types=1);

function migrate_catalog_seo(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS product_slug_history (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        product_id BIGINT UNSIGNED NOT NULL,
        locale ENUM('es','en') NOT NULL,
        old_slug VARCHAR(210) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_old_product_slug (locale,old_slug),
        KEY idx_product_slug_history_product (product_id),
        CONSTRAINT fk_product_slug_history_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $column = $db->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='product_categories' AND column_name=?");
    foreach (['seo_slug_es', 'seo_slug_en'] as $name) {
        $column->execute([$name]);
        if (!(bool) $column->fetchColumn()) $db->exec("ALTER TABLE product_categories ADD $name VARCHAR(100) NOT NULL DEFAULT ''");
    }
    $db->exec("UPDATE product_categories SET
        seo_slug_es=CASE slug WHEN 'pulses' THEN 'legumbres' WHEN 'grains' THEN 'granos-andinos' WHEN 'corn' THEN 'maices' WHEN 'spices' THEN 'especias' ELSE slug END,
        seo_slug_en=CASE slug WHEN 'grains' THEN 'andean-grains' ELSE slug END
        WHERE seo_slug_es='' OR seo_slug_en=''");

    $marker = $db->query("SELECT value_text FROM settings WHERE setting_key='seo_slug_alias_seed_20260925' LIMIT 1")->fetchColumn();
    if ($marker === false) {
        $aliases = $db->query("SELECT t.product_id,t.locale,t.slug,l.slug AS line_slug FROM product_translations t JOIN products p ON p.id=t.product_id JOIN product_lines l ON l.id=p.line_id");
        $save = $db->prepare('INSERT IGNORE INTO product_slug_history(product_id,locale,old_slug) VALUES(?,?,?)');
        foreach ($aliases as $row) {
            $id = (int) $row['product_id'];
            $old = (string) preg_replace('/-' . $id . '$/', '', (string) $row['slug']);
            if ($row['line_slug'] === 'retail') $old = 'retail-' . $old;
            if ($old !== $row['slug'] && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $old)) $save->execute([$id, $row['locale'], $old]);
        }
        $sql = file_get_contents(__DIR__ . '/seo-legacy-aliases.sql');
        if ($sql !== false) $db->exec($sql);
        $db->exec("INSERT INTO settings(setting_key,value_text,is_public) VALUES('seo_slug_alias_seed_20260925','1',0)");
    }

    $editorialMarker = $db->query("SELECT value_text FROM settings WHERE setting_key='seo_editorial_20260925' LIMIT 1")->fetchColumn();
    if ($editorialMarker === false) {
        $editorialSql = (string) file_get_contents(__DIR__ . '/seo-editorial-20260925.sql');
        $db->beginTransaction();
        try {
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $editorialSql) as $statement) {
                $statement = trim((string) preg_replace('/^--[^\r\n]*(?:\r?\n|$)/m', '', $statement));
                if ($statement !== '') $db->exec($statement);
            }
            $db->exec("INSERT INTO settings(setting_key,value_text,is_public) VALUES('seo_editorial_20260925','1',0)");
            $db->commit();
        } catch (Throwable $error) {
            $db->rollBack();
            throw $error;
        }
    }

    $replacementMarker = $db->query("SELECT value_text FROM settings WHERE setting_key='seo_legacy_replacements_20260925' LIMIT 1")->fetchColumn();
    if ($replacementMarker === false) {
        $replacementSql = (string) file_get_contents(__DIR__ . '/seo-legacy-replacements-20260925.sql');
        $db->beginTransaction();
        try {
            foreach (preg_split('/;\s*(?:\r?\n|$)/', $replacementSql) as $statement) {
                $statement = trim((string) preg_replace('/^--[^\r\n]*(?:\r?\n|$)/m', '', $statement));
                if ($statement !== '') $db->exec($statement);
            }
            $db->exec("INSERT INTO settings(setting_key,value_text,is_public) VALUES('seo_legacy_replacements_20260925','1',0)");
            $db->commit();
        } catch (Throwable $error) {
            $db->rollBack();
            throw $error;
        }
    }

    $nameAliasMarker = $db->query("SELECT value_text FROM settings WHERE setting_key='seo_name_aliases_20260925' LIMIT 1")->fetchColumn();
    if ($nameAliasMarker === false) {
        $db->beginTransaction();
        try {
            $aliases = $db->query("SELECT t.product_id,t.locale,t.name,t.slug FROM product_translations t JOIN products p ON p.id=t.product_id WHERE p.is_active=1 AND p.deleted_at IS NULL");
            $save = $db->prepare('INSERT IGNORE INTO product_slug_history(product_id,locale,old_slug) VALUES(?,?,?)');
            $current = $db->prepare('SELECT product_id FROM product_translations WHERE locale=? AND slug=? LIMIT 1');
            foreach ($aliases as $row) {
                $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim((string) $row['name'])) ?: (string) $row['name'];
                $old = trim(strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii)), '-');
                if ($old === '' || $old === $row['slug']) continue;
                $current->execute([$row['locale'], $old]);
                if ($current->fetchColumn() !== false) continue;
                $save->execute([(int) $row['product_id'], $row['locale'], $old]);
            }
            $db->exec("INSERT INTO settings(setting_key,value_text,is_public) VALUES('seo_name_aliases_20260925','1',0)");
            $db->commit();
        } catch (Throwable $error) {
            $db->rollBack();
            throw $error;
        }
    }
}
