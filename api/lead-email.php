<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
const CONSENT_VERSION = '2026-09-11';
const RETENTION_SECONDS = 7776000;
const MAX_BODY = 16384;
const STORAGE_PATH = __DIR__ . '/../storage/';
function answer(int $code,array $data){http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if(($_SERVER['REQUEST_METHOD']??'')!=='POST')answer(405,['ok'=>false,'error'=>'Метод не разрешён']);
if((int)($_SERVER['CONTENT_LENGTH']??0)>MAX_BODY)answer(413,['ok'=>false,'error'=>'Слишком большой запрос']);
$host=strtolower((string)preg_replace('/:\d+$/','',$_SERVER['HTTP_HOST']??''));$origin=$_SERVER['HTTP_ORIGIN']??'';
if($origin!==''){$originHost=strtolower((string)parse_url($origin,PHP_URL_HOST));if($host===''||!hash_equals($host,$originHost))answer(403,['ok'=>false,'error'=>'Источник запроса не разрешён']);}
$type=strtolower($_SERVER['CONTENT_TYPE']??'');
if(strpos($type,'application/json')!==false){$input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))answer(400,['ok'=>false,'error'=>'Некорректный JSON']);}else{$input=$_POST;}
if(!empty($input['website']))answer(201,['ok'=>true,'message'=>'Заявка принята']);
if(($input['pd_consent']??'')!=='1'||($input['adult_confirmed']??'')!=='1'||($input['consent_version']??'')!==CONSENT_VERSION)answer(400,['ok'=>false,'error'=>'Необходимо отдельное согласие и подтверждение возраста']);
$fullName=trim((string)($input['name']??''));$fullName=(string)preg_replace('/\s+/u',' ',$fullName);$phone=trim((string)($input['phone']??''));$message=trim((string)($input['message']??''));
if(mb_strlen($fullName)<5||mb_strlen($fullName)>150||preg_match('/^[\p{L}][\p{L}\s\-\'’]+$/u',$fullName)!==1||count(preg_split('/\s+/u',$fullName,-1,PREG_SPLIT_NO_EMPTY))<2)answer(400,['ok'=>false,'error'=>'Укажите фамилию и имя']);
$digits=(string)preg_replace('/\D+/','',$phone);if(strlen($digits)<10||strlen($digits)>15)answer(400,['ok'=>false,'error'=>'Проверьте номер телефона']);
if(mb_strlen($message)>1000)answer(400,['ok'=>false,'error'=>'Сообщение слишком длинное']);
if(!is_dir(STORAGE_PATH)&&!mkdir(STORAGE_PATH,0700,true)&&!is_dir(STORAGE_PATH))answer(500,['ok'=>false,'error'=>'Ошибка сервера']);
$now=time();$secret=getenv('APP_SECRET')?:hash('sha256',__FILE__);$ip=(string)($_SERVER['REMOTE_ADDR']??'');$rateKey=hash_hmac('sha256',$ip,$secret);$rateFile=STORAGE_PATH.'rate-limits.json';
$rf=@fopen($rateFile,'c+');if(!$rf)answer(500,['ok'=>false,'error'=>'Ошибка сервера']);flock($rf,LOCK_EX);$rate=json_decode(stream_get_contents($rf)?:'[]',true);if(!is_array($rate))$rate=[];foreach($rate as $key=>$times){$rate[$key]=array_values(array_filter((array)$times,function($time)use($now){return (int)$time>$now-3600;}));if(!$rate[$key])unset($rate[$key]);}if(count($rate[$rateKey]??[])>=5){flock($rf,LOCK_UN);fclose($rf);answer(429,['ok'=>false,'error'=>'Слишком много запросов. Повторите позже']);}$rate[$rateKey][]=$now;ftruncate($rf,0);rewind($rf);fwrite($rf,(string)json_encode($rate));fflush($rf);flock($rf,LOCK_UN);fclose($rf);@chmod($rateFile,0600);
$id='lead_'.gmdate('YmdHis').'_' . bin2hex(random_bytes(4));$lead=['id'=>$id,'name'=>$fullName,'phone'=>$phone,'message'=>$message,'status'=>'new','created_at'=>gmdate('c'),'consent'=>['accepted'=>true,'version'=>CONSENT_VERSION,'accepted_at'=>gmdate('c'),'source'=>'website_form'],'adult_confirmed'=>true];
$file=STORAGE_PATH.'leads.json';$fh=@fopen($file,'c+');if(!$fh)answer(500,['ok'=>false,'error'=>'Ошибка сервера']);flock($fh,LOCK_EX);$leads=json_decode(stream_get_contents($fh)?:'[]',true);if(!is_array($leads))$leads=[];$leads=$leads['leads']??$leads;if(!is_array($leads))$leads=[];$leads=array_values(array_filter($leads,function($item)use($now){return is_array($item)&&isset($item['created_at'])&&strtotime((string)$item['created_at'])>$now-RETENTION_SECONDS;}));$leads[]=$lead;$json=json_encode($leads,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);if($json===false){flock($fh,LOCK_UN);fclose($fh);answer(500,['ok'=>false,'error'=>'Ошибка сервера']);}ftruncate($fh,0);rewind($fh);fwrite($fh,$json);fflush($fh);flock($fh,LOCK_UN);fclose($fh);@chmod($file,0600);
$admin=(string)getenv('ADMIN_EMAIL');if($admin!==''&&filter_var($admin,FILTER_VALIDATE_EMAIL)){$subject='[КОНТАНТА] Новая заявка '.$id;if(function_exists('mb_encode_mimeheader'))$subject=mb_encode_mimeheader($subject,'UTF-8','B');$safeName=str_replace(["\r","\n"],' ',$fullName);$safePhone=str_replace(["\r","\n"],' ',$phone);$safeMessage=str_replace("\0",'',$message);$body="Новая заявка с сайта\n\nID: $id\nФИО: $safeName\nТелефон: $safePhone\nВопрос: ".($safeMessage!==''?$safeMessage:'не указан')."\nДата UTC: ".gmdate('c')."\nВерсия согласия: ".CONSENT_VERSION;$headers="MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";@mail($admin,$subject,$body,$headers);}
answer(201,['ok'=>true,'message'=>'Заявка принята. Специалист свяжется с вами.']);
