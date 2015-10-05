<?php
function outputRadio(&$radio, $checked)  
{  
    foreach ($radio as $item)  
        echo "<input name=\"frmjobsolution\" type=\"radio\" value=\"{$item['Value']}\"" . ($item['Value'] === $checked ? " checked=\"checked\"" : '') . ">{$item['Text']}</input>\n"; 
} 
function outputOptionList(&$list, $selected)
{
	foreach ($list as $item)
		echo "<option value=\"{$item['Value']}\"" . ($item['Value'] === $selected ? " selected=\"selected\"" : '') . ">{$item['Text']}</option>\n";
}
function outputTime(&$list, $selected)
{
	foreach ($list as $item)
			/*echo $item['Value'].$item['Text']."<br/>";*/
			if ($selected == $item['Value']){
			echo $item['Text'];
			}
}
function correctdate($dateinput)
{
	$arr = explode("-",$dateinput);
	$marr = array_reverse($arr);
	$str = implode($marr,"-");
	return $str;
}
?> 
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>National Bank - Stationery Order</title>
<link href="css/ezone.css" rel="stylesheet" type="text/css" />
<link type="text/css" rel="stylesheet" href="css/lightbox-form.css">
<script src="include/lightbox-form.js" type="text/javascript"></script>
<script type='text/javascript' src='include/jquery.js?ver=1.3.2'></script>

	<script type=text/javascript>
    setTimeout('window.location=window.location' ,10000);
    </script>
    
</head>
<?php

        include ("include/adLDAP.php");
		$adldap = new adLDAP();
		session_start();
    	$username =	$_SESSION["username"];
	    $password =	$_SESSION["password"];
		//authenticate the user		
		if ($adldap -> authenticate($username,$password)){			

/**/
				$userinfo = $adldap->user()->info($username);	
				for($loopcount = 0; $loopcount < $userinfo[count]; $loopcount++)
				{
					$getadID=$userinfo[$loopcount][samaccountname][0];	
					$getadfullname=$userinfo[$loopcount][displayname][0];					
					$getadmail=$userinfo[$loopcount][mail][0];					
					$getaddepartment=$userinfo[$loopcount][department][0];	
					$getadtitle=$userinfo[$loopcount][title][0];
					$getadext=$userinfo[$loopcount][ipphone][0];
					$getaddescription=$userinfo[$loopcount][description][0];					
					$getadcompany=$userinfo[$loopcount][company][0];	
					$getadmobile=$userinfo[$loopcount][mobile][0];		
					$getmemberof=$userinfo[$loopcount][mmemberof][0];							
				}
				
				$_SESSION["fullname"] = $getadfullname;	
				$_SESSION["department"] = $getaddepartment;	
				$_SESSION["login"] = $getadID;
				$_SESSION["logemail"] = $getadmail;
/**/				
		$memberof = $adldap->user()->groups($username,false);
		$_SESSION["memberof"] = $memberof;
			

		} else {		
			echo "user name and password invalid";			
			$redir="Location: http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/login.php";
			header($redir);
			exit;				
		}	

?>
<!-- -->
<?php 
function convert_datetime($str) {
	list($date, $time) = explode(' ', $str);
	list($year, $month, $day) = explode('-', $date);
	list($hour, $minute, $second) = explode(':', $time);
	$timestamp = mktime($hour, $minute, $second, $month, $day, $year);
	return $timestamp;}
?> 
<?php
function VerifyForm(&$values, &$errors)
{
	// Do all necessary form verification
    /* TODO!!! all QTY fields must have numberic value only*/
	return (count($errors) == 0);
}

function DisplayForm($values, $errors)
{
/* start web page view */
?>	
<body onLoad="show_clock()">

<div id="containerindex">



<!-- start query sql-->
<?php
include("include/function.php");
?>   
<?php
// config call
require("include/config.inc.php");

// database class
require("include/Database.class.php");

// create the $db ojbect
$db = new Database($config['server'], $config['user'], $config['pass'], $config['database'], $config['tablePrefix']);

// connect to the server
$db->connect();
?>
<?php

/*start pending display*/
$sqlpending = 'select (logorders.ID) as ID, `DATE`, (logsm.NAME) as SM, ACTIVE, (logstatus.NAME) as STATUS from logorders inner join logstatus on logstatus.ID = logorders.STATUS inner join logsm on logsm.ID = logorders.SM where logorders.USER = "'.$_SESSION["fullname"].'" and logorders.ACTIVE = 1';
$resp = $db->query($sqlpending) or die('error');
$numRowsp = mysql_num_rows($resp);
/*end pending display*/

/*start main display*/
$sql = "select logitems.ID, logitems.NAME, count(logsubmit.ITEMID) as orderID, sum(IF(logorders.STATUS = 5, logsubmit.QTY,0)) as QTY, sum(logsubmit.QTY) as QTYDONE, 
sum(logsubmit.QTYSENT) as QTYSENT, 
sum(logsubmit.REMITEMS) as REMITEMS, logorders.STATUS from logitems 
left outer join logsubmit on logitems.ID = logsubmit.ITEMID
left outer join logorders on logsubmit.ORDERID = logorders.ID
group by logitems.ID";
$res = $db->query($sql) or die('error');
$numRows = mysql_num_rows($res);
/*end main display*/

/*start authorisation display*/
$sqlauth = 'select logorders.ID, `DATE`, USER, ACTIVE, DEPT from logorders inner join logsm on logorders.SM = logsm.ID where STATUS = 3 and logsm.EMAIL = "'.$_SESSION["logemail"].'"';
$resauth = $db->query($sqlauth) or die('error');
$numAuth = mysql_num_rows($resauth);
/*end authorisation display*/

/*start order display*/
$sqlorder = "select * from logitems";
$resorder = $db->query($sqlorder) or die('error');
$totalitems = mysql_num_rows($resorder);
/*end order display*/

/*sm list */
$sqlsm = "SELECT * from logsm order by ID desc";
$ressm = $db->query($sqlsm);

while ($rowsm = $db->fetch_array($ressm)) {
	$smlist[]=array('Value'=> $rowsm[ID], 'Text' => $rowsm[NAME]);
}
/*end sm list*/

/*start logistics*/
$sqlpr = 'select logorders.ID, `DATE`, (logsm.NAME) as SM, ACTIVE, USER, DEPT from logorders inner join logsm on logsm.ID = logorders.SM where logorders.ACTIVE = 1 and logorders.STATUS = 5';
$respr = $db->query($sqlpr) or die('error');
$numRowspr = mysql_num_rows($respr);
/*end logistics*/

/*start logistics - john a*/
$sqlclose = 'select logorders.ID, `DATE`, (logsm.NAME) as SM, ACTIVE, USER, DEPT from logorders inner join logsm on logsm.ID = logorders.SM where logorders.ACTIVE = 1 and logorders.STATUS = 1';
$resclose = $db->query($sqlclose) or die('error');
$numRowsclose = mysql_num_rows($resclose);
/*end logistics*/

$db->close();
?>
<div class="header">
  <h1>Welcome to the NEW Stationery Order System</h1></div>	
<div>
<div id="navcrumbs">        
<?php
session_start();
include("include/Breadcrumb.php");
$trail = new Breadcrumb();
$trail->add('home', $_SERVER['PHP_SELF'], 0);

//Now output the navigation.
$trail->output();
?>
</div>

<div class="logsession">
    <?php
    session_start();
    if (isset($_SESSION["username"])) {
        print "Logged in as: ".$_SESSION["fullname"]." | <a href='login.php?logout=yes'>Logout?<a> | <a href='admin.php'>admin?</a>";	
		}		
    else {
		print "You must login to access this page"; 				
    }
    ?>	
</div>
</div>
<!-- -->
<div class="leftform">
<br/><br/>

<!-- -->
<?php
/* start group access filter define */
		$lga = $_SESSION["memberof"];
		/*
		print_r($lga);
		echo "<br/>";
		*/

		foreach ( $lga as $lgakey => $laccess ) {
			if ( preg_match( '/^Logistics/', $laccess ) ) {
				$logisticcount = 1;
			}
		} 	
		/*
 	    echo $logisticcount;
		*/
?>
<!-- -->
<hr/>
<h3>Pending Orders by You</h3>

<?php if ($numRowsp != ""){ ?>
<table width="100%" border="1" align="center" cellpadding="2" cellspacing="0">
<tr>
  <td><b>DATE</b></td><td>Supervisor</td><td>STATUS</td><td>Action</td>
</tr>

	<?php while ($rowp = $db->fetch_array($resp)) { ?>    
    <tr bgcolor="#BCE6F6">
    <td><?php echo correctdate($rowp[DATE]); ?></td>
    <td><?php echo $rowp[SM]; ?></td>
    <td><?php echo $rowp[STATUS]; ?></td>    
    <td><a href="#">send a followup email?</a><br/><a href="pdf.php?oid=<?php echo $rowp[ID]; ?>" target="_blank">view order?</a></td>
    </tr>    
    <?php } ?>
</table>
<?php }else{ ?>
<?php echo "you have no pending orders at the moment"; ?>
<?php } ?>
<br/><br/>
<hr/>
<h3>Pending Authorisation [Supervisor / Manager Access only]</h3>
<?php if ($numAuth != ""){ ?>
<table width="100%" border="1" align="center" cellpadding="2" cellspacing="0">
<tr>
  <td><b>DATE</b></td><td>Officer</td><td>Action</td>
</tr>

	<?php while ($rowauth = $db->fetch_array($resauth)) { ?>    
    <tr bgcolor="#BCE6F6">
    <td><?php echo correctdate($rowauth[DATE]); ?></td>
    <td><?php echo $rowauth[USER]; ?></td>    
    <td><a href="pdf.php?oid=<?php echo $rowauth[ID]; ?>" target="_blank">view order</a> | <a href="approve.php?oid=<?php echo $rowauth[ID]; ?>" >approve</a> 
    | <a href="decline.php?oid=<?php echo $rowauth[ID]; ?>" >decline</a></td>
    </tr>    
    <?php } ?>
</table>
<?php }else{ ?>
<?php echo "you have no pending Authorisation at the moment or you are not registered as a supervisor / manager."; ?>
<?php } ?>
<br/>
<?php 
/*start logistics access*/
if ($logisticcount != NULL){
?>
<hr/> 
<h3>Pending Orders (Authorised) [Logisitics Access only]</h3>

<?php if ($numRowspr != ""){ ?>
<table width="100%" border="1" align="center" cellpadding="2" cellspacing="0">
<tr>
  <td><b>DATE</b></td><td>Officer</td><td>Supervisor</td><td>Dept</td><td>Action</td>
</tr>

	<?php while ($rowpr = $db->fetch_array($respr)) { ?>    
    <tr bgcolor="#BCE6F6">
    <td><?php echo correctdate($rowpr[DATE]); ?></td>
    <td><?php echo $rowpr[USER]; ?></td>
    <td><?php echo $rowpr[SM]; ?></td>    
    <td><?php echo $rowpr[DEPT]; ?></td>    
    <td><a href="pdf.php?oid=<?php echo $rowpr[ID]; ?>" target="_blank">view order?</a> | <a href="process.php?oid=<?php echo $rowpr[ID]; ?>">process?</a></td>
    </tr>    
    <?php } ?>
</table>
<?php }else{ ?>
<?php echo "you have no pending orders at the moment"; ?>
<?php } ?>
<br/><br/>
<hr/>

<h3>Processed Orders [Logisitics Access only] - John A</h3>
<?php if ($numRowsclose != ""){ ?>
<table width="100%" border="1" align="center" cellpadding="2" cellspacing="0">
<tr>
  <td><b>DATE</b></td><td>Officer</td><td>Supervisor</td><td>Dept</td><td>Action</td>
</tr>

	<?php while ($rowclose = $db->fetch_array($resclose)) { ?>    
    <tr bgcolor="#BCE6F6">
    <td><?php echo correctdate($rowclose[DATE]); ?></td>
    <td><?php echo $rowclose[USER]; ?></td>
    <td><?php echo $rowclose[SM]; ?></td>    
    <td><?php echo $rowclose[DEPT]; ?></td>    
    <td><a href="pdf.php?oid=<?php echo $rowclose[ID]; ?>" target="_blank">view order?</a> | <a href="updateprocess.php?oid=<?php echo $rowclose[ID]; ?>">edit?</a> | 
    <a href="close.php?oid=<?php echo $rowclose[ID]; ?>">close order</a></td>
    </tr>    
    <?php } ?>
</table>
<?php }else{ ?>
<?php echo "you have no pending orders at the moment"; ?>
<?php } ?>
<br/><br/>
<hr/> 
<h3>Stationery Orders [Logistics access only]</h3>
<span style="color:red"><b><?php echo $errors['frmbranchid'] ?></b></span>	
<br/>
<!-- -->
<?php
/* declare color*/
$color="1";
?>
<table width="100%" border="1" align="center" cellpadding="2" cellspacing="0">
<tr>
  <td align="center"><b>Item - Description</b></td><td align="center">TOTAL QTY<br/>Ordered<br/><span style="color:red"><b>on process</b></span></td>
  <td align="center">TOTAL QTY<br/>Ordered<br/><span style="color:green"><b>processed</b></span></td><td align="center">Qty Sent <br/>from Head<br/> Office</td><td align="center">Remaining<br/>Items</td>
</tr>

<?php
while ($row1 = $db->fetch_array($res)) {
?>
<?php
if($color==1){
?>
<tr bgcolor="#BCE6F6">
<td><?php echo $row1[NAME]; ?></td>
<td align="center"><?php echo $row1[QTY]; ?></td>
<td align="center"><?php echo $row1[QTYDONE]; ?></td>
<td align="center"><?php echo $row1[QTYSENT]; ?></td>
<td align="center"><?php echo $row1[REMITEMS]; ?></td>
</tr>
<?php
// Set $color==2, for switching to other color 
$color="2";
}
// When $color not equal 1, use this table row color 
else {
?>
<tr bgcolor="#C6FF00">
<td><?php echo $row1[NAME]; ?></td>
<td align="center"><?php echo $row1[QTY]; ?></td>
<td align="center"><?php echo $row1[QTYDONE]; ?></td>
<td align="center"><?php echo $row1[QTYSENT]; ?></td>
<td align="center"><?php echo $row1[REMITEMS]; ?></td>
</tr>
<?php
// Set $color back to 1 
$color="1";
}
?>

<?php } ?>

</table>
<!-- -->

<?php 
/*end logistics access*/
}
?>


</div>
<!-- add to css file below-->
<div class="rightstats" >
<h3>Date: <?php echo date("F j, Y");?></h3>
<script language="javascript" src="include/liveclock.js"></script>
<br/><br/>

<!-- -->
    <div id="shadowing"></div>
    <div id="box">
        <span id="boxtitle">Submit a Balance Time</span>  
        
<!-- -->
<b>Please enter your order details below:</b>
<br/>
<b>To SUBMIT please scroll all the way down.</b>
<br/>
<b>Logistics team will process your order shortly. <br/>You will receive an email once your order has been processed.</b>
<br/><br/>
<form action="<?php echo $_SERVER['PHP_SELF'] ?>" method="POST">
<input name="frmitemstotal" type="hidden" value="<?php echo $totalitems; ?>" />
Date: <b><?php echo date("F j, Y");?></b>
<br/>
Department/Branch: <b><?php echo $_SESSION["department"]; ?></b><input name="frmDEPT" type="hidden" size="30" value="<?php echo $_SESSION["department"]; ?>"/>
<br/>
Ordering Officer: <b><?php echo $_SESSION["fullname"]; ?></b><input name="frmUSER" type="hidden" size="50" value="<?php echo $_SESSION["fullname"]; ?>"/>
<input name="frmUSERLOGIN" type="hidden" size="20" value="<?php echo $_SESSION["login"]; ?>"/>
<br/>
Supervisor / Manager: <select name="frmSM"><?php echo outputOptionList($smlist, $values['frmSM']); ?></select>                      
<br/>
<hr/>

<br/>
<!-- -->
<?php
/* declare color*/
$color="1";
?>
<table width="100%" border="1" align="center" cellpadding="2" cellspacing="0">
<tr>
  <td>select</td><td><b>Item - Description</b></td><td>QTY<br/>to Order</td><td>Remaining<br/>Items</td>
</tr>

<?php
while ($row2 = $db->fetch_array($resorder)) {
?>
<?php
if($color==1){
?>
<tr bgcolor="#BCE6F6">
<td>
<input name="frmROWtick<?php echo $row2[ID]; ?>" type="checkbox"/>
</td>
<td><?php echo $row2[NAME]; ?></td>
<td><input name="frmQTYno<?php echo $row2[ID]; ?>" type="text" size="5" value="<?php echo htmlentities($values['frmQTY.$row2[ID]']) ?>"/></td>
<td><input name="frmREMno<?php echo $row2[ID]; ?>" type="text" size="5" value="<?php echo htmlentities($values['frmREM.$row2[ID]']) ?>"/></td>

</tr>
<?php
// Set $color==2, for switching to other color 
$color="2";
}
// When $color not equal 1, use this table row color 
else {
?>
<tr bgcolor="#C6FF00">
<td>
<input name="frmROWtick<?php echo $row2[ID]; ?>" type="checkbox"/>
</td>
<td><?php echo $row2[NAME]; ?></td>
<td><input name="frmQTYno<?php echo $row2[ID]; ?>" type="text" size="5" value="<?php echo htmlentities($values['frmQTY.$row2[ID]']) ?>"/></td>
<td><input name="frmREMno<?php echo $row2[ID]; ?>" type="text" size="5" value="<?php echo htmlentities($values['frmREM.$row2[ID]']) ?>"/></td>

</tr>
<?php
// Set $color back to 1 
$color="1";
}
?>

<?php } ?>

</table>
<!-- -->
<br/>

<hr/>
<input type="submit" value="submit an order" /> | <input type="button" name="cancel" value="Cancel" onClick="closebox()">
</form>        
<!-- -->
        
    </div>
<a href="#" onClick="openbox('Stationery Order Form', 0)">submit an Order</a>
<!-- -->
<hr/>
<!-- -->
<?php 
/*start logistics access*/
if ($logisticcount != NULL){
?>
<h3>Logistics access only</h3>
Process Orders
<br/>
<a href="insertitem.php">add a new stationery</a>
<br/>
<!-- <a href="export.php">export list</a> -->
<a href="insertsm.php">add a new supervisor / manager</a>
<br/><br/>
<?php
/*end logisitics access*/
}
?>


</div>
<!-- -->

<div style="clear:both"></div>

<div class="footer">
    <div class="footer_right">Stationery Orders</div>
    </div>
</div>


</div>
</body>
<?php
}
	function ProcessForm($values)
	{			

		include 'include/config1.php';
		mysql_connect("$host", "$username", "$password")or die("cannot connect"); 
		mysql_select_db("$db_name")or die("cannot select DB");
		$sql = "SELECT EMAIL from logsm WHERE ID = ".$values['frmSM'];
		$result = mysql_query($sql);
		$rowemail = mysql_fetch_array($result);
		$grp_email = $rowemail["EMAIL"];			

		$subjectcontent = 'A stationery order was lodged to the Logistics Order system. A login to http://redmine/logistics/ is required to authorise the order.<br/>';
		$subject = "Stationery Order - Approval required";	 
		
		$to = $grp_email;
		$body=$subjectcontent;
		
		// To send HTML mail, the Content-type header must be set
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		
		//additional headers
		$headers .= "From: stationery@mail.com"."\r\n";
		$headers .= "cc: ".$_SESSION["logemail"]."\r\n";

	  	 if (mail($to, $subject, $body, $headers)) {

			$redir="Location: http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF'])."/index.php";					
			header($redir);
			exit;

		 } else {
		    echo("<p> Order successfully logged however email delivery failed... please advice your friendly I.T. department</p>");
		 }
		

		

	}

if ($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$formValues = $_POST;
	$formErrors = array();	 

	if (!VerifyForm($formValues, $formErrors)){
		DisplayForm($formValues, $formErrors);}
	else{		

		// config call
		require("include/config.inc.php");
		// database class
		require("include/Database.class.php");
		// create the $db ojbect
		$db = new Database($config['server'], $config['user'], $config['pass'], $config['database'], $config['tablePrefix']);
		// connect to the server
		$db->connect();	

		$data['DATE'] = date("Y-m-d");		
		$data['USER'] = $_SESSION["fullname"];
		$data['USERLOGIN'] = $_SESSION["login"];		
		$data['SM'] = $formValues['frmSM'];		
		$data['ACTIVE'] = '1';
		/*pending approval*/
		$data['STATUS'] = '3';		
		$data['DEPT'] = $formValues['frmDEPT'];		
		$data['TIMESTAMP'] = convert_datetime(date('Y-m-d H:i:s'));					

		$db->query_insert("logorders", $data);				

		$frmorderid = mysql_insert_id();

/* filter orders only */
		$orderkeys = array_keys( $formValues );
		foreach ( $orderkeys as $keyorders ) {
			if ( preg_match( '/^frmROWtick/', $keyorders ) ) {
			$itemsordered[] = $keyorders;		
			}
		}

		foreach ( $itemsordered as $ordersubmit ) {   
				  $data2['ORDERID'] = $frmorderid;			  			  
				  $data2['ITEMID'] = substr($ordersubmit,10);				  
       		      $data2['DATE'] = date("Y-m-d");					  
            	  $data2['TIMESTAMP'] = convert_datetime(date('Y-m-d H:i:s'));								
		          $data2['ACTIVE'] = '1';					  
				  $data2['QTY'] = $formValues['frmQTYno'.substr($ordersubmit,10)];	
				  $data2['REMITEMS'] = $formValues['frmREMno'.substr($ordersubmit,10)];					  				  
				  
		  		$db->query_insert("logsubmit", $data2);							  			
			}  			 			  			
		
		$db->close();

		ProcessForm($formValues);		
		
		}
	
	 /*add to logactivity table*/

}
else
	DisplayForm(null, null);
?>
</html>
