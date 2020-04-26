<?php

include ("/usr/local/cwpsrv/htdocs/resources/admin/include/ipv6.php");

//Comprobamos si ya existen las tablas necesarias, si no existen se instala el modulo.
if( mysqli_num_rows(mysqli_query($mysql_conn,"SHOW TABLES LIKE 'ipv6_domain' ")) == 0 ){
	creartablasbd ($mysql_conn);
	//Si es el primer uso del módulo, añadimos el hook para cuando se elimina una cuenta
	creardirectorioyarchivo();
}else{
/*Recogemos los valores para insertar en la bd*/
if(isset($_POST['addipv6'])){
$dominiointroducido = $_POST['domain'];
$ipintroducida = $_POST['ipv6'];

//****Generar IPv6****//
generaripv6($mysql_conn, $dominiointroducido, $ipintroducida);
}

if(isset($_POST['delete'])){
//Recogemos IPv6 y el dominio	
$ipintroducida = $_POST['ipv6'];
$dominiointroducido = $_POST['domain'];
	
	//Eliminar ipv6 de SSH
	shell_exec("/sbin/ip -6 addr del ".$ipintroducida." dev eth0");
	
	//Eliminar de archivo de configuracion sin SSL
	deleteconf($dominiointroducido, $ipintroducida, "no");
	//Eliminar de archivo de configuracion con SSL
	deleteconf($dominiointroducido, $ipintroducida, "ssl");
	
	//Eliminar de la base de datos
	$sqlEliminar = mysqli_query($mysql_conn,"DELETE FROM ipv6_domain WHERE ipv6='".$ipintroducida."'") or die(mysqli_error());
	
	//Eliminamos el registro DNS del archivo
	eliminardns($dominiointroducido, $ipintroducida);
	
	//Reiniciamos NGINX
	shell_exec("service nginx restart");
	
}
	
if(isset($_POST['rebuildipv6'])){
	//Reconstruimos todas las IPv6 existentes y rehacemos sus configuraciones.
	rebuildipv6($mysql_conn);
}

echo "Modulo para administrar IPV6<br>";
echo "Desde aqui, podemos asignar IPV6 a los dominios del servidor.<br>";
echo "Recuerda que para poder asignar IPV6, el servidor ya debe de tener una asignada, puedes consultar con tu proveedor.<br>";
echo "<h3>Listado de dominios</h3>";	
echo "<form method='post' action='index.php?module=ipv6'><input type='hidden' name='rebuildipv6' value='rebuildipv6'><div>Si necesitas reconstruir las IPv6 pincha aqui: <input class='btn btn-info btn-xs mr5 mb10 deletezone' type='submit' value='Reconstruir IPv6'></form></div>";


echo '
<div class="table-responsive" style="overflow: hidden; width: 50%; height: auto;">
<table align="left" border="0" width="50%" class="table table-bordered table-hover dataTable no-footer" id="userTable" role="grid" aria-describedby="userTable_info" style="width: 50%;">
<thead>
	<tr role="row">
		<th class="sorting_disabled" tabindex="0" rowspan="1" colspan="1" >Dominio</th>
		<th class="sorting_disabled" tabindex="0" rowspan="1" colspan="1" >IPv6</th>
		<th class="sorting_disabled" tabindex="0" rowspan="1" colspan="1" style="width: 100px;" >Acciones</th>
	</tr>
</thead>';

$resp=mysqli_query($mysql_conn,"Select domain FROM user");
while ($row=mysqli_fetch_assoc($resp)){	
	echo '<tr>
			<td>'.$row['domain'].'</td>';
	
	//Comprobamos si ya tiene alguna IPv6 asignada el dominio
	$consultasihayipv6= mysqli_query($mysql_conn,"SELECT * FROM ipv6_domain WHERE domain='".$row["domain"]."'");  
	
	if(mysqli_num_rows($consultasihayipv6)!=0) 
	{     
		//Consultamos y mostramos la ipv6 asignada
		$cualipv6=mysqli_fetch_array($consultasihayipv6);
		echo "
		<form method='post' action='index.php?module=ipv6'>
		<input type='hidden' name='delete' value='delete'>
		<input type='hidden' name='domain' value='".$cualipv6["domain"]."' />
		<input type='hidden' name='ipv6' value='".$cualipv6["ipv6"]."' />
		<td>IPv6 asignada: <a target='_blank' href='http://[".$cualipv6['ipv6']."]'><b>".$cualipv6['ipv6']."</b></a></td>
		<td><input class='btn btn-danger btn-block' btn-xs mr5 mb10 deletezone type='submit' value='Eliminar'></td>
		</form>"; 
	}else{
		echo '<form method="post" action="index.php?module=ipv6">
<input type="hidden" name="addipv6" value="addipv6">';
		
		echo '<td><select name="ipv6">';
		
			//Consultamos la tabla de ipv6 para ver si hay alguna en el servidor
			$respipv6=mysqli_query($mysql_conn,"Select * FROM ipv6");
			while ($rowipv6=mysqli_fetch_array($respipv6)){
			echo '<option value="'.$rowipv6['ipv6'].'/'.$rowipv6['ipv6range'].'">'.$rowipv6['ipv6'].'/'.$rowipv6['ipv6range'].'</option>';
			}
		echo'</select></td>
		<td><input class="btn btn-success btn-block" type="submit" value="Asignar IPv6"></td>';
		echo '<input type="hidden" name="domain" value="'.$row["domain"].'" /></form>';
	}
	echo '</tr>';
}
echo "</table>
</div>";
	
};//Cerramos la consulta del primer uso y crear la bd
?>