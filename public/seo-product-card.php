<?php
$cardPath = seo_catalog_product_path($row, $lang);
if ($cardPath === '') return;
$cardName = (string) ($row['name_' . $lang] ?: $row['name_es']);
$cardImage = seo_catalog_public_image($db, $row);
?>
<article class="card product-card"><a href="<?= $e($cardPath) ?>"><?php if ($cardImage !== ''): ?><img src="<?= $e($cardImage) ?>" alt="<?= $e($cardName) ?>" width="480" height="320" loading="lazy"><?php endif; ?><div><span class="eyebrow"><?= $e($row['line_slug'] === 'retail' ? ($lang === 'en' ? 'Retail' : 'Retail') : ($categories[seo_catalog_category_key((string) $row['category_slug'])][$lang]['name'] ?? '')) ?></span><h3><?= $e($cardName) ?></h3><?php $cardDescription = (string) ($row['short_' . $lang] ?? ''); if ($cardDescription !== ''): ?><p><?= $e($cardDescription) ?></p><?php endif; ?><span class="more"><?= $lang === 'en' ? 'View product →' : 'Ver producto →' ?></span></div></a></article>
