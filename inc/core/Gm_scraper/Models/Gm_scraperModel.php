<?php
namespace Core\Gm_scraper\Models;

class Gm_scraperModel
{
    public $config;

    public function __construct(){
        $this->db = \Config\Database::connect();
        $this->config = parse_config( include realpath(__DIR__."/../Config.php") );
    }

    public function block_plans(){
        return [
            'tab' => __('Features'),
            'position' => 1000,
            'permission' => true,
            'label' => __('Extrator de Leads'),
            'items' => [
                [
                    'id' => 'gm_scraper',
                    'name' => __('Permitir uso do Extrator'),
                ]
            ]
        ];
    }

    public function block_whatsapp(){
        return array(
            "position" => 9000,
            "config" => $this->config
        );
    }
}
