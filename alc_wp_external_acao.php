<?php



namespace alc_wp_external_acao;

use ElementorPro\Modules\Forms\Classes\Action_Base;

if (!defined('ABSPATH')) 
	{
   exit; // Impede acesso direto
	}

require_once __DIR__ .'/alc_wp_external.php';

class alc_wp_external_form extends Action_Base 
	{

   // Nome da ação (usado no "Actions After Submit")
   public function get_name() 
   	{
      return 'alc_wp_external_form'; // Nome usado no Elementor
    	}

    // Título visível no painel do Elementor
    public function get_label() 
    	{
      return __('Autenticação Externa', 'alc_wp_external');
    	}
   
   // Método obrigatório: Configurações da ação no editor de formulário (pode ficar vazio se não usar configurações específicas)
    public function register_settings_section($widget) 
    	{
      // Sem configurações adicionais para este exemplo
    	}

    // Método obrigatório: Exportar configurações ao exportar o formulário
    public function on_export($element) 
    	{
      // Sem ações específicas para exportação
    	}

    // Processamento da ação
    public function run($record, $handler) 
    	{
    	global $alc_wp_api_base_url;
		global $alc_wp_external_nameform;
	   // Obter os dados enviados pelo formulário
	   $raw_fields = $record->get('fields');
	   $fields = [];
	   foreach ($raw_fields as $id => $field) 
	   	{
	      $fields[$id] = $field['value'];
	    	}
	
	  	// Verifique o nome do formulário para aplicar a ação apenas em um formulário específico
	   $form_name = $record->get_form_settings('form_name');
	   if ($alc_wp_external_nameform !== $form_name) 
	   	{
	      return;
	    	}
	   
	   $username = $fields['usuario'];
		$password = $fields['senha'];
	   $url = $alc_wp_api_base_url."/login/login?userName=".urlencode($username)."&password=".$password;
		
		$ext_auth = wp_remote_get ($url,array('timeout' => 120, 'httpversion' => '1.1'));
		$resultado = alc_wp_response($ext_auth);
		if (!is_null($resultado))
			{
			if (isset($resultado->token))
				{
				alc_wp_guardar_dado('nome',$resultado->name);
				alc_wp_guardar_dado('email',$resultado->email);
				alc_wp_guardar_dado('logged_in_at',time());
				alc_wp_guardar_dado('token_logado',$resultado->token);
				}
			}
		else 
			{
	      error_log('Falha na autenticação: ' . $resultado->message);
	    	}
    	}
	}
