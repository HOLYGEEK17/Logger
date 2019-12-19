<?php session_start();?>

<!DOCTYPE html>
<head>
  <link rel="stylesheet" href="styles.css">
  <!-- <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous"> -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootswatch/4.4.1/darkly/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js" integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

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

echo <<<EOF
  <div class="container-fluid">
    <div class='m-3'><img src='$gpic' style='width: 30px'> <p style='display: inline-block; margin-left: 10px;'> $gname [$gmail]</p></div>
    <form action='\' method="post" autocomplete="off" class="m-3">    
      <div class="form-row">
        <div class="col"> 
          <input type="text" id="form-log" class="form-control" name=log placeholder="Log">
        </div>
        <div class="col"> 
          <input type="text" id="form-cat" class="form-control" name=category placeholder="Category">
        </div>    
        <div class="col"> 
          <input type="text" class="form-control" name=amount placeholder="Amount">
        </div>

        <button type=submit class="btn btn-primary">Log</button>      
      </div>
    </form>
  EOF;

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
    if($result === FALSE) die(mysqli_error($conn));    
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
    query("insert into users (gid) values ('$gid')");
    $uid = getSingle("select uid from users where gid = '$gid'");
    console_log("Generated uid: $uid");
} else {
    console_log("uid: $uid");
}

// Auto-complete paramters
$autoLogs = getRows("select distinct log from logs where uid = $uid", "log");
$autoCats = getRows("select distinct category from logs where uid = $uid", "category");

// Insert log
if ($_REQUEST['log']) {
  $log = mysqli_real_escape_string($conn, $_REQUEST['log']);
  $category = mysqli_real_escape_string($conn, $_REQUEST['category']);
  $amount = mysqli_real_escape_string($conn, $_REQUEST['amount']);
  $ip = $_SERVER['REMOTE_ADDR'];

  date_default_timezone_set('america/new_york');
  $date = Date("Y-m-d H:i:s");
  query("insert into logs (uid, log, category, amount, date) values ($uid, '$log', '$category', '$amount', '$date')");
}

// Render logs

$res = query("select * from logs where uid = $uid order by date desc");
print <<<EOF
<table class="table m-3">
<thead>
<tr>
  <th scope="col">Log</th>
  <th scope="col">Category</th>
  <th scope="col">Amount</th>
  <th scope="col">Date</th>
</tr>
</thead>
<tbody>

EOF;
while ($row = mysqli_fetch_assoc($res)) {
  $uid = $row['uid'];
  $log = htmlspecialchars($row['log']);
  $category = htmlspecialchars($row['category']);
  $amount = htmlspecialchars($row['amount']);
  $date = $row['date'];

  print <<<EOF
  <tr>
    <td>$log</td>
    <td>$category</td>
    <td>$amount</td>
    <td>$date</td>
  </tr>
  EOF;
}
print "</tbody>";
print "</table>";

mysqli_close($conn);

?>
</div>

<!-- autocomplete -->
<script>
$(function() {
  var logs = <?php echo json_encode($autoLogs);?>;  
  
  var cats = <?php echo json_encode($autoCats);?>;
  
  $("#form-log").autocomplete({
    source: logs
  });
  $("#form-cat").autocomplete({
    source: cats
  });
});
</script>

</html>