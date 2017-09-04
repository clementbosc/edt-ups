<?php

require ('source.php');

$eventsFullCall = [];

foreach ($fullCal as $key => $e) {
    $start = new DateTime($e['horaires']['begin']);
    $end = new DateTime($e['horaires']['end']);

    $eventsFullCall[$key] = [
        'id' => $e['id'],
        'title' => $e['matiere'],
        'start' => $start->format(DateTime::ISO8601),
        'end' => $end->format(DateTime::ISO8601),
        'backgroundColor' => '#'.$e['color'],
        'salle' => $e['salle']
    ];
    if(isset($e['notes'])){
        $eventsFullCall[$key]['description'] = $e['notes'];
    }
}

if(isset($_GET['start'], $_GET['end'])){
    echo json_encode($eventsFullCall);
    exit();
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Emploi du temps</title>

        <link rel="manifest" href="/edt/manifest.json">
        <meta name="theme-color" content="#4CAF50">

        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" id="status-bar" content="white-translucent">
        <meta name="format-detection" content="telephone=no">

        <link href="https://fonts.googleapis.com/css?family=Roboto|Roboto+Mono" rel="stylesheet">

        <link rel='stylesheet' href='fullcalendar/fullcalendar.css' />
        <meta name="robots" content="noindex">
        <style media="screen">
            body{
                background: #fafafa;
                font-family: 'Roboto', sans-serif;
            }

            header {
                height: 50px;
                background-color: #4CAF50;
                position: absolute;
                top: 0;
                width: 100%;
                left: 0;
                box-shadow: 0 2px 5px rgba(0,0,0,0.26);
                color: #fff;
            }

            h1{
                margin: 0 0 0 20px;
                line-height: 50px;
                font-family: 'Roboto Mono', monospace;
                font-weight: normal;
                letter-spacing: 0px;
                font-size: 22px;
            }

            .inputLinkICS-container {
                position: absolute;
                top: 0;
                right: 20px;
                display: flex;
                align-items: center;
                height: 50px;
            }
            #inputLinkICS{
                width: 180px;
                border: none;
                font-size: 14px;
                background: #4caf50;
                border-bottom: 2px solid #fff;
                padding: 0 0 4px 0;
                color: #fff;
                outline: none;
            }

            .content{
                margin: 70px auto 20px;
                width: 98%;
                max-width: 1200px;
            }

            .fc-event {
                padding: 5px;
                border: none;
                box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
                color: #000;
            }
            .fc-event:hover{
                color: #000;
            }
            .fc-desc {
                font-size: 85%;
                color: #5a5a5a;
            }
            .fc-title {
                border-bottom: 1px solid grey;
                margin-bottom: 4px;
            }

            .ics-img{
                background-image: url("ics.svg");
                height: 42px;
                background-position: center center;
                background-repeat: no-repeat;
                width: 80px;
                position: absolute;
                left: -90px;
            }

            @media screen and (max-width: 710px) {
                .inputLinkICS-container{
                    display: none;
                }
            }

            @media screen and (max-width: 400px){
                h1{
                    font-size: 18px;
                }
            }

            footer{
                display: flex;
                color: #9a9a9a;
                border-top: 1px solid #cccccc;
                padding: 10px 20px;
                align-items: center;
                justify-content: space-between;
            }
            footer a {
                color: inherit;
            }
            p{
                margin: 0;
            }

        </style>
        <script src='jquery.js'></script>
        <script src='moment.js'></script>
        <script src='fullcalendar/fullcalendar.js'></script>
        <script src='fullcalendar/locale/fr.js'></script>
        <script type="text/javascript">
        $(document).ready(function() {
            // page is now ready, initialize the calendar...

            $('#calendar').fullCalendar({
                events: '/edt'+location.search,
                weekNumbers: true,
                weekends: false,
                views: {
                  agendaThreeDay: {
                    type: 'agenda',
                    duration: { days: 3 },
                    buttonText: '3 jours'
                  }
                },
                header: {
                  left: 'prev,next today',
                  center: 'title',
                  right: 'agendaWeek,agendaThreeDay'
                },
                defaultView: 'agendaWeek',
                eventRender: function(event, element) {
                    if(event.salle != null){
                        element.find('.fc-title').after("<div class='fc-salle'>"+event.salle+"</div>");
                    }
                    if(event.description != null){
                        element.find('.fc-salle').after("<div class='fc-desc'>"+event.description+"</div>");
                    }
                },
                minTime: '07:45:00',
                maxTime: '20:00:00'
            });


        });
        </script>

        <script>
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
                    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
                m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

            ga('create', 'UA-82548402-2', 'auto');
            ga('send', 'pageview');

        </script>
    </head>
    <body>
        <header>
            <h1>Emploi du temps groupe M1 DC</h1>
            <div class="inputLinkICS-container">
                <div class="ics-img"></div>
                <input type="text" id="inputLinkICS" value="https://<?= $_SERVER['HTTP_HOST'].'/edt/get-ics.php?group='.$group.'&subgroup='.$subgroup ?>">
                <input type="image" src="copy_icon.svg" style="outline: none; margin: 0 0 0 10px;" data-clipboard-target="#inputLinkICS" id="copyButton">
            </div>
        </header>
        <div class="content">

            <div id='calendar'></div>
        </div>

        <footer>
            <p>Emploi du temps UPS - par <a href="https://clementbosc.fr">clementbosc</a></p>
            <p>Me payer un café : <a href="https://paypal.me/bosc/3" target="_blank">paypal.me/bosc</a></p>
        </footer>

        <script src="clipboard.min.js"></script>
        <script>
            new Clipboard('#copyButton');
        </script>
    </body>
</html>

<?php exit(); ?>
