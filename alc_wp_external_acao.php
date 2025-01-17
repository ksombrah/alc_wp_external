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
      $widget->start_controls_section(
        'section_redirect_settings',
        [
            'label' => __('Configurações de Redirecionamento', 'alc_wp_external'),
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]
    	);

    	$widget->add_control(
        'redirect_url',
        [
            'label' => __('URL de Redirecionamento', 'alc_wp_external'),
            'type' => \Elementor\Controls_Manager::TEXT,
            'placeholder' => site_url('/sua-pagina-de-destino'), // Usando site_url() para gerar a URL base
            'description' => __('Defina a URL para onde o formulário deve redirecionar após o sucesso.', 'alc_wp_external'),
        ]
   	);

    	$widget->end_controls_section();
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
		
		//error_log('A ação do formulário foi acionada.');
		
	   // Obter os dados enviados pelo formulário
	   $raw_fields = $record->get('fields');
	   $fields = [];
	   foreach ($raw_fields as $id => $field) 
	   	{
	      $fields[$id] = $field['value'];
	    	}
	
	  	// Verifique o nome do formulário para aplicar a ação apenas em um formulário específico
	   $form_name = $record->get_form_settings('form_name');
	   error_log('Nome do Formulário: '.$form_name);
	   if ($alc_wp_external_nameform !== $form_name) 
	   	{
	      return;
	    	}
	    	
	  	// Verifique se os campos obrigatórios estão preenchidos
    	if (empty($fields['usuario']) || empty($fields['senha'])) 
    		{
    		error_log('Usuario e/ou Senha Vazio');
        	$handler->add_error('usuario', __('O campo "Usuário" é obrigatório.', 'alc_wp_external'));
			$handler->add_error('senha', __('O campo "Senha" é obrigatório.', 'alc_wp_external'));
        	return;
    		}
	   
	   $username = $fields['usuario'];
		$password = $fields['senha'];
	   $url = $alc_wp_api_base_url."/login/login?userName=".urlencode($username)."&password=".$password;
		
		$ext_auth = wp_remote_get ($url,array('timeout' => 120, 'httpversion' => '1.1'));
		$resultado = alc_wp_response($ext_auth);
		if (is_wp_error($ext_auth) || is_null($resultado)) 
			{
    		$message = is_wp_error($ext_auth) ? $ext_auth->get_error_message() : __('Resposta inválida da API.', 'alc_wp_external');
    		error_log($message);
    		$handler->add_error(null, __('Erro ao conectar-se à API: ' . $message, 'alc_wp_external'));
    		return;
			}
		else
			{
			if (isset($resultado->token))
				{
				alc_wp_guardar_dado('nome',$resultado->name);
				alc_wp_guardar_dado('email',$resultado->email);
				alc_wp_guardar_dado('logged_in_at',time());
				alc_wp_guardar_dado('token_logado',$resultado->token);
				error_log('Token: '.$resultado->token);
				// Obter a URL de redirecionamento configurada no formulário
        		$redirect_url = $record->get_form_settings('redirect_url');
        		if ($redirect_url) 
        			{
            	$handler->add_response_data('redirect_url', esc_url($redirect_url));
        			}
				}
			else
				{ 
			 	$error_message = isset($resultado->message) ? $resultado->message : __('Erro desconhecido.', 'alc_wp_external');
			 	error_log($error_message);
				$handler->add_error(null, __('Erro ao conectar-se à API: ' . $error_message, 'alc_wp_external'));
				return;
				}
	    	}
    	}
	}
