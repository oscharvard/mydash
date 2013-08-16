<?php
include('sites/default/files/' . $mydash_home . '/inc/common.php'); 

$t=1; # downloads are default.
$gi=get_myid($user->uid);
#$gi=$user->name; # user name is default. 

if ( isset($_GET["gi"])){
  $gi = $_GET["gi"];
}


if ( isset($_GET["t"])){
  $t = $_GET["t"];
}

// validation
/* if ( !( is_numeric($t) && preg_match("/^[a-z0-9\/ ]*$/i",$gi)) ) { */
/*   error_log("Invalid query string: " . $_SERVER['QUERY_STRING']); */
/*   print pre("Invalid input. This incident has been logged."); */
/*   $runquery=false;  */
/*  } */
#$fv = sqlite_escape_string($fv);

## todo: we need to support multiple plots.

$datescores= array();

$sql = "select hc.startdate,hc.days from hitcounts hc where hc.type = :t and hc.group_id = :gi ";
$stmt = $db->prepare($sql);
$stmt->bindParam(':t', $t);
$stmt->bindParam(':gi', $gi);
#print pre($sql);

$runquery = true;

if($runquery ) {
  $gn = groupId2name($db,$gi);
  $tn = typeId2name($t);
  try {
    $stmt->execute();
    //$result = $db->query($sql);
    //print_r(pre($result));
    //foreach($result as $row) {
    while ($row = $stmt->fetch()) {
      $startdate = $row['startdate'];
      $scores    = preg_split( "/\,/", $row['days'],-1, PREG_SPLIT_NO_EMPTY );
      $dateparts = preg_split( "/\-/", $row['startdate'],-1, PREG_SPLIT_NO_EMPTY );
      $y = $dateparts[0];
      $m = $dateparts[1];
      $d = $dateparts[2];
      $highcharts_date = join(",", array($y,$m-1,$d));
      foreach ( $scores as $score ) {
	$datescore=array();
	$datescore['date'] = $y . "," . $m;
	$datescore['score'] = $score;
	$datescores[] = $datescore;
	$m+=1;
	if ( $m > 12 ) {
	  $m=1;
	  $y++;
	}
      }
    }
  } catch(PDOException $e) {
    print '<p>SQL: ' . $sql;
    print '</p><p>Exception : '.$e->getMessage();
    print '<p>';
  }
 }

drupal_add_js('sites/default/files/mydash/assets/jquery.js');
drupal_add_js('sites/default/files/mydash/assets/highcharts/js/highcharts.js');
drupal_add_js('sites/default/files/mydash/assets/highcharts/js/modules/exporting.js');
drupal_add_js('sites/default/files/mydash/inc/mydash-highcharts-conf.js');

?>

<script type="text/javascript">

var hitkind ="<?php print $tn ?>";
var tmpdiv = document.createElement("div");
tmpdiv.innerHTML = "<?php print $gn ?>";
var groupname = tmpdiv.innerText || tmpdiv.textContent;
var startdate = Date.UTC(<?php print $highcharts_date; ?>);
var counts = [ <?php print join(",", $scores) ?>];

if (<?php print $t ?> === 4) {  // $t == 4 does not produce a value for hitkind -- is that right?
  var current = 0;
  var cumulative = [];
  for (var i in counts) {
    current += counts[i];
    cumulative.push(current);
  }
}

var mywindow = counts.length / 50;
if (mywindow < 7) {
  mywindow = 7;
} else {
  mywindow = Math.round(mywindow);
}

function simple_moving_averager(period) { // from http://rosettacode.org/wiki/Averages/Simple_moving_average#JavaScript
  var nums = [];
  return function(num) {
    nums.push(num);
    if (nums.length > period)
      nums.splice(0,1);  // remove the first element of the array
    var sum = 0;
    for (var i in nums)
      sum += nums[i];
    var n = period;
    if (nums.length < period)
      n = nums.length;
    return(sum/n);
  }
}

var simple_moving_average = simple_moving_averager(mywindow);

var averages=[];

for (var i in counts) {
  var n = counts[i];
  averages.push(Math.round(simple_moving_average(n)));
}

var chart;
if (<?php print $t ?> === 4) { // must be a better way
  $(document).ready(function() {
      chart = new Highcharts.Chart(chartconfig(hitkind, 1));
    });
} else {
  $(document).ready(function() {
      chart = new Highcharts.Chart(chartconfig(hitkind, 0));
    });
}
				
		</script>
		
	</head>
	<body>
		
		<!-- 3. Add the container -->
		<div id="container" style="width: 700px; height: 400px; margin: 0 auto"></div>
		
