<?php

// DB connections

$dbhost = 'localhost';
$dbname = 'logger';
$dbuser = 'root';
$dbpass = 'zmYHyBgKJcc*9N';
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

if (!$conn) die('Could not connect: ' . mysqli_connect_error());

// functions

function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
    if ($with_script_tags) $js_code = '<script>' . $js_code . '</script>';
    echo $js_code;
}

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

// DB Functions

function getUIDs() {
    $uids = getRows("select * from users", "uid");
    return $uids;
}

function getSaving($uid, $dtyear, $dtmonth) {
    $net_income = getSingle("select -sum(amount) from logs where uid=$uid and YEAR(date) = $dtyear and MONTH(date) = $dtmonth");
    $recurr_sum = getSingle("select -sum(ramount) from recurrs where uid=$uid;");
    $recurr_saving = getSingle("select sum(ramount) from recurrs where uid=$uid and rtag='Saving';");

    if (!$net_income) $net_income = 0;
    if (!$recurr_sum) $recurr_sum = 0;
    if (!$recurr_saving) $recurr_saving = 0;

    $net_income += $recurr_sum + $recurr_saving;

    return $net_income;
}

// main
$uids = getUIDs();
// $dty = "YEAR(CURRENT_DATE())";
// $dtm = "MONTH(CURRENT_DATE())";

$dty = date("Y", strtotime("first day of previous month"));
$dtm = date("n", strtotime("first day of previous month"));

foreach($uids as $uid) {
    // console_log($uid);
    $saving = getSaving($uid, $dty, $dtm);
    // console_log($saving);
    query("insert into savings (uid, year, month, amount) values ('$uid', $dty, $dtm, '$saving');");
}

?>