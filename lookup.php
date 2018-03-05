<?php
$apitk = "<INSER YOURS>";
$block = "route-to=666";
$defcallerid = "Unknown Call";
$curlua = "Mozilla/5.0 (X11; Linux x86_64; rv:59.0) Gecko/20100101 Firefox/59.0";
$ani   = (int) $_GET['ani'];
$url   = "http://apps.2gis.ru/phone/". $ani;
$tcapi = "https://tcapi.phphive.info/".$apitk."/search/".$ani;
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

function find_truecall($phone) {
    global $tcapi;
    global $block;
    global $curlua;
  if (strlen(redis_get($phone)) == 0) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tcapi);
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
    global $url;
    global $block;
    global $curlua;
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
                echo translit($id);
        } elseif ($id = find_2gis($ani)) {
                echo translit($id);
        } elseif ($id = find_truecall($ani)) {
                echo translit($id);
        } else {
                echo $defcallerid;
        }
}catch( Exception $e ){
        echo $defcallerid;
        // echo 'Error: ' . $e->getMessage();
}
