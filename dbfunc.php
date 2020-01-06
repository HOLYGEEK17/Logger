<?php 
session_start();
$uid = $_SESSION["uid"];
if (!$uid) die();

// DB connections
$dbhost = 'localhost';
$dbname = 'logger';
$dbuser = 'root';
$dbpass = 'zmYHyBgKJcc*9N';
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

if (!$conn) die('Could not connect: ' . mysqli_connect_error());

// cookies

function getCookie($cname) {
    if(!isset($_COOKIE[$cname])) {
        return "";
    } else {
        return $_COOKIE[$cname];
    }
}

$dtyear = getCookie("dtyear");
$dtmonth = getCookie("dtmonth");
// $dtday = getCookie("dtday");
$dtday = date('d');

if (!$dtyear) $dtyear = "YEAR(CURRENT_DATE())";
if (!$dtmonth) $dtmonth = "MONTH(CURRENT_DATE())";
// if (!$dtday) $dtmonth = date('d');
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

function getRowsArr($query) {
    global $conn;
    $result = mysqli_query($conn, $query);
    if($result === FALSE) die(mysqli_error($conn));   
    $array = array();
    while ($row = mysqli_fetch_assoc($result)){      
      $array[] = $row;
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
    case 'toggleSavingRecurr';
        toggleSavingRecurr();
        break;
    case 'getNetIncome';
        getNetIncome();
        break;
    case 'getSummary';
        getSummary();
        break;
    case 'getSummaryTitle';
        getSummaryTitle();
        break;
    case 'getSummaryValue';
        getSummaryValue();
            break;
    case 'getAutoLogs';
        getAutoLogs();
        break;
    case 'getAutoCats';
        getAutoCats();
        break;
    case 'getLogCatRecords';
        getLogCatRecords();
        break;      
    case 'getDailySpending';
        getDailySpending();
        break;  
    case 'getDailySpendingHistory';
        getDailySpendingHistory();
        break;     
}

// DB Functions

function getAutoLogs() {
    global $uid;
    $autoLogs = getRows("select distinct log from logs where uid = $uid order by log", "log");
    echo json_encode($autoLogs);
}

function getAutoCats() {
    global $uid;
    $autoCats = getRows("select distinct category from logs where uid = $uid order by category", "category");
    echo json_encode($autoCats);
}

function getLogCatRecords() {
    global $uid;
    $logCatPairs = getRowsArr("select distinct log, category from logs where uid = $uid ");
    echo json_encode($logCatPairs);
}

function getDailySpending() {
    global $uid, $dtyear, $dtmonth, $dtday;
    $sql  = "select calendar_day.day as date, IFNULL(avg(logs.amount), 0) as amount ";
    $sql .= "from logs right join calendar_day on DAY(logs.date) = calendar_day.day and YEAR(logs.date) = $dtyear and MONTH(logs.date) = $dtmonth ";
    $sql .= "and logs.amount > 0 and uid = $uid ";
    $sql .= "group by calendar_day.day limit $dtday; ";
    $logCatPairs = getRowsArr($sql);    
    echo json_encode($logCatPairs);
}
function getDailySpendingHistory() {
    global $uid, $dtyear, $dtmonth, $dtday;
    $sql  = "select calendar_day.day as date, IFNULL(avg(logs.amount), 0) as amount ";
    $sql .= "from logs right join calendar_day on DAY(logs.date) = calendar_day.day and (YEAR(date) != YEAR(CURRENT_DATE()) or MONTH(date) != MONTH(CURRENT_DATE())) ";
    $sql .= "and logs.amount > 0 and uid = $uid ";
    $sql .= "group by calendar_day.day; ";
    $logCatPairs = getRowsArr($sql);
    echo json_encode($logCatPairs);
}

function getNetIncome() {
    global $uid, $dtyear, $dtmonth;
    $net_income = getSingle("select -sum(amount) from logs where uid=$uid and YEAR(date) = $dtyear and MONTH(date) = $dtmonth");
    $recurr_sum = getSingle("select -sum(ramount) from recurrs where uid=$uid;");

    if (!$net_income) $net_income = 0;
    if (!$recurr_sum) $recurr_sum = 0;

    $net_income += $recurr_sum;

    echo $net_income;
}

function getSummary() {
    global $uid, $dtyear, $dtmonth;
    $autoCats = getRows("select category, sum(amount) from logs where uid = $uid and YEAR(date) = $dtyear and MONTH(date) = $dtmonth group by category order by sum(amount) desc;", "category");
    $dt = date('F Y');

    // echo "<h5 class='mt-3 mb-3'>Summary for $dt</h5>";
    echo "<table class='table'>";
    echo "<tbody>";

    foreach($autoCats as $cat){
        echo "<tr><td></td><td style='width: 400px'></td><td></td></tr>";
        $catSum = getSingle("select sum(amount) from logs where category='$cat' and uid='$uid' and YEAR(date) = $dtyear and MONTH(date) = $dtmonth");
        echo "<tr style='font-weight: bold;'><td><h5>$cat</h5></td><td></td><td align='right'>$catSum</td></tr>";
        
        $res = query("select category, log, sum(amount) as sum from logs where category='$cat' and uid='$uid' and YEAR(date) = $dtyear and MONTH(date) = $dtmonth group by category, log");  
        while ($row = mysqli_fetch_assoc($res)) {
          $s_sum = $row['sum'];
          $s_log = htmlspecialchars($row['log']);  
      
          // process sum
          $sum_display = "style='color: orangered;'";
          if ($s_sum < 0) {
              $s_sum *= -1;
              $sum_display = "style='color: green;'";
          }

          // Get summary details
          $sdetail_str = "";
          $sdetail = query("select amount, date from logs where uid='$uid' and category='$cat' and log='$s_log' and YEAR(date) = $dtyear and MONTH(date) = $dtmonth order by date");  
          while ($srow = mysqli_fetch_assoc($sdetail)) {
              $damount = $srow['amount'];
              $ddate = $srow['date'];          
              $sdetail_str .= "$$damount   spent@$ddate\n";    
          }
              
          echo "<tr><td style='cursor: pointer' data-toggle='popover' data-placement='right' data-content='$sdetail_str'>$s_log</td><td></td><td align='right' $sum_display>$s_sum</td></tr>";    
        }
      }
      
      echo "</tbody>";
      echo "</table>";
}

function getSummaryTitle() {
    global $uid, $dtyear, $dtmonth;
    $summaryTitle = getRows("select category, sum(amount) as sum from logs where uid='$uid' and YEAR(date) = $dtyear and MONTH(date) = $dtmonth group by category order by sum desc", "category");
    echo json_encode($summaryTitle);
}

function getSummaryValue() {
    global $uid, $dtyear, $dtmonth;
    $summaryValue = getRows("select category, sum(amount) as sum from logs where uid='$uid' and YEAR(date) = $dtyear and MONTH(date) = $dtmonth group by category order by sum desc", "sum");
    echo json_encode($summaryValue);
}

function insertLog() {
    global $uid;
    global $conn;

    $log = mysqli_real_escape_string($conn, $_REQUEST['llog']);
    $category = mysqli_real_escape_string($conn, $_REQUEST['lcategory']);
    $amount = $_REQUEST['lamount'];

    date_default_timezone_set('america/new_york');
    $date = Date("Y-m-d H:i:s");

    $sql = "insert into logs (uid, log, category, amount, date) values ('$uid', '$log', '$category', '$amount', '$date')";
    query($sql);
    echo "success";
}

function getLog() {
    global $uid, $dtyear, $dtmonth;
    $res = query("select * from logs where uid = '$uid' and YEAR(date) = $dtyear and MONTH(date) = $dtmonth order by date desc");
    print <<<EOF
    <table class="table">
    <thead>
    <tr>
    <th scope="col">Log</th>
    <th scope="col">Category</th>
    <th scope="col" style="text-align: right;">Amount</th>
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
        $amount = number_format($amount * -1, 2);
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

function toggleSavingRecurr() {
    $rid = $_REQUEST['rid'];
    $flag = $_REQUEST['flag'];
    global $uid;
    switch($flag) {
        case "0":
            query("update recurrs set rtag = null where uid = '$uid' and rid = '$rid'");
            echo "remove saving tag";
            break;
        case "1":
            query("update recurrs set rtag = 'Saving' where uid = '$uid' and rid = '$rid'");
            echo "added as saving";
            break;
    }
}

function insertRecurr() {
    global $uid;
    global $conn;

    $rname = mysqli_real_escape_string($conn, $_REQUEST['rname']);
    $ramount = mysqli_real_escape_string($conn, $_REQUEST['ramount']);

    // echo "<p>Setup recurr with $rname of amount $ramount for user $uid</p>";

    // insert into recurr table
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
        <th scope="col" style="text-align: right;">Amount</th>
        <th scope="col" style="text-align: center;">Tags</th>
        <th scope="col"></th>
    </tr>
    </thead>
    <tbody>

    EOF;
    while ($row = mysqli_fetch_assoc($res)) {
        $rid = $row['rid'];
        $name = htmlspecialchars($row['rname']);
        $amount = htmlspecialchars($row['ramount']);
        $tag = htmlspecialchars($row['rtag']);

        // process amount
        $amount_display = "style='color: orangered; width: 120px;'";
        if ($amount < 0) {
            $amount *= -1;
            $amount_display = "style='color: green; width: 120px;'";
        }

        // process tag
        $color = "dimgrey";
        $toggle = "1";
        if ($tag) {
            $color = "green";
            $toggle = "0";
        }

        print <<<EOF
        <tr id="rec_$rid">
            <td>$name</td>
            <td align="right" $amount_display>$amount</td>
        <td style="color: $color; cursor: pointer; text-align: center;" onclick="toggleSavingRecurr(this.parentElement, $toggle)">
            <i class="fas fa-money-check-alt" data-toggle="tooltip" data-placement="bottom" title="add or remove as Saving"></i>
        </td>
            <td style="color: grey; cursor: pointer; font-size: small;" onclick="deleteRecurr(this.parentElement)">x</td>
        </tr>
        EOF;
    }
    echo "</tbody>";
    echo "</table>";
}
?>