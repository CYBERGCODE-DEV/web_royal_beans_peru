<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
$user = require_permission('editor', ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' ? 'view' : 'edit');
$db = admin_db();
header('Content-Type: application/json; charset=utf-8');
$respond = static function(array $data,int $status=200):never{if(session_status()===PHP_SESSION_ACTIVE)session_write_close();http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;};
$normaliseRuns = static function(mixed $runs,string $value):array{
    if(!is_array($runs)||count($runs)>200)return[];
    $clean=[];$combined='';
    foreach($runs as $run){
        if(!is_array($run)||!isset($run['text'])||!is_string($run['text']))return[];
        $text=$run['text'];$color=(string)($run['color']??'');
        if($text==='')continue;
        if($color!==''&&!preg_match('/^#[0-9a-f]{6}$/i',$color))return[];
        $combined.=$text;
        $last=count($clean)-1;
        if($last>=0&&($clean[$last]['color']??'')===$color)$clean[$last]['text'].=$text;
        else$clean[]=$color===''?['text'=>$text]:['text'=>$text,'color'=>strtolower($color)];
    }
    return$combined===$value?$clean:[];
};
$uploadedPath='';$uploadCreation='';$fieldSaved=false;
try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $page=preg_replace('/[^a-z0-9_-]/i','',(string)($_GET['page']??''));$key=(string)($_GET['key']??'');[$section,$field]=array_pad(explode('.',$key,2),2,'');
        $s=$db->prepare('SELECT * FROM content_fields WHERE page_key=? AND section_key=? AND field_key=? LIMIT 1');$s->execute([$page,$section,$field]);
        $row=$s->fetch()?:['id'=>0,'page_key'=>$page,'section_key'=>$section,'field_key'=>$field,'field_type'=>'text','label'=>ucfirst(str_replace(['-','_'], ' ', $field)),'value_es'=>'','value_en'=>'','style_json'=>null];
        $media=[];$includeMedia=(string)($_GET['include_media']??'1')!=='0';if($includeMedia&&(($row['field_type']??'')==='image'||$field==='image')){$media=$db->query("SELECT id,path,original_name FROM media WHERE mime_type LIKE 'image/%' AND path REGEXP '^https?://' ORDER BY id DESC LIMIT 300")->fetchAll();}
        $csrf=csrf_token();session_write_close();$respond(['field'=>$row,'media'=>$media,'csrf'=>$csrf]);
    }
    verify_csrf();
    if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
    $page=preg_replace('/[^a-z0-9_-]/i','',(string)($_POST['page_key']??''));$key=(string)($_POST['key']??'');[$section,$field]=array_pad(explode('.',$key,2),2,'');
    if(!$page||!$section||!$field)throw new RuntimeException('Campo visual inválido.');
    $type=in_array($_POST['field_type']??'', ['text','textarea','image','url'],true)?$_POST['field_type']:'text';
    $current=$db->prepare('SELECT value_es,value_en,style_json FROM content_fields WHERE page_key=? AND section_key=? AND field_key=? LIMIT 1');$current->execute([$page,$section,$field]);$previous=$current->fetch();
    $valueEs=is_array($previous)?(string)$previous['value_es']:'';$valueEn=is_array($previous)?(string)$previous['value_en']:'';
    if(array_key_exists('value_es',$_POST))$valueEs=trim((string)$_POST['value_es']);
    if(array_key_exists('value_en',$_POST))$valueEn=trim((string)$_POST['value_en']);
    $editingLang=(($_POST['editing_lang']??'es')==='en')?'en':'es';
    if($type==='image'&&$previous){$valueEs=(string)$previous['value_es'];$valueEn=(string)$previous['value_en'];}
    $previousStyle=is_array($previous)?json_decode((string)($previous['style_json']??''),true):[];$style=is_array($previousStyle)?$previousStyle:[];
    $font=in_array($_POST['font_family']??'', ['', 'display','body','editorial'],true)?(string)($_POST['font_family']??''):'';
    $color=preg_match('/^#[0-9a-f]{6}$/i',(string)($_POST['text_color']??''))?(string)$_POST['text_color']:'';
    $size=(int)($_POST['font_size']??0);
    foreach(['font'=>$font,'color'=>$color,'size'=>$size>=10&&$size<=120?$size:null]as$styleKey=>$styleValue){if($styleValue===''||$styleValue===null)unset($style[$styleKey]);else$style[$styleKey]=$styleValue;}
    if(isset($_POST['rich_text_json'])){
        $submitted=json_decode((string)$_POST['rich_text_json'],true);$rich=[];
        if(is_array($submitted)){
            $esRuns=$normaliseRuns($submitted['es']??[],$valueEs);$enRuns=$normaliseRuns($submitted['en']??[],$valueEn);
            if($esRuns)$rich['es']=$esRuns;if($enRuns)$rich['en']=$enRuns;
        }
        if($rich)$style['richText']=$rich;else unset($style['richText']);
    }elseif(isset($style['richText'])&&is_array($style['richText'])){
        $rich=[];$esRuns=$normaliseRuns($style['richText']['es']??[],$valueEs);$enRuns=$normaliseRuns($style['richText']['en']??[],$valueEn);
        if($esRuns)$rich['es']=$esRuns;if($enRuns)$rich['en']=$enRuns;
        if($rich)$style['richText']=$rich;else unset($style['richText']);
    }
    $isHeroImage=$section==='hero'&&$field==='image';
    if($isHeroImage){$style=is_array($previousStyle)?$previousStyle:[];if(isset($_POST['hero_settings_present'])){$style=['heroHeight'=>max(360,min(950,(int)($_POST['hero_height']??620))),'heroOverlay'=>max(0,min(90,(int)($_POST['hero_overlay']??72))),'heroShowText'=>isset($_POST['hero_show_text'])];}}
    $imageChanged=false;$removeImage=$type==='image'&&((string)($_POST['remove_image']??''))==='1';$selectedMedia=trim((string)($_POST['selected_media']??''));
    if($type==='image'&&!empty($_FILES['image']['name'])){$uploadedPath=save_upload($db,$_FILES['image'],(int)$user['id'],$uploadCreation);$selectedMedia=$uploadedPath;}
    if($type==='image'&&$selectedMedia!==''){$mediaCheck=$db->prepare("SELECT path FROM media WHERE path=? AND mime_type LIKE 'image/%' AND path REGEXP '^https?://' LIMIT 1");$mediaCheck->execute([$selectedMedia]);$selectedMedia=(string)($mediaCheck->fetchColumn()?:'');if($selectedMedia==='')throw new RuntimeException('Selecciona una imagen válida de Cloudflare R2.');if($isHeroImage){$valueEs=$selectedMedia;$valueEn=$selectedMedia;}elseif($editingLang==='en')$valueEn=$selectedMedia;else$valueEs=$selectedMedia;$imageChanged=true;}
    elseif($removeImage){if($isHeroImage){$valueEs='';$valueEn='';}elseif($editingLang==='en')$valueEn='';else$valueEs='';$imageChanged=true;}
    if($isHeroImage&&$imageChanged)$style['heroShowText']=true;
    $label=trim((string)($_POST['label']??$field));
    $s=$db->prepare('INSERT INTO content_fields (page_key,section_key,field_key,field_type,label,value_es,value_en,style_json,updated_by) VALUES (?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE field_type=VALUES(field_type),label=VALUES(label),value_es=VALUES(value_es),value_en=VALUES(value_en),style_json=VALUES(style_json),updated_by=VALUES(updated_by)');
    $s->execute([$page,$section,$field,$type,$label,$valueEs,$valueEn,$style?json_encode($style):null,(int)$user['id']]);$fieldSaved=true;
    if($imageChanged&&$previous){$cleanup=release_replaced_media($db,[(string)$previous['value_es'],(string)$previous['value_en']],[$valueEs,$valueEn],(int)$user['id'],'content',$page.'.'.$key);if($cleanup['errors'])audit($db,(int)$user['id'],'cleanup-failed','media',$page.'.'.$key,$cleanup);}
    audit($db,(int)$user['id'],'visual-edit','content',$page.'.'.$key);mark_public_content_changed($type==='image'?'media':'content');$respond(['ok'=>true,'label'=>$label,'value_es'=>$valueEs,'value_en'=>$valueEn,'type'=>$type,'style'=>$style,'image_removed'=>$removeImage]);
} catch(Throwable $error){if(!$fieldSaved&&$uploadedPath!==''&&$uploadCreation!=='')cleanup_failed_uploads($db,[['path'=>$uploadedPath,'creation'=>$uploadCreation]]);$respond(['error'=>$error->getMessage()],422);}
