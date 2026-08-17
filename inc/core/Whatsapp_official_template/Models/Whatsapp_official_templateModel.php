<?php
namespace Core\Whatsapp_official_template\Models;
use CodeIgniter\Model;

class Whatsapp_official_templateModel extends Model
{
    protected $config;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    public function block_plans()
    {
        return [
            "tab" => 15,
            "position" => 360,
            "label" => __("Whatsapp tool"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Templates Oficiais WhatsApp"),
                ],
            ]
        ];
    }
}
