<?php
declare(strict_types=1);
require __DIR__.'/_bootstrap.php';
require __DIR__.'/product-import-lib.php';

$user=require_admin();
$db=admin_db();
$line=(string)($_POST['line']??$_GET['line']??(can('products_conventional','view',$user)?'conventional':'retail'));
if(!in_array($line,['conventional','retail'],true))$line='conventional';
$section=$line==='retail'?'products_retail':'products_conventional';
require_permission($section,'view');
$template=$line==='retail'?'Plantilla_Retail.xlsx':'Plantilla_Convencional.xlsx';
$error='';

if(($_GET['download']??'')==='current'){
    try{
        $download=bulk_export_current($db,$line,__DIR__.'/templates/'.$template);
        try{
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="Productos_'.($line==='retail'?'Retail':'Granel').'_actuales.xlsx"');
            header('Cache-Control: no-store, max-age=0');
            header('Content-Length: '.filesize($download));
            readfile($download);
        }finally{@unlink($download);}
        exit;
    }catch(Throwable $exception){$error='No se pudo descargar el Excel actual: '.$exception->getMessage();}
}

$canWrite=can($section,'create',$user)||can($section,'edit',$user);
$preview=null;
$clearStage=static function():void {
    $stage=$_SESSION['bulk_import']??null;
    if(is_array($stage))foreach(['xlsx','zip'] as $key){
        $path=$stage[$key]??'';
        if(is_string($path)&&str_starts_with($path,sys_get_temp_dir().DIRECTORY_SEPARATOR.'rb-import-')&&is_file($path))@unlink($path);
    }
    unset($_SESSION['bulk_import']);
};
$stage=$_SESSION['bulk_import']??null;
if(is_array($stage)&&time()-(int)($stage['created']??0)>3600){$clearStage();$stage=null;}

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST')try{
    verify_csrf();
    if(!$canWrite)throw new RuntimeException('No tienes permiso para importar productos.');
    $action=(string)($_POST['action']??'');
    if($action==='cancel'){$clearStage();header('Location: /admin/product-import.php?line='.$line);exit;}
    if($action==='preview'){
        $clearStage();
        $xlsx=$_FILES['xlsx']??[];$zip=$_FILES['assets']??[];
        if(($xlsx['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||($xlsx['size']??0)>5*1024*1024)throw new RuntimeException('Selecciona un Excel .xlsx válido de máximo 5 MB.');
        if(strtolower(pathinfo((string)($xlsx['name']??''),PATHINFO_EXTENSION))!=='xlsx')throw new RuntimeException('El archivo de datos debe tener extensión .xlsx.');
        $hasZip=($zip['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE;
        if($hasZip&&(($zip['error']??0)!==UPLOAD_ERR_OK||($zip['size']??0)>80*1024*1024||strtolower(pathinfo((string)($zip['name']??''),PATHINFO_EXTENSION))!=='zip'))throw new RuntimeException('El paquete de imágenes debe ser un ZIP válido de máximo 80 MB.');
        $xlsxPath=tempnam(sys_get_temp_dir(),'rb-import-xlsx-');
        if($xlsxPath===false||!move_uploaded_file((string)$xlsx['tmp_name'],$xlsxPath))throw new RuntimeException('No se pudo preparar el Excel.');
        $zipPath='';
        if($hasZip){
            $zipPath=tempnam(sys_get_temp_dir(),'rb-import-zip-');
            if($zipPath===false||!move_uploaded_file((string)$zip['tmp_name'],$zipPath)){
                @unlink($xlsxPath);
                throw new RuntimeException('No se pudo preparar el ZIP.');
            }
        }
        $_SESSION['bulk_import']=['line'=>$line,'xlsx'=>$xlsxPath,'zip'=>$zipPath,'token'=>bin2hex(random_bytes(24)),'created'=>time(),'draft_incomplete_new'=>false];
        $stage=$_SESSION['bulk_import'];
    }elseif($action==='confirm'||$action==='preview-drafts'){
        if(!is_array($stage)||($stage['line']??'')!==$line||!hash_equals((string)($stage['token']??''),(string)($_POST['token']??'')))throw new RuntimeException('La previsualización expiró. Vuelve a cargar el Excel.');
        if(!is_file((string)$stage['xlsx'])||(($stage['zip']??'')!==''&&!is_file((string)$stage['zip'])))throw new RuntimeException('Los archivos temporales ya no están disponibles. Vuelve a cargar el Excel.');
        if($action==='preview-drafts'){
            if($line!=='retail')throw new RuntimeException('Esta opción solo corresponde a Retail.');
            $stage['draft_incomplete_new']=true;
            $_SESSION['bulk_import']=$stage;
        }
    }else throw new RuntimeException('Acción no válida.');
    $sheets=bulk_read_workbook((string)$stage['xlsx'],$line);
    $assets=bulk_asset_index(($stage['zip']??'')?:null);
    $preview=bulk_validate($db,$line,$sheets,$assets,($stage['zip']??'')?:null,$user,(bool)($stage['draft_incomplete_new']??false));
    if($action==='confirm'){
        if($preview['errors'])throw new RuntimeException('El Excel cambió o contiene errores. No se aplicó ningún cambio.');
        $result=bulk_apply($db,$line,$preview,(string)($stage['zip']??''),$user);
        $clearStage();
        flash('success','Importación completa: '.$result['created'].' creados y '.$result['updated'].' actualizados.');
        header('Location: /admin/product-import.php?line='.$line);exit;
    }
}catch(Throwable $exception){$error=$exception->getMessage();}

$flash=pull_flash();
$categories=$db->query('SELECT slug,name_es FROM product_categories WHERE is_active=1 ORDER BY sort_order,name_es')->fetchAll();
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Importar productos | Royal Beans</title><link rel="stylesheet" href="/admin/admin.css">
<style>
.bulk-import{max-width:1200px}.bulk-tabs,.bulk-actions,.bulk-downloads{display:flex;gap:10px;align-items:center;flex-wrap:wrap}.bulk-tabs{margin:12px 0 20px}.bulk-tabs a{border:1px solid #cbdacb;border-radius:8px;padding:10px 16px;text-decoration:none;color:#0c3129}.bulk-tabs a.active{background:#0c3129;color:white}.bulk-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:18px}.bulk-import .admin-panel{padding:24px}.bulk-import ol{padding-left:20px;line-height:1.7}.bulk-import label{display:block;margin:18px 0 7px;font-weight:700}.bulk-import input[type=file]{display:block;width:100%;padding:12px;border:1px solid #cbdacb;border-radius:8px;background:white}.bulk-note{color:#536a62;font-size:.91rem;line-height:1.55}.bulk-downloads{margin:14px 0}.bulk-table-wrap{overflow:auto}.bulk-table{width:100%;border-collapse:collapse}.bulk-table th,.bulk-table td{text-align:left;padding:11px;border-bottom:1px solid #dce7db}.bulk-errors{padding-left:20px;color:#a22929}.bulk-actions{margin-top:20px}.bulk-categories{columns:2;margin:0;padding-left:20px}.bulk-categories li{break-inside:avoid}.bulk-kpi{display:flex;gap:20px;margin:16px 0}.bulk-kpi strong{font-size:1.4rem}.bulk-kpi span{display:block;font-size:.8rem;color:#536a62}@media(max-width:850px){.bulk-grid{grid-template-columns:1fr}.bulk-categories{columns:1}}
</style>
</head>
<body class="admin-body">
<aside class="admin-sidebar"><a class="admin-brand" href="/admin/"><img src="/images/logo.webp" alt=""><span><strong>ROYAL BEANS</strong><small>ADMINISTRACIÓN</small></span></a><nav><a href="/admin/">Resumen</a><a class="active" href="/admin/?view=products">Productos</a></nav><div class="admin-user"><strong><?=e($user['name'])?></strong><a href="/admin/?view=logout">Cerrar sesión</a></div></aside>
<main class="admin-main bulk-import">
<header class="admin-top"><div><p class="admin-eyebrow">Productos</p><h1>Importar productos por lotes</h1><p>Descarga, completa y revisa el Excel de una línea a la vez.</p></div><a class="secondary-button" href="/admin/?view=products&amp;line=<?=e($line)?>">Volver a productos</a></header>
<?php if($flash):?><div class="alert <?=e($flash[0])?>"><?=e($flash[1])?></div><?php endif;?>
<?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<div class="bulk-tabs">
<?php if(can('products_conventional','view',$user)):?><a class="<?=$line==='conventional'?'active':''?>" href="/admin/product-import.php?line=conventional">Línea a Granel</a><?php endif;?>
<?php if(can('products_retail','view',$user)):?><a class="<?=$line==='retail'?'active':''?>" href="/admin/product-import.php?line=retail">Línea Retail</a><?php endif;?>
</div>
<div class="bulk-grid">
<section class="admin-panel">
<h2>1. Descarga el Excel</h2>
<div class="bulk-downloads"><a class="primary-button" href="/admin/product-import.php?line=<?=e($line)?>&amp;download=current">Descargar con productos actuales</a><a class="secondary-button" href="/admin/templates/<?=e($template)?>" download="<?=e($line==='retail'?'Plantilla_Retail.xlsx':'Plantilla_Granel.xlsx')?>">Plantilla vacía</a></div>
<p class="bulk-note">El Excel con productos actuales incluye los ID, nombres, textos, categorías, presentaciones y demás datos guardados en la BD para esta línea. Conserva el ID cuando quieras actualizar un producto. Para añadir uno nuevo, agrega una fila con ID vacío y una <code>ref</code> nueva.</p>
<?php if($line==='retail'):?><p class="bulk-note">Puedes importar Retail sin presentaciones o con presentaciones sin imagen si marcas <code>activo=0</code>. Para publicar (<code>activo=1</code>) se requiere al menos una presentación disponible con imagen.</p><?php endif;?>
<ol><li>Completa la hoja Productos y, cuando corresponda, Presentaciones, Cosechas y Galeria. La columna <code>ref</code> vincula sus filas.</li><li>Si agregas imágenes o PDF nuevos, colócalos directamente en un ZIP y escribe su nombre exacto en el Excel. Las rutas de imágenes ya exportadas se conservan sin volver a subirlas.</li><li>Sube el Excel y revisa los productos existentes o nombres repetidos que detecte la previsualización.</li></ol>
<p class="bulk-note">Si incluyes filas de Presentaciones, Cosechas o Galeria para un producto existente, reemplazarán ese bloque. Si no incluyes filas, el bloque se conserva. En los demás campos, una celda vacía se guarda vacía. No se crean tablas nuevas.</p>
<?php if($canWrite):?>
<form method="post" enctype="multipart/form-data">
<?=csrf_field()?><input type="hidden" name="action" value="preview"><input type="hidden" name="line" value="<?=e($line)?>">
<label for="bulk-xlsx">Excel para importar (.xlsx)</label><input id="bulk-xlsx" type="file" name="xlsx" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
<label for="bulk-assets">Imágenes y PDFs nuevos (.zip, opcional)</label><input id="bulk-assets" type="file" name="assets" accept=".zip,application/zip">
<p class="bulk-note">Excel: máximo 5 MB. ZIP: máximo 80 MB. Imagen: máximo 8 MB. PDF: máximo 12 MB.</p>
<button class="primary-button" type="submit">Previsualizar importación</button>
</form>
<?php else:?><p>No tienes permisos de creación o edición en esta línea.</p><?php endif;?>
</section>
<aside class="admin-panel"><h2>Categorías activas</h2><p class="bulk-note">Usa el código o el nombre exacto en <code>categoria</code>.</p><ul class="bulk-categories"><?php foreach($categories as $category):?><li><code><?=e($category['slug'])?></code> — <?=e($category['name_es'])?></li><?php endforeach;?></ul></aside>
</div>
<?php if($preview):?>
<section class="admin-panel" style="margin-top:18px"><h2>2. Revisa antes de guardar</h2>
<div class="bulk-kpi"><div><strong><?=$preview['creates']?></strong><span>Por crear</span></div><div><strong><?=$preview['updates']?></strong><span>Existentes por actualizar</span></div><div><strong><?=count($preview['errors'])?></strong><span>Errores</span></div></div>
<?php if($preview['errors']):?>
<div class="alert error">No se guardó ningún producto. Corrige el Excel y vuelve a previsualizar.</div><ul class="bulk-errors"><?php foreach(array_slice($preview['errors'],0,100) as $item):?><li><?=e($item)?></li><?php endforeach;?></ul><?php if(count($preview['errors'])>100):?><p>Se muestran los primeros 100 errores.</p><?php endif;?>
<?php if($line==='retail'&&$preview['creates']>0&&!($stage['draft_incomplete_new']??false)):?><form method="post" class="bulk-actions"><?=csrf_field()?><input type="hidden" name="action" value="preview-drafts"><input type="hidden" name="line" value="retail"><input type="hidden" name="token" value="<?=e((string)($stage['token']??''))?>"><button class="secondary-button" type="submit">Revisar productos nuevos como borradores</button><span class="bulk-note">Solo los nuevos que no tengan una presentación disponible con imagen pasarán a borrador. Los existentes no cambiarán de estado por esta opción.</span></form><?php endif;?>
<?php else:?>
<?php if($preview['drafted']):?><div class="alert success"><?=count($preview['drafted'])?> producto(s) nuevo(s) incompleto(s) se guardarán como borradores, sin publicarse. Revisa el estado antes de confirmar.</div><?php endif;?>
<div class="bulk-table-wrap"><table class="bulk-table"><thead><tr><th>Ref</th><th>Acción</th><th>Producto</th><th>Categoría</th><th>Presentaciones</th><th>Estado al guardar</th></tr></thead><tbody>
<?php foreach($preview['products'] as $ref=>$row):?><tr><td><?=e($ref)?></td><td><?=$row['id']!==''?'Actualizar ID '.e($row['id']):'Crear'?></td><td><?=e($row['nombre_es'])?></td><td><?=e($row['categoria'])?></td><td><?=count($preview['packages'][$ref]??[])?></td><td><?=bulk_bool($row['activo'],0)===1?'Publicado':'Borrador'?><?=in_array($ref,$preview['drafted'],true)?' (ajustado)':''?></td></tr><?php endforeach;?>
</tbody></table></div>
<form method="post" class="bulk-actions"><?=csrf_field()?><input type="hidden" name="action" value="confirm"><input type="hidden" name="line" value="<?=e($line)?>"><input type="hidden" name="token" value="<?=e((string)($stage['token']??''))?>"><button class="primary-button" type="submit">Confirmar e importar</button><span class="bulk-note">Se guardarán todos los productos o ninguno.</span></form>
<?php endif;?>
<form method="post" class="bulk-actions"><?=csrf_field()?><input type="hidden" name="action" value="cancel"><input type="hidden" name="line" value="<?=e($line)?>"><button class="secondary-button" type="submit">Descartar previsualización</button></form>
</section>
<?php endif;?>
</main></body></html>
