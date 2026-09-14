<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit("CLI only\n");
$config = require dirname(__DIR__) . '/public/cms-config/database.php';
$db = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']), $config['username'], $config['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
if ((int) $db->query('SELECT COUNT(*) FROM products')->fetchColumn() > 0) exit("Products already seeded\n");
$lineId=(int)$db->query("SELECT id FROM product_lines WHERE slug='conventional'")->fetchColumn();
$categories=[];foreach($db->query('SELECT id,slug FROM product_categories') as $row)$categories[$row['slug']]=$row['id'];
$items=json_decode((string)file_get_contents(dirname(__DIR__).'/components/products.json'),true,512,JSON_THROW_ON_ERROR);
$product=$db->prepare('INSERT INTO products (line_id,category_id,image_path,is_active,featured_home,sort_order) VALUES (?,?,?,?,?,?)');
$translation=$db->prepare('INSERT INTO product_translations (product_id,locale,name,slug) VALUES (?,?,?,?)');
$slug=static function(string $value):string{$ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value)?:$value;return trim(strtolower((string)preg_replace('/[^a-zA-Z0-9]+/','-',$ascii)),'-');};
$db->beginTransaction();
foreach($items as $index=>$item){$product->execute([$lineId,$categories[$item['category']],$item['image'],1,1,$index+1]);$id=(int)$db->lastInsertId();foreach(['es','en'] as $locale)$translation->execute([$id,$locale,$item[$locale],$slug($item[$locale]).'-'.$id]);}
$db->commit();
echo count($items)." products imported\n";
