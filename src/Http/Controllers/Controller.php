<?php

namespace ChinhlePa\Attestation\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message , $code)
    {
        $response = [
            'success' => true,
            'code' => $code,
            'data'    => $result,
            'message' => $message,
        ];
        return response()->json($response, 200);
    }


    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($message = [], $code = 400 , $status = 400)
    {
        if(!is_array($message)){
            $errorMessages = $message;
        }else{
            $errorMessages = implode('<br />', $message);
        }
        $response = [
            'success' => false,
            'code' => $code,
            'message' => $errorMessages,
        ];
        return response()->json($response, $status);
    }

    public function makeCert($bindata) {
        $beginpem = "-----BEGIN CERTIFICATE-----\n";
       $endpem = "-----END CERTIFICATE-----\n";
    
       $pem = $beginpem;
       $cbenc = base64_encode($bindata);
       for($i = 0; $i < strlen($cbenc); $i++) {
           $pem .= $cbenc[$i];
           if (($i + 1) % 64 == 0)
               $pem .= "\n";
       }
       $pem .= "\n".$endpem;
    
       return $pem;
    }
}