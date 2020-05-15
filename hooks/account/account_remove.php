<?php 
function account_remove($array){
	include('/usr/local/cwpsrv/htdocs/resources/admin/include/db_conn.php');
	
	$mysql_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

	//Eliminamos el registro de la base de datos
	//Delete IP's from database
	$sqlEliminar = mysqli_query($mysql_conn,"DELETE FROM ipv6_domain WHERE domain='".$array['domain']."'") or die(mysqli_error());
}
?>
