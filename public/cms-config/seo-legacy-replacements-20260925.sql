-- Reassign legacy slugs only where a published replacement is the same product.
-- Discontinued products without an equivalent published ficha remain unavailable.
UPDATE product_slug_history SET product_id=50 WHERE product_id=2;
UPDATE product_slug_history SET product_id=36 WHERE product_id=8;
UPDATE product_slug_history SET product_id=42 WHERE product_id=9;
UPDATE product_slug_history SET product_id=35 WHERE product_id=10;
UPDATE product_slug_history SET product_id=34 WHERE product_id=13;
UPDATE product_slug_history SET product_id=39 WHERE product_id=14;
UPDATE product_slug_history SET product_id=49 WHERE product_id=18;
UPDATE product_slug_history SET product_id=25 WHERE product_id=23;
UPDATE product_slug_history SET product_id=53 WHERE product_id=27;
UPDATE product_slug_history SET product_id=47 WHERE product_id=29;
