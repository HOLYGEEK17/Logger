<?php 
session_start();
$uid = $_SESSION["uid"];

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

// Get params
$func = $_REQUEST['func'];
switch ($func) {
    case 'insertRecurr':
        insertRecurr();
        break;
    case 'getRecurr':
        getRecurr();
        break;
}

// DB Functions

function insertRecurr() {
    $rname = $_REQUEST['rname'];
    $ramount = $_REQUEST['ramount'];

    // echo "<p>Setup recurr with $rname of amount $ramount for user $uid</p>";

    // insert into recurr table
    query("insert into recurrs (uid, rname, ramount) values ('$uid', '$rname', '$ramount')");
    echo "success";
}

function getRecurr() {
    // get all recurrence records
    $res = query("select * from recurrs where uid = '$uid' order by ramount desc");
    print <<<EOF
    <table class="table">
    <thead>
    <tr>
    <th scope="col">Name</th>
    <th scope="col">Amount</th>
    </tr>
    </thead>
    <tbody>

    EOF;
    while ($row = mysqli_fetch_assoc($res)) {
    $name = htmlspecialchars($row['rname']);
    $amount = htmlspecialchars($row['ramount']);

    print <<<EOF
    <tr>
        <td>$name</td>
        <td align="right">$amount</td>
    </tr>
    EOF;
    }
    echo "</tbody>";
    echo "</table>";
}
?>