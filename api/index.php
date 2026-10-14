<?php
declare(strict_types=1);
ini_set('display_errors','0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
session_set_cookie_params(['httponly'=>true,'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','samesite'=>'Strict','path'=>'/']);
session_start();
require __DIR__.'/../config.php';
require __DIR__.'/../includes/content.php';
const DIR=__DIR__.'/../data/';
function respond($v,int $status=200): void { http_response_code($status); echo json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }
function validImage($value):string{$s=textField($value,2000);if(cm_image($s)!==$s)respond(['error'=>'Use a local image path or a secure image URL'],422);return $s;}
function validId($value):string{$s=textField($value,80);if(!preg_match('/^[a-z0-9][a-z0-9-]{0,79}$/',$s))respond(['error'=>'Invalid page identifier'],422);return $s;}
function lines($value,int $max=30):array{if(!is_array($value)||count($value)>$max)respond(['error'=>'Invalid list'],422);return array_values(array_filter(array_map(fn($v)=>textField($v,1500),$value),fn($v)=>$v!==''));}
function readStore(string $name,$fallback=[]){$p=DIR.$name.'.php';return is_file($p)?cm_json_file($p):$fallback;}
function writeStore(string $name,$value):void {$json=json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);if(file_put_contents(DIR.$name.'.php',"<?php exit; ?>\n".$json,LOCK_EX)===false)throw new RuntimeException('Storage failed');}
function textField($v,int $max=3000):string{if(!is_string($v)||strlen($v)>$max)respond(['error'=>'Invalid information'],422);return trim($v);}
function locked(callable $fn){$lock=fopen(DIR.'lock.php','c+');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Lock failed');try{return $fn();}finally{flock($lock,LOCK_UN);fclose($lock);}}
function rate(string $scope,int $max,int $seconds):void{locked(function()use($scope,$max,$seconds){$all=readStore('rates');$now=time();foreach($all as $k=>$v)if($v['until']<$now)unset($all[$k]);$key=hash('sha256',$scope.($_SERVER['REMOTE_ADDR']??''));$v=$all[$key]??['count'=>0,'until'=>$now+$seconds];if($v['count']>=$max)respond(['error'=>'Too many requests. Please try again later.'],429);$v['count']++;$all[$key]=$v;writeStore('rates',$all);});}
$action=$_GET['action']??'content';$method=$_SERVER['REQUEST_METHOD'];
try{
if($action==='content'&&$method==='GET'){ $adminScope=($_GET['scope']??'')==='admin';if($adminScope&&empty($_SESSION['admin']))respond(['error'=>'Please sign in first'],401);respond(cm_content($adminScope)); }
if($action==='session'&&$method==='GET'){$_SESSION['csrf']=$_SESSION['csrf']??bin2hex(random_bytes(32));respond(['authenticated'=>!empty($_SESSION['admin']),'setup'=>!is_file(DIR.'auth.php'),'csrf'=>$_SESSION['csrf']]);}
if($method==='POST'){
if((int)($_SERVER['CONTENT_LENGTH']??0)>8000000)respond(['error'=>'File is too large'],413);
if(in_array($action,['upload','quote'],true))$input=$action==='quote'?$_POST:[];else {$raw=file_get_contents('php://input');$input=json_decode($raw,true);if(!is_array($input))respond(['error'=>'Invalid request'],400);}

if($action==='quote'){
rate('contact',5,600);if(!empty($input['website']))respond(['ok'=>true,'reference'=>'CM-RECEIVED']);
$name=textField($input['name']??'',300);$phone=textField($input['phone']??'',50);$email=textField($input['email']??'',150);$message=textField($input['message']??'',9000);
$city=textField($input['city']??'',450);$area=textField($input['area']??'',50);$spaceType=textField($input['spaceType']??'',100);$timeline=textField($input['timeline']??'',150);$serviceId=textField($input['service']??'',80);$project=textField($input['project']??'',80);$material=textField($input['material']??'',80);
if($name===''||!preg_match('/^[+0-9 ()-]{5,40}$/',$phone)||$message===''||$city===''||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||($input['consent']??'')!=='yes')respond(['error'=>'Please check your contact details, location, project information and consent'],422);
if($area!==''&&(!is_numeric($area)||(float)$area<=0||(float)$area>1000000))respond(['error'=>'Invalid area'],422);
if(!in_array($spaceType,['Villa','Apartment','Commercial space','Swimming pool','Other'],true)||!in_array($timeline,['Not decided yet','As soon as possible','Within a month','Within three months','Later'],true))respond(['error'=>'Invalid space type or start date'],422);
$public=cm_content();$serviceLabel=$serviceId==='complete'?'Complete project':'';
foreach($public['services']as$service)if($service['id']===$serviceId)$serviceLabel=$service['title'];
if($serviceLabel==='')respond(['error'=>'This service is unavailable. Please reload the page.'],422);
if($project!==''){if(!array_filter($public['projects'],fn($p)=>$p['id']===$project))respond(['error'=>'The selected project is unavailable'],422);}
$materialTitle='';$materialCode='';if($material!==''){foreach($public['products']as$product)if($product['id']===$material){$materialTitle=$product['title'];$materialCode=$product['code'];break;}if($materialTitle==='')respond(['error'=>'The selected material is currently unavailable'],422);}
$images=$_FILES['images']??null;$pending=[];
if($images){
if(!is_array($images['name'])||count($images['name'])>3)respond(['error'=>'You can attach up to 3 images'],422);
$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
foreach($images['name']as$i=>$ignored){if(($images['error'][$i]??-1)===UPLOAD_ERR_NO_FILE)continue;
if(($images['error'][$i]??-1)!==UPLOAD_ERR_OK||($images['size'][$i]??0)>2097152)respond(['error'=>'Each image must be smaller than 2 MB'],422);
$temp=$images['tmp_name'][$i];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($temp);$info=getimagesize($temp);
if(!isset($types[$mime])||!$info||$info[0]>16000||$info[1]>16000)respond(['error'=>'Only valid image files are accepted'],422);
$pending[]=['temp'=>$temp,'file'=>bin2hex(random_bytes(16)).'.'.$types[$mime],'mime'=>$mime];}
}
$id=bin2hex(random_bytes(12));$reference='CM-'.strtoupper(substr($id,0,12));$attachments=[];$moved=[];
try{
foreach($pending as $file){$target=DIR.'uploads/'.$file['file'];if(!move_uploaded_file($file['temp'],$target))throw new RuntimeException('Quote upload failed');$moved[]=$target;$attachments[]=['file'=>$file['file'],'mime'=>$file['mime']];}
locked(function()use($id,$reference,$name,$phone,$email,$message,$city,$area,$spaceType,$timeline,$serviceId,$serviceLabel,$project,$material,$materialTitle,$materialCode,$attachments){
$rows=readStore('leads');if(count($rows)>=5000)throw new RuntimeException('Lead limit reached');
array_unshift($rows,['id'=>$id,'reference'=>$reference,'date'=>gmdate('c'),'type'=>'quote','status'=>'new','notes'=>'','name'=>$name,'phone'=>$phone,'email'=>$email,'message'=>$message,'city'=>$city,'area'=>$area,'spaceType'=>$spaceType,'timeline'=>$timeline,'serviceId'=>$serviceId,'service'=>$serviceLabel,'project'=>$project,'material'=>$material,'materialTitle'=>$materialTitle,'materialCode'=>$materialCode,'attachments'=>$attachments,'consentAt'=>gmdate('c')]);writeStore('leads',$rows);});
}catch(Throwable $e){foreach($moved as$path)if(is_file($path))unlink($path);throw $e;}
respond(['ok'=>true,'reference'=>$reference]);
}


if($action==='contact'){
rate('contact',5,600);if(!empty($input['website']))respond(['ok'=>true]);
$name=textField($input['name']??'',300);$phone=textField($input['phone']??'',50);$email=textField($input['email']??'',150);$message=textField($input['message']??'',9000);$service=textField($input['service']??'',200);
if($name===''||$phone===''||$message===''||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL)))respond(['error'=>'Please check your name, phone, project details and email'],422);
locked(function()use($name,$phone,$email,$message,$service){$leads=readStore('leads');if(count($leads)>=5000)respond(['error'=>'Unable to receive your request at the moment'],503);array_unshift($leads,['id'=>bin2hex(random_bytes(8)),'date'=>gmdate('c'),'name'=>$name,'phone'=>$phone,'email'=>$email,'message'=>$message,'service'=>$service,'type'=>'contact','status'=>'new','notes'=>'']);writeStore('leads',$leads);});respond(['ok'=>true]);}
if(!isset($_SESSION['csrf'])||!hash_equals($_SESSION['csrf'],$_SERVER['HTTP_X_CSRF_TOKEN']??''))respond(['error'=>'Please reload the page'],403);
if($action==='setup'){
rate('setup',5,900);if(!hash_equals(SETUP_KEY,(string)($input['key']??'')))respond(['error'=>'Invalid setup key'],403);
$password=textField($input['password']??'',200);if(strlen($password)<12)respond(['error'=>'Your password must contain at least 12 characters'],422);
locked(function()use($password){if(is_file(DIR.'auth.php'))respond(['error'=>'Dashboard already set up'],409);writeStore('auth',['hash'=>password_hash($password,PASSWORD_DEFAULT)]);});
session_regenerate_id(true);$_SESSION['admin']=true;respond(['ok'=>true]);}
if($action==='login'){
rate('login',8,900);$auth=readStore('auth');if(!password_verify((string)($input['password']??''),$auth['hash']??''))respond(['error'=>'Incorrect password'],401);session_regenerate_id(true);$_SESSION['admin']=true;respond(['ok'=>true]);}
if(empty($_SESSION['admin']))respond(['error'=>'Please sign in first'],401);
if($action==='lead-update'){
$id=textField($input['id']??'',80);$state=textField($input['status']??'',30);$notes=textField($input['notes']??'',9000);
if(!in_array($state,['new','contacted','completed'],true))respond(['error'=>'Invalid enquiry status'],422);
locked(function()use($id,$state,$notes){$rows=readStore('leads');$found=false;foreach($rows as &$row)if($row['id']===$id){$row['status']=$state;$row['notes']=$notes;$row['updatedAt']=gmdate('c');$found=true;break;}unset($row);if(!$found)respond(['error'=>'Enquiry not found'],404);writeStore('leads',$rows);});respond(['ok'=>true]);}
if($action==='logout'){$_SESSION=[];session_destroy();respond(['ok'=>true]);}
if($action==='password'){$old=textField($input['old']??'',200);$new=textField($input['password']??'',200);$auth=readStore('auth');if(!password_verify($old,$auth['hash']??'')||strlen($new)<12)respond(['error'=>'Please check your current password and the length of your new password'],422);locked(function()use($new){writeStore('auth',['hash'=>password_hash($new,PASSWORD_DEFAULT)]);});session_regenerate_id(true);respond(['ok'=>true]);}
if($action==='save'){
$out=[];foreach(['heroTitle','heroDescription','about','phone','email','whatsapp'] as $k)$out[$k]=textField($input[$k]??'',9000);
if($out['heroTitle']===''||$out['heroDescription']===''||$out['about']==='')respond(['error'=>'Complete the required text fields'],422);
if($out['email']!==''&&!filter_var($out['email'],FILTER_VALIDATE_EMAIL))respond(['error'=>'Invalid email address'],422);
if($out['phone']!==''&&!preg_match('/^[+0-9 ()-]{5,40}$/',$out['phone']))respond(['error'=>'Invalid phone number'],422);
if($out['whatsapp']!==''&&!preg_match('/^[+0-9 ()-]{5,40}$/',$out['whatsapp']))respond(['error'=>'Invalid WhatsApp number'],422);

$existing=cm_content(true);$projects=$input['projects']??[];if(!is_array($projects)||count($projects)>60)respond(['error'=>'Invalid number of projects'],422);$out['projects']=[];$ids=[];
foreach($projects as $i=>$p){
if(!is_array($p))respond(['error'=>'Invalid project'],422);
$item=[];foreach(['title','location','description','category','scope','area','year'] as $k)$item[$k]=textField($p[$k]??'',6000);
if(!in_array($item['category'],['porcelain','bathroom','pool'],true)||$item['title']==='')respond(['error'=>'Please check the project title and category'],422);
$item['id']=validId($p['id']??'project-'.($i+1));if(isset($ids[$item['id']]))respond(['error'=>'Project identifiers must be unique'],422);$ids[$item['id']]=true;
$item['image']=validImage($p['image']??'');$gallery=$p['gallery']??[];if(!is_array($gallery)||count($gallery)>20)respond(['error'=>'You can add up to 20 images per project'],422);$item['gallery']=array_values(array_map('validImage',$gallery));
$item['sample']=!empty($p['sample']);$item['published']=($p['published']??true)===true;$out['projects'][]=$item;
}
$services=$input['services']??$existing['services'];if(!is_array($services)||count($services)>30)respond(['error'=>'Invalid number of services'],422);$out['services']=[];$ids=[];
foreach($services as $service){if(!is_array($service))respond(['error'=>'Invalid service'],422);$item=[];foreach(['title','summary','description']as$k)$item[$k]=textField($service[$k]??'',12000);
if($item['title']===''||$item['summary']==='')respond(['error'=>'Complete the service name and description'],422);
$item['id']=validId($service['id']??'');if(isset($ids[$item['id']]))respond(['error'=>'Service identifiers must be unique'],422);$ids[$item['id']]=true;
$item['image']=validImage($service['image']??'');$item['features']=lines($service['features']??[]);$item['steps']=lines($service['steps']??[]);
$item['published']=($service['published']??true)===true;$out['services'][]=$item;
}



$products=$input['products']??$existing['products'];if(!is_array($products)||count($products)>150)respond(['error'=>'Invalid number of materials'],422);$out['products']=[];$ids=[];
foreach($products as$product){if(!is_array($product))respond(['error'=>'Invalid material'],422);$item=[];foreach(['title','code','category','color','finish','size','description']as$k)$item[$k]=textField($product[$k]??'',9000);if($item['title']===''||!in_array($item['category'],['porcelain','ceramic','mosaic'],true))respond(['error'=>'Please check the material name and category'],422);
$item['id']=validId($product['id']??'');if(isset($ids[$item['id']]))respond(['error'=>'Material identifiers must be unique'],422);$ids[$item['id']]=true;$item['image']=validImage($product['image']??'');$item['usage']=lines($product['usage']??[],15);$item['sample']=!empty($product['sample']);$item['published']=($product['published']??true)===true;$out['products'][]=$item;}
$faqs=$input['faqs']??$existing['faqs'];if(!is_array($faqs)||count($faqs)>50)respond(['error'=>'Invalid number of questions'],422);$out['faqs']=[];$ids=[];
foreach($faqs as$faq){if(!is_array($faq))respond(['error'=>'Invalid question'],422);$item=['id'=>validId($faq['id']??''),'question'=>textField($faq['question']??'',1500),'answer'=>textField($faq['answer']??'',12000),'published'=>($faq['published']??true)===true];if($item['question']===''||$item['answer']==='')respond(['error'=>'Complete the question and answer'],422);if(isset($ids[$item['id']]))respond(['error'=>'Question identifiers must be unique'],422);$ids[$item['id']]=true;$out['faqs'][]=$item;}


locked(function()use($out){writeStore('content',$out);});respond(['ok'=>true]);}
if($action==='upload'){
$file=$_FILES['image']??null;if(!$file||$file['error']!==UPLOAD_ERR_OK||$file['size']>6000000)respond(['error'=>'Choose an image up to 6 MB'],422);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);if(!isset($types[$mime])||!getimagesize($file['tmp_name']))respond(['error'=>'Allowed image formats: JPG andPNG andWebP only'],422);$path='assets/images/'.bin2hex(random_bytes(16)).'.'.$types[$mime];if(!move_uploaded_file($file['tmp_name'],__DIR__.'/../'.$path))throw new RuntimeException('Upload failed');respond(['path'=>$path]);}
}
if($action==='attachment'&&$method==='GET'){
if(empty($_SESSION['admin']))respond(['error'=>'Please sign in first'],401);
$id=$_GET['id']??'';$index=filter_var($_GET['index']??'',FILTER_VALIDATE_INT);
if(!is_string($id)||$index===false||$index<0)respond(['error'=>'Invalid attachment'],404);
foreach(readStore('leads') as $row)if($row['id']===$id){
$file=$row['attachments'][$index]??null;
if(!$file||!preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/',$file['file']))respond(['error'=>'Attachment not found'],404);
$path=DIR.'uploads/'.$file['file'];if(!is_file($path))respond(['error'=>'Attachment not found'],404);
header('Content-Type: '.$file['mime']);header('Content-Disposition: inline; filename="project-image.'.pathinfo($path,PATHINFO_EXTENSION).'"');header('Content-Length: '.filesize($path));readfile($path);exit;}
respond(['error'=>'Enquiry not found'],404);}
if($action==='leads'&&$method==='GET'){if(empty($_SESSION['admin']))respond(['error'=>'Please sign in first'],401);respond(readStore('leads'));}
respond(['error'=>'Unsupported request'],405);
}catch(Throwable $e){error_log('Cera-Mik: '.$e->getMessage());respond(['error'=>'Unable to complete this action. Check permissions for data or contact your website administrator.'],500);}

