<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://srv631324.hstgr.cloud:8081/login/login?userName=cliente%40gmail.com&password=121212',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
));

$response = curl_exec($curl);

curl_close($curl);

$resultado = json_decode($response);
if (!is_null($resultado))
	{
	var_dump( $resultado );

	if (isset($resultado->token))
		{
		echo ($resultado->token);
		}
	}

?>