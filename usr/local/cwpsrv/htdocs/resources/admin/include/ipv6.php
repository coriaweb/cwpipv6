<?php
$ipv6_version = "1.1";

//***Inicio de la función para crear las tablas necesarias con el primero uso del modulo****////
function creartablasbd($conn) {
// Creamos la conexión con la base de datos
// Aquí se revisa la conexión con MySQL
if (!$conn) {
    die("la conexión ha fallado: " . mysqli_connect_error());
}
// La variable que creara la tabla en la base de datos.
$mi_tabla1= "CREATE TABLE ipv6(
idipv6 INT(11) NOT NULL,
ipv6 VARCHAR(65) NOT NULL,
ipv6range INT(4) NOT NULL
)ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$mi_tabla2= "CREATE TABLE ipv6_domain(
iddomain INT(6) NOT NULL,
domain VARCHAR(250) NOT NULL,
ipv6 VARCHAR(60) NOT NULL
)ENGINE=MyISAM DEFAULT CHARSET=utf8;";
// Condicional PHP que creará la tabla
if (mysqli_query($conn, $mi_tabla1)) {
// Se ha creado bien la primera tabla, creamos la segunda.
	if (mysqli_query($conn, $mi_tabla2)) {
		echo "<b>Al ser la primera vez que inicia este modulo, se han creado las bases de datos necesarias para su funcionamiento. Ya se puede utilizar el modulo.</b> <a href='index.php?module=ipv6'>Acceder al modulo </a><br>";
	}else{
		echo "<b>El modulo no se ha instalado correctamente, error: BD2</b><br>";
	}
} else {
    // Mostramos mensaje si hubo algún error en el proceso de creación
    echo "<b>El modulo no se ha instalado correctamente, error: BD1</b><br>";
}
}
//***Fin de la función para crear las tablas necesarias con el primero uso del modulo****////

//***Inicio de la funcion para escribir la configuracion****////
function escribirconf($dominio, $ipv6final, $ssl){
	//Si $ssl es si, cambiamos la ruta del archivo
	if($ssl=="ssl"){
		$filename = ( '/etc/nginx/conf.d/vhosts/'.$dominio.'.ssl.conf' );
		$iprecibida = "listen [".$ipv6final."]:443  ssl http2;";
	}else{
		$filename = ( '/etc/nginx/conf.d/vhosts/'.$dominio.'.conf' );
		$iprecibida = "listen [".$ipv6final."]:80;";
		
	}
	if (!file_exists($filename)) {
		
	}else{
	$lines=[];
	$file = fopen($filename,"r");
	while(! feof($file)) {
		$lines[]=fgets($file);
	}
	fclose($file);

	$find=false;
	for ( $cont=0; $cont<count($lines); $cont++ ) {
		if ( strpos( $lines[$cont], "listen [") > 0 ) {
			$lines[$cont]="\t".$iprecibida."\n";
			$find=true;
		}
	}
	if ( !$find ) {
		array_splice( $lines, 2, 0, "\t".$iprecibida."\n" );
	}

	$file = fopen($filename,"w");
	for ( $cont=0; $cont<count($lines); $cont++ ) {
		//echo $lines[$cont]."<br>";
		fwrite($file, $lines[$cont]);
	}
	fclose($file);
	}
}
//***Fin de la funcion para eliminar la configuracion****////

//***Inicio de la funcion para eliminar la configuracion****////
function deleteconf($dominio, $ipv6final, $ssl){
	//Si $ssl es si, cambiamos la ruta del archivo
	if($ssl=="ssl"){
		$filename = ( '/etc/nginx/conf.d/vhosts/'.$dominio.'.ssl.conf' );
	}else{
		$filename = ( '/etc/nginx/conf.d/vhosts/'.$dominio.'.conf' );
	}
	//Si no existe el archivo no hacemos nada
	if (!file_exists($filename)) {
		
	}else{
	$lines=[];
	$file = fopen($filename,"r");
	while(! feof($file)) {
		$lines[]=fgets($file);
	}
	fclose($file);

	$find=false;
	for ( $cont=0; $cont<count($lines); $cont++ ) {
		if ( strpos( $lines[$cont], "listen [") > 0 ) {
			array_splice($lines, $cont, 1);
			$find=true;
		}
	}

	if ( $find==true ){
		$file = fopen($filename,"w");
		for ( $cont=0; $cont<count($lines); $cont++ ) {
			//echo $lines[$cont]."<br>";
			fwrite($file, $lines[$cont]);
		}
		fclose($file);
	}
	}
}
//***Fin de la funcion para eliminar la configuracion****////

//***Eliminar DNS****////
function eliminardns($dominio, $ipv6){
	$cadena_a_borrar = "@ 14400 IN AAAA ".$ipv6;
	$filenamedns = ('/var/named/'.$dominio.'.db');
	$texto = '';
	$cadena_a_borrar = "@ 14400 IN AAAA ".$ipv6;
	$nombre_archivo = $filenamedns;
	$lineas = file($nombre_archivo);
	foreach ($lineas as $linea) {
		preg_match('/20[0-9][0-9]{7}/',$linea,$match);
			if ($match) {
				$newserial = $match[0] + 1;
				$linea = preg_replace("/$match[0]/","$newserial", $linea);
			}
		if (!strstr($linea, $cadena_a_borrar)) {
			$texto .= $linea;
		}
	}
	$f = fopen($nombre_archivo, 'w'); 
	fwrite($f, $texto); 
	fclose($f);
	echo shell_exec("rndc reload $dominio");
}
//***Fin Eliminar DNS****////

//***Escribir DNS****////
function escribirdns($dominio, $ipv6){
        $cadena_a_agregar = '@ 14400 IN AAAA '.$ipv6;
        $filenamedns = ('/var/named/'.$dominio.'.db');
        $data = file_get_contents($filenamedns);
        $data .= "$cadena_a_agregar\n";
        preg_match('/20[0-9][0-9]{7}/',$data,$match);
        $newserial = $match[0] + 1;
        $data = preg_replace("/$match[0]/","$newserial", $data);
        file_put_contents($filenamedns,$data);
        echo shell_exec("rndc reload $dominio");
}
//***Fin Escribir DNS****////

//****Generar IPv6****//
function generaripv6($mysql_conn, $dominiointroducido, $ipintroducida){
	$a_Prefix = $ipintroducida;
	// Validate input superficially with a RegExp and split accordingly
    if(!preg_match('~^([0-9a-f:]+)[[:punct:]]([0-9]+)$~i', trim($a_Prefix), $v_Slices)){
		return false;
    }
    // Make sure we have a valid ipv6 address
    if(!filter_var($v_FirstAddress = $v_Slices[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
        return false;
    }
    // The /## end of the range
    $v_PrefixLength = intval($v_Slices[2]);
    if($v_PrefixLength > 128){
        return false; // kind'a stupid :)
    }
    $v_SuffixLength = 128 - $v_PrefixLength;

    // Convert the binary string to a hexadecimal string
    $v_FirstAddressBin = inet_pton($v_FirstAddress);
    $v_FirstAddressHex = bin2hex($v_FirstAddressBin);

    // Build the hexadecimal string of the network mask
    // (if the manually formed binary is too large, base_convert() chokes on it... so we split it up)
    $v_NetworkMaskHex = str_repeat('1', $v_PrefixLength) . str_repeat('0', $v_SuffixLength);
    $v_NetworkMaskHex_parts = str_split($v_NetworkMaskHex, 8);
    foreach($v_NetworkMaskHex_parts as &$v_NetworkMaskHex_part){
        $v_NetworkMaskHex_part = base_convert($v_NetworkMaskHex_part, 2, 16);
        $v_NetworkMaskHex_part = str_pad($v_NetworkMaskHex_part, 2, '0', STR_PAD_LEFT);
    }
    $v_NetworkMaskHex = implode(null, $v_NetworkMaskHex_parts);
    unset($v_NetworkMaskHex_part, $v_NetworkMaskHex_parts);
    $v_NetworkMaskBin = inet_pton(implode(':', str_split($v_NetworkMaskHex, 4)));

    // We have the network mask so we also apply it to First Address
    $v_FirstAddressBin &= $v_NetworkMaskBin;
    $v_FirstAddressHex = bin2hex($v_FirstAddressBin);

    // Convert the last address in hexadecimal
    $v_LastAddressBin = $v_FirstAddressBin | ~$v_NetworkMaskBin;
    $v_LastAddressHex =  bin2hex($v_LastAddressBin);

    // Return a neat object with information
    $v_Return = array(
        'Prefix'    => "{$v_FirstAddress}/{$v_PrefixLength}",
        'FirstHex'  => $v_FirstAddressHex,
        'LastHex'   => $v_LastAddressHex,
        'MaskHex'   => $v_NetworkMaskHex,
    );
    //return (object)$v_Return;
	
	$primeraipv6 = implode(':', str_split($v_FirstAddressHex, 4));
	$ultimaipv6 = implode(':', str_split($v_LastAddressHex, 4));

	$IpStart_v6_FromDb = $primeraipv6;
	$IpEnd_v6_FromDb = $ultimaipv6;

	$ip1 = $IpStart_v6_FromDb;
	$ip2 = $IpEnd_v6_FromDb;
	if ($ip1 === null || $ip2 === null) {
		die;
	}
	// length is 39 to account for 7 colons
	for ($i = 0; $i < 39 && $ip1[$i] === $ip2[$i]; $i++);

	$ipv6_prefix = substr($ip1, 0, $i);
	$ipv6_start = hexdec(substr($ip1, $i));
	$ipv6_end = hexdec(substr($ip2, $i));

	if (strlen($ipv6_prefix) < 26) {
		// adjust this to requirements to prevent too large ranges
		die;
	}
	for ($a = $ipv6_start; $a <= $ipv6_end; $a++) {
    $hex = dechex($a);
	$ipv6shell = $ipv6_prefix.$hex;
	
	$ipv6sinpuntos = bin2hex(inet_pton($ipv6shell));
	$ipv6extconpuntos = implode(':', str_split($ipv6sinpuntos, 4));
	
	//Consultamos si esta o no en la base de datos, si no esta se añade al dominio y se para la consulta
	$consultasihayipv6domain= mysqli_query($mysql_conn,"SELECT * FROM ipv6_domain WHERE ipv6='$ipv6extconpuntos'");  
	if(mysqli_num_rows($consultasihayipv6domain)!=0) 
	{
		//"Hay";
	}else{
		//"No hay";
		
		//Insertar en la base de datos, dominio + ipv6 asignada
		//Todo parece correcto procedemos con la inserccion de la ipv6
		$query = "INSERT INTO ipv6_domain (domain, ipv6) VALUES('".mysqli_real_escape_string($mysql_conn,$dominiointroducido)."','".mysqli_real_escape_string($mysql_conn,$ipv6extconpuntos)."')"; 
		$registro=mysqli_query($mysql_conn,$query) or die(mysqli_error());
		
		//Insertamos linea en archivo de configuracion Sn SSL
		escribirconf($dominiointroducido, $ipv6extconpuntos, "no");
		escribirconf($dominiointroducido, $ipv6extconpuntos, "ssl");
		
		//Insertamos comando en SSH.
		shell_exec("/sbin/ip -6 addr add ".$ipv6extconpuntos." dev eth0");
		
		//Agregamos registro DNS al archivo
		escribirdns($dominiointroducido, $ipv6extconpuntos);
		
		//Reiniciamos NGINX
		shell_exec("service nginx restart");

		break;
	}
	}
}
//****Fin Generar IPv6****//

//****Inicio reconstruir IPv6****//
function rebuildipv6 ($mysql_conn){
	// Consulta para pedir información de la tabla
$resultados = mysqli_query($mysql_conn, "SELECT * FROM ipv6_domain");

// Ejecuta el ciclo de acuerdo a la consulta SQL $resultados
while ($fila = mysqli_fetch_array($resultados)){
	//echo $fila["ipv6"]." - ".$fila["domain"]."<br>";
	escribirconf($fila["domain"], $fila["ipv6"], "no");
	escribirconf($fila["domain"], $fila["ipv6"], "ssl");
	
	//Insertamos comando en SSH.
		shell_exec("/sbin/ip -6 addr add ".$fila["ipv6"]." dev eth0");
}	
	//Reiniciamos NGINX
		shell_exec("service nginx restart");
	echo "<b>Reconstruido correctamente.</b></br>";
}
//****Fin reconstruir IPv6****//

//****Inicio crear directorio y archivo para el hook de eliminar la cuenta****//
function creardirectorioyarchivo(){
	
	$nombre_fichero = '/usr/local/cwpsrv/htdocs/resources/admin/hooks/account/account_remove.php';
	if (!file_exists($nombre_fichero)) {
		mkdir("/usr/local/cwpsrv/htdocs/resources/admin/hooks/account/", 0700, true);
		$miArchivo = fopen("/usr/local/cwpsrv/htdocs/resources/admin/hooks/account/account_remove.php", "w") or die("No se puede abrir/crear el archivo!");
 

	$php = "<?php 
function account_remove(\$array){
	include('/usr/local/cwpsrv/htdocs/resources/admin/include/db_conn.php');
	
	\$mysql_conn = new mysqli(\$db_host, \$db_user, \$db_pass, \$db_name);

	//eliminamos el registro de la base de datos
	\$sqlEliminar = mysqli_query(\$mysql_conn,\"DELETE FROM ipv6_domain WHERE domain='\".\$array['domain'].\"'\") or die(mysqli_error());
}
?>";

	fwrite($miArchivo, $php);
	fclose($miArchivo);
	}
}
//****Fin crear directorio y archivo para el hook de eliminar la cuenta****//

//******Inicio Codigo para generar la primera IPv6 del rango *******//
function generarprimeraipv6delrango ($mysql_conn,$ipv6, $rango){
	//Generamos la primera ipv6 del rango y la añadimos a la bd
$a_Prefix = $ipv6."/".$rango;
    // Validate input superficially with a RegExp and split accordingly
    if(!preg_match('~^([0-9a-f:]+)[[:punct:]]([0-9]+)$~i', trim($a_Prefix), $v_Slices)){
		return false;
    }
    // Make sure we have a valid ipv6 address
    if(!filter_var($v_FirstAddress = $v_Slices[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)){
        return false;
    }
    // The /## end of the range
    $v_PrefixLength = intval($v_Slices[2]);
    if($v_PrefixLength > 128){
        return false; // kind'a stupid :)
    }
    $v_SuffixLength = 128 - $v_PrefixLength;

    // Convert the binary string to a hexadecimal string
    $v_FirstAddressBin = inet_pton($v_FirstAddress);
    $v_FirstAddressHex = bin2hex($v_FirstAddressBin);

    // Build the hexadecimal string of the network mask
    // (if the manually formed binary is too large, base_convert() chokes on it... so we split it up)
    $v_NetworkMaskHex = str_repeat('1', $v_PrefixLength) . str_repeat('0', $v_SuffixLength);
    $v_NetworkMaskHex_parts = str_split($v_NetworkMaskHex, 8);
    foreach($v_NetworkMaskHex_parts as &$v_NetworkMaskHex_part){
        $v_NetworkMaskHex_part = base_convert($v_NetworkMaskHex_part, 2, 16);
        $v_NetworkMaskHex_part = str_pad($v_NetworkMaskHex_part, 2, '0', STR_PAD_LEFT);
    }
    $v_NetworkMaskHex = implode(null, $v_NetworkMaskHex_parts);
    unset($v_NetworkMaskHex_part, $v_NetworkMaskHex_parts);
    $v_NetworkMaskBin = inet_pton(implode(':', str_split($v_NetworkMaskHex, 4)));

    // We have the network mask so we also apply it to First Address
    $v_FirstAddressBin &= $v_NetworkMaskBin;
    $v_FirstAddressHex = bin2hex($v_FirstAddressBin);

    // Convert the last address in hexadecimal
    $v_LastAddressBin = $v_FirstAddressBin | ~$v_NetworkMaskBin;
    $v_LastAddressHex =  bin2hex($v_LastAddressBin);

    // Return a neat object with information
    $v_Return = array(
        'Prefix'    => "{$v_FirstAddress}/{$v_PrefixLength}",
        'FirstHex'  => $v_FirstAddressHex,
        'LastHex'   => $v_LastAddressHex,
        'MaskHex'   => $v_NetworkMaskHex,
    );
    //return (object)$v_Return;*/

//echo "v_FirstAddressHex: ".implode(':', str_split($v_FirstAddressHex, 4))."</br>";
$primeraipv6 = implode(':', str_split($v_FirstAddressHex, 4));
//echo "v_LastAddressHex: ".implode(':', str_split($v_LastAddressHex, 4))."</br>";
//var_dump($v_Return);

$IpStart_v6_FromDb = $primeraipv6;
	
	//echo "Primera ip del rango es: ".$IpStart_v6_FromDb."</br>";
////****** FIN Codigo para generar las IPv6 *******/////
	
//Todo parece correcto procedemos con la inserccion de la ipv6
$query = "INSERT INTO ipv6 (ipv6, ipv6range) VALUES('".mysqli_real_escape_string($mysql_conn,$IpStart_v6_FromDb)."','".mysqli_real_escape_string($mysql_conn,$rango)."')"; 
$registro=mysqli_query($mysql_conn,$query) or die(mysqli_error());
 
echo "<b> Se ha agregado la IP ".$IpStart_v6_FromDb."/".$rango." correctamente.</b></br>";
}
//******Fin Codigo para generar la primera IPv6 del rango *******//
?>