<?php
include ("/usr/local/cwpsrv/htdocs/resources/admin/include/ipv6.php");
//   IPv6 addresses Assigned to Machine
// ip addr | grep -oP "inet6\K(.*)" | cut -d "/" -f1
/*Recogemos valores por post de eliminar*/
if(isset($_POST['delete']))
 
{
 
$idipv6 = $_POST['idipv6'];
$ipv6 = $_POST['ipv6'];
$rango = $_POST['ipv6range'];
	
$sqlEliminar = mysqli_query($mysql_conn,"DELETE FROM ipv6 WHERE idipv6='".$idipv6."'") or die(mysqli_error());
 
echo "<b> Se ha eliminado la IP correctamente.</b></br>";
 
}

/*Recogemos los valores para insertar en la bd*/

if(isset($_POST['addipv6'])){	
generarprimeraipv6delrango($mysql_conn, $_POST['ipv6'], $_POST['ipv6range']);
}

/**Aquí se mostrará la página para agregar rangos de IPv6 a CWP**/

/**Consultamos la base de datos para saber si ya tiene alguna IPv6 añadida**/
echo "LISTADO DE RANGOS IPV6 A&Ntilde;ADIDOS AL SERVIDOR"; 
$resp=mysqli_query($mysql_conn,"Select * FROM ipv6");
echo '<table align="left" border="0" width="30%" class="table table-bordered table-hover dataTable no-footer" id="userTable" role="grid" aria-describedby="userTable_info" style="width: 30%;">
<thead>
	<tr role="row">
		<th class="sorting_disabled" tabindex="0" rowspan="1" colspan="1" >IPv6 / Rango</th>
		<th class="sorting_disabled" tabindex="0" rowspan="1" colspan="1" style="width: 100px;" >Acciones</th>
	</tr>
</thead>';
while ($row=@mysqli_fetch_array($resp)){
    echo '<tr>
			<td><b>'.$row['ipv6'].'/'.$row['ipv6range'].'</b></td>
			<td><form method="post" action="index.php?module=ipv6_list">
			<input type="hidden" name="delete" value="delete">
			<input type="hidden" name="idipv6" value="'.$row["idipv6"].'" />
			<input type="hidden" name="ipv6" value="'.$row["ipv6"].'" />
			<input type="hidden" name="ipv6range" value="'.$row["ipv6range"].'" />
			<input class="btn btn-danger" type="submit" value="Eliminar"></form></td>
		</tr>';
}
echo "</table>";

/*Añadimos formulario para añadir nuevos rangos de IPv6*/

?>
<div>A&Ntilde;ADIR UN NUEVO RANGO IPV6 AL SERVIDOR.</div>
<table align="left" border="0" width="30%" class="table table-bordered table-hover dataTable no-footer" id="userTable" role="grid" aria-describedby="userTable_info" style="width: 30%;">
	<tr>
<form method="post" action="index.php?module=ipv6_list">
<input type="hidden" name="addipv6" value="addipv6">
<td>IPV6: <input type="text" name="ipv6"></td>
<td>Rango: /
<select name="ipv6range">
	<option value="112">112</option>
	</select></td>
<td><input class="btn btn-success" type="submit" value="A&ntilde;adir IPv6"></td>	
</form>
	</tr>
</table>
