<?php
$apitk = "<INSERT YOURS>";
$block = "route-to=666";
$defcallerid = "Unknown Call";
$curlua = "Mozilla/5.0 (X11; Linux x86_64; rv:59.0) Gecko/20100101 Firefox/59.0";
$ani   = (int) $_GET['ani'];

$tgtoken="<INSERT YOURS>";
$tgid='<INSERT YOURS>';

$redis = new Redis();

function redis_conn($rhost, $rport) {
        global $redis;
        $redis->connect($rhost, $rport);
        return $redis;
}

function redis_get($key){
        global $redis;
        return $redis->get($key);
}

function redis_set($key, $value){
        global $redis;
        $redis->set($key, $value);
}

function find_shouldianswer($phone) {
    global $block;
    global $curlua;
    $url='https://www.neberitrubku.ru/nomer-telefona/'.$phone;
  if (strlen(redis_get($phone)) == 0) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $curlua);
    $data = curl_exec($ch);
    curl_close($ch);
    $result = false;
	if (!preg_match('/<div class="score negative"><\/div>/', $data, $match)) {
            return false;
        } else {
           $result = $block;
        }
    redis_set($phone, $result);
  } else {
    $result = redis_get($phone);
  }
    return $result;
}


function find_truecall($phone) {
    global $block;
    global $curlua;
    $url = "https://tcapi.phphive.info/".$apitk."/search/".$phone;
  if (strlen(redis_get($phone)) == 0) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $curlua);
    $data = curl_exec($ch);
    curl_close($ch);
    $result = false;
    $json = json_decode($data, true);
    if (false === $json) {
            return false;
    }
    if ($json['name']) {
        if (strlen($json['spamType']) == 0){
           $result = $json['name'];
        } else {
           $result = $block;
        }
    redis_set($phone, $result);
    }
  } else {
   $result = redis_get($phone);
  }
    return $result;
}

function find_2gis($phone) {
    global $block;
    global $curlua;
    $url   = "http://apps.2gis.ru/phone/". $phone;
  if (strlen(redis_get($phone)) == 0) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $curlua);
    $data = curl_exec($ch);
    curl_close($ch);
    $result = false;
    if (!preg_match('/var data = (\{.*?\});/', $data, $match)) {
            return false;
    }
    $json = json_decode($match[1], true);
    if ($json['company']) {
        if (strlen($json['blockedInfo']) == 0){
           $result = $json['company'];
        } else {
           $result = $block;
        }
    redis_set($phone, $result);
    }
  } else {
    $result = redis_get($phone);
  }
    return $result;
}

function find_local($phone) {
    $f = fopen(__DIR__ . '/local.db', "r");
    $result = false;
    while ($row = fgetcsv($f)) {
        if ($row[0] == $phone) {
            $result = $row[1];
            break;
        }
    }
    fclose($f);
    return $result;
}

function genout($phone,$id) {
	global $defcallerid;
	global $tgtoken;
	global $tgid;
	global $block;
	$url = "https://api.telegram.org/bot".$tgtoken;
	if (strlen($id) < 1) {
                $id=$defcallerid;
        }
	echo translit($id);
	// Telegram Ntfy
	if($id == $block){
		$id = "Спамеры https://www.neberitrubku.ru/nomer-telefona/".$phone;
	}
	$params=[
		'chat_id'=>$tgid,
		'parse_mode'=>'markdown',
		'text'=>'*NEW INCOMING VOICE CALL*
*Phone number:* +'.$phone.'
*Caller ID:* '.$id
	];
	$ch = curl_init($url . '/sendMessage');
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	curl_close($ch);
}

function translit($string) {
    $converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',
        'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
        'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',
        'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',
        'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
        'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

        'А' => 'A',   'Б' => 'B',   'В' => 'V',
        'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
        'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',
        'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',
        'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
        'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );
    return strtr($string, $converter);
}

try{
        redis_conn( 'localhost', 6379 );

        if($id = find_local($ani)) {
		genout($ani,$id);
        } elseif ($id = find_shouldianswer($ani)) {
		genout($ani,$id);
        } elseif ($id = find_truecall($ani)) {
		genout($ani,$id);
        } else {
		genout($ani,$id);
        }
}catch( Exception $e ){
        echo $defcallerid;
        // echo 'Error: ' . $e->getMessage();
}
