<?php

include('sites/default/files/mydash/inc/htmlgen.php'); 

if ( ! $mydash_home ) {
  print("Error! mydash_home has not been set. ");
  print("This should be done in drupal.");
 }

$dbname='sites/default/files/' . $mydash_home . '/data/mydash.sqlite';
$db = new PDO('sqlite:' . $dbname);
global $user;

define('WORK',1);
define('AUTHOR',2);
define('DEPARTMENT',3);
define('DEPARTMENT_GROUP',4);
define('SCHOOL',5);
define('ALL_DASH',6);

define('DOWNLOADS',1);
define('PREVIEWS',2);

define('MYDASH_HOME', $mydash_home);

function quote_join($array,$quote,$delimiter){
  $string = "";
  for ( $i=0; $i < count($array); $i++ ) {
    $string .= $quote . $array[$i] . $quote;
    if ( $i+1 < count($array) ) {
      $string .= $delimiter;
    }
  }
  return $string;
}

/**
 * some schools and departments don't have enough users to protect author privacy.
 * remove these from public view.
 *
 * @param role object
 * @return list of restricted groups
 */
function get_restricted_groups($role){

  $restricted_groups = array();
  if ( $role['level'] == ALL_DASH ) {
     # superusers can see everything.
  } else {
    $file = "sites/default/files/" . MYDASH_HOME . "/inc/private.json";
    $privacy_tree = json_decode(file_get_contents($file),true);
    $private_schools = $privacy_tree[SCHOOL];
    foreach ( $private_schools as $school) {
	if ( $role['limit'] != $school ) {
	  # school level admins for this school can see it but not other restricted schools.
	  $restricted_groups []= $school;
	}
    }
    $private_departments = $privacy_tree[DEPARTMENT];
    foreach ($private_departments as $school => $departments ) {
      if ( $role['limit'] != $school ) {
	# school level admins can see restricted 
	foreach ( $departments as $department ) {
	  $restricted_groups []= $department;
	}
      }
    }
  }
  return $restricted_groups;
}

/**
 * encrypted huid is the key we use to store info about harvard people.
 * look this up in the mysql table created by osc_pinserver by drupal user id.
 *
 * @param drupal user id
 * @return encrypted huid
 */
function get_hashed_huid($uid){
  // we don't need to use a prepared statement here because $uid is coming from Drupal
  // by way of get_myid()
  //return db_query("SELECT huid FROM {pinserver_osc} WHERE uid = ':uid'", array(':uid' => $uid))->fetchField;
  if ($uid == 1) {
    return $uid;
  } else {
    global $user;
    return $user->name;
  }
}

/**
 * Ripe for refactoring. Why have this extra, unnecessary third user id which is really just a copy of another?
 *
 * @param drupal user id
 * @return mydash user id (either hashed huid or if none then drupal user uid)
 */
function get_myid($uid){
  $myid = get_hashed_huid($uid);
  if ( ! $myid ) {
    $myid = $uid;
  }
  return $myid;
}

/**
 * Figure out what the user can access.
 * Works but a tad strange. This whole $myid thing needs rethinking.
 *
 * @param mydash user id
 * @return role object containing the level and limit of privs.
 */
function get_mydash_role($myid){
  # note: 2nd argument to json_decode needs to be true or it does goofy object stuff.
  $privs = json_decode(file_get_contents("sites/default/files/" . MYDASH_HOME . "/inc/privs.json"),true);
  $role = array();
  if ( $privs[$myid]){
    $role = $privs[$myid];
  } else {
    $role['level'] = AUTHOR;
    $role['limit'] = $myid;
  }
  return $role;
}

## common sqlite queries

function groupId2name($db,$id){
  $sql = "select g.name from groups g where g.id = :id";
  $stmt = $db->prepare($sql);
  $stmt->bindParam(':id', $id);
  try {
    $stmt->execute();
    while ($row = $stmt->fetch()) {
      return htmlentities($row['name'], ENT_QUOTES, "UTF-8");
    }
  } catch(PDOException $e) {
    //print '<p>SQL: ' . $sql . '</p>'; ## because we're using a prepared $stmt, we can't get back the SQL:
    // http://stackoverflow.com/questions/1786322/in-php-with-pdo-how-to-check-the-final-sql-parametrized-query
    print '<p>Exception : '.$e->getMessage();
    print '</p>';
  }
}

function typeId2name($id){  // Get from same source as dropdown
  if ( $id == 1 ) {
    return "Articles Downloaded";
  } else if ( $id ==2 ) {
    return "Articles Previewed";
  } else if ( $id == 3 ) {
    return "Visitors";
  } else if ( $id == 4 ) {
    return "Articles Posted";
  }
}

## html generation methods.


function periodselect($p) {
  return select("p",array("last7","last30","alltime"),array("Last 7 Days", "Last 30 Days","All Time"),$p);
}

function typeselect($t) {
  return select("t",array("1","2","3"),array("# Article Downloads", "# Article Previews","# Visitors"),$t);
}


?>