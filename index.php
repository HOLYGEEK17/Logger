<?php session_start();?>

<!DOCTYPE html>
<head>
  <link rel="stylesheet" href="styles.css">  
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.4.1/darkly/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

  <style>

  /* Extra small devices (portrait phones, less than 576px) */
  /* No media query since this is the default in Bootstrap */

  /* Small devices (landscape phones, 576px and up) */
  /* @media (min-width: 576px) { html {font-size: 2rem;} } */

  /* Medium devices (tablets, 768px and up) */
  /* @media (min-width: 768px) { html {font-size: 1.5rem;} } */

  /* Large devices (desktops, 992px and up) */
  /* @media (min-width: 992px) { html {font-size: 1rem;} } */

  /* Extra large devices (large desktops, 1200px and up) */
  /* @media (min-width: 1200px) { html {font-size: 1rem;} } */

  <?php
      if (strstr($_SERVER['HTTP_USER_AGENT'],'iPhone')) {
          echo "html {font-size: 2rem;}";
      }
  ?>
  </style>

  <title>Logger</title>
  <link rel="icon" href="https://i.ibb.co/c3cNLQ7/billd3.png">
</head>

<body>

<?php
require_once 'vendor/autoload.php';

// init configuration
$clientID = '172854096684-niqcqe1al91vo0vl3gth63nv0t17lqvb.apps.googleusercontent.com';
$clientSecret = 'tGD3rKypVsep2A1iVgq3X8xw';
$redirectUri = 'http://logger.today';

// create Client Request to access Google API
$client = new Google_Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// authenticate code from Google OAuth Flow
if (!$_SESSION["gid"]) {
  if (isset($_GET['code'])) {
      $code = $_GET['code'];
      $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
      $client->setAccessToken($token['access_token']);

      // get profile info
      $google_oauth = new Google_Service_Oauth2($client);
      $google_account_info = $google_oauth->userinfo->get();
      $gmail =  $google_account_info->email;
      $gname =  $google_account_info->name;
      $gid = $google_account_info->id;
      $gpic = $google_account_info->picture;

      console_log("ID: $gid");
      console_log("ID: $code");        

      $_SESSION["gid"] = $gid;
      $_SESSION["gname"] = $gname;
      $_SESSION["gmail"] = $gmail;
      $_SESSION["gpic"] = $gpic;
  } else {
      $gurl = $client->createAuthUrl();
      echo <<<EOF
      <div class="text-center">
        <img style="width: 100px; margin-top: 80px;" src="https://s5.gifyu.com/images/20453d5c971c78b_a.gif">
        <button class="btn btn-primary" type="submit" style="margin-top: 100px;" onclick="location.href = '$gurl';">Google Login</button>
      </div>
      EOF;
      die();
  }
} else {
  $gid = $_SESSION["gid"];
  $gname = $_SESSION["gname"];
  $gmail = $_SESSION["gmail"];
  $gpic = $_SESSION["gpic"];

  console_log("From session");
  console_log("ID: $gid");  
}

// requests info
console_log('$_REQUEST: ');
console_log(print_r($_REQUEST, true));

console_log('$_SESSION: ');
console_log(print_r($_SESSION, true));

// DB connection

$dbhost = 'localhost';
$dbname = 'logger';
$dbuser = 'root';
$dbpass = 'zmYHyBgKJcc*9N';
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

if (!$conn) die('Could not connect: ' . mysqli_connect_error());

// functions

function query($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if ($result === FALSE) {
      console_log("Fail: $query");
      console_log(mysqli_error($conn));
      die(mysqli_error($conn));    
    } 
    return $result;
}

function getSingle($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if($result === FALSE) die(mysqli_error($conn));   
    $row = mysqli_fetch_row($result)[0];
    return $row;
}

function getRows($query, $key) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if($result === FALSE) die(mysqli_error($conn));   
    $array = array();
    while ($row = mysqli_fetch_assoc($result)){      
      $array[] = $row["$key"];
    }
    return $array;
}


function println($str) {
  print $str . '<br>';
}

function console_log($output, $with_script_tags = true) {
  $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
  if ($with_script_tags) $js_code = '<script>' . $js_code . '</script>';
  echo $js_code;
}


// Get parameters

// Get uid
$uid = getSingle("select uid from users where gid = '$gid'");
if (!$uid) {
    $ip = $_SERVER['REMOTE_ADDR'];

    query("insert into users (ip, gid, gname, gmail, gpic) values ('$ip', '$gid', '$gname', '$gmail', '$gpic')");
    $uid = getSingle("select uid from users where gid = '$gid'");
    console_log("Generated uid: $uid");
} else {
    console_log("uid: $uid");
}
$_SESSION["uid"] = $uid; // put uid in session

// body

// customize for pi
if ($gmail == 'atara.sun18@gmail.com') $gpic = 'https://i.ibb.co/DVvMD9K/IMG-2168.jpg';
if ($gmail == 'atara.sun18@gmail.com') $gpic = 'https://s5.gifyu.com/images/ezgif.com-crop3ce89edfc94b9e98.gif';
if ($gmail == 'holygeek17@gmail.com') $gpic = 'https://s5.gifyu.com/images/20453d5c971c78b_a.gif';
// if ($gmail == 'holygeek17@gmail.com') $gpic = 'https://s5.gifyu.com/images/ezgif.com-crop3ce89edfc94b9e98.gif';
  ?>


  <div class="container-fluid">
    <div class='m-3'>
      <img src='<?php echo $gpic; ?>' style='width: 30px'> 
      <p style='display: inline-block; margin-left: 10px;'> <?php echo $gname; ?> [<?php echo $gmail; ?>]</p>
      <p id='net-income' style='display: inline-block; float: right;'> </p>      
    </div>

    <ul class="nav nav-tabs m-3" id="myTab" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="log-tab" data-toggle="tab" href="#log" role="tab" aria-controls="log" aria-selected="true">Log</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="summary-tab" data-toggle="tab" href="#summary" role="tab" aria-controls="summary" aria-selected="false">Summary</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="recurr-tab" data-toggle="tab" href="#recurr" role="tab" aria-controls="recurr" aria-selected="false">Recurr</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="stats-tab" data-toggle="tab" href="#stats" role="tab" aria-controls="stats" aria-selected="false">Stats</a>
      </li>
    </ul>
    <div class="tab-content m-3" id="myTabContent">
      <div class="tab-pane fade show active" id="log" role="tabpanel" aria-labelledby="log-tab">
        <form id="log-form" autocomplete="off">    
          <div class="form-row">
            <div class="col"> 
              <input type="text" class="form-control" id=llog name=llog placeholder="Log" required="true">
            </div>
            <div class="col"> 
              <input type="text" class="form-control" id=lcategory name=lcategory placeholder="Category" required="true">
            </div>    
            <div class="col"> 
              <input type="number" step="any" class="form-control" id=lamount name=lamount placeholder="Amount" required="true">
            </div>

            <button type=submit class="btn btn-primary" data-toggle="tooltip" data-placement="bottom" title="Log a record of expense, with name, category and the amount. Enter minus value for money income">Log</button>      
          </div>
        </form>
        <div id='log-list'></div>
      </div>
      <div class="tab-pane fade" id="summary" role="tabpanel" aria-labelledby="summary-tab">
        <div id="summary"></div>
      </div>
      <div class="tab-pane fade" id="recurr" role="tabpanel" aria-labelledby="recurr-tab">
<?php
// Recurr Tab
?>
        <h5 class="mt-3 mb-3">Monthly Recurrents</h5>
        <form id="recurr-form" autocomplete="off">    
          <div class="form-row">
            <div class="col"> 
              <input type="text" class="form-control" id="rname" name=rname placeholder="Name" required="true">
            </div>
            <div class="col"> 
              <input type="number" step="any" class="form-control" id="ramount" name=ramount placeholder="Amount" required="true">
            </div>
            <button type=submit class="btn btn-primary" data-toggle="tooltip" data-placement="bottom" title="Add recurrent such as income or rent that will repeat monthly">Add Recurrent</button>      
          </div>
          <!-- <input type="hidden" id=uid name=uid value="<?php echo $uid; ?>">  -->
        </form>
        <div id="recurr-list"></div>
      </div>
      <div class="tab-pane fade" id="stats" role="tabpanel" aria-labelledby="stats-tab">
        <p>施工中...</p>
        <p><img style="width: 200px; margin-top: 10px;" src="https://s5.gifyu.com/images/16be4c1a9ebdc0db80.gif"></p>
      </div>
    </div>
</div>

<?php mysqli_close($conn); ?>


<script>
$(function() {
    // Fill contents
    getLog();
    getRecurr();
    getSummary();
    setNetIncome();

    // Autocomplete
    setAutocomplete();
});

// Tooltips
$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})
$(document).ready(function(){ // Hide tooltips after click
    $('[data-toggle="tooltip"]').click(function () {
      $('[data-toggle="tooltip"]').tooltip("hide");
    });
});

// Popovers
$(function () {
  $('[data-toggle="popover"]').popover()
})

function setAutocomplete() {
    var logs;
    var cats;

    $.ajax({
        url: "dbfunc.php",
        dataType:"json",
        data: "func=getAutoLogs"
      }).done(function (response, textStatus, jqXHR){   
        $("#llog").autocomplete({
          source: response
        });
      }).fail(function (jqXHR, textStatus, errorThrown){
          console.log(errorThrown);
    }) 

    $.ajax({
        url: "dbfunc.php",
        dataType:"json",
        data: "func=getAutoCats"
      }).done(function (response, textStatus, jqXHR){   
        $("#lcategory").autocomplete({
          source: response
        });
      }).fail(function (jqXHR, textStatus, errorThrown){
          console.log(errorThrown);
    })
}

function setNetIncome() {
  let netIncome = 0;
  $.ajax({
      url: "dbfunc.php",
      data: "func=getNetIncome"
    }).done(function (response, textStatus, jqXHR){      
        netIncome = parseFloat(response);
        $("#net-income").html("小钱钱: " + response);
        if (netIncome < 0) {
            $("#net-income").css("color", "orangered");
        } else {
            $("#net-income").css("color", "green");
        }
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        console.log("Error getting recurr list");
        console.log(errorThrown);
  })  
}

function getSummary() {
    $("#summary").html(`<div class="text-center"><img style="width: 200px; margin-top: 80px;" src="https://s5.gifyu.com/images/50453c5553eeb38_a.gif"></div>`);
    $.ajax({
        url: "dbfunc.php",
        data: "func=getSummary"
      }).done(function (response, textStatus, jqXHR){
          // console.log(response);
          $("#summary").html(response);          
          $('[data-toggle="popover"]').popover()

      }).fail(function (jqXHR, textStatus, errorThrown){
          // Show error
          console.log("Error getting recurr list");
          console.log(errorThrown);
      })
}

function getRecurr() {
    $("#recurr-list").html(`<div class="text-center"><img style="width: 200px; margin-top: 80px;" src="https://s5.gifyu.com/images/50453c5553eeb38_a.gif"></div>`);
    // Get Recurr contents
    $.ajax({
      url: "dbfunc.php",
      type: "get",
      data: "func=getRecurr"
    }).done(function (response, textStatus, jqXHR){
        // console.log(response);
        $("#recurr-list").html(response);
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        console.log("Error getting recurr list");
        console.log(errorThrown);
    })
}

function getLog() {
    $("#log-list").html(`<div class="text-center"><img style="width: 200px; margin-top: 80px;" src="https://s5.gifyu.com/images/50453c5553eeb38_a.gif"></div>`);
    // Get Log contents
    $.ajax({
      url: "dbfunc.php",
      type: "get",
      data: "func=getLog"
    }).done(function (response, textStatus, jqXHR){
        // console.log(response);
        $("#log-list").html(response);
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        console.log("Error getting log list");
        console.log(errorThrown);
    })
}

function deleteLog(row) {    
    let lid = row.id.replace("log_", "");
    console.log(lid);

    $.ajax({
      url: "dbfunc.php",
      data: "func=deleteLog&lid=" + lid
    }).done(function (response, textStatus, jqXHR){
        console.log(response);
        $(row).hide();
        setNetIncome();
        getSummary();
        setAutocomplete();
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        alert(errorThrown);
    })
}


function deleteRecurr(row) {
    console.log(row);    
    let lid = row.id.replace("rec_", "");
    console.log(lid);

    $.ajax({
      url: "dbfunc.php",
      data: "func=deleteRec&rid=" + lid
    }).done(function (response, textStatus, jqXHR){
        console.log(response);
        $(row).hide();
        setNetIncome();
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        alert(errorThrown);
    })
}

// Post method for Recurr tab
$("#recurr-form").submit(function(event) {    
    /* Stop form from submitting normally */
    event.preventDefault();

    /* Get from elements values */
    var values = $(this).serialize();
    
    // clean form
    $("#rname").val('');
    $("#ramount").val('');

    // Insert Recurr record
    $.ajax({
        url: "dbfunc.php",
        type: "post",
        data: values + "&func=insertRecurr"
    }).done(function (response, textStatus, jqXHR){
        console.log(response);
        getRecurr();
        setNetIncome();
    }).fail(function (){
        // Show error
        console.log("Error post to dbfunc.php");
        console.log(errorThrown);
    })
});

// Post method for Log tab
$("#log-form").submit(function(event) {    
    /* Stop form from submitting normally */
    event.preventDefault();

    /* Get from elements values */
    var values = $(this).serialize();
    
    // clean form
    $("#llog").val('');
    $("#lcategory").val('');
    $("#lamount").val('');

    // Insert Recurr record
    $.ajax({
        url: "dbfunc.php",
        type: "post",
        data: values + "&func=insertLog"
    }).done(function (response, textStatus, jqXHR){
        console.log(response);
        getLog();
        setNetIncome();
        getSummary();
        setAutocomplete();
    }).fail(function (){
        // Show error
        console.log("Error post to dbfunc.php");
        console.log(errorThrown);
    })
});

function toggleSummaryDetail(id) {
  var arr = id.split("&&&");
  if (arr.length != 3) return;
  var dcat = arr[1];
  var dlog = arr[2];

  console.log("dcat: " + dcat + ", dlog: " + dlog);

  $.ajax({
      url: "dbfunc.php",
      data: "func=getSummaryDetail&dcat=" + dcat + "&dlog=" + dlog
    }).done(function (response, textStatus, jqXHR){
        // console.log(response);

        var target = document.getElementById(id);
        $(target).popover({"placement": "right", "title": response});
        $(target).popover('show');
        // $(target).attr({"data-toggle": "popover", "data-placement": "right", "data-content": "test"});

        // setNetIncome();
        // getSummary();
        // setAutocomplete();
    }).fail(function (jqXHR, textStatus, errorThrown){
        // Show error
        alert(errorThrown);
    })
}
</script>

</html>