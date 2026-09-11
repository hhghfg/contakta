<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
const CONSENT_VERSION = '2026-09-11';
const RETENTION_SECONDS = 7776000; // 90 days
const MAX_BODY = 16384;
const STORAGE_PATH = __DIR__ . '/../storage/';
function out(int $code, array $data): never { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(405, ['ok'=>false,'error'=>'Метод не разрешён']);
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > MAX_BODY) out(413, ['ok'=>false,'error'=>'Слишком большой запрос']);
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin !== '') { $ohost = strtolower((string)parse_url($origin, PHP_URL_HOST)); if ($host === '' || !hash_equals($host, $ohost)) out(403, ['ok'=>false,'error'=>'Источник запроса не разрешён']); }
$type = strtolower($_SERVER['CONTENT_TYPE'] ?? '');
if (str_contains($type, 'application/json')) { $raw=file_get_contents('php://input'); $input=json_decode($raw ?: '', true); if (!is_array($input)) out(400,['ok'=>false,'error'=>'Некорректный JSON']); } else { $input=$_POST; }
if (!empty($input['website'])) out(201, ['ok'=>true,'message'=>'Заявка принята']);
if (($input['pd_consent'] ?? '') !== '1' || ($input['adult_confirmed'] ?? '') !== '1' || ($input['consent_version'] ?? '') !== CONSENT_VERSION) out(400,['ok'=>false,'error'=>'Необходимо отдельное согласие и подтверждение возраста']);
$name=trim((string)($input['name'] ?? '')); $phone=trim((string)($input['phone'] ?? '')); $message=trim((string)($input['message'] ?? ''));
if (mb_strlen($name)<2 || mb_strlen($name)>100) out(400,['ok'=>false,'error'=>'Проверьте имя']);
$digits=preg_replace('/\D+/', '', $phone); if (strlen($digits)<10 || strlen($digits)>15) out(400,['ok'=>false,'error'=>'Проверьте телефон']);
if (mb_strlen($message)>1000) out(400,['ok'=>false,'error'=>'Сообщение слишком длинное']);
if (!is_dir(STORAGE_PATH) && !mkdir(STORAGE_PATH,0700,true) && !is_dir(STORAGE_PATH)) out(500,['ok'=>false,'error'=>'Ошибка сервера']);
$now=time(); $secret=getenv('APP_SECRET') ?: hash('sha256', __FILE__); $ip=(string)($_SERVER['REMOTE_ADDR'] ?? ''); $key=hash_hmac('sha256',$ip,$secret); $rateFile=STORAGE_PATH.'rate-limits.json';
$rf=@fopen($rateFile,'c+'); if (!$rf) out(500,['ok'=>false,'error'=>'Ошибка сервера']); flock($rf,LOCK_EX); $rate=json_decode(stream_get_contents($rf) ?: '[]',true); if(!is_array($rate))$rate=[]; foreach($rate as $k=>$v){$rate[$k]=array_values(array_filter((array)$v,fn($t)=>$t>$now-3600));if(!$rate[$k])unset($rate[$k]);} if(count($rate[$key]??[])>=5){flock($rf,LOCK_UN);fclose($rf);out(429,['ok'=>false,'error'=>'Слишком много запросов. Повторите позже']);}$rate[$key][]=$now;ftruncate($rf,0);rewind($rf);fwrite($rf,json_encode($rate));fflush($rf);flock($rf,LOCK_UN);fclose($rf);@chmod($rateFile,0600);
$file=STORAGE_PATH.'leads.json'; $fh=@fopen($file,'c+'); if(!$fh)out(500,['ok'=>false,'error'=>'Ошибка сервера']);flock($fh,LOCK_EX);$leads=json_decode(stream_get_contents($fh)?:'[]',true);if(!is_array($leads))$leads=[];$leads=array_values(array_filter($leads,fn($x)=>isset($x['created_at'])&&strtotime((string)$x['created_at'])>$now-RETENTION_SECONDS));
$id='lead_'.gmdate('YmdHis').'_' . bin2hex(random_bytes(4)); $lead=['id'=>$id,'name'=>$name,'phone'=>$phone,'message'=>$message,'status'=>'new','created_at'=>gmdate('c'),'consent'=>['accepted'=>true,'version'=>CONSENT_VERSION,'accepted_at'=>gmdate('c'),'source'=>'website_form'],'adult_confirmed'=>true];$leads[]=$lead;ftruncate($fh,0);rewind($fh);fwrite($fh,json_encode($leads,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));fflush($fh);flock($fh,LOCK_UN);fclose($fh);@chmod($file,0600);
$admin=getenv('ADMIN_EMAIL') ?: ''; if($admin && filter_var($admin,FILTER_VALIDATE_EMAIL)){ $safeName=str_replace(["\r","\n"],' ',$name); $subject='[КОНТАНТА] Новая заявка'; $body="ID: $id\nИмя: $safeName\nТелефон: $phone\nВопрос: $message\nСогласие: ".CONSENT_VERSION; @mail($admin,$subject,$body,"Content-Type: text/plain; charset=UTF-8\r\n"); }
out(201,['ok'=>true,'message'=>'Заявка принята. Специалист свяжется с вами.']);
