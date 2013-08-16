<?php
include('sites/default/files/' . $mydash_home . '/inc/common.php'); 

global $user;

drupal_add_js('sites/default/files/mydash/assets/jquery.js');

$t=1; # downloads are default.
$gi=get_myid($user->uid);
#$gi=$user->name; # user name is default. 
$p="last30";
$v="geomap";

if ( isset($_GET["gi"])){
  $gi = $_GET["gi"];
}


if ( isset($_GET["t"])){
  $t = $_GET["t"];
}

if ( isset($_GET["p"])){
  $p = $_GET["p"];
 }

// validation
/* if ( !( is_numeric($t) && preg_match("/^[a-z0-9\/ ]*$/i",$gi)) ) { */
/*   error_log("Invalid query string: " . $_SERVER['QUERY_STRING']); */
/*   print pre("Invalid input. This incident has been logged."); */
/*   $runquery=false;  */
/*  } */

$sql = "select hc.geomap,g.name from hitcounts hc, groups g where hc.group_id = g.id and hc.type = :t and hc.group_id= :gi ";
$stmt = $db->prepare($sql);
$stmt->bindParam(':t', $t);
$stmt->bindParam(':gi', $gi);
#print pre($sql);

$runquery = true;

if($runquery ) {
  try {
    $stmt->execute();
    //$result = $db->query($sql);
    //print_r(pre($result));
    //foreach($result as $row) {
    while ($row = $stmt->fetch()) {
      $map_data = $row['geomap'];
      $group_name = $row['name'];
    }
  } catch(PDOException $e) {
    print '<p>SQL: ' . $sql;
    print '</p><p>Exception : '.$e->getMessage();
    print '</p>';
  }
 }


print '<form name="myd_cf" method="GET" action="">';
print hidden('v',$v);
print hidden('gi',$gi);
print '<p><input type="submit" value="Show" />';
print typeselect($t) . " for " . $group_name . " during " . periodselect($p); 
print "</p></form>";
?>

  <script type='text/javascript' src='sites/default/files/mydash/inc/countries.json'></script>
  <script type='text/javascript' src='sites/default/files/mydash/assets/ammap/ammap/ammap.js'></script>
  <script type='text/javascript' src='sites/default/files/mydash/assets/ammap/ammap/maps/js/world_low.js'></script>


  <script type='text/javascript'>

    var mapdata = <?php print $map_data ?>;
    var hitkind = <?php print $t ?>;

  </script>

  <script type='text/javascript' src='sites/default/files/mydash/inc/mydash-maps.js'></script>

  <div id="map_canvas" style="width: 550px; background-color:#E6F5FF; height: 360px"></div>