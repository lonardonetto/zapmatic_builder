<?php
namespace Core\Group_manager\Models;
use CodeIgniter\Model;

class Group_managerModel extends Model
{
    public function block_plans(){
        return [
            "tab" => 30,
            "position" => 100,
            "label" => __("Advanced features"),
            "items" => [
                [
                    "id" => "group_manager",
                    "name" => __("Gerenciamento de Grupos"),
                ],
            ]
        ];
    }
}
