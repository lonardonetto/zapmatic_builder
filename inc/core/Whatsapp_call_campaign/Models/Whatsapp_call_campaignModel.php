<?php

namespace Core\Whatsapp_call_campaign\Models;

use CodeIgniter\Model;

class Whatsapp_call_campaignModel extends Model
{
    protected $config;

    public function __construct()
    {
        parent::__construct();
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    public function block_whatsapp()
    {
        return array(
            "position" => 4100,
            "config" => $this->config
        );
    }
}
