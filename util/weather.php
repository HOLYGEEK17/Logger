<!DOCTYPE html>
<head>  
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.4.1/darkly/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
  <script src="https://kit.fontawesome.com/d105316e91.js" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-colorschemes"></script>

  <title>Weather</title>
  <link rel="icon" href="https://i.ibb.co/c3cNLQ7/billd3.png">
</head>

<body>
    <div id="air-quality" class='m-3'>
        <p id="aq-loc"></p>
        <p id="aq-time"></p>
        <p id="aq-pp25"></p>
        <p id="aq-ozone"></p>
    </div>

    <div id="rss-feeds" style="display: None"></div>
</body>

<?php
?>

<script>

function getAirQuality() {
    $.get( "airnow", function( data ) {
        $('#rss-feeds').append($(data))

        let td = $("td[valign='top']")[0];
        let location = $($(td).find('div')[0]).text();    
        let infoStr = $(td).find('div')[1];
        let _infoArr = $(infoStr).text().trim().split("\n");
        let infoArr = []
        $(_infoArr).each(function(index, s) {
            s = s.trim();
            if (s.length > 0) {
                infoArr.push(s);
            }
        });

        let aqtime = infoArr[1];
        let aqpp25 = infoArr[2];
        let aqozone = infoArr[3];

        $("#aq-loc").text(location);
        $("#aq-time").text('Updated on: ' + aqtime);
        $("#aq-pp25").text(aqpp25);
        $("#aq-ozone").text(aqozone);
    });
}
setInterval(getAirQuality(), 1000 * 60 * 20); // refresh every 20 min


</script>
</html>