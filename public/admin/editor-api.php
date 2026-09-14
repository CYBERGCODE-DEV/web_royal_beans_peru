<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
$user = require_admin();
$db = admin_db();
header('Content-Type: application/json; charset=utf-8');
$respond = static function(array $data,int $status=200):never{http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;};
try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $page=preg_replace('/[^a-z0-9_-]/i','',(string)($_GET['page']??''));$key=(string)($_GET['key']??'');[$section,$field]=array_pad(explode('.',$key,2),2,'');
        $s=$db->prepare('SELECT * FROM content_fields WHERE page_key=? AND section_key=? AND field_key=? LIMIT 1');$s->execute([$page,$section,$field]);
        $row=$s->fetch()?:['id'=>0,'page_key'=>$page,'section_key'=>$section,'field_key'=>$field,'field_type'=>'text','label'=>ucfirst(str_replace(['-','_'], ' ', $field)),'value_es'=>'','value_en'=>''];
        $respond(['field'=>$row,'csrf'=>csrf_token()]);
    }
    verify_csrf();
    $page=preg_replace('/[^a-z0-9_-]/i','',(string)($_POST['page_key']??''));$key=(string)($_POST['key']??'');[$section,$field]=array_pad(explode('.',$key,2),2,'');
    if(!$page||!$section||!$field)throw new RuntimeException('Campo visual inválido.');
    $type=in_array($_POST['field_type']??'', ['text','textarea','image','url'],true)?$_POST['field_type']:'text';
    $valueEs=trim((string)($_POST['value_es']??''));$valueEn=trim((string)($_POST['value_en']??''));
    if($type==='image'&&!empty($_FILES['image']['name'])){$path=save_upload($db,$_FILES['image'],(int)$user['id']);if(($_POST['editing_lang']??'es')==='en')$valueEn=$path;else$valueEs=$path;}
    $s=$db->prepare('INSERT INTO content_fields (page_key,section_key,field_key,field_type,label,value_es,value_en,updated_by) VALUES (?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE field_type=VALUES(field_type),label=VALUES(label),value_es=VALUES(value_es),value_en=VALUES(value_en),updated_by=VALUES(updated_by)');
    $s->execute([$page,$section,$field,$type,trim((string)($_POST['label']??$field)),$valueEs,$valueEn,(int)$user['id']]);
    audit($db,(int)$user['id'],'visual-edit','content',$page.'.'.$key);$respond(['ok'=>true,'value_es'=>$valueEs,'value_en'=>$valueEn,'type'=>$type]);
} catch(Throwable $error){$respond(['error'=>$error->getMessage()],422);}
