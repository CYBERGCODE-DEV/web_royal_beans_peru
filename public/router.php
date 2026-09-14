<?php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)??'/';
if(preg_match('#^/productos/(linea-convencional|linea-retail)/([^/]+)/?$#',$path,$m)){$_GET['lang']='es';$_GET['line']=$m[1];$_GET['slug']=urldecode($m[2]);require __DIR__.'/product.php';return true;}
if(preg_match('#^/en/products/(conventional-line|retail-line)/([^/]+)/?$#',$path,$m)){$_GET['lang']='en';$_GET['line']=$m[1];$_GET['slug']=urldecode($m[2]);require __DIR__.'/product.php';return true;}
return false;
