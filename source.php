<?php
//error_reporting(E_STRICT);

$id = 'g29475';

if(isset($_GET['group']) && $_GET['group'] != ''){
    $group = $_GET['group'];
}else{
    $group = '4';
}


if(isset($_GET['subgroup']) && $_GET['subgroup'] != ''){
    $subgroup = $_GET['subgroup'];
}else{
    $subgroup = '2';
}

$nbGroups = 4;

$colorArray = [
    'BEA7B8' => '81C784', //cours/td
    '9FBFBF' => '64B5F6', //tp
    'FFBFBF' => 'FFB74D', //td
    'BFBF7F' => 'e57373' //controles
];


function deleteEmptyBox(array $array){
    $return = [];
    foreach ($array as $key => $value) {
        if(sizeof($value) > 0){
            $return[] = $value;
        }
    }

    return $return;
}

function dateOK($date){
  if(preg_match('#(0[1-9]|[12][0-9]|3[0-1])/(0[1-9]|1[0-2])/([0-9]{4})#', $date, $matches)){
    return $matches[3].'-'.$matches[2].'-'.$matches[1]; //cette variable contient la valeur "sql-ready" de la date
  }
  elseif (preg_match('#([0-9]{4})-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[0-1])#', $date)) {
    return $date;
  }
  else {
    return false;
  }
}

function removeNewLines($string){
    $output = str_replace(array("\r\n", "\r"), "\n", $string);
    $lines = explode("\n", $output);
    $new_lines = array();

    foreach ($lines as $i => $line) {
        if(!empty($line))
            $new_lines[] = trim($line);
    }
    return implode($new_lines);
}



$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'https://edt.univ-tlse3.fr/FSI/FSImentionL/Info/'.$id.'.xml');
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$xml = curl_exec($curl);
curl_close($curl);

//echo $xml;

$dom = new DOMDocument();
$dom->loadXML($xml);


$cours = [];
$jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];


$events = $dom->getElementsByTagName('event');
$spans = $dom->getElementsByTagName('span');


$semaines = [];
foreach ($spans as $key => $span) {
    $semaines[] = [
        'description' => $span->getElementsByTagName('description')[0]->nodeValue,
        'alleventweeks' => $span->getElementsByTagName('alleventweeks')[0]->nodeValue,
        'dateDebut' => dateOk($span->getAttribute('date'))
    ];
}

//die(print_r($semaines));
$i = 0;
foreach ($events as $key => $event) {
    $color = $event->getAttribute('colour');
    if(isset($colorArray[$color])){
        $color = $colorArray[$color];
    }
    $id = $event->getAttribute('id');
    if(isset($event->getElementsByTagName('module')[0])){
        $matiere = $event->getElementsByTagName('module')[0]->nodeValue;
    }else{
        $matiere = '';
    }

    $matiere = preg_replace('#^(.*) - (.*) \(.*\)$#s', '$2', $matiere);

    $groupe = removeNewLines($event->getElementsByTagName('group')[0]->nodeValue);
    if(isset($event->getElementsByTagName('room')[0])){
        $salle = removeNewLines($event->getElementsByTagName('room')[0]->nodeValue);
    }else{
        $salle = '';
    }
    $horaires = $event->getElementsByTagName('prettytimes')[0]->nodeValue;
    $day = $event->getElementsByTagName('day')[0]->nodeValue;
    if(isset($event->getElementsByTagName('notes')[0])){
        $notes = $event->getElementsByTagName('notes')[0]->nodeValue;
    }else{
        $notes = '';
    }
    $rawweeks = $event->getElementsByTagName('rawweeks')[0]->nodeValue;

    if(preg_match('#(TD|TP)A'.$group.'('.$subgroup.')?$#', $groupe) || preg_match('#^L3 INFO$#', $groupe) || preg_match('#^L3 INFO s[0-9]{1} - CMA$#', $groupe) || preg_match('/(L3 INFO s[0-9]{1} - (TD|TP)A[0-9]{1}(1)?){'.$nbGroups.'}$/m', $groupe)){

            $cours[] = [
                'id' => $id,
                'matiere' => $matiere,
                'groupe' => $groupe,
                'day' => $day,
                'color' => $color,
                'rawweeks' => $rawweeks
            ];

        if(preg_match('#([0-9]{2}:[0-9]{2})-([0-9]{2}:[0-9]{2}) ([A-Z\/]+)#', $horaires, $matches)){
            $cours[$i]['horaires'] = [
                'begin' => $matches[1],
                'end' => $matches[2]
            ];
            $cours[$i]['type'] = $matches[3];
        }

        if(isset($notes)){
            $cours[$i]['notes'] = $notes;
        }
        if(isset($salle)){
            $cours[$i]['salle'] = $salle;
        }


        $i++;
    }
}

$final = []; //initialisation du tableau


for($i=0; $i<sizeof($cours); $i++){
    foreach ($semaines as $key => $s) {
        if($cours[$i]['rawweeks'] == $s['alleventweeks']){
            $final[$key][$cours[$i]['day']][] = $cours[$i];
        }
    }
}

foreach ($final as $key => $semaine) { // key == le numéro de la semaine
    $dateDebutSemaine = new DateTime($semaines[$key]['dateDebut']);
    foreach ($semaine as $key2 => $jour) {
        $dateJour = clone $dateDebutSemaine;
        $dateJour->add(new DateInterval('P'.$key2.'D'));
        foreach ($jour as $key3 => $cour) {
            $final[$key][$key2][$key3]['horaires']['begin'] = $dateJour->format('Y-m-d').' '.$cour['horaires']['begin'].':00';
            $final[$key][$key2][$key3]['horaires']['end'] = $dateJour->format('Y-m-d').' '.$cour['horaires']['end'].':00';
        }
    }
}


$fullCal = [];
foreach ($final as $key => $semaine) {
    foreach ($semaine as $key2 => $jour) {
        foreach ($jour as $key3 => $cour) {
            $fullCal[] = $cour;
        }
    }
}
