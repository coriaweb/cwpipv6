<?php

if(!isset($include_path)){echo "invalid access"; exit(); }
if(!isset($_GET['lang'])){
    if(!file_exists('/usr/local/cwpsrv/htdocs/resources/admin/modules/language/en/ipv6.ini')){
        if(!file_exists('/usr/local/cwpsrv/htdocs/resources/admin/modules/language/en/')){
            shell_exec('mkdir -p /usr/local/cwpsrv/htdocs/resources/admin/modules/language/en/');
        }
        shell_exec('touch /usr/local/cwpsrv/htdocs/resources/admin/modules/language/en/ipv6.ini');
    }
    $lang=parse_ini_file('/usr/local/cwpsrv/htdocs/resources/admin/modules/language/en/ipv6.ini');
}else{
    $lang=parse_ini_file('/usr/local/cwpsrv/htdocs/resources/admin/modules/language/'.$_GET['lang'].'/ipv6.ini');
}

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
$usernameipv6 = $_POST['usernameipv6'];

//****Generar IPv6****//
generaripv6($mysql_conn, $dominiointroducido, $ipintroducida, $usernameipv6);
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

echo $lang['TITLE']."<br>";
echo $lang['SUB1']."<br>";
echo $lang['SUB2']."<br>";
echo "<h3>".$lang['SUB3']."</h3>";	
echo "<form method='post' action='index.php?module=ipv6'><input type='hidden' name='rebuildipv6' value='rebuildipv6'><div>Si necesitas reconstruir las IPv6 pincha aqui: <input class='btn btn-info btn-xs mr5 mb10 deletezone' type='submit' value='Reconstruir IPv6'></form></div>";


echo '
<div class="table-responsive" style="overflow: hidden; width: 50%; height: auto;">
<table align="left" border="0" width="50%" class="table table-bordered table-hover dataTable no-footer" id="userTable" role="grid" aria-describedby="userTable_info" style="width: 50%;">
<thead>
	<tr role="row">
		<th class="sorting_disabled" tabindex="0" rowspan="1" colspan="1" >'.$lang["domain"].'</th>
		<th class="sorting_disabled" tabindex="0" rowspan="1" colspan="1" >IPv6</th>
		<th class="sorting_disabled" tabindex="0" rowspan="1" colspan="1" style="width: 100px;" >'.$lang["actions"].'</th>
	</tr>
</thead>';

$resp=mysqli_query($mysql_conn,"Select username, domain FROM user ORDER BY domain ASC");
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
		<td>IPv6 {$lang['assign']}: <a target='_blank' href='http://[".$cualipv6['ipv6']."]'><b>".$cualipv6['ipv6']."</b></a></td>
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
		echo '<input type="hidden" name="domain" value="'.$row["domain"].'" />
		<input type="hidden" name="usernameipv6" value="'.$row["username"].'" /></form>';
	}
	echo '</tr>';
	//Si tiene dominios adicionales los añadimos para poder ponerles ipv6
	$consultasihaydominiosadicionales= mysqli_query($mysql_conn,"SELECT * FROM domains WHERE user='".$row["username"]."' ORDER BY domain ASC");
	if(mysqli_num_rows($consultasihaydominiosadicionales)!=0) 
	{ 
		while ($rowdominiosadicionales=mysqli_fetch_array($consultasihaydominiosadicionales)){
			echo '<tr>
				<td><b>'.$lang["addit"].': &#8593;</b> '.$rowdominiosadicionales["domain"].'</td>';
			
				$consultasihayipv6adicional= mysqli_query($mysql_conn,"SELECT * FROM ipv6_domain WHERE domain='".$rowdominiosadicionales["domain"]."'");
				if(mysqli_num_rows($consultasihayipv6adicional)!=0) 
				{
					//Consultamos y mostramos la ipv6 asignada
					$cualipv6adicional=mysqli_fetch_array($consultasihayipv6adicional);
					echo "
					<form method='post' action='index.php?module=ipv6'>
					<input type='hidden' name='delete' value='delete'>
					<input type='hidden' name='domain' value='".$cualipv6adicional["domain"]."' />
					<input type='hidden' name='ipv6' value='".$cualipv6adicional["ipv6"]."' />
					<td>IPv6 {$lang['assign']}: <a target='_blank' href='http://[".$cualipv6adicional['ipv6']."]'><b>".$cualipv6adicional['ipv6']."</b></a></td>
					<td><input class='btn btn-danger btn-block' btn-xs mr5 mb10 deletezone type='submit' value='Eliminar'></td>
					</form>"; 
				}else{
					echo '<form method="post" action="index.php?module=ipv6">
					<input type="hidden" name="addipv6" value="addipv6">';
					echo '<td><select name="ipv6">';
						//Consultamos la tabla de ipv6 para ver si hay alguna en el servidor
						$respipv6adicional=mysqli_query($mysql_conn,"Select * FROM ipv6");
						while ($rowipv6adicional=mysqli_fetch_array($respipv6adicional)){
						echo '<option value="'.$rowipv6adicional['ipv6'].'/'.$rowipv6adicional['ipv6range'].'">'.$rowipv6adicional['ipv6'].'/'.$rowipv6adicional['ipv6range'].'</option>';
						}
					echo'</select></td>
					<td><input class="btn btn-success btn-block" type="submit" value="Asignar IPv6"></td>';
					echo '<input type="hidden" name="domain" value="'.$rowdominiosadicionales["domain"].'" />
					<input type="hidden" name="usernameipv6" value="'.$row["username"].'" /></form>';
				}
			echo '</tr>';
		}
	}	
}
echo "</table>
</div>";
	
};//Cerramos la consulta del primer uso y crear la bd
?>
