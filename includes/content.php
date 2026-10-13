<?php
declare(strict_types=1);
function cm_json_file(string $path):array{
 if(!is_file($path))return [];
 $f=fopen($path,'r');if(!$f)return [];
 flock($f,LOCK_SH);$raw=stream_get_contents($f);flock($f,LOCK_UN);fclose($f);
 return json_decode(substr($raw,strpos($raw,"\n")+1),true)??[];
}
function cm_content(bool $all=false):array{
 $defaults=cm_json_file(__DIR__.'/defaults.php');
 $c=array_replace($defaults,cm_json_file(__DIR__.'/../data/content.php'));
 foreach($c['projects'] as $i=>&$p){$p['id']=$p['id']??'project-'.($i+1);$p['gallery']=$p['gallery']??[];$p['published']=$p['published']??true;}unset($p);
 foreach(['heroTitle','heroDescription','about']as$key)if(isset($c[$key]))$c[$key]=preg_replace('/\.(?=\s|$)/u','',$c[$key]);
 foreach(['services','projects']as$group)foreach($c[$group]as&$item){
 foreach(['title','summary','description','location','scope','area','year']as$key)if(isset($item[$key]))$item[$key]=preg_replace('/\.(?=\s|$)/u','',$item[$key]);
 foreach(['features','steps']as$key)if(isset($item[$key]))$item[$key]=array_map(fn($line)=>preg_replace('/\.(?=\s|$)/u','',$line),$item[$key]);
 }unset($item);
 if(!$all){foreach(['services','projects','products','faqs']as$k)$c[$k]=array_values(array_filter($c[$k],fn($item)=>($item['published']??true)));}
 return $c;
}
function cm_h($s):string{return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function cm_image($s):string{
 $s=(string)$s;
 return preg_match('~^assets/images/[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|webp)$~',$s)||preg_match('~^https://[^\s]+$~',$s)?$s:'assets/images/living.jpg';
}


function cm_page_content():array{
 $preview=false;
 if(($_GET['preview']??'')==='1'){
  header('Cache-Control: no-store');
  if(session_status()!==PHP_SESSION_ACTIVE)session_start();
  $preview=!empty($_SESSION['admin']);
 }
 return cm_content($preview);
}
