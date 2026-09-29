<?php
declare(strict_types=1);
require __DIR__.'/_bootstrap.php';

$user=require_permission('editor',($_SERVER['REQUEST_METHOD']??'GET')==='POST'?'edit':'view');
$db=admin_db();$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    $newPath='';$creation='';
    try{
        verify_csrf();
        $lang=(string)($_POST['lang']??'');
        if(!in_array($lang,['es','en'],true))throw new RuntimeException('Selecciona un idioma válido.');
        $file=$_FILES['video']??[];
        if(($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)throw new RuntimeException('Selecciona un video MP4 o WebM.');
        $newPath=save_video_upload($db,$file,(int)$user['id'],$creation);
        $column=$lang==='en'?'value_en':'value_es';
        $query=$db->prepare('SELECT value_es,value_en FROM content_fields WHERE page_key=? AND section_key=? AND field_key=? LIMIT 1');
        $query->execute(['nosotros','about-video','source']);$before=$query->fetch()?:[];$oldPath=(string)($before[$column]??'');
        $db->beginTransaction();
        $save=$db->prepare("INSERT INTO content_fields(page_key,section_key,field_key,field_type,label,$column,updated_by) VALUES('nosotros','about-video','source','url','Video institucional',?,?) ON DUPLICATE KEY UPDATE $column=VALUES($column),updated_by=VALUES(updated_by)");
        $save->execute([$newPath,(int)$user['id']]);
        audit($db,(int)$user['id'],'save','about-video',$lang);$db->commit();
        if($oldPath!==''&&$oldPath!==$newPath)try{release_media_if_unused($db,$oldPath);}catch(Throwable $cleanupError){audit($db,(int)$user['id'],'cleanup-failed','media',$oldPath,['message'=>$cleanupError->getMessage()]);}
        mark_public_content_changed('content');flash('success','Video '.($lang==='es'?'en español':'en inglés').' guardado en Cloudflare R2.');
        header('Location: /admin/about-videos.php');exit;
    }catch(Throwable $exception){
        if($db->inTransaction())$db->rollBack();
        if($newPath!==''&&$creation!=='')try{release_media_if_unused($db,$newPath);}catch(Throwable){}
        $error=$exception->getMessage();
    }
}
$query=$db->prepare("SELECT value_es,value_en FROM content_fields WHERE page_key='nosotros' AND section_key='about-video' AND field_key='source' LIMIT 1");
$query->execute();$videos=$query->fetch()?:['value_es'=>'','value_en'=>''];$flash=pull_flash();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Videos de Nosotros | Royal Beans Perú</title><link rel="stylesheet" href="/admin/admin.css"><style>
.about-video-admin{max-width:1100px}.about-video-admin .video-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.about-video-admin .admin-panel{min-width:0}.about-video-admin video{display:block;width:100%;aspect-ratio:16/9;margin:14px 0;background:#052f26;border-radius:9px}.about-video-admin label{display:grid;gap:8px;margin:18px 0;font-weight:700}.about-video-admin input[type=file]{width:100%;padding:12px;border:1px solid #cbdacb;border-radius:8px;background:#fff}.about-video-admin .video-empty{display:grid;place-items:center;aspect-ratio:16/9;margin:14px 0;border:1px dashed #cbdacb;border-radius:9px;color:#536a62;background:#f6f9f3}.about-video-admin small{display:block;color:#536a62;line-height:1.5}@media(max-width:760px){.about-video-admin .video-grid{grid-template-columns:1fr}}
</style></head><body class="admin-body"><aside class="admin-sidebar"><a class="admin-brand" href="/admin/"><img src="/images/logo.webp" alt=""><span><strong>ROYAL BEANS</strong><small>ADMINISTRACIÓN</small></span></a><nav><a href="/admin/">Resumen</a><a href="/admin/?view=products">Productos</a><a class="active" href="/admin/editor.php">Edición Visual</a></nav></aside><main class="admin-main about-video-admin"><header class="admin-top"><div><p class="admin-eyebrow">Nosotros</p><h1>Videos institucionales</h1><p>Un video para español y otro para inglés. Se reproducen en la misma página al pulsar reproducir.</p></div><a class="secondary-button" href="/admin/editor.php">Volver al editor</a></header>
<?php if($flash):?><div class="alert <?=e($flash[0])?>"><?=e($flash[1])?></div><?php endif;?><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<div class="video-grid"><?php foreach(['es'=>'Español','en'=>'English'] as $lang=>$label):$url=(string)($videos['value_'.$lang]??'');?><section class="admin-panel"><h2><?=e($label)?></h2><?php if($url!==''):?><video controls preload="none" playsinline src="<?=e($url)?>"></video><small>Video actual en Cloudflare R2. Al subir otro, se reemplazará solo esta versión.</small><?php else:?><div class="video-empty">Aún no hay video para este idioma.</div><?php endif;?><form method="post" enctype="multipart/form-data" class="admin-form"><?=csrf_field()?><input type="hidden" name="lang" value="<?=e($lang)?>"><label>Subir video <?=e($label)?><input type="file" name="video" accept="video/mp4,video/webm,.mp4,.webm" required></label><small>MP4 (H.264 recomendado) o WebM, máximo 120 MB. El archivo no se descarga en la web pública hasta que se pulse reproducir.</small><button class="primary-button" type="submit">Guardar video <?=e($label)?></button></form></section><?php endforeach;?></div></main></body></html>
