<?php
declare(strict_types=1);

function bulk_headers(string $line): array {
    $common=['ref','id','categoria','nombre_es','nombre_en','activo','mostrar_inicio','orden','seo_titulo_es','seo_titulo_en','seo_descripcion_es','seo_descripcion_en'];
    $products=$line==='retail'
        ? array_merge($common,['descripcion_es','descripcion_en','caracteristicas_es','caracteristicas_en'])
        : array_merge($common,['descripcion_breve_es','descripcion_breve_en','descripcion_completa_es','descripcion_completa_en','imagen_principal','nombre_cientifico','partida_arancelaria','calibre_es','calibre_en','destinos_es','destinos_en','ficha_pdf']);
    $sheets=['Productos'=>$products,'Presentaciones'=>['ref','presentacion','equivalencia','texto_es','texto_en','imagen','tipo','disponible','orden']];
    if($line==='conventional'){
        $sheets['Cosechas']=['ref','ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
        $sheets['Galeria']=['ref','imagen','alt_es','alt_en','orden'];
    }
    return $sheets;
}

function bulk_xml(string $content): DOMDocument {
    if(str_contains($content,'<!DOCTYPE'))throw new RuntimeException('El Excel contiene una estructura XML no permitida.');
    $document=new DOMDocument();
    if(!@$document->loadXML($content,LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING))throw new RuntimeException('El Excel contiene XML no válido.');
    return $document;
}

function bulk_zip_part(ZipArchive $zip,string $part): string {
    $value=$zip->getFromName($part);
    if($value===false||strlen($value)>12*1024*1024)throw new RuntimeException('No se pudo leer una hoja del Excel.');
    return $value;
}

function bulk_column_index(string $address): int {
    $letters=preg_replace('/[^A-Z].*$/','',strtoupper($address));$index=0;
    foreach(str_split($letters) as $letter)$index=$index*26+ord($letter)-64;
    return $index-1;
}

function bulk_read_workbook(string $path,string $line): array {
    $zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('El archivo debe ser un Excel .xlsx válido.');
    try{
        if($zip->numFiles>250)throw new RuntimeException('El Excel contiene demasiados archivos internos.');
        $expanded=0;for($entry=0;$entry<$zip->numFiles;$entry++){$expanded+=(int)($zip->statIndex($entry)['size']??0);if($expanded>50*1024*1024)throw new RuntimeException('El Excel expandido supera 50 MB.');}
        $workbook=bulk_xml(bulk_zip_part($zip,'xl/workbook.xml'));
        $relations=bulk_xml(bulk_zip_part($zip,'xl/_rels/workbook.xml.rels'));
        $xpath=new DOMXPath($workbook);$relationXpath=new DOMXPath($relations);$targets=[];
        foreach($relationXpath->query('//*[local-name()="Relationship"]') as $node)$targets[$node->getAttribute('Id')]=$node->getAttribute('Target');
        $shared=[];$sharedXml=$zip->getFromName('xl/sharedStrings.xml');
        if($sharedXml!==false){
            if(strlen($sharedXml)>12*1024*1024)throw new RuntimeException('El Excel contiene demasiados textos.');
            $strings=bulk_xml($sharedXml);$sx=new DOMXPath($strings);
            foreach($sx->query('//*[local-name()="si"]') as $item){$text='';foreach($sx->query('.//*[local-name()="t"]',$item) as $fragment)$text.=$fragment->textContent;$shared[]=$text;}
        }
        $found=[];
        foreach($xpath->query('//*[local-name()="sheet"]') as $sheet){
            $name=$sheet->getAttribute('name');if(!array_key_exists($name,bulk_headers($line)))continue;
            $relationship=$sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships','id');
            $target=$targets[$relationship]??'';
            if($target==='')throw new RuntimeException('Falta la hoja '.$name.' en el Excel.');
            $part=str_starts_with($target,'/')?ltrim($target,'/'):'xl/'.ltrim($target,'/');
            if(str_contains($part,'..'))throw new RuntimeException('Ruta inválida dentro del Excel.');
            $document=bulk_xml(bulk_zip_part($zip,$part));$sx=new DOMXPath($document);$rows=[];
            foreach($sx->query('//*[local-name()="sheetData"]/*[local-name()="row"]') as $row){
                $number=(int)$row->getAttribute('r');if($number<1)continue;
                $cells=[];
                foreach($sx->query('./*[local-name()="c"]',$row) as $cell){
                    if($sx->query('./*[local-name()="f"]',$cell)->length)throw new RuntimeException("No uses fórmulas en $name, fila $number; pega los valores.");
                    $index=bulk_column_index($cell->getAttribute('r'));if($index<0||$index>60)continue;
                    $type=$cell->getAttribute('t');$value='';
                    if($type==='inlineStr'){foreach($sx->query('.//*[local-name()="t"]',$cell) as $fragment)$value.=$fragment->textContent;}
                    else{$node=$sx->query('./*[local-name()="v"]',$cell)->item(0);$value=$node?$node->textContent:'';if($type==='s')$value=$shared[(int)$value]??'';}
                    $cells[$index]=trim((string)$value);
                }
                $rows[$number]=$cells;
            }
            $headers=array_values($rows[1]??[]);$expected=bulk_headers($line)[$name];
            for($column=0;$column<count($expected);$column++)if(($rows[1][$column]??'')!==$expected[$column])throw new RuntimeException("La hoja $name no coincide con la plantilla en la columna ".($column+1).'.');
            $data=[];
            foreach($rows as $number=>$cells){
                if($number===1)continue;if($number>1002)throw new RuntimeException("La hoja $name supera el límite de 1000 filas.");
                if(!array_filter($cells,static fn($value):bool=>$value!==''))continue;
                $mapped=['_row'=>$number];foreach($expected as $column=>$key)$mapped[$key]=$cells[$column]??'';$data[]=$mapped;
            }
            $found[$name]=$data;
        }
        foreach(bulk_headers($line) as $name=>$headers)if(!isset($found[$name]))throw new RuntimeException("Falta la hoja $name.");
        return $found;
    }finally{$zip->close();}
}

function bulk_asset_index(?string $path): array {
    if(!$path)return [];$zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('El ZIP de imágenes no es válido.');
    try{
        $found=[];$total=0;if($zip->numFiles>2000)throw new RuntimeException('El ZIP contiene demasiados archivos.');
        for($index=0;$index<$zip->numFiles;$index++){
            $item=$zip->statIndex($index);$name=(string)$item['name'];if(str_ends_with($name,'/'))continue;
            if($name!==basename($name)||str_contains($name,'\\')||str_contains($name,'..'))throw new RuntimeException('Coloca los archivos directamente dentro del ZIP, sin carpetas.');
            if(isset($found[$name]))throw new RuntimeException('El ZIP contiene un nombre de archivo duplicado: '.$name);
            $size=(int)$item['size'];$total+=$size;
            if($size>12*1024*1024||$total>250*1024*1024)throw new RuntimeException('El ZIP contiene archivos demasiado grandes.');
            $found[$name]=$size;
        }
        return $found;
    }finally{$zip->close();}
}

function bulk_asset_bytes(string $zipPath,string $name): string {
    $zip=new ZipArchive();if($zip->open($zipPath)!==true)throw new RuntimeException('No se pudo abrir el ZIP.');
    try{$bytes=$zip->getFromName($name);if($bytes===false||strlen($bytes)>12*1024*1024)throw new RuntimeException('No se pudo leer '.$name.' del ZIP.');return $bytes;}
    finally{$zip->close();}
}

function bulk_bool(string $value,?int $default=null): ?int {
    if($value==='')return $default;
    return match(mb_strtolower(trim($value))){'1','si','sí','yes','true'=>1,'0','no','false'=>0,default=>null};
}

function bulk_name_key(string $name): string {
    return admin_slug(trim(preg_replace('/\s+/u',' ',$name)??$name));
}

function bulk_validate(PDO $db,string $line,array $sheets,array $assets,?string $zipPath,array $user,bool $draftIncompleteNewRetail=false): array {
    $errors=[];$products=[];$byRef=[];$byId=[];$byName=[];$packages=[];$harvest=[];$gallery=[];$fileChecks=[];$drafted=[];
    $categories=[];
    foreach($db->query('SELECT id,slug,name_es,name_en FROM product_categories WHERE is_active=1') as $category){
        foreach(['slug','name_es','name_en'] as $field){$key=mb_strtolower(trim((string)$category[$field]));if($key==='')continue;$categories[$key]=isset($categories[$key])&&$categories[$key] !== (int)$category['id']?0:(int)$category['id'];}
    }
    $findProduct=$db->prepare('SELECT p.id,l.slug,p.image_path FROM products p JOIN product_lines l ON l.id=p.line_id WHERE p.id=? AND p.deleted_at IS NULL LIMIT 1');
    $findPackage=$db->prepare('SELECT image_path FROM product_packages WHERE product_id=? AND weight_primary=? ORDER BY sort_order,id LIMIT 1');
    $findGallery=$db->prepare("SELECT 1 FROM product_gallery WHERE product_id=? AND media_type='image' AND media_path=? LIMIT 1");
    $existingNames=[];
    $namesStatement=$db->prepare('SELECT p.id,t.locale,t.name FROM products p JOIN product_lines l ON l.id=p.line_id JOIN product_translations t ON t.product_id=p.id WHERE l.slug=? AND p.deleted_at IS NULL');
    $namesStatement->execute([$line]);
    foreach($namesStatement->fetchAll(PDO::FETCH_ASSOC) as $item)$existingNames[$item['locale']][bulk_name_key((string)$item['name'])][]=(int)$item['id'];
    $checkFile=static function(string $name,string $kind,string $where)use(&$errors,&$fileChecks,$assets,$zipPath):void{
        if($name==='')return;
        if($name!==basename($name)||str_contains($name,'\\')){$errors[]="$where: usa solo el nombre del archivo, sin carpetas.";return;}
        if(!array_key_exists($name,$assets)||!$zipPath){$errors[]="$where: falta $name en el ZIP.";return;}
        $key=$kind.':'.$name;if(isset($fileChecks[$key]))return;
        try{
            $bytes=bulk_asset_bytes($zipPath,$name);$mime=(new finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
            if($kind==='pdf'){
                if($mime!=='application/pdf'||strlen($bytes)>12*1024*1024)throw new RuntimeException('el PDF no es válido o supera 12 MB');
            }else{
                $types=['image/png'=>IMAGETYPE_PNG,'image/jpeg'=>IMAGETYPE_JPEG,'image/webp'=>IMAGETYPE_WEBP];
                $info=@getimagesizefromstring($bytes);
                if(!isset($types[$mime])||!$info||($info[2]??0)!==$types[$mime]||strlen($bytes)>8*1024*1024||$info[0]*$info[1]>50000000)throw new RuntimeException('la imagen no es PNG, JPG o WebP válido, o supera 8 MB');
            }
            $fileChecks[$key]=true;
        }catch(Throwable $error){$errors[]="$where: ".$error->getMessage().'.';}
    };
    foreach($sheets['Productos'] as $row){
        $where='Productos, fila '.$row['_row'];$ref=$row['ref'];
        if(!preg_match('/^[A-Za-z0-9_-]{1,60}$/',$ref))$errors[]="$where: ref debe tener de 1 a 60 letras, números, guiones o guiones bajos.";
        elseif(isset($byRef[$ref]))$errors[]="$where: ref duplicada ($ref).";
        else $byRef[$ref]=true;
        $id=$row['id'];
        if($id!==''&&!preg_match('/^[1-9][0-9]*$/',$id))$errors[]="$where: id debe ser un entero positivo.";
        elseif($id!==''){
            if(isset($byId[$id]))$errors[]="$where: ID duplicado en el Excel ($id).";
            $byId[$id]=true;
            $findProduct->execute([(int)$id]);$found=$findProduct->fetch();
            if(!$found||$found['slug']!==$line)$errors[]="$where: el ID $id no pertenece a esta línea o no existe.";
            elseif($line==='conventional')$row['_existing_main_image']=(string)$found['image_path'];
            if(!can($line==='retail'?'products_retail':'products_conventional','edit',$user))$errors[]="$where: no tienes permiso para actualizar.";
        }elseif(!can($line==='retail'?'products_retail':'products_conventional','create',$user))$errors[]="$where: no tienes permiso para crear.";
        $categoryKey=mb_strtolower(trim($row['categoria']));$categoryId=$categories[$categoryKey]??null;
        if(!$categoryId)$errors[]="$where: categoría inexistente, inactiva o ambigua.";
        $row['_category_id']=$categoryId;
        foreach(['nombre_es'=>190,'nombre_en'=>190,'seo_titulo_es'=>190,'seo_titulo_en'=>190,'seo_descripcion_es'=>320,'seo_descripcion_en'=>320] as $field=>$max){
            if(str_starts_with($field,'nombre_')&&$row[$field]==='')$errors[]="$where: $field es obligatorio.";
            if(mb_strlen($row[$field])>$max)$errors[]="$where: $field supera $max caracteres.";
        }
        foreach(['es','en'] as $locale){
            $name=$row['nombre_'.$locale];if($name==='')continue;
            $key=bulk_name_key($name);
            foreach($existingNames[$locale][$key]??[] as $existingId)if((int)$id!==$existingId){$errors[]="$where: $name ya existe en esta línea (ID $existingId). Escribe ese ID para actualizarlo.";break;}
            if(isset($byName[$locale][$key])&&$byName[$locale][$key]!==$ref)$errors[]="$where: $name está repetido en el Excel (ref {$byName[$locale][$key]}).";
            $byName[$locale][$key]=$ref;
        }
        foreach($line==='retail'?['descripcion_es'=>500,'descripcion_en'=>500]:['descripcion_breve_es'=>500,'descripcion_breve_en'=>500,'nombre_cientifico'=>190,'partida_arancelaria'=>80,'calibre_es'=>190,'calibre_en'=>190,'destinos_es'=>500,'destinos_en'=>500] as $field=>$max)
            if(mb_strlen($row[$field])>$max)$errors[]="$where: $field supera $max caracteres.";
        foreach(['activo','mostrar_inicio'] as $field)if(bulk_bool($row[$field])===null&&$row[$field]!=='')$errors[]="$where: $field debe ser 1 o 0.";
        if($row['orden']!==''&&!preg_match('/^-?[0-9]{1,8}$/',$row['orden']))$errors[]="$where: orden no es válido.";
        if($line==='conventional'){
            if($row['imagen_principal']!==($row['_existing_main_image']??''))$checkFile($row['imagen_principal'],'image',$where.' imagen_principal');
            $sheet=$row['ficha_pdf'];if($sheet!==''&&!preg_match('#^(https://|/uploads/)#i',$sheet))$checkFile($sheet,'pdf',$where.' ficha_pdf');
        }
        $products[$ref]=$row;
    }
    if(!$products)$errors[]='La hoja Productos está vacía.';
    foreach($sheets['Presentaciones'] as $row){
        $ref=$row['ref'];$where='Presentaciones, fila '.$row['_row'];
        if(!isset($byRef[$ref]))$errors[]="$where: ref no existe en Productos.";
        if($row['presentacion']===''||mb_strlen($row['presentacion'])>80)$errors[]="$where: presentación obligatoria (máximo 80 caracteres).";
        foreach(['equivalencia'=>80,'texto_es'=>190,'texto_en'=>190] as $field=>$max)if(mb_strlen($row[$field])>$max)$errors[]="$where: $field supera $max caracteres.";
        if($row['tipo']!==''&&!in_array($row['tipo'],['bag','sack','big-bag','other'],true))$errors[]="$where: tipo debe ser bag, sack, big-bag u other.";
        if($row['disponible']!==''&&bulk_bool($row['disponible'])===null)$errors[]="$where: disponible debe ser 1 o 0.";
        if($row['orden']!==''&&!preg_match('/^[0-9]{1,8}$/',$row['orden']))$errors[]="$where: orden debe ser un número positivo.";
        $productId=(int)($products[$ref]['id']??0);
        if($productId){$findPackage->execute([$productId,$row['presentacion']]);$row['_existing_image']=(string)($findPackage->fetchColumn()?:'');}
        if($row['imagen']!==($row['_existing_image']??''))$checkFile($row['imagen'],'image',$where.' imagen');
        $packages[$ref][]=$row;
    }
    if($line==='conventional'){
        foreach($sheets['Cosechas'] as $row){$ref=$row['ref'];$where='Cosechas, fila '.$row['_row'];if(!isset($byRef[$ref]))$errors[]="$where: ref no existe en Productos.";if(isset($harvest[$ref]))$errors[]="$where: ref duplicada.";foreach(array_slice(bulk_headers($line)['Cosechas'],1) as $month)if($row[$month]!==''&&!in_array($row[$month],['none','planting','harvest','available','limited'],true))$errors[]="$where: $month debe ser none, planting, harvest, available o limited.";$harvest[$ref]=$row;}
        foreach($sheets['Galeria'] as $row){$ref=$row['ref'];$where='Galeria, fila '.$row['_row'];if(!isset($byRef[$ref]))$errors[]="$where: ref no existe en Productos.";if($row['imagen']==='')$errors[]="$where: falta imagen.";$productId=(int)($products[$ref]['id']??0);if($productId&&$row['imagen']!==''){$findGallery->execute([$productId,$row['imagen']]);if($findGallery->fetchColumn())$row['_existing_image']=$row['imagen'];}if($row['imagen']!==($row['_existing_image']??''))$checkFile($row['imagen'],'image',$where.' imagen');foreach(['alt_es','alt_en'] as $field)if(mb_strlen($row[$field])>255)$errors[]="$where: $field supera 255 caracteres.";if($row['orden']!==''&&!preg_match('/^[0-9]{1,8}$/',$row['orden']))$errors[]="$where: orden no es válido.";$gallery[$ref][]=$row;}
    }
    if($line==='retail'){
        $findPublishablePackage=$db->prepare("SELECT 1 FROM product_packages WHERE product_id=? AND is_available=1 AND image_path<>'' LIMIT 1");
        foreach($products as $ref=>$row){
            if(bulk_bool($row['activo'],0)!==1)continue;
            if(isset($packages[$ref])){
                $ready=(bool)array_filter($packages[$ref],static fn($item):bool=>bulk_bool($item['disponible'],1)===1&&($item['imagen']!==''||!empty($item['_existing_image'])));
            }else{
                $ready=false;
                if($row['id']!==''){$findPublishablePackage->execute([(int)$row['id']]);$ready=(bool)$findPublishablePackage->fetchColumn();}
            }
            if(!$ready){
                if($draftIncompleteNewRetail&&$row['id']===''){
                    $products[$ref]['activo']='0';
                    $drafted[]=$ref;
                }else $errors[]="Productos, fila {$row['_row']}: para publicar Retail se necesita al menos una presentación disponible con imagen. Guarda el producto como borrador (activo=0) hasta completarla.";
            }
        }
    }
    return ['errors'=>$errors,'products'=>$products,'packages'=>$packages,'harvest'=>$harvest,'gallery'=>$gallery,'drafted'=>$drafted,'creates'=>count(array_filter($products,static fn($row):bool=>$row['id']==='')),'updates'=>count(array_filter($products,static fn($row):bool=>$row['id']!==''))];
}

function bulk_import_image(PDO $db,string $zipPath,string $name,int $userId,array &$created): string {
    $bytes=bulk_asset_bytes($zipPath,$name);
    if(strlen($bytes)>8*1024*1024)throw new RuntimeException('La imagen '.$name.' supera 8 MB.');
    $temp=tempnam(sys_get_temp_dir(),'rb-bulk-image-');
    if($temp===false)throw new RuntimeException('No se pudo preparar la imagen '.$name.'.');
    try{
        if(file_put_contents($temp,$bytes)!==strlen($bytes))throw new RuntimeException('No se pudo preparar la imagen '.$name.'.');
        $creation='';
        $path=store_managed_image_source($db,$temp,$name,$userId,$creation);
        if($creation!=='')$created[]=['path'=>$path,'creation'=>$creation];
        return $path;
    }finally{@unlink($temp);}
}

function bulk_import_pdf(PDO $db,string $zipPath,string $name,int $userId,array &$created): string {
    $bytes=bulk_asset_bytes($zipPath,$name);
    $folder='/uploads/'.date('Y/m');$absolute=dirname(__DIR__).str_replace('/',DIRECTORY_SEPARATOR,$folder);
    if(!is_dir($absolute)&&!mkdir($absolute,0755,true)&&!is_dir($absolute))throw new RuntimeException('No se pudo crear la carpeta de documentos.');
    $path=$folder.'/'.bin2hex(random_bytes(14)).'.pdf';$file=dirname(__DIR__).str_replace('/',DIRECTORY_SEPARATOR,$path);
    if(file_put_contents($file,$bytes)!==strlen($bytes))throw new RuntimeException('No se pudo guardar el PDF '.$name.'.');
    $created[]=['path'=>$path,'creation'=>'local'];
    register_media($db,$path,$name,'application/pdf',strlen($bytes),$userId);
    return $path;
}

function bulk_apply(PDO $db,string $line,array $validation,string $zipPath,array $user): array {
    if($validation['errors'])throw new RuntimeException('Corrige los errores antes de importar.');
    $lineStatement=$db->prepare('SELECT id FROM product_lines WHERE slug=? LIMIT 1');$lineStatement->execute([$line]);$lineId=(int)$lineStatement->fetchColumn();
    if(!$lineId)throw new RuntimeException('No existe la línea de productos.');
    $userId=(int)$user['id'];$created=[];$ids=[];$replacedMedia=[];
    $months=['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    try{
        $db->beginTransaction();
        $find=$db->prepare('SELECT image_path FROM products WHERE id=? AND line_id=? AND deleted_at IS NULL FOR UPDATE');
        $insert=$db->prepare('INSERT INTO products(line_id,category_id,image_path,is_active,featured_home,sort_order) VALUES(?,?,?,?,?,?)');
        $update=$db->prepare('UPDATE products SET category_id=?,image_path=?,is_active=?,featured_home=?,sort_order=? WHERE id=? AND line_id=? AND deleted_at IS NULL');
        $translation=$db->prepare('INSERT INTO product_translations(product_id,locale,name,slug,short_description,description,seo_title,seo_description) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE name=VALUES(name),slug=VALUES(slug),short_description=VALUES(short_description),description=VALUES(description),seo_title=VALUES(seo_title),seo_description=VALUES(seo_description)');
        $existingTranslationSlugs=$db->prepare('SELECT locale,slug FROM product_translations WHERE product_id=?');
        $spec=$db->prepare('INSERT INTO product_specs(product_id,scientific_name,tariff_code,caliber_es,caliber_en,destinations_es,destinations_en,technical_sheet_path) VALUES(?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE scientific_name=VALUES(scientific_name),tariff_code=VALUES(tariff_code),caliber_es=VALUES(caliber_es),caliber_en=VALUES(caliber_en),destinations_es=VALUES(destinations_es),destinations_en=VALUES(destinations_en),technical_sheet_path=VALUES(technical_sheet_path)');
        $oldSheet=$db->prepare('SELECT technical_sheet_path FROM product_specs WHERE product_id=?');
        $deletePackages=$db->prepare('DELETE FROM product_packages WHERE product_id=?');
        $package=$db->prepare('INSERT INTO product_packages(product_id,weight_primary,weight_secondary,material_es,material_en,image_path,is_available,package_type,sort_order) VALUES(?,?,?,?,?,?,?,?,?)');
        $findPublishablePackage=$db->prepare("SELECT 1 FROM product_packages WHERE product_id=? AND is_available=1 AND image_path<>'' LIMIT 1");
        $deleteHarvest=$db->prepare('DELETE FROM product_harvest WHERE product_id=?');
        $harvest=$db->prepare('INSERT INTO product_harvest(product_id,month_number,availability) VALUES(?,?,?)');
        $deleteGallery=$db->prepare("DELETE FROM product_gallery WHERE product_id=? AND media_type='image'");
        $gallery=$db->prepare("INSERT INTO product_gallery(product_id,media_type,media_path,alt_es,alt_en,sort_order) VALUES(?,'image',?,?,?,?)");
        foreach($validation['products'] as $ref=>$row){
            $id=(int)$row['id'];$image='';
            if($id){$find->execute([$id,$lineId]);$image=$find->fetchColumn();if($image===false)throw new RuntimeException('El producto '.$id.' cambió o ya no pertenece a esta línea.');$image=(string)$image;$replacedMedia[$id][]=$image;
                if(isset($validation['packages'][$ref])){$old=$db->prepare('SELECT image_path FROM product_packages WHERE product_id=?');$old->execute([$id]);$replacedMedia[$id]=array_merge($replacedMedia[$id],$old->fetchAll(PDO::FETCH_COLUMN));}
                if($line==='conventional'&&isset($validation['gallery'][$ref])){$old=$db->prepare("SELECT media_path FROM product_gallery WHERE product_id=? AND media_type='image'");$old->execute([$id]);$replacedMedia[$id]=array_merge($replacedMedia[$id],$old->fetchAll(PDO::FETCH_COLUMN));}
            }
            if($line==='conventional'&&$row['imagen_principal']!==''&&$row['imagen_principal']!==$image)$image=bulk_import_image($db,$zipPath,$row['imagen_principal'],$userId,$created);
            $active=bulk_bool($row['activo'],0);$featured=bulk_bool($row['mostrar_inicio'],0);$order=$row['orden']===''?100:(int)$row['orden'];
            if($id){$update->execute([(int)$row['_category_id'],$image,$active,$featured,$order,$id,$lineId]);}
            else{$insert->execute([$lineId,(int)$row['_category_id'],$image,$active,$featured,$order]);$id=(int)$db->lastInsertId();}
            $existingTranslationSlugs->execute([$id]);$savedSlugs=array_column($existingTranslationSlugs->fetchAll(),'slug','locale');
            foreach(['es','en'] as $locale){
                $name=$row['nombre_'.$locale];
                $short=$line==='retail'?$row['descripcion_'.$locale]:$row['descripcion_breve_'.$locale];
                $description=$line==='retail'?$row['caracteristicas_'.$locale]:$row['descripcion_completa_'.$locale];
                $translation->execute([$id,$locale,$name,$savedSlugs[$locale]??(admin_slug($name).'-'.$id),$short,$description,$row['seo_titulo_'.$locale],$row['seo_descripcion_'.$locale]]);
            }
            if($line==='conventional'){
                $oldSheet->execute([$id]);$sheet=(string)($oldSheet->fetchColumn()?:'');
                if($row['ficha_pdf']!=='')$sheet=preg_match('#^(https://|/uploads/)#i',$row['ficha_pdf'])?$row['ficha_pdf']:bulk_import_pdf($db,$zipPath,$row['ficha_pdf'],$userId,$created);
                $spec->execute([$id,$row['nombre_cientifico'],$row['partida_arancelaria'],$row['calibre_es'],$row['calibre_en'],$row['destinos_es'],$row['destinos_en'],$sheet]);
            }
            if(isset($validation['packages'][$ref])){
                $deletePackages->execute([$id]);$firstImage='';$firstAvailable='';
                foreach($validation['packages'][$ref] as $index=>$item){
                    $packageImage=$item['imagen']!==''&&$item['imagen']!==($item['_existing_image']??'')?bulk_import_image($db,$zipPath,$item['imagen'],$userId,$created):(string)($item['_existing_image']??'');
                    $available=bulk_bool($item['disponible'],1);if($firstImage===''&&$packageImage!=='')$firstImage=$packageImage;if($firstAvailable===''&&$available&&$packageImage!=='')$firstAvailable=$packageImage;
                    $package->execute([$id,$item['presentacion'],$item['equivalencia'],$item['texto_es'],$item['texto_en'],$packageImage,$available,$item['tipo']?:'sack',$item['orden']===''?($index+1):(int)$item['orden']]);
                }
                if($line==='retail')$update->execute([(int)$row['_category_id'],$firstAvailable?:$firstImage?:$image,$active,$featured,$order,$id,$lineId]);
            }
            if($line==='retail'&&$active===1){
                $findPublishablePackage->execute([$id]);
                if(!$findPublishablePackage->fetchColumn())throw new RuntimeException('Para publicar Retail se necesita al menos una presentación disponible con imagen.');
            }
            if($line==='conventional'&&isset($validation['harvest'][$ref])){
                $deleteHarvest->execute([$id]);foreach($months as $monthIndex=>$month)$harvest->execute([$id,$monthIndex+1,$validation['harvest'][$ref][$month]?:'none']);
            }
            if($line==='conventional'&&isset($validation['gallery'][$ref])){
                $deleteGallery->execute([$id]);foreach($validation['gallery'][$ref] as $index=>$item){$galleryImage=($item['_existing_image']??'')!==''?$item['_existing_image']:bulk_import_image($db,$zipPath,$item['imagen'],$userId,$created);$gallery->execute([$id,$galleryImage,$item['alt_es'],$item['alt_en'],$item['orden']===''?($index+1):(int)$item['orden']]);}
            }
            $ids[]=$id;
        }
        audit($db,$userId,'bulk-import','products',$line,['ids'=>$ids,'created'=>$validation['creates'],'updated'=>$validation['updates']]);
        $db->commit();
        $created=[];foreach($replacedMedia as $productId=>$paths){$cleanup=release_replaced_media($db,$paths,current_product_media_paths($db,(int)$productId),$userId,'product',$productId);if($cleanup['errors'])audit($db,$userId,'cleanup-failed','media',$productId,$cleanup);}
        mark_public_content_changed('products');
        return ['created'=>$validation['creates'],'updated'=>$validation['updates'],'ids'=>$ids];
    }catch(Throwable $error){
        if($db->inTransaction())$db->rollBack();
        cleanup_failed_uploads($db,$created);
        foreach($created as $asset)if(($asset['creation']??'')==='local'){
            $path=(string)$asset['path'];if(str_starts_with($path,'/uploads/')&&!str_contains($path,'..'))@unlink(dirname(__DIR__).str_replace('/',DIRECTORY_SEPARATOR,$path));
        }
        throw $error;
    }
}

function bulk_current_rows(PDO $db,string $line): array {
    $rows=array_fill_keys(array_keys(bulk_headers($line)),[]);
    $statement=$db->prepare('SELECT p.*,c.slug category_slug FROM products p JOIN product_lines l ON l.id=p.line_id JOIN product_categories c ON c.id=p.category_id WHERE l.slug=? AND p.deleted_at IS NULL ORDER BY p.sort_order,p.id');
    $statement->execute([$line]);$products=$statement->fetchAll(PDO::FETCH_ASSOC);
    if(count($products)>1000)throw new RuntimeException('La línea supera 1000 productos. Divide la exportación en lotes.');
    $byId=[];foreach($products as $product)$byId[(int)$product['id']]=$product;
    $readRelated=static function(PDO $db,string $table,string $line,string $order=''):array{
        $sql="SELECT r.* FROM $table r JOIN products p ON p.id=r.product_id JOIN product_lines l ON l.id=p.line_id WHERE l.slug=? AND p.deleted_at IS NULL".$order;
        $stmt=$db->prepare($sql);$stmt->execute([$line]);return $stmt->fetchAll(PDO::FETCH_ASSOC);
    };
    $translations=[];foreach($readRelated($db,'product_translations',$line) as $item)$translations[(int)$item['product_id']][$item['locale']]=$item;
    $specs=[];if($line==='conventional')foreach($readRelated($db,'product_specs',$line) as $item)$specs[(int)$item['product_id']]=$item;
    foreach($products as $product){
        $id=(int)$product['id'];$ref='p'.$id;$es=$translations[$id]['es']??[];$en=$translations[$id]['en']??[];
        $row=array_fill_keys(bulk_headers($line)['Productos'],'');
        $row['ref']=$ref;$row['id']=(string)$id;$row['categoria']=(string)$product['category_slug'];
        $row['nombre_es']=(string)($es['name']??'');$row['nombre_en']=(string)($en['name']??'');
        $row['activo']=(string)$product['is_active'];$row['mostrar_inicio']=(string)$product['featured_home'];$row['orden']=(string)$product['sort_order'];
        foreach(['es'=>$es,'en'=>$en] as $locale=>$translation){
            $row['seo_titulo_'.$locale]=(string)($translation['seo_title']??'');
            $row['seo_descripcion_'.$locale]=(string)($translation['seo_description']??'');
            if($line==='retail'){$row['descripcion_'.$locale]=(string)($translation['short_description']??'');$row['caracteristicas_'.$locale]=(string)($translation['description']??'');}
            else{$row['descripcion_breve_'.$locale]=(string)($translation['short_description']??'');$row['descripcion_completa_'.$locale]=(string)($translation['description']??'');}
        }
        if($line==='conventional'){
            $spec=$specs[$id]??[];$row['imagen_principal']=(string)$product['image_path'];
            foreach(['scientific_name'=>'nombre_cientifico','tariff_code'=>'partida_arancelaria','caliber_es'=>'calibre_es','caliber_en'=>'calibre_en','destinations_es'=>'destinos_es','destinations_en'=>'destinos_en','technical_sheet_path'=>'ficha_pdf'] as $source=>$target)$row[$target]=(string)($spec[$source]??'');
        }
        $rows['Productos'][]=$row;
    }
    foreach($readRelated($db,'product_packages',$line,' ORDER BY p.sort_order,p.id,r.sort_order,r.id') as $item){
        $row=array_fill_keys(bulk_headers($line)['Presentaciones'],'');
        $row['ref']='p'.$item['product_id'];$row['presentacion']=(string)$item['weight_primary'];$row['equivalencia']=(string)$item['weight_secondary'];
        $row['texto_es']=(string)$item['material_es'];$row['texto_en']=(string)$item['material_en'];$row['imagen']=(string)$item['image_path'];
        $row['tipo']=(string)$item['package_type'];$row['disponible']=(string)$item['is_available'];$row['orden']=(string)$item['sort_order'];
        $rows['Presentaciones'][]=$row;
    }
    if($line==='conventional'){
        $months=['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];$harvest=[];
        foreach($readRelated($db,'product_harvest',$line) as $item){$id=(int)$item['product_id'];$month=(int)$item['month_number'];if($month>=1&&$month<=12)$harvest[$id][$months[$month-1]]=(string)$item['availability'];}
        foreach($harvest as $id=>$values){$row=array_fill_keys(bulk_headers($line)['Cosechas'],'');$row['ref']='p'.$id;foreach($months as $month)$row[$month]=$values[$month]??'none';$rows['Cosechas'][]=$row;}
        foreach($readRelated($db,'product_gallery',$line," AND r.media_type='image' ORDER BY p.sort_order,p.id,r.sort_order,r.id") as $item){
            $row=array_fill_keys(bulk_headers($line)['Galeria'],'');$row['ref']='p'.$item['product_id'];$row['imagen']=(string)$item['media_path'];
            $row['alt_es']=(string)$item['alt_es'];$row['alt_en']=(string)$item['alt_en'];$row['orden']=(string)$item['sort_order'];$rows['Galeria'][]=$row;
        }
    }
    foreach($rows as $sheet=>$items)if(count($items)>1000)throw new RuntimeException("La hoja $sheet supera 1000 filas. Divide la exportación en lotes.");
    return $rows;
}

function bulk_column_name(int $index): string {
    $name='';for($value=$index+1;$value>0;$value=intdiv($value-1,26))$name=chr(65+(($value-1)%26)).$name;
    return $name;
}

function bulk_export_current(PDO $db,string $line,string $template): string {
    $rows=bulk_current_rows($db,$line);
    $output=tempnam(sys_get_temp_dir(),'rb-export-');
    if($output===false||!copy($template,$output))throw new RuntimeException('No se pudo crear el Excel descargable.');
    $zip=new ZipArchive();if($zip->open($output)!==true){@unlink($output);throw new RuntimeException('No se pudo abrir la plantilla Excel.');}
    try{
        $workbook=bulk_xml(bulk_zip_part($zip,'xl/workbook.xml'));
        $relations=bulk_xml(bulk_zip_part($zip,'xl/_rels/workbook.xml.rels'));
        $targets=[];$relationXPath=new DOMXPath($relations);
        foreach($relationXPath->query('//*[local-name()="Relationship"]') as $item)$targets[$item->getAttribute('Id')]=$item->getAttribute('Target');
        $workbookXPath=new DOMXPath($workbook);
        $namespace='http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        foreach($workbookXPath->query('//*[local-name()="sheet"]') as $sheet){
            $name=$sheet->getAttribute('name');if(!isset($rows[$name]))continue;
            $relationship=$sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships','id');
            $target=$targets[$relationship]??'';if($target==='')throw new RuntimeException('La plantilla Excel no tiene la hoja '.$name.'.');
            $part=str_starts_with($target,'/')?ltrim($target,'/'):'xl/'.ltrim($target,'/');
            if(str_contains($part,'..'))throw new RuntimeException('Ruta inválida dentro del Excel.');
            $document=bulk_xml(bulk_zip_part($zip,$part));$xpath=new DOMXPath($document);
            $sheetData=$xpath->query('//*[local-name()="sheetData"]')->item(0);
            $prototype=$xpath->query('./*[local-name()="row" and @r="2"]',$sheetData)->item(0);
            if(!$sheetData||!$prototype)throw new RuntimeException('La hoja '.$name.' no tiene una fila modelo.');
            $prototype=$prototype->cloneNode(true);
            foreach(iterator_to_array($xpath->query('./*[local-name()="row"]',$sheetData)) as $oldRow)if((int)$oldRow->getAttribute('r')>1)$sheetData->removeChild($oldRow);
            $headers=bulk_headers($line)[$name];
            foreach($rows[$name] as $index=>$values){
                $number=$index+2;$newRow=$prototype->cloneNode(true);$newRow->setAttribute('r',(string)$number);
                foreach($xpath->query('./*[local-name()="c"]',$newRow) as $cell){
                    $column=bulk_column_index($cell->getAttribute('r'));
                    if(!isset($headers[$column]))continue;
                    $cell->setAttribute('r',bulk_column_name($column).$number);
                    while($cell->firstChild)$cell->removeChild($cell->firstChild);
                    $value=(string)($values[$headers[$column]]??'');
                    if($value===''){$cell->removeAttribute('t');continue;}
                    if(in_array($headers[$column],['id','activo','mostrar_inicio','orden','disponible'],true)&&preg_match('/^[0-9]+$/',$value)){
                        $cell->setAttribute('t','n');$cell->appendChild($document->createElementNS($namespace,'x:v',$value));
                    }else{
                        $cell->setAttribute('t','inlineStr');$inline=$document->createElementNS($namespace,'x:is');$text=$document->createElementNS($namespace,'x:t');
                        $text->setAttributeNS('http://www.w3.org/XML/1998/namespace','xml:space','preserve');
                        $text->appendChild($document->createTextNode(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u','',$value)??''));
                        $inline->appendChild($text);$cell->appendChild($inline);
                    }
                }
                $sheetData->appendChild($newRow);
            }
            if(!$rows[$name])$sheetData->appendChild($prototype);
            $end=max(31,count($rows[$name])+2);
            foreach($xpath->query('//*[local-name()="dataValidation"]') as $validation){
                $ranges=preg_split('/\s+/',trim($validation->getAttribute('sqref')));$updated=[];
                foreach($ranges as $range)$updated[]=preg_replace_callback('/^([A-Z]+)2:([A-Z]+)31$/',static fn($m):string=>$m[1].'2:'.$m[2].$end,$range);
                $validation->setAttribute('sqref',implode(' ',$updated));
            }
            $xml=$document->saveXML();if($xml===false||strlen($xml)>12*1024*1024)throw new RuntimeException('La hoja '.$name.' es demasiado grande.');
            if(!$zip->addFromString($part,$xml))throw new RuntimeException('No se pudo completar el Excel.');
        }
    }catch(Throwable $error){$zip->close();@unlink($output);throw $error;}
    $zip->close();return $output;
}
