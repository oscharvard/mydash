<?php
## simple web service to do autocomplete for mydash web forms

## give it a partial group name or partial id and a level and it'll find matching group "objects"

$mydash_home = "mydash";
$dbname='sites/default/files/' . $mydash_home . '/data/mydash.sqlite';
$db = new PDO('sqlite:' . $dbname);
$gl = "";

if ( isset($_GET["gl"]) ) {
  $gl = $_GET["gl"];
 }
if ( isset($_GET["term"] ) ) {
  $gn = $_GET["term"];
 }

$sql = "select id,name from groups where ";

if ( $gl ) {
  $sql .= " level = " . $gl . " and ";
}

$sql .= " ( name like '%" . $gn . "%' or id like '%" . $gn . "%')" ;

$jsonrows =  array();

$runquery=true;

if($runquery ) {
  $i=0;
  try {
    $result = $db->query($sql);
    //print_r(pre($result));
    foreach($result as $row) {
      $a = array();
      $a['value']=$row['id'];
      $a['label']=$row['name'];
      $jsonrows[] = $a;
    }
  } catch(PDOException $e) {
    print '<p>SQL: ' . $sql;
    print '</p><p>Exception : '.$e->getMessage();
    print '<p>';
  }
  $db = NULL;
 }

print json_encode($jsonrows);

?>


