<?php
namespace Core\Social_network_settings\Controllers;

class Social_network_settings extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }
    
    public function index( $page = false ) {
        $block_social_settings = $this->request->block_social_settings;

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "blocks" => $block_social_settings
        ];

        if( isset($block_social_settings[$page]) && isset($block_social_settings[$page]) ){
            $data['content'] = view('Core\Social_network_settings\Views\content', [ 'block_tab' => $block_social_settings[$page] ]);
        }else{
            $data['content'] = view('Core\Social_network_settings\Views\empty');
        }

        return view('Core\Social_network_settings\Views\index', $data);
    }

    public function save(){
        $data = $this->request->getPost();

        if(is_array($data)){
            // Validação dos campos da Meta (Global Meta Configuration): impede que
            // App ID / App Secret inválidos (lixo) sejam gravados e quebrem o
            // onboarding (FB.init/FB.login). Regra única centralizada no resolvedor.
            $metaErrors = [];
            if (isset($data['meta_app_id'])) {
                $raw = trim((string) $data['meta_app_id']);
                if ($raw !== '' && !\Core\Whatsapp_profiles\Libraries\MetaAppIdResolver::isValid($raw)) {
                    $metaErrors[] = __('Meta App ID deve conter apenas números (ex.: 763786439394524).');
                }
            }
            if (isset($data['meta_app_secret'])) {
                $raw = trim((string) $data['meta_app_secret']);
                if ($raw !== '' && !\Core\Whatsapp_profiles\Libraries\MetaAppIdResolver::isValidSecret($raw)) {
                    $metaErrors[] = __('Meta App Secret deve ter 32 caracteres hexadecimais (0-9, a-f).');
                }
            }
            if (!empty($metaErrors)) {
                ms([
                    "status"  => "error",
                    "message" => implode(' ', $metaErrors),
                ]);
                return;
            }

            foreach ($data as $key => $value) {
                if($key != 'csrf'){
                    update_option( $key, trim( htmlspecialchars( $value ) ) );
                }
            }
        }

        ms([
            "status"  => "success",
            "message" => __('Success'),
        ]);
    }
}