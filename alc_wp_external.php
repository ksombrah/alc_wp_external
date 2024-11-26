<?php
/**
 * Plugin Name
 *
 * @package           Autenticação Externa - Swagger UI
 * @author            Alcione Ferreira
 * @copyright         2024 AlcioneSytes.net
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Autenticação Externa - Swagger UI
 * Plugin URI:        https://github.com/ksombrah/alc_wp_external
 * Description:       Plugin para Autenticação Externa - Swagger UI
 * Version:           1.0.2
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Alcione Ferreira
 * Author URI:        https://alcionesytes.net
 * Text Domain:       alc_wp_external
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Update URI:        https://github.com/ksombrah/alc_wp_external
 */
 
 /*
{Plugin Name} is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

{Plugin Name} is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with {Plugin Name}. If not, see {URI to Plugin License}.
*/
 
global $alc_wp_external_db_version;
$alc_wp_external_db_version = '1.0';
global $alc_wp_api_base_url;
$alc_wp_api_base_url = 'https://srv631324.hstgr.cloud:8081';

//require_once plugin_dir_path(__FILE__).'vendor/autoload.php';
//require_once plugin_dir_path(__FILE__).'vendor/smarty/smartysrc/Smarty.php';
//require_once plugin_dir_path(__FILE__).'Visao.php';

add_action( 'init', 'alc_wp_iniciar_sessao', 1 );

function alc_wp_iniciar_sessao() 
	{
   if ( ! session_id() ) 
   	{
      session_start();
    	}
	}
	
function alc_wp_finalizar_sessao() 
	{
   // Verifica se uma sessão foi iniciada
   if ( session_id() ) 
   	{
      // Limpa as variáveis de sessão
      $_SESSION = array();

      // Se deseja matar a sessão, delete também o cookie de sessão.
      // Nota: Isso irá destruir a sessão e não apenas os dados da sessão!
      if ( ini_get("session.use_cookies") ) 
      	{
         $params = session_get_cookie_params();
         setcookie( session_name(), '', time() - 42000,
         	$params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
            );
        	}

     	// Finalmente, destrói a sessão.
      session_destroy();
    	}
	}
	
// Definir uma variável de sessão
function alc_wp_guardar_dado( $chave, $valor ) 
	{
   $_SESSION[$chave] = $valor;
	}

// Obter uma variável de sessão
function alc_wp_pegar_dado( $chave ) 
	{
   return isset( $_SESSION[$chave] ) ? $_SESSION[$chave] : null;
	}

function alc_wp_external_install() 
	{
	global $alc_wp_external_db_version;
 	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	add_option( 'alc_wp_external_db_version', $alc_wp_external_db_version );
	}
	
function alc_wp_external_uninstall()
	{

	}

function alc_wp_response($response) 
	{
   if (is_wp_error($response)) 
   	{
      //$error_message = $response->get_error_message();
      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body);
      return $data; // Manipule os dados conforme necessário
      //return "Algo deu errado: $error_message";
     	} 
   else 
   	{
      $body = wp_remote_retrieve_body($response);
      $data = json_decode($body);
      return $data; // Manipule os dados conforme necessário
     	}
 	}

add_filter( 'authenticate', 'alc_wp_auth', 21, 3 );

function alc_wp_auth( $user, $username, $password )
	{
	global $alc_wp_api_base_url;
   // Certifique-se de que um nome de usuário e uma senha estejam presentes para que possamos trabalhar com eles
   if($username == '' || $password == '')
   	{ 
   	return;
   	}
   /*if ( is_user_logged_in() ) 
   	{
		wp_logout();
		}
   $creds =  array (
   	'user_login'    => $username,
		'user_password' => $password,
		'remember'      => true,
   	);
   //tentando login
   $user = wp_signon ($creds, false);*/
   //verificando login
   if ( ! $user )
   	{
   	//testando dados externamente
	  	$response = wp_remote_get( $alc_wp_api_base_url."/login/login?userName=".urlencode($username)."&password=".$password,array('timeout' => 120, 'httpversion' => '1.1'));
	  	$external = false;
	  	$ext_auth = alc_wp_response($response);
	  	if (!is_null($ext_auth))
			{
			$external = true;
			}
	  	
	   if( !$external ) 
	   	{
	      // Usuário não existem na API
	      $user = new WP_Error( 'denied', __("ERROR: Usuário ou Senha inválidos") );
	     	} 
	  	else  
	  		{
	      // External user exists, try to load the user info from the WordPress user table
	      $userobj = new WP_User();
	      $user = $userobj->get_data_by( 'email', $ext_auth->email ); // Does not return a WP_User object :(
	      $user = new WP_User($user->ID); // Attempt to load up the user with that ID
	
	      if( $user->ID == 0 ) 
	      	{
	         // O usuário não existe atualmente na tabela de usuários do WordPress.
				// Você chegou a uma bifurcação na estrada, escolha seu destino sabiamente

				// Se você não quiser adicionar novos usuários ao WordPress se eles não
				// já existirem, descomente a linha a seguir e remova o código de criação do usuário
	         //$user = new WP_Error( 'denied', __("ERROR: Not a valid user for this system") );
	
	         // Configure as informações mínimas necessárias do usuário para este exemplo
	         /*$userdata = array( 'user_email' => $ext_auth->email,
	                                'user_login' => $ext_auth->email,
	                                'first_name' => $ext_auth->name,
	                                'last_name' => $ext_auth->name,
	                                );*/
	         // Um novo usuário
	         $new_user_id = wp_create_user( $username, $password, $ext_auth->email );
        		if( is_wp_error( $new_user_id ) ) 
        			{
            	// Erro Ao Criar usuário
            	$user = new WP_Error( 'denied', __("ERROR: Não foi possível Criar novo usuário") );
        			}
        		else
        			{
	         	// Carregue as novas informações do usuário
	         	$user = new WP_User ($new_user_id);
	         	}
	         } 
	     	}
		}

  	// Comente esta linha se você deseja recorrer à autenticação do WordPress
	// Útil para momentos em que o serviço externo está offline
   //remove_action('authenticate', 'wp_authenticate_username_password', 20);
   
	if ( ! $user )
   	{
   	//Não existe usuário
   	$user = new WP_Error( 'denied', __("ERROR: Usuário ou Senha Inválidos") );
		}
	return $user;
	}
	
remove_filter( 'authenticate', 'alc_wp_auth', 21, 3 );
	
function alc_wp_test()
	{
	global $alc_wp_api_base_url;
	error_reporting( E_ALL );
	ini_set( 'display_errors', 1 );
	$username = "cliente@gmail.com";
	$password = "121212";
	$url = $alc_wp_api_base_url."/login/login?userName=".urlencode($username)."&password=".$password;
	
	/*$curl = curl_init();

	curl_setopt_array($curl, array(
  		CURLOPT_URL => $url,
  		CURLOPT_RETURNTRANSFER => true,
  		CURLOPT_ENCODING => '',
  		CURLOPT_MAXREDIRS => 10,
  		CURLOPT_TIMEOUT => 0,
  		CURLOPT_FOLLOWLOCATION => true,
  		CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  		CURLOPT_CUSTOMREQUEST => 'GET',
		));

	$ext_auth = curl_exec($curl);*/
	$ext_auth = wp_remote_get ($url,array('timeout' => 120, 'httpversion' => '1.1'));

	//curl_close($curl);
	$retorno = "";
	$resultado = alc_wp_response($ext_auth);
	if (!is_null($resultado))
		{
		//var_dump( $resultado );
	
		if (isset($resultado->token))
			{
			$retorno .= $resultado->token.'<br/>';
			}
		}
   $userobj = new WP_User();
	$user = $userobj->get_data_by( 'email', $resultado->email ); // Does not return a WP_User object :(
	$user = new WP_User($user->ID);
	if ($user->ID == 0)
		{
		$retorno .= "NOVO";
		/*$userdata = array( 'user_email' => $ext_auth->email,
                             'user_login' => $ext_auth->email,
                             'first_name' => $ext_auth->name,
                             'last_name' => $ext_auth->name
                             );*/
      $new_user_id = wp_create_user( $username, $password, $resultado->email );; // A new user has been created

      // Carregue as novas informações do usuário
      $user = new WP_User ($new_user_id);
      //var_dump($user);
		}
	
   $retorno .= "<br/>TESTE - ".$url;        
   
	echo '<html>'.$retorno.'</html>';
	}
	
add_action( 'rest_api_init', function () 
	{
  	register_rest_route( 'widgets', '/alc_wp_external_login', array(
    	'methods' => 'GET',
    	'callback' => 'alc_wp_test',
    	'permission_callback' => '__return_true',
  		) 
  		);
	});
	
register_activation_hook( __FILE__, 'alc_wp_external_install');

register_deactivation_hook(__FILE__, 'alc_wp_external_uninstall');

?>
