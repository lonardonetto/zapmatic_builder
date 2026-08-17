<?php
namespace Core\Whatsapp_export_participants\Models;
use CodeIgniter\Model;

class Whatsapp_export_participantsModel extends Model
{
	public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }

    public function block_whatsapp(){
        return array(
            "position" => 8000,
            "config" => $this->config
        );
    }

    public function block_plans(){
        return [
            "tab" => 15,
            "position" => 700,
            "label" => __("Whatsapp tool"),
            "items" => [
                [
                    "id" => $this->config['id'],
                    "name" => __("Exportar Participantes e Clone de Grupos"),
                ],
            ]
        ];
    }
}
