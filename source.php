<?php
//error_reporting(E_STRICT);

$parcours = 'DC';
$idCal = 'g241537';

if(isset($_GET['parcours']) && $_GET['parcours'] != ''){

    if(preg_match('/([A-Z]+)_(g[0-9]+)/', $_GET['parcours'], $matches)){
        $parcours = $matches[1];
        $idCal = $matches[2];
    }
}

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
curl_setopt($curl, CURLOPT_URL, 'https://edt.univ-tlse3.fr/FSI/2017_2018/M1/M1_INF_'.$parcours.'/'.$idCal.'.xml');
curl_setopt($curl, CURLOPT_FAILONERROR,1);
curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1);
curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
curl_setopt($curl, CURLOPT_TIMEOUT, 15);
$xml = curl_exec($curl);
curl_close($curl);

$dom = new SimpleXMLElement($xml);

$cours = [];
$jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi'];


$events = $dom->event;
$spans = $dom->span;


$semaines = [];
foreach ($spans as $key => $span) {
    $semaines[] = [
        'description' => (string) $span->description,
        'alleventweeks' => (string) $span->alleventweeks,
        'dateDebut' => dateOk($span['date'])
    ];
}

$i = 0;
foreach ($events as $key => $event) {
    $color = (string) $event['colour'];
    if(isset($colorArray[$color])){
        $color = $colorArray[$color];
    }
    $id = (string) $event['id'];

    if(isset($event->resources->module->item)){
        $matiere = (string) $event->resources->module->item;
    }else{
        $matiere = '';
    }

    $matiere = preg_replace('#^(.*) - (.*) \(.*\)$#s', '$2', $matiere);

    $groupe = removeNewLines((string) $event->resources->group->item);
    if(isset($event->resources->room->item)){
        $salle = removeNewLines((string) $event->resources->room->item);
    }else{
        $salle = '';
    }
    $horaires = (string) $event->prettytimes;
    $day = (string) $event->day;
    if(isset($event->notes)){
        $notes = (string) $event->notes;
    }else{
        $notes = '';
    }
    $rawweeks = (string) $event->rawweeks;

    //if(preg_match('/M1 INF-'.$parcours.' s1/', $groupe)){
    //if(preg_match('#(TD|TP)A'.$group.'('.$subgroup.')?$#', $groupe) || preg_match('#^M1 INF-DC$#', $groupe) || preg_match('#^M1 INF-DC s[0-9]{1} - CMA$#', $groupe) || preg_match('/(M1 INF-DC s[0-9]{1} - (TD|TP)A[0-9]{1}(1)?){'.$nbGroups.'}$/m', $groupe)){

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
    //}
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


