<?php
namespace Core\Coupons\Controllers;

class Coupons extends \CodeIgniter\Controller
{
    public function __construct(){
        $this->config = parse_config( include realpath( __DIR__."/../Config.php" ) );
    }
    
    public function index( $page = false ) {
        $team_id = get_team("id");
        $result = db_fetch("*", TB_COUPONS, [], "created", "ASC");

        $data = [
            "result" => $result,
            "title" => $this->config['menu']['sub_menu']['name'],
            "desc" => $this->config['desc'],
        ];

        switch ( $page ) {
            case 'update':
                $item = false;
                $ids = uri('segment', 4);
                if( $ids ){
                    $item = db_get("*", TB_COUPONS, [ "ids" => $ids ]);
                }

                $plans = db_fetch("*", TB_PLANS, []);

                $data['content'] = view('Core\Coupons\Views\update', ["result" => $item, "plans" => $plans]);
                break;

            default:
                $data['content'] = view('Core\Coupons\Views\empty');
                break;
        }

        return view('Core\Coupons\Views\index', $data);
    }

    public function save( $ids = "" ){
        $name = post("name");
        $code = post("code");
        $price = post("price");
        $status = post("status");
        $by = post("by");
        $expiration_date = post("expiration_date");
        $plans = post("plans");
        $item = false;

        if ($ids != "") {
            $item = db_get("*", TB_COUPONS, ["ids" => $ids]);
        }

        if (!$this->validate([
            'name' => 'required'
        ])) {
            ms([
                "status" => "error",
                "message" => __("Coupon name is required")
            ]);
        }

        if (!$this->validate([
            'code' => 'required'
        ])) {
            ms([
                "status" => "error",
                "message" => __("Coupon code is required")
            ]);
        }

        if (!$this->validate([
            'price' => 'required'
        ])) {
            ms([
                "status" => "error",
                "message" => __("Price/Percent is required")
            ]);
        }

        if (!$this->validate([
            'expiration_date' => 'required'
        ])) {
            ms([
                "status" => "error",
                "message" => __("Expiration date is required")
            ]);
        }

        if (!$this->validate([
            'plans' => 'required'
        ])) {
            ms([
                "status" => "error",
                "message" => __("Please select a plan")
            ]);
        }

        if (!$this->validate([
            'status' => 'required'
        ])) {
            ms([
                "status" => "error",
                "message" => __("Status is required")
            ]);
        }

        if (!$this->validate([
            'by' => 'required'
        ])) {
            ms([
                "status" => "error",
                "message" => __("Coupon by is required")
            ]);
        }

        $data = [
            "name" => $name,
            "code" => $code,
            "price" => $price,
            "by" => $by,
            "expiration_date" => timestamp_sql( $expiration_date ),
            "plans" => json_encode($plans),
            "status" => $status,
            "changed" => time(),
        ];

        if( empty($item) ){
            $data['ids'] = ids();
            $data['created'] = time();

            db_insert(TB_COUPONS, $data);
        }else{
            db_update(TB_COUPONS, $data, [ "id" => $item->id ]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }

    public function delete( $ids = '' ){
        if($ids == ''){
            $ids = post('id');
        }

        if( empty($ids) ){
            ms([
                "status" => "error",
                "message" => __('Please select an item to delete')
            ]);
        }

        if( is_array($ids) )
        {
            foreach ($ids as $id) 
            {
                db_delete(TB_COUPONS, ['ids' => $id]);
            }
        }
        elseif( is_string($ids) )
        {
            db_delete(TB_COUPONS, ['ids' => $ids]);
        }

        ms([
            "status" => "success",
            "message" => __('Success')
        ]);
    }
}