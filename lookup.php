<?php
$ani = filter_input(INPUT_GET, 'ani', FILTER_SANITIZE_URL);
$url = "http://apps.2gis.ru/phone/".$ani;

function find_2gis($phone) {
    global $url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:59.0) Gecko/20100101 Firefox/59.0');
    $data = curl_exec($ch);
    curl_close($ch);
    $result = false;
    preg_match('/var data = {(.*?)};/', $data, $match);
    if($match) $json = json_decode('{'.$match[1].'}', true);
    $result = $json['company'].', '.$json['region'];
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

if($id = find_local($ani)) {
        echo translit($id);
} elseif ($id = find_2gis($ani)) {
        echo translit($id);
} else {
        echo "Unknown Call";
}
