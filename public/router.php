<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)??'/';
if($path==='/'||rtrim($path,'/')==='/en'||$path==='/index.txt'||$path==='/en/index.txt'){
    $rsc=str_ends_with($path,'.txt')||isset($_GET['_rsc'])||($_SERVER['HTTP_RSC']??'')==='1';
    $_GET=['lang'=>str_starts_with($path,'/en')?'en':'es'];
    if($rsc)$_GET['format']='rsc';
    require __DIR__.'/seo-home.php';return true;
}
foreach (['/en/home/' => '/en/', '/en/index.html' => '/en/', '/home/' => '/', '/index.html' => '/', '/en/products/retail-line/' => '/en/products/retail/', '/productos/linea-convencional/' => '/productos/linea-a-granel/', '/en/products/conventional-line/' => '/en/products/bulk-line/', '/productos/' => '/productos/linea-a-granel/', '/en/products/' => '/en/products/bulk-line/'] as $old => $new) {
    if (rtrim($path, '/') !== rtrim($old, '/')) continue;
    header('Location: ' . $new, true, 301);
    return true;
}
if(preg_match('#^/(nosotros|participacion|impacto|contacto|politica-de-privacidad|terminos-y-condiciones|productos/linea-a-granel|productos/linea-retail|en/about-us|en/events|en/impact|en/contact|en/privacy-policy|en/terms-and-conditions|en/products/bulk-line|en/products/retail)/index\.html$#',$path,$m)){
    header('Location: /'.$m[1].'/',true,301);return true;
}
if($path==='/sitemap.xml'){require __DIR__.'/sitemap.php';return true;}
if(preg_match('#^/productos/(legumbres|granos-andinos|maices|especias)/?$#',$path)){header('Location: /productos/linea-a-granel/',true,301);return true;}
if(preg_match('#^/en/products/(pulses|andean-grains|corn|spices)/?$#',$path)){header('Location: /en/products/bulk-line/',true,301);return true;}
if(preg_match('#^/(nosotros|participacion|impacto|contacto|politica-de-privacidad|terminos-y-condiciones|productos/linea-a-granel|productos/linea-retail|en/about-us|en/events|en/impact|en/contact|en/privacy-policy|en/terms-and-conditions|en/products/bulk-line|en/products/retail)/(index\.txt)?$#',$path,$m)){
    $_GET['path']=$m[1];
    if(!empty($m[2]))$_GET['format']='rsc';
    require __DIR__.'/cms-page.php';return true;
}
if(preg_match('#^/productos/(linea-convencional|linea-a-granel|linea-retail)/([^/]+)/?$#',$path,$m)){$_GET=['lang'=>'es','line'=>$m[1],'slug'=>rawurldecode($m[2])];require __DIR__.'/seo-legacy-product.php';return true;}
if(preg_match('#^/en/products/(conventional-line|bulk-line|retail-line)/([^/]+)/?$#',$path,$m)){$_GET=['lang'=>'en','line'=>$m[1],'slug'=>rawurldecode($m[2])];require __DIR__.'/seo-legacy-product.php';return true;}
if(preg_match('#^/productos/(legumbres|granos-andinos|maices|especias)/([^/]+)/?$#',$path,$m)){$_GET=['lang'=>'es','slug'=>rawurldecode($m[2])];require __DIR__.'/seo-legacy-product.php';return true;}
if(preg_match('#^/en/products/(pulses|andean-grains|corn|spices)/([^/]+)/?$#',$path,$m)){$_GET=['lang'=>'en','slug'=>rawurldecode($m[2])];require __DIR__.'/seo-legacy-product.php';return true;}
if(preg_match('#^/productos/([^/]+)/?$#',$path,$m)){$_GET=['lang'=>'es','slug'=>rawurldecode($m[1])];require __DIR__.'/seo-legacy-product.php';return true;}
if(preg_match('#^/en/products/([^/]+)/?$#',$path,$m)){$_GET=['lang'=>'en','slug'=>rawurldecode($m[1])];require __DIR__.'/seo-legacy-product.php';return true;}
return false;
