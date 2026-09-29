-- Ejecutar una vez en la base de datos de producción. No cambia slug ni rutas.
UPDATE product_lines
SET name_es = 'Línea a Granel', name_en = 'Bulk Line'
WHERE slug = 'conventional';

UPDATE content_fields
SET value_es = REPLACE(REPLACE(value_es, 'Línea Convencional', 'Línea a Granel'), 'Línea convencional', 'Línea a granel'),
    value_en = REPLACE(REPLACE(value_en, 'Conventional Line', 'Bulk Line'), 'Conventional line', 'Bulk line'),
    label = REPLACE(REPLACE(label, 'Línea Convencional', 'Línea a Granel'), 'Línea convencional', 'Línea a granel')
WHERE value_es LIKE '%Línea Convencional%'
   OR value_en LIKE '%Conventional Line%'
   OR label LIKE '%Línea Convencional%';

INSERT IGNORE INTO content_fields(page_key, section_key, field_key, field_type, label, value_es, value_en)
VALUES ('nosotros', 'about-video', 'source', 'url', 'Video institucional', '', '');
