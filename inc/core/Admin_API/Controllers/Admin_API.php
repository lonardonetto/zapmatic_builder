<?php

namespace Core\Admin_API\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\Controller;
use Error;

class Admin_API extends Controller
{
    use ResponseTrait;
    public string $api_key;

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
        $this->class_name = get_class_name($this);
        $this->model = new \Core\Admin_API\Models\Admin_APIModel();
        $this->api_key = get_option("admin_api_key", "asg12345");
    }

    public function index($page = false)
    {
        if (!permission("admin_api")) {
            redirect_to(base_url());
        }

        $api_key = $this->api_key ;

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "content" => view('Core\Admin_API\Views\content', ['api_key' => $api_key, "config" => $this->config]),
            "api_key" => $api_key
        ];

        return view('Core\Admin_API\Views\index', $data);
    }


    public function get_users()
    {
        try {
            $this->check_api_key();
            $current_page = (int)((post("page") ?? 1) - 1);
            $per_page = (int)(post("per_page") ?? 20);
            $total_items = post("total_items");

            $total_items = $this->model->get_list(false);
            $result = $this->model->get_list(true);
            $data = [
                "status" => "success",
                "data" => $result,
                "total_items" => $total_items,
                "per_page" => $per_page,
                "current_page" => $current_page + 1,
            ];
            return $this->respond($data, 200);
        } catch (\Throwable $th) {
            return $this->manage_exception($th);
        }
    }

    public function create_user()
    {
        try {

            $iData = (array) json_decode(file_get_contents("php://input"));

            $username       = $iData['username'] ?? null;
            $fullname       = $iData['fullname'] ?? null;
            $email          = $iData['email'] ?? null;
            $role           = (int)($iData['role'] ?? 0);
            $password       = $iData['passssword'] ?? explode("@", $email)[0] . '_' . date("Ym");
            $expired_date   = $iData['expired_date'] ?? null;
            $timezone       = $iData['timezone'] ?? "America/Mexico_City";
            $plan_id        = $iData['plan_id'] ?? null;;
            $is_admin       = (int)($iData['is_admin'] ?? 0);
            $status         = (int)($iData['status'] ?? 2);

            $this->val_par("null", __("email"), $email);
            $this->val_par("email", "email", $email);
            $this->val_par("null", __("username"), $username);
            $this->val_par("null", __("fullname"), $fullname);
            $this->val_par("null", __("timezone"), $timezone);
            $this->val_par("null", __("expired_date"), $expired_date);
            $this->val_par('min_length', __('Password'), $password, 6);

            $email_check = db_get('id', TB_USERS, ["email" => $email]);
            $this->val_par('not_empty', __('This email already exists'), $email_check);
            $username_check = db_get("id", TB_USERS, ['username' => $username]);
            $this->val_par('not_empty', __('This username already exists'), $username_check);
            $plan_check = db_get("*", TB_PLANS, ['id' => $plan_id]);
            $this->val_par('empty', __('This plan not exists'), $plan_check);

            $avatar = save_img(get_avatar($fullname), WRITEPATH . 'avatar/');

            // $expired_date = date("Y-m-d", strtotime($expired_date));
            $password       = md5($password);

            $expired_date = $expired_date ? strtotime(date_sql($expired_date)) : 0;

            $id = db_insert(TB_USERS, [
                "ids" => ids(),
                "is_admin" => $is_admin,
                "role" => $role,
                "fullname" => $fullname,
                "username" => $username,
                "email" => $email,
                "password" => md5($password),
                "plan" => $plan_id,
                "expiration_date" => $expired_date,
                "timezone" => $timezone,
                "login_type" => 'direct',
                "avatar" => $avatar,
                "status" => $status,
                "changed" => time(),
                "created" => time()
            ]);

            db_insert(TB_TEAM, [
                "ids" => ids(),
                "owner" => $id,
                "pid" => $plan_id,
                "permissions" => $plan_check->permissions
            ]);


            return $this->respond(["status" => "success", "message" => "success"], 200);
        } catch (\Throwable $th) {
            return $this->manage_exception($th);
        }
    }

    public function update_user()
    {
        try {
            $this->check_api_key();
            $user_email = post('user');
            $this->val_par('null', 'user', $user_email);

            $user = db_get('*', TB_USERS, ["email" => $user_email]);
            $this->val_par('empty', __('User not found'), $user, '', 404);

            $iData = (array) json_decode(file_get_contents("php://input"));

            $username       = $iData['username'] ?? $user->username;
            $fullname       = $iData['fullname'] ?? $user->fullname;
            $email          = $iData['email'] ?? $user->email;
            $role           = (int)($iData['role'] ?? $user->role);
            $password       = $iData['passssword'] ?? null;
            $expired_date   = $iData['expired_date'] ?? date("Y-m-d", $user->expiration_date);
            $timezone       = $iData['timezone'] ?? $user->timezone;
            $plan_id        = $iData['plan_id'] ?? $user->plan;;
            $is_admin       = (int)($iData['is_admin'] ?? $user->is_admin);
            $status         = (int)($iData['status'] ?? $user->status);


            $email_check = db_get("*", TB_USERS, ['email' => $email, 'id != ' => $user->id]);
            $this->val_par('not_empty', __('This email already exists'), $email_check);
            $username_check = db_get("*", TB_USERS, ['username' => $username, 'id != ' => $user->id]);
            $this->val_par('not_empty', __('This username already exists'), $username_check);

            if ($plan_id != $user->plan) {
                $plan_check = db_get("*", TB_PLANS, ['id' => $plan_id]);
                $this->val_par('empty', __('This plan not exists'), $plan_check);
            }

            $data = [
                "is_admin" => $is_admin,
                "role" => $role,
                "fullname" => $fullname,
                "username" => $username,
                "email" => $email,
                "plan" => $plan_id,
                "expiration_date" => $expired_date ? strtotime(date_sql($expired_date)) : 0,
                "timezone" => $timezone,
                "status" => $status,
                "changed" => time()
            ];

            if ($password && $password != "") {
                $data['password'] = md5($password);
            }

            db_update(TB_USERS, $data, ["id" => $user->id]);

            if ($plan_id != $user->plan) {
                $team = db_get("*", TB_TEAM, ["owner" => $user->id]);
                update_team_data("number_accounts", $plan_check->number_accounts, $team->id);

                db_update(
                    TB_TEAM,
                    [
                        "permissions" => $plan_check->permissions,
                        "pid" => $plan_check->id
                    ],
                    [
                        "owner" => $user->id
                    ]
                );
            }


            return $this->respond(["status" => "success", "message" => "success"], 200);
        } catch (\Throwable $th) {
            return $this->manage_exception($th);
        }
    }

    public function delete_user()
    {
        try {
            $this->check_api_key();
            $user_email = post('user');
            $this->val_par('null', 'user', $user_email);
            $user = db_get('*', TB_USERS, ["email" => $user_email]);
            $this->val_par('empty', __('User not found'), $user, '', 404);

            if (!$user->is_admin) {
                db_delete(TB_USERS, ['id' => $user->id]);
                return $this->respond(["status" => "success", "message" => "success"], 200);
            } else {
                throw new Error("admin user can't be delete", 400);
            }
        } catch (\Throwable $th) {
            return $this->manage_exception($th);
        }
    }

    public function get_autologin()
    {
        try {
            $this->check_api_key();

            $user_search = post("user") ?? null;
            $this->val_par('null', 'user', $user_search);

            $user = db_get('*', TB_USERS, ["email" => $user_search]);
            $this->val_par('empty', __('User not found'), $user, '', 404);

            if ($user->status == 1 || $user->status == 0) {
                throw new Error("user account in not available");
            }

            $privateKey = $this->api_key;
            $objDateTime = date_create('+1day');
            $domain = base_url();
            $url = $domain . '/admin_api/check_token';

            $hash = hash('sha256', $privateKey . $url . $user_search . $objDateTime->getTimestamp());

            $autoLoginUrl = http_build_query(array(
                'user' => $user_search,
                'time_limit' => $objDateTime->getTimestamp(),
                'token' => $hash
            ));

            $data = [
                "url" => $url . '?' . $autoLoginUrl,
                'user' => $user_search,
                'time_limit' => $objDateTime->getTimestamp(),
                'token' => $hash
            ];


            return $this->respond(["status" => "success", "message" => "success", "data" =>  $data], 200);
        } catch (\Throwable $th) {
            return $this->manage_exception($th);
        }
    }

    public function migrate_users()
    {
        try {

            $this->check_api_key();

            $current_page = (int)((post("page") ?? 1) - 1);
            $per_page = post("size") ?? 30;

            $url = post("url");
            //$this->val_par('null', '66param url', $url);

            $key_src = post("key");
            //$this->val_par('null', '66param key', $key);



            $db = \Config\Database::connect();
            $builder = $db->table(TB_USERS . " as a");
            $builder->select('a.*');

            $builder->limit($per_page, $per_page * $current_page);
            $query = $builder->get();
            $result = $query->getResult();
            $query->freeResult();

            $ret = array();
            foreach ($result as $key => $user) {
                $res = $this->create_66_user($user, $url, $key_src);
                $ret[] = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'result' => $res
                ];
            }



            return $this->respond(["status" => "success", "message" => "success", "data" =>  $ret], 200);
        } catch (\Throwable $th) {
            return $this->manage_exception($th);
        }
    }

    private function create_66_user($user, $base_url, $api_key)
    {

        try {

            if (!$base_url || $base_url == '') {
                throw new Error('url not configured');
            }

            if (!$api_key || $api_key == '') {
                throw new Error('api_key not configured');
            }

            $curl               =   curl_init($base_url . '/users');
            $data               =   array();
            $data['email']      =   $user->email;
            $data['name']       =   $user->fullname;
            $data['password']   =   explode("@", $user->email)[0] . '_' . date("Ym");


            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $headers = array(
                "'Content-Type: multipart/form-data",
                "Authorization: Bearer " . $api_key,
            );
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($curl);
            curl_close($curl);

            $expired_date = date('Y-m-d', strtotime($user->expiration_date));

            $nw_usr                 =   json_decode($response); // creo el objeto del nuevo usuario

            if (isset($nw_usr->data->id)) {
                $curl                   =   curl_init($base_url . '/users/' . $nw_usr->data->id); // asigno la url al curl con el id del nuevo usuario
                $dataUpdate             =   array();
                $dataUpdate['plan_id']  =   $user->plan; // asigno el plan a la data que se enviará
                $dataUpdate['tz']       =   $user->timezone;

                if (isset($expired_date)) $dataUpdate["plan_expiration_date"]    = $expired_date;

                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($dataUpdate));
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                $responseUpdate = curl_exec($curl);
                curl_close($curl);

                return $nw_usr;
            } else {
                return $nw_usr;
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function check_token()
    {
        try {

            remove_session(["uid"]);
            remove_session(["team_id"]);
            delete_cookies("uid");
            delete_cookies("team_id");

            $timeLimit = post('time_limit');
            $this->val_par('null', 'time_limit', $timeLimit);

            if ((int)$timeLimit < time()) {
                throw new Error('expired token');
            }

            $privateKey = $this->api_key;
            $domain = base_url();
            $url = $domain . '/admin_api/check_token';

            $user_email = post('user');

            $hash = hash('sha256', $privateKey . $url . $user_email . $timeLimit);

            if ($hash != post('token')) {
                throw new Error('invalid token');
            }

            $user = db_get('*', TB_USERS, ["email" => addslashes($user_email)]);
            $this->val_par('empty', __('User not found'), $user, '', 404);

            $team = db_get("id,ids", TB_TEAM, "owner = '{$user->id}'");
            $this->val_par('empty', __('There is a problem on your account. Please try again later'), $team, '', 500);

            if ($user->status == 1) {
                throw new Error(__('Your account is not activated'));
            }

            if ($user->status == 0) {
                throw new Error(__('Your account is banned'));
            }

            $u = db_update(TB_USERS, ["last_login" => time()], ["id" => $user->id]);

            set_session(["uid" => $user->ids]);
            set_session(["team_id" => $team->ids]);

            return redirect()->to(base_url() . '/dashboard');
        } catch (\Throwable $th) {
            return $this->manage_exception($th);
        }
    }

    private function check_api_key()
    {
        $api_key = post("api_key");
        if (!isset($api_key)) {

            throw new Error("api-key is required", 403);
        } elseif ($api_key != $this->api_key) {
            throw new Error("Not Allowed", 401);
        }
    }



    private function manage_exception(\Throwable $th)
    {
        if ($th->getCode() >= 200 && $th->getCode() <= 499) {
            return $this->respond(["status" => "error", "message" => $th->getMessage()], $th->getCode());
        } else {
            return $this->respond(["status" => "error", "message" => $th->getMessage()], 500);
        }
    }

    private function val_par(string $type, string $message, $data, $value = '', $code = 400)
    {
        switch ($type) {
            case 'email':
                if (!filter_var($data, FILTER_VALIDATE_EMAIL)) {
                    throw new Error(sprintf(__('%s is not a valid email address'), $message), $code);
                }
                break;
            case 'empty':
                if (empty($data)) {
                    throw new Error($message, $code);
                }
                break;
            case 'min_length':
                if (strlen($data) < $value) {
                    throw new Error(sprintf(__('%s must be greater than or equal to %d characters'), $message, $value), $code);
                }
                break;
            case 'not_empty':
                if (!empty($data)) {
                    throw new Error($message, $code);
                }
                break;
            default:
                if ($data != null || is_numeric($data)) {
                } else {
                    throw new Error(sprintf(__('%s is required'), $message), $code);
                }
        }
    }
}
