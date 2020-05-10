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
  <link rel="icon" href="https://cdn2.iconfinder.com/data/icons/weather-flat-14/64/weather02-512.png">
</head>

<body>
    <div id="weather" class='m-3'>
        <p id="w-desc" style="font-size: x-large"></p>
        <p id="w-temp"></p>
        <p id="w-humidity"></p>
        <p id="w-uv"></p>
    </div>

    <!-- <hr style="background-color: white;"> -->

    <div id="air-quality" class='m-3'>
        <p id="aq-loc"></p>
        <p id="aq-time"></p>
        <p id="aq-pp25"></p>
        <p id="aq-ozone"></p>
    </div>

    <div  id='widget'></div>  
    <div id='map' style='height:380px;'></div>

    <div id="rss-feeds" style="display: None"></div>
</body>

<?php
?>

<script>

function toTitleCase(str) {
        return str.replace(
            /\w\S*/g,
            function(txt) {
                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
            }
        );
    }

function getAirQuality() {
    $.get( "airnow", { "_": $.now() }, function( data ) {
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

        // $("#aq-loc").text(location);
        $("#aq-time").text('Air Quality [' + aqtime + ']');
        $("#aq-pp25").text(aqpp25);
        $("#aq-ozone").text(aqozone);

        console.log(aqtime + ': ' + aqpp25 + ' ' + aqozone);        
    });
}

function getWeather() {
    var weatherAPI = "https://api.openweathermap.org/data/2.5/weather?q=new%20york&units=metric&APPID=b692b306f1748d1bbd80ed2fcbe6aa44";
    var UVAPI = "https://api.openweathermap.org/data/2.5/uvi?appid=b692b306f1748d1bbd80ed2fcbe6aa44&lat=40.72&lon=-74.04";
    $.getJSON( weatherAPI, {
        format: "json"
    })
    .done(function(data) {
        let weather = data["weather"][0]["description"];
        let temp = data["main"]["temp"];
        let temp_min = data["main"]["temp_min"];
        let temp_max = data["main"]["temp_max"];
        let temp_feel = data["main"]["feels_like"];
        let temp_humid = data["main"]["humidity"];        

        $("#w-desc").text(toTitleCase(weather));
        $("#w-temp").text('Temperature: ' + temp + '°C    Feels like ' + temp_feel + '°C [' + temp_min + '°C - ' + temp_max + '°C]');
        $("#w-humidity").text('Humidity: ' + temp_humid + '%');
    });

    // $.getJSON( UVAPI, {
    //     format: "json"
    // })
    // .done(function(data) {        
    //     $("#w-uv").text('UV index: ' + data["value"]);
    // });
}

function dashboard() {
    getWeather();
    getAirQuality();
}

function initMap() {
        // Styles a map in night mode.
        var map = new google.maps.Map(document.getElementById('map'), {
          center: {lat: 40.7215, lng: -74.0280},
          zoom: 12,
          disableDefaultUI: true,
          styles: [
            {
                "elementType": "geometry",
                "stylers": [
                {
                    "color": "#1d2c4d"
                }
                ]
            },
            {
                "elementType": "labels.text.fill",
                "stylers": [
                {
                    "color": "#8ec3b9"
                }
                ]
            },
            {
                "elementType": "labels.text.stroke",
                "stylers": [
                {
                    "color": "#1a3646"
                }
                ]
            },
            {
                "featureType": "administrative.country",
                "elementType": "geometry.stroke",
                "stylers": [
                {
                    "color": "#4b6878"
                }
                ]
            },
            {
                "featureType": "administrative.land_parcel",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "administrative.land_parcel",
                "elementType": "labels.text.fill",
                "stylers": [
                {
                    "color": "#64779e"
                }
                ]
            },
            {
                "featureType": "administrative.neighborhood",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "administrative.province",
                "elementType": "geometry.stroke",
                "stylers": [
                {
                    "color": "#4b6878"
                }
                ]
            },
            {
                "featureType": "landscape.man_made",
                "elementType": "geometry.stroke",
                "stylers": [
                {
                    "color": "#334e87"
                }
                ]
            },
            {
                "featureType": "landscape.natural",
                "elementType": "geometry",
                "stylers": [
                {
                    "color": "#023e58"
                }
                ]
            },
            {
                "featureType": "poi",
                "elementType": "geometry",
                "stylers": [
                {
                    "color": "#283d6a"
                }
                ]
            },
            {
                "featureType": "poi",
                "elementType": "labels.text",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "poi",
                "elementType": "labels.text.fill",
                "stylers": [
                {
                    "color": "#6f9ba5"
                }
                ]
            },
            {
                "featureType": "poi",
                "elementType": "labels.text.stroke",
                "stylers": [
                {
                    "color": "#1d2c4d"
                }
                ]
            },
            {
                "featureType": "poi.business",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "poi.park",
                "elementType": "geometry.fill",
                "stylers": [
                {
                    "color": "#023e58"
                }
                ]
            },
            {
                "featureType": "poi.park",
                "elementType": "labels.text.fill",
                "stylers": [
                {
                    "color": "#3C7680"
                }
                ]
            },
            {
                "featureType": "road",
                "elementType": "geometry",
                "stylers": [
                {
                    "color": "#304a7d"
                }
                ]
            },
            {
                "featureType": "road",
                "elementType": "labels",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "road",
                "elementType": "labels.icon",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "road",
                "elementType": "labels.text.fill",
                "stylers": [
                {
                    "color": "#98a5be"
                }
                ]
            },
            {
                "featureType": "road",
                "elementType": "labels.text.stroke",
                "stylers": [
                {
                    "color": "#1d2c4d"
                }
                ]
            },
            {
                "featureType": "road.arterial",
                "elementType": "labels",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "road.highway",
                "elementType": "geometry",
                "stylers": [
                {
                    "color": "#2c6675"
                }
                ]
            },
            {
                "featureType": "road.highway",
                "elementType": "geometry.stroke",
                "stylers": [
                {
                    "color": "#255763"
                }
                ]
            },
            {
                "featureType": "road.highway",
                "elementType": "labels",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "road.highway",
                "elementType": "labels.text.fill",
                "stylers": [
                {
                    "color": "#b0d5ce"
                }
                ]
            },
            {
                "featureType": "road.highway",
                "elementType": "labels.text.stroke",
                "stylers": [
                {
                    "color": "#023e58"
                }
                ]
            },
            {
                "featureType": "road.local",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "transit",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "transit",
                "elementType": "labels.text.fill",
                "stylers": [
                {
                    "color": "#98a5be"
                }
                ]
            },
            {
                "featureType": "transit",
                "elementType": "labels.text.stroke",
                "stylers": [
                {
                    "color": "#1d2c4d"
                }
                ]
            },
            {
                "featureType": "transit.line",
                "elementType": "geometry.fill",
                "stylers": [
                {
                    "color": "#283d6a"
                }
                ]
            },
            {
                "featureType": "transit.station",
                "elementType": "geometry",
                "stylers": [
                {
                    "color": "#3a4762"
                }
                ]
            },
            {
                "featureType": "water",
                "elementType": "geometry",
                "stylers": [
                {
                    "color": "#0e1626"
                }
                ]
            },
            {
                "featureType": "water",
                "elementType": "labels.text",
                "stylers": [
                {
                    "visibility": "off"
                }
                ]
            },
            {
                "featureType": "water",
                "elementType": "labels.text.fill",
                "stylers": [
                {
                    "color": "#4e6d70"
                }
                ]
            }
            ]
        });
        var t = new Date().getTime();  
        var waqiMapOverlay = new google.maps.ImageMapType({  
                        getTileUrl: function(coord,  zoom)  {  
                            return 'https://tiles.aqicn.org/tiles/usepa-aqi/' + zoom + "/" + coord.x + "/" + coord.y + ".png?token=f6b6f1330518d6d1853d9b25e0b9d20893e44428";  
                        },  
                        name: "Air Quality",  
            });  
        var waqiMapOverlay2 = new google.maps.ImageMapType({  
                    getTileUrl: function(coord,  zoom)  {  
                        return 'https://tiles.aqicn.org/tiles/usepa-pm25/' + zoom + "/" + coord.x + "/" + coord.y + ".png?token=f6b6f1330518d6d1853d9b25e0b9d20893e44428";  
                    },  
                    name: "Air Quality",  
        });          
        map.overlayMapTypes.insertAt(0,waqiMapOverlay); 
        map.overlayMapTypes.insertAt(0,waqiMapOverlay2); 
      }

$(document).ready(function() {
    $.ajaxSetup({ cache: false });

    // (function(w,d,t,f){  w[f]=w[f]||function(c,k,n){s=w[f],k=s['k']=(s['k']||(k?('&k='+k):''));s['c']=  
    // c=(c  instanceof  Array)?c:[c];s['n']=n=n||0;L=d.createElement(t),e=d.getElementsByTagName(t)[0];  
    // L.async=1;L.src='//feed.aqicn.org/feed/'+(c[n].city)+'/'+(c[n].lang||'')+'/feed.v1.js?n='+n+k;  
    // e.parentNode.insertBefore(L,e);  };  })(  window,document,'script','_aqiFeed'  ); 

    // _aqiFeed({  city:"new york",  callback:function(aqi){  
    //     $("#widget").html(aqi.details);  
    // }  });  

    // var map = new google.maps.Map(document.getElementById('map'), {  
    //                 center: new google.maps.LatLng(40.7215, -74.0280),
    //                 mapTypeId: google.maps.MapTypeId.ROADMAP,  
    //                 zoom: 13
    //             });  

    dashboard();
    setInterval(dashboard, 1000 * 60 * 5); // refresh every 5 min
});

</script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB7BqrWrh2krOFmIQQ2W1nbP2r1_5V7jJw&language=en&callback=initMap"
    async defer></script>
</html>