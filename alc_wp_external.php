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
 * Version:           1.1.0
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
global $alc_wp_log_path;
$alc_wp_log_path = plugin_dir_path(__FILE__).'logs_erros';
global $alc_wp_external_nameform;
$alc_wp_external_nameform = 'form_name'; //trocar pelo nome do formulário usado para autenticar

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
   if (isset($_SESSION['alc_wp_external']))
   	{
   	// Verificar validade
		$session_timeout = 3600; // 1 hora
		if (time() - $_SESSION['alc_wp_external']['logged_in_at'] > $session_timeout) 
			{
    		session_destroy();
    		echo "Sessão expirada.";
			}
		}
	}

// Finalizar a sessão ao encerrar o WordPress
add_action('wp_logout', 'alc_wp_finalizar_sessao');
add_action('wp_login', 'alc_wp_finalizar_sessao');
	
function alc_wp_finalizar_sessao() 
	{
   // Verifica se uma sessão foi iniciada
   if ( session_id() ) 
   	{
      // Limpa as variáveis de sessão
      $_SESSION = array();
     	// Finalmente, destrói a sessão.
      session_destroy();
    	}
	}
	
// Definir uma variável de sessão
function alc_wp_guardar_dado( $chave, $valor ) 
	{
   $_SESSION['alc_wp_external'][$chave] = $valor;
	}

// Obter uma variável de sessão
function alc_wp_pegar_dado( $chave ) 
	{
   return isset( $_SESSION['alc_wp_external'][$chave] ) ? $_SESSION['alc_wp_external'][$chave] : null;
	}

function alc_wp_external_install() 
	{
	global $alc_wp_external_db_version;
 	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	add_option( 'alc_wp_external_db_version', $alc_wp_external_db_version );
	//add_filter( 'authenticate', 'alc_wp_auth', 21, 3 );
	}
	
function alc_wp_external_uninstall()
	{
	//remove_filter( 'authenticate', 'alc_wp_auth', 21, 3 );
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
	
add_shortcode('show_user_name', 'alc_wp_external_show_user_name');

function alc_wp_external_show_user_name() 
	{
   if (isset($_SESSION['user_data'])) 
   	{
      return "Bem-vindo, " . esc_html($_SESSION['user_data']['nome']) . "!";
    	}
  	return "Usuário não autenticado.";
	}

add_action('elementor_pro/init', 'alc_wp_external_register_action');

function alc_wp_external_register_action() 
	{
   // Verifica se o Elementor Pro está ativo
   if (!class_exists('\ElementorPro\Plugin')) 
   	{
      return;
    	}

  	// Obtém o módulo de formulários do Elementor Pro
   $module = \ElementorPro\Plugin::instance()->modules_manager->get_modules('forms');

   if (!$module) 
   	{
      // Caso o módulo de formulários não esteja ativo
      error_log('Módulo de formulários não encontrado.');
      return;
    	}

    // Registra a ação personalizada
   require_once __DIR__ . '/alc_wp_external_acao.php'; // Atualize o caminho corretamente
   $module->add_form_action('alc_wp_external_form', new \alc_wp_external_acao\alc_wp_external_form());
	}
	
register_activation_hook( __FILE__, 'alc_wp_external_install');

register_deactivation_hook(__FILE__, 'alc_wp_external_uninstall');

?>
