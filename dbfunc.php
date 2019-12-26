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
    case 'insertLog';
        insertLog();
        break;
    case 'getLog';
        getLog();
        break;
    case 'deleteLog';
        deleteLog();
        break;
    case 'deleteRec';
        deleteRec();
        break;
    case 'getNetIncome';
        getNetIncome();
        break;
}

// DB Functions

function getNetIncome() {
    global $uid;
    $net_income = getSingle("select -sum(amount) from logs where uid=$uid and YEAR(date) = YEAR(CURRENT_DATE()) and MONTH(date) = MONTH(CURRENT_DATE())");
    $recurr_sum = getSingle("select -sum(ramount) from recurrs where uid=$uid;");

    if (!$net_income) $net_income = 0;
    if (!$recurr_sum) $recurr_sum = 0;

    $net_income += $recurr_sum;

    echo $net_income;
}

function insertLog() {
    global $uid;

    $log = $_REQUEST['llog'];
    $category = $_REQUEST['lcategory'];
    $amount = $_REQUEST['lamount'];

    date_default_timezone_set('america/new_york');
    $date = Date("Y-m-d H:i:s");

    $sql = "insert into logs (uid, log, category, amount, date) values ('$uid', '$log', '$category', '$amount', '$date')";
    query($sql);
    echo "success";
}

function getLog() {
    global $uid;
    $res = query("select * from logs where uid = '$uid' and YEAR(date) = YEAR(CURRENT_DATE()) and MONTH(date) = MONTH(CURRENT_DATE()) order by date desc");
    print <<<EOF
    <table class="table">
    <thead>
    <tr>
    <th scope="col">Log</th>
    <th scope="col">Category</th>
    <th scope="col">Amount</th>
    <th scope="col">Date</th>
    <th scope="col"></th>
    </tr>
    </thead>
    <tbody>

    EOF;
    while ($row = mysqli_fetch_assoc($res)) {
    $lid = $row['lid'];
    $log = htmlspecialchars($row['log']);
    $category = htmlspecialchars($row['category']);
    $amount = htmlspecialchars($row['amount']);
    $date = $row['date'];

    // process amount
    $amount_display = "style='color: orangered;'";
    if ($amount < 0) {
        $amount *= -1;
        $amount_display = "style='color: green;'";
    }

    print <<<EOF
    <tr id="log_$lid">
        <td>$log</td>
        <td>$category</td>
        <td align="right" $amount_display>$amount</td>
        <td>$date</td>
        <td style="color: grey; cursor: pointer; font-size: small;" onclick="deleteLog(this.parentElement)">x</td>
    </tr>
    EOF;
    }
    echo "</tbody>";
    echo "</table>";
}

function deleteLog() {
    $lid = $_REQUEST['lid'];
    global $uid;
    query("delete from logs where uid = '$uid' and lid = '$lid'");
    echo "deleted";
}

function deleteRec() {
    $rid = $_REQUEST['rid'];
    global $uid;
    query("delete from recurrs where uid = '$uid' and rid = '$rid'");
    echo "deleted";
}

function insertRecurr() {
    $rname = $_REQUEST['rname'];
    $ramount = $_REQUEST['ramount'];

    // echo "<p>Setup recurr with $rname of amount $ramount for user $uid</p>";

    // insert into recurr table
    global $uid;
    query("insert into recurrs (uid, rname, ramount) values ('$uid', '$rname', '$ramount')");
    echo "success";
}

function getRecurr() {
    // get all recurrence records
    global $uid;
    $res = query("select * from recurrs where uid = '$uid' order by ramount desc");
    print <<<EOF
    <table class="table">
    <thead>
    <tr>
        <th scope="col">Name</th>
        <th scope="col">Amount</th>
        <th scope="col"></th>
    </tr>
    </thead>
    <tbody>

    EOF;
    while ($row = mysqli_fetch_assoc($res)) {
        $rid = $row['rid'];
        $name = htmlspecialchars($row['rname']);
        $amount = htmlspecialchars($row['ramount']);

        // process amount
        $amount_display = "style='color: orangered;'";
        if ($amount < 0) {
            $amount *= -1;
            $amount_display = "style='color: green;'";
        }

        print <<<EOF
        <tr id="rec_$rid">
            <td>$name</td>
            <td align="right" $amount_display>$amount</td>
            <td style="color: grey; cursor: pointer; font-size: small;" onclick="deleteRecurr(this.parentElement)">x</td>
        </tr>
        EOF;
    }
    echo "</tbody>";
    echo "</table>";
}
?>