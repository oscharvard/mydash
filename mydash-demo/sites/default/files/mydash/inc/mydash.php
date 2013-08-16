<?php

# $mydash_home must be set by including script.

# todo: school and department level info are now PUBLIC.
# so don't apply sql filters to queries EXCEPT for author and work queries.

include('sites/default/files/' . $mydash_home . '/inc/common.php'); 

## attempt at eliminating the possibility of SQL injection
# The only elements of the prepared statement that come from _GET are
# $t, $gl, and $fi; $fl does not make it in to the statement, and 
# we are producing $limit and $joined_restricted_groups -- prepare them
# anyway?
# Do we need to pass in $db?  I think so. 
function build_sql_stmt($db,$gl,$t,$fl,$fi,$level,$limit,$role){
  $select = "SELECT distinct g.id, g.name, hc.last7,hc.last30,hc.alltime,hc.trend,g.level ";
  $from   = "FROM hitcounts hc, groups g ";
  $where = " WHERE hc.group_id = g.id and hc.type = :t and g.level = :gl";
  $order_by = " order by hc.last7 desc";
  if ( $level != ALL_DASH ) {
    # DASH admin level can see everything.
    if ( $gl <= 2 ) {
      # Don't restrict stuff except for group levels 1 (work) and 2 (author)
      $from .= " , hierarchy ph ";
      $where .= " and ph.child_id = g.id and ph.parent_id = '" . $limit . "' ";
    }
  }
  if ( ! empty($fl) and ! empty($fi) ) {
    $from  .= ", hierarchy fh ";
    $where .= " and g.id = fh.child_id ";
    $where .= " and fh.parent_id = :fi ";
  }
  if ( ! empty($role) ) {
    $restricted_groups = get_restricted_groups($role);
    if ( count($restricted_groups) > 0 ) {
      # exclude schools and departments with too few members for privacy.
      $joined_restricted_groups = quote_join($restricted_groups,"'",",");
      $where .= " and g.id not in (" . $joined_restricted_groups . ") ";
    }
  }

  $sql = $select . " " . $from . " " . $where . " " . $order_by;

  $stmt = $db->prepare($sql);
  $stmt->bindParam(':t', $t);
  $stmt->bindParam(':gl', $gl);
  if ( ! empty($fl) and ! empty($fi) ) {
    $stmt->bindParam(':fi', $fi);
  }
  return $stmt;
}

$myid = get_myid($user->uid); 

$role= get_mydash_role($myid);
$level = $role['level'];
$limit = $role['limit'];

drupal_add_js('sites/default/files/mydash/assets/jquery.js');
drupal_add_js('sites/default/files/mydash/assets/jquery.dataTables.min.js');
drupal_add_js('sites/default/files/mydash/assets/jquery.sparkline.js');
drupal_add_css('sites/default/files/mydash/assets/dash_table.css');
drupal_add_js('sites/default/files/mydash/assets/datadumper.js');

# Set Default Values. 

## authors see works by (themselves) author
$fi = $myid;

$fl = AUTHOR;
$gl =  WORK;
$t  =  DOWNLOADS;

$displayname = $user->name;

#if ( preg_match("/^[0-9a-f]{32}$/i",$displayname ) ) {
  # md5 hash. presumably hashed havard pin.
#  $via_pin = " via pin ";
#  $displayname = groupId2name($db,$fi); 
# }

$runquery=true;

if ( $level == ALL_DASH || $user->uid == 0) {
  ## global admins see and public sees downloads by school
  $fi = "";
  $fl = SCHOOL;
  $gl =  SCHOOL;
 } elseif ( $level == SCHOOL ) {
   $fl= SCHOOL;
   $fi = $limit;
   if ( $limit == "1/1" ) {
     ## fas admins see fas departments.
     $gl = DEPARTMENT;
     $fi = $limit;
   } else {
     ## other school admins see authors 
     $gl =  AUTHOR;
   }
 } elseif ( $level == DEPARTMENT ) {
   ## department admins see authors in department.
   $gl = AUTHOR;
   $fl = DEPARTMENT;
   $fi = $limit;

 }

if ( isset($_GET["fi"])){
  $fi = $_GET["fi"];
 }
if ( isset($_GET["fl"]) ) {
  $fl = $_GET["fl"];
 }
if ( isset($_GET["gl"]) ) {
  $gl = $_GET["gl"];
 }
if ( isset($_GET["t"]) ) {
  $t = $_GET["t"];
 }

$fn="";

if ( $fi == "Any" || ! $fi) {
  $fi = "";
  $fn = "Any";
 } else {
  $fn = groupId2name($db,$fi);
 }

// validation
if ( !( is_numeric($gl) && is_numeric($t) && ( is_numeric($fl) || empty($fl)) && preg_match("/^[a-z0-9\/ _]*$/i",$fi)) ) {
  error_log("Invalid query string: " . $_SERVER['QUERY_STRING']);
  print pre("Invalid input. This incident has been logged.");
  $runquery=false; 
 }
#$fi = sqlite_escape_string($fi);


# todo: school heads can see any author for their school.
# requires consultation of  a table mapping authors to schools.
# parameters:
# t   = resource type (1=bitstreams, 2=previews, 3=visitors)
# gl  = group level to display (1=work,2=author,3=department,4=departmentgroup, 5=school, 6= all)
# fl  = filter level to filter by (same optoins as gl)
# fi  = filter id    to filter by
 //='c9e989d522c00c122594ca888fb74c44' ";

# reinos: todo: guard against SQL injection
# brs: done? 2013-05-01

# reinhard: simplified table structure example query: get all works for peter suber, limit to law school.
#sqlite> select g.name,hc.* from hitcounts hc, groups g, hierarchy ph, hierarchy fh where g.level=1 and hc.type=1 and g.id = hc.group_id and ph.child_id = hc.group_id and ph.parent_id =  'Harvard Law School' and fh.parent_id = 'f94c46578ac41cecdb7064e7ef8a5649' and fh.child_id = g.id  order by hc.last7 desc limit 5 ;

#sqlite> select count(*) from hitcounts hc, groups g, hierarchy ph, hierarchy fh where g.level=1 and hc.type=1 and g.id = hc.group_id and ph.child_id = hc.group_id and ph.parent_id =  'Harvard Law School' and fh.parent_id = 'f94c46578ac41cecdb7064e7ef8a5649' and fh.child_id = g.id  order by hc.last7 desc;
#283
#sqlite> 

$stmts= array();

$stmts[]=build_sql_stmt($db,$gl,$t,$fl,$fi,$level,$limit,$role);

## add all articles for author.
if ( $gl == 1 and ! empty($fi) and $fl == 2 ) {
  $stmts[]=build_sql_stmt($db,$gl+1,$t,$fl,$fi,$level,$limit, '');
}

## reinos: doesn't work. figure out. add all of dash for school summary.
if ( $gl == 5 and empty($fi) and $fl == 5 ) {
  $stmts[]=build_sql_stmt($db,$gl+1,$t,$fl+1,$fi,$level,$limit, '');
}

if ( $gl == "1" ) {
  $labelColumn =  "Title";
 } elseif ( $gl == "2" ) {
   $labelColumn =  "Author";
 } elseif ( $gl == "3" ) {
   $labelColumn="Department";
 } elseif ( $gl == "4" ) {
   $labelColumn="Collection";
 } elseif ( $gl == "5" ) {
   $labelColumn="School";
 }

//phpinfo();

$jsonrows =  array();
$trends = array();

if($runquery ) {
  $i=0;
  try {
    foreach($stmts as $stmt) {
      $stmt->execute();
      while ($row = $stmt->fetch()) {
	$a = array();
	$a[]=$row['id'];
	if ( $row['level'] == $gl ) {
	  $a[]=htmlentities($row['name'], ENT_QUOTES, "UTF-8");
	} else {
	  $a[]="All";
	}
	$a[]=$row['last7'];
	$a[]=$row['last30'];
	$a[]=$row['alltime'];
	$a[]=$i;
	$trends[] =$row['trend'];
	$a[]=$row['level'];
	$jsonrows[] = $a;
	$i++;
      }
    }
  } catch(PDOException $e) {
    //print '<p>SQL: ' . $sql . '</p>'; ## because we're using a prepared $stmt, we can't get back the SQL:
    // http://stackoverflow.com/questions/1786322/in-php-with-pdo-how-to-check-the-final-sql-parametrized-query
    print '<p>Exception : '.$e->getMessage();
    print '</p>';
  }
 }
$db = NULL;

  #var_dump($user);

?>

<?php 

if ( $user->uid != 0 ) {
  print '<p>You are logged in as ' . $displayname . '. [ <a href="' . $base_path  . 'user/logout?destination=' . $mydash_home . '">logout</a> ]</p>';
 }
?>

<script>

var hitkind = <?php print $t; ?>;

var labelColumn="<?php print $labelColumn; ?>";

var mydash_home = "<?php print $mydash_home; ?>";

<?php

if ( $user->uid == 0 ) {
  # public should just go to appropriate dash pages from label links.
print "
var labelLinks= ['test',
		 'http://dash.harvard.edu/handle/',
'http://dash.harvard.edu/handle/',
'http://dash.harvard.edu/browse?type=department&value=',
'http://dash.harvard.edu/handle/',
'http://dash.harvard.edu/handle/',
		 'http://dash.harvard.edu/?'
		 ];"
  ;


 } else {
print "

var labelLinks= ['test',
		 'http://dash.harvard.edu/handle/',
		 mydash_home + '?t=1&gl=1&fl=2&fi=',
		 mydash_home + '?t=1&gl=2&fl=3&fi=',
		 mydash_home + '?t=1&gl=2&fl=5&fi=',
		 mydash_home + '?t=1&gl=2&fl=5&fi=',
		 'http://dash.harvard.edu/?'
		 ];
";


 }

?>


var seen = {}; // desperate hack.

var trends = [
<?php

for ($i=0; $i<sizeof($trends); $i++ ) {
  print "\"";
  print $trends[$i];
  print "\",";
 }

?>
];

$(document).ready(function() {


  $('#dynamic').html( '<table cellpadding="0" cellspacing="0" border="0" class="display" id="example"></table>' );
  $('#example').dataTable({
      "aaData": [
		 <?php
# print json_encode($jsonrows);
		 for ($i=0;$i < sizeof($jsonrows); $i++ ) {
		   $row = $jsonrows[$i];
		   print "[";
		   for ($ii=0;$ii < sizeof($row); $ii++ ) {
		     print "\"" . $row[$ii] . "\"";
		     if ( ($ii+1) < sizeof($row) ) {
		       print ",";
		     }
		   }
		   print "]";
		   if ( ($i+1) < sizeof($jsonrows) ) {
		     print ",";
		   }
		   print "\n";
		 }

 ?>
],
	"aoColumns" : [
		       { "sTitle": "", "bSortable" : false, "sWidth" : "21px", "fnRender": function(cell) { if (hitkind !== 4) {return  "<a href=\"<?php print $mydash_home; ?>?v=geomap&t=<?php print $t?>&gi=" + cell.aData[0] + "\"><img src=\"sites/default/files/mydash/images/g.gif\" /></a>" } else {return ""} } },
		       { "sTitle": labelColumn, "asSorting": [ "asc", "desc" ], "fnRender": function(cell) { return "<a href=\"" + labelLinks[cell.aData[6]] + cell.aData[0] + "\">" + cell.aData[1] + "</a>"} },
		       { "sTitle": "Last 7 Days", "asSorting": [ "desc", "asc"] },
		       { "sTitle": "Last 30 Days",  "asSorting": [ "desc", "asc"] },
		       { "sTitle": "All Time", "asSorting": [ "desc", "asc"] },
		       { "sTitle": "Trend", "bSortable" : false , "sWidth" : "50px", "fnRender": function(cell) { return "<a href=\"<?php print $mydash_home;?>?v=timeline&t=<?php print $t?>&gi=" + cell.aData[0] + "\"><span class=\"inlinesparkline\">" + trends[cell.aData[5]] + "</span></a>"; }},
		       { "sTitle": "Type", "bVisible": false }
		       ],
	"aaSorting": [[ 2, "desc" ]],
	"oLanguage": {"sSearch": "Filter:" },
	"bAutoWidth": false,
	"fnDrawCallback": function() {   $('.inlinesparkline').sparkline("html",{type: 'line', spotColor: false}); },
      "bStateSave": false
	});
} );

// if ( ! seen[cell.aData[5]] === 1 )  {  seen[cell.aData[5]]=1;

</script>

<p>
<form name="myd_cf" method="GET" action="" onsubmit="return handleSubmit()">

<?php 
  print select("t",array("1","2","3","4"),array("# Works Downloaded", "# Works Previewed","# Visitors", "# Works Posted"),$t);
?>
 for each
<?php


$levels=array("1","2","3","4","5");
$level_names=array("Work","Author","Department","Collection","School");

if ( $user->uid == 0 ) {
  # hide work and author granularity from public.
  array_shift($levels);
  array_shift($level_names);
  array_shift($levels);
  array_shift($level_names);
 }


print select("gl",$levels,$level_names,$gl);
?> 
where 
<?php
print select("fl",$levels,$level_names,$fl);
?>
is
<?php if ( ! $fi) { $fi = "Any"; } ?> 
<!--<input type="text" name="fi" value="<?php print $fi; ?>"/>-->


<link rel="stylesheet" href="sites/default/files/mydash/assets/jquery-ui-1.8.8/jquery-ui.css" type="text/css" media="all" /> 
<link rel="stylesheet" href="sites/default/files/mydash/assets/jquery-ui-1.8.8/ui.theme.css" type="text/css" media="all" /> 
<script src="sites/default/files/mydash/assets/jquery-ui-1.8.8/jquery-ui.min.js" type="text/javascript"></script> 


<style>
.ui-autocomplete {
  max-height: 100px;
  overflow-y: auto;
  /* prevent horizontal scrollbar */
  overflow-x: hidden;
  /* add padding to account for vertical scrollbar */
  padding-right: 20px;
}
/* IE 6 doesn't support max-height
 * we use height instead, but this forces the menu to always be this tall
 */
* html .ui-autocomplete {
 height: 100px;
}
</style>
<script>

var lastSingleDatum="";
var anyItem = { "value" : "Any", "label" : "Any" };

function handleSubmit(){
  //alert("handleSubmit: " +  $("#fi").val());
  if ( $("#fi").val()){
    //alert("fi!!!!!");
  } else {
    //alert("gaga: " + lastSingleDatum.value);
    if ( lastSingleDatum ) {
      $("#fi").val(lastSingleDatum.value);
    } else {
      if ( $("#fn").val() === "Any" ) {
	$("#fi").val(anyItem.value);
      } else {
	alert("Please specify an unambiguous search criterion value");
	return false;
      }
    }
  }
}

$(function() {

    var schools = [
      { "value" : "2/1", "label" : "School of Time Geography" },
      { "value" : "2/2", "label" : "College of Prehistoric Economics" },
      { "value" : "2/3", "label" : "Graduate School of Forestry" },
      { "value" : "2/4", "label" : "Miskatonic Agricultural College" },
      { "value" : "2/5", "label" : "College of Canon Law" },
      { "value" : "2/6", "label" : "Institute for Study of the Elder Gods" },
      { "value" : "2/7", "label" : "Faculty of Tunnel Theory" },
      { "value" : "2/8", "label" : "School of Extraterrestrial Religion" },
      { "value" : "2/9", "label" : "College of Lunch Studies" },
      { "value" : "2/10", "label" : "Institute for Metadynamics" },
      { "value" : "2/11", "label" : "College of String" }
      ];

    var collections = [
      { "value" : "2/12", "label" : "SER Occasional Pamphlets" },
      { "value" : "2/13", "label" : "STG Scholarly Articles" },
      { "value" : "2/14", "label" : "SER Scholarly Articles" },
      { "value" : "2/15", "label" : "CPE Scholarly Articles" },
      { "value" : "2/16", "label" : "ISEG Scholarly Articles" },
      { "value" : "2/17", "label" : "CCL Scholarly Articles" },
      { "value" : "2/18", "label" : "GSF Scholarly Articles" },
      { "value" : "2/19", "label" : "MAC Scholarly Articles" },
      { "value" : "2/20", "label" : "FTT Faculty Scholarship" },
      { "value" : "2/21", "label" : "FTT Occasional Pamphlets" },
      { "value" : "2/22", "label" : "CLS Scholarly Articles" },
      { "value" : "2/23", "label" : "IM Scholarly Articles" },
      { "value" : "2/24", "label" : "CS Scholarly Articles" },
      { "value" : "2/25", "label" : "CS Occasional Pamphlets" },
      { "value" : "2/26", "label" : "STG Student Papers" },
      { "value" : "2/27", "label" : "FTT Visiting Researchers Publications" }
    ];

 
    var departments = [
{ "value" : "Abyssal Studies", "label" : "Abyssal Studies" },
{ "value" : "Areography", "label" : "Areography" },
{ "value" : "Art of Ancient New England", "label" : "Art of Ancient New England" },
{ "value" : "Cthulhu Studies", "label" : "Cthulhu Studies" },
{ "value" : "Deep Ecology", "label" : "Deep Ecology" },
{ "value" : "Dickens Studies", "label" : "Dickens Studies" },
{ "value" : "Flensing Studies", "label" : "Flensing Studies" },
{ "value" : "Grey Literature", "label" : "Grey Literature" },
{ "value" : "History of Metal", "label" : "History of Metal" },
{ "value" : "History of Shopping", "label" : "History of Shopping" },
{ "value" : "Hyperborean Studies", "label" : "Hyperborean Studies" },
{ "value" : "Hypercoristics", "label" : "Hypercoristics" },
{ "value" : "Hypocoristics", "label" : "Hypocoristics" },
{ "value" : "Irreal Studies", "label" : "Irreal Studies" },
{ "value" : "Justice and Injustice", "label" : "Justice and Injustice" },
{ "value" : "Languages of Posthistory", "label" : "Languages of Posthistory" },
{ "value" : "Literature of Prehistory", "label" : "Literature of Prehistory" },
{ "value" : "Massive Cells", "label" : "Massive Cells" },
{ "value" : "Monarchy Science", "label" : "Monarchy Science" },
{ "value" : "Live Music", "label" : "Live Music" },
{ "value" : "Phatic Languages", "label" : "Phatic Languages" },
{ "value" : "Philology", "label" : "Philology" },
{ "value" : "Phrenology and Phlebotomy", "label" : "Phrenology and Phlebotomy" },
{ "value" : "Phycology", "label" : "Phycology" },
{ "value" : "Plant Civilizations", "label" : "Plant Civilizations" },
{ "value" : "Prophecy Studies", "label" : "Prophecy Studies" },
{ "value" : "Proteomics", "label" : "Proteomics" },
{ "value" : "Proteonomics", "label" : "Proteonomics" },
{ "value" : "Protonics", "label" : "Protonics" },
{ "value" : "Rock Science", "label" : "Rock Science" },
{ "value" : "Rubber Band Theory", "label" : "Rubber Band Theory" },
{ "value" : "Shipping Studies", "label" : "Shipping Studies" },
{ "value" : "Subterranean Genomics", "label" : "Subterranean Genomics" },
{ "value" : "The Science of Play", "label" : "The Science of Play" },
{ "value" : "Theater", "label" : "Theater" },
{ "value" : "Viral Archaeology", "label" : "Viral Archaeology" },
{ "value" : "Xenobiology", "label" : "Xenobiology" }
];    

var cache = {},
  lastXhr;

    $( "#fn" ).autocomplete({
      minLength: 2,
      source:  function(request,response) { 
	  var lcterm = request.term.toLowerCase();
	  var fl= $('#fl').val(); 
	  if ( fl === '5' ) {
	    handleResponse(response,filter(schools,lcterm,this));
	  } else if ( fl === '4' ) {
	    handleResponse(response,filter(collections,lcterm,this));
	  } else if ( fl === '3' ) {
	    handleResponse(response,filter(departments,lcterm,this));
	  } else if ( fl === '2' || fl === '1') {
	    var searchUrl = "sites/default/files/mydash/inc/groups.php?gl=" + fl + "&mh=" + mydash_home;
	    if ( lcterm in cache ) {
	      handleResponse( response,filter(cache[ lcterm ],lcterm,this) );
	      return;
	    }
	    lastXhr = $.getJSON( searchUrl, request, function( data, status, xhr ) {
				   cache[ lcterm ] = data;
				   if ( xhr === lastXhr ) {
				     handleResponse( response,filter(data,lcterm,this) );
				   }
				 });
	  }
	}, // end source
	  select: function(event,ui){
	  return hideUglyId(event,ui,this);
	}, // end select
	  focus: function(event,ui){
	  return hideUglyId(event,ui,this);
	}, // end focus
	  close: function(event,ui){
	  //alert("close!" + 	  Dumper.write(ui));
	  //this.value = ui.item.label;
	  //$("#fi").val(ui.item.value);
	  //return false;
	}, // end close
	  open: function(event,ui){
	  //alert("open!");
	  $("#fi").val("");
	}
      });
  });

function handleResponse(response,data){
  if ( data.length==1 ) {
    lastSingleDatum = data[0];
  } else {
    lastSingleDatum = "";
  }
  response(data);
}


function hideUglyId(event,ui,textfield){
  textfield.value = ui.item.label;
  $("#fi").val(ui.item.value);
  return false;
}

function filter(oldlist,substring){
  var newlist = [];
  if ( anyItem.value.toLowerCase().indexOf(substring) > -1 ) {
    newlist.push(anyItem);
  }
  for ( var i=0; i < oldlist.length; i++ ) {
    if ( oldlist[i].value.toLowerCase().indexOf(substring) > -1 || oldlist[i].label.toLowerCase().indexOf(substring) > -1 ) {
      newlist.push(oldlist[i]);
    }
  }
  return newlist;
}

</script>


  <input id="fn" value="<?php print $fn ?>"/>
  <input id="fi" name="fi" type="hidden" value="<?php print $fi ?>"/>

  <input type="submit" value="Show" />

  </form>
</p>



<p>
<div id="dynamic"></div> 
</p>


<?php 

if ( isset($_GET["debug"] )) {
  print pre($sql);
  print pre("myid: " . $myid);
  print pre("user: " . $user->name);
  print pre("level: " . $level);
  print pre("limit: " . $limit);
  print pre("role: " . json_encode($role));

  print "<pre>";
  print_r($user);
  print "</pre>";

}


//print json_encode($jsonrows);

 ?>

<div>&nbsp</div>

<p>Please <a href="http://dash.harvard.edu/feedback">contact us</a> if you have questions or comments about MyDASH, or would like to see your school or department included.</p>
