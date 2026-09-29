-- Existing commercial names and specifications remain sourced from product_translations.
INSERT IGNORE INTO product_slug_history(product_id,locale,old_slug)
SELECT product_id,locale,slug FROM product_translations
WHERE product_id=24 AND locale='en' AND slug='green-beans-24';

UPDATE product_translations
SET name='Peruvian Chaucha Beans',
    slug='chaucha-beans-24',
    seo_title='Peruvian Chaucha Beans | Royal Beans Perú',
    seo_description='Peruvian Chaucha beans (Phaseolus vulgaris) with red, cream-mottled grains. Enquire about specifications and packaging for export.'
WHERE product_id=24 AND locale='en' AND slug='green-beans-24';

UPDATE product_translations
SET seo_title='Frejol Loctao Jumbo Peruano para Exportación | Royal Beans Perú'
WHERE product_id=1 AND locale='es' AND seo_title LIKE '%Raw Food%';

UPDATE product_translations
SET seo_title='Peruvian Jumbo Mung Beans for Export | Royal Beans Perú'
WHERE product_id=1 AND locale='en' AND seo_title LIKE '%Raw Food%';

UPDATE product_translations
SET seo_title=CONCAT(LEFT(seo_title,CHAR_LENGTH(seo_title)-CHAR_LENGTH(' | Royal Beans Peru')),' | Royal Beans Perú')
WHERE seo_title LIKE '% | Royal Beans Peru';

UPDATE product_translations
SET seo_title=CONCAT(seo_title,' Perú')
WHERE seo_title LIKE '% | Royal Beans';

UPDATE product_translations t
JOIN products p ON p.id=t.product_id
JOIN product_lines l ON l.id=p.line_id
SET t.seo_title=REPLACE(t.seo_title,' | Royal Beans Perú',' Retail | Royal Beans Perú')
WHERE l.slug='retail' AND t.seo_title LIKE '% | Royal Beans Perú' AND t.seo_title NOT LIKE '% Retail | Royal Beans Perú';
