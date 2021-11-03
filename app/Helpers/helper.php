<?php

use Illuminate\Support\Facades\Redis;


function response_success( $message = '', $data = '')
{
    return [
        'status' => true,
        'message' => $message,
        'data' => $data
    ];
}

function response_error($message = '')
{
    return [
        'status' => false,
        'message' => $message,
    ];
}

function redis_cache($id='',$data = '',$expired=180)
{
    try {
        $id = json_encode($id);
        $redis_key = date("Y-m-d") . "-" .$id;
        if ( Redis::get($redis_key) == false) {
            $res = $data;
            if ($res !== '') {
                $res = json_encode($res);
                $_res = $res;
                Redis::set($redis_key,$_res,'EX', $expired);
            }else{
                return false;
            }
        }else{
            $_res = Redis::get($redis_key);
        }
        $_res = json_decode($_res);
        return $_res;
    }
    catch (Exception $e) {
        return response()->json($e,500);
    }
}
?>