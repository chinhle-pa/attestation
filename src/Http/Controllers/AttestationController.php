<?php

namespace ChinhlePa\Attestation\Http\Controllers;
use Illuminate\Http\Request;
use Validator;
use ChinhlePa\Attestation\Models\Attestation;

use CBOR\Decoder;
use CBOR\StringStream;

use Google\Client;
use Google\Service\PlayIntegrity;
use Google\Service\PlayIntegrity\DecodeIntegrityTokenRequest;

class AttestationController extends Controller
{
    private String $challenge;
    private String $integrityToken;
    private String $keyIndentifier;
    private Array $messages;

    public function verifyAttestation(Request $request) {
        $header = [];
        foreach($request->header() as $key => $value){
            $header[$key] = trim($value[0]);
        }
        $val_rq = array_merge($header, $request->all());

        # validate body
        $validator = Validator::make($val_rq, [
            'device-platform' => 'required',
            'challenge' => 'required',
            'integrityToken' => 'required',
            'keyIndentifier' => 'required_if:device-platform,iOS',
        ]);
        
        if ($validator->fails()) {
            return $this->sendError($validator->errors()->all(), 400, 400);
        }

        $this->challenge = $request->challenge;
        $this->integrityToken = $request->integrityToken;
        $this->deviceOs = $request->header('device-platform');
        $this->keyIndentifier =  $request->keyIndentifier ?? '';

        if( $this->_verify() ) {
            //get response from challenge key then return
            $response = Attestation::where('challenge', $this->challenge)->first();
            if(empty($response)){
                return $this->sendError(__('attestation::frontend.challenge_key_incorrect'), 400, 400); 
            } else {
                $response =  json_decode($response->response);
            }
            return $response;
        } else {
            return $this->sendError($this->messages, 400, 400);
            // return $this->sendError("Please check your app and device!", 400, 400);
        }
    }
    private function _verify() {
        if($this->deviceOs === "Android") {
            return $this->_verifyAndroid();
        } elseif($this->deviceOs === "iOS") {
            return $this->_verifyiOS();
        }
        return false;
    }
    private function _verifyiOS() {
        $decoder = new Decoder();
        $encoded_data = $this->integrityToken;

        $object = $decoder->decode(new StringStream(base64_decode($encoded_data)));

        $norm = $object->getNormalizedData();
        $fmt = $norm['fmt'];
        $x5c = $norm['attStmt']['x5c'];

        $cred_cert = openssl_x509_read($this->makeCert($x5c[0]));
        $ca_cert = $this->makeCert($x5c[1]);
        $pubkeyid = file_get_contents(config("attestation.{$this->deviceOs}.PUBLIC_KEY_URL"));

        /**
         * Step 1: Verify cert chain in attestation object against Apple trusted root
         */
        if( openssl_x509_verify($this->makeCert($x5c[1]), openssl_x509_read($pubkeyid)) != openssl_x509_verify($this->makeCert($x5c[0]), openssl_x509_read($this->makeCert($x5c[1]))) ) {
            $this->messages[] = __('attestation::frontend.step_1_fails');
            return false;
        }

        /**
         * Step 2: Append clientDataHash of the one time challenge to authData
         */
        $challenge = $this->challenge;
        $client_data_hash = hash('sha256', $challenge, true);
        $composite_item = $norm['authData'].$client_data_hash;

        /**
         * Step 3: Generate a new SHA256 hash of the composite item to create nonce. 
         */
        $nonce = hash('sha256', $composite_item, true);
        $seq = \Sop\ASN1\Type\UnspecifiedType::fromDER($x5c[0])->asSequence();
        $greeting = $seq->at(0)->asSequence()->at(0)->asTagged()->asExplicit()->asInteger()->intNumber();
        // $nonce_oid =$seq->at(0)->asSequence()->at(1)->asInteger()->intNumber();
        $nonce_oid =$seq->at(0)->asSequence()->at(7)->asTagged()->asExplicit()->asSequence()->at(4)->asSequence()->at(0)->asObjectIdentifier()->oid();
        // $nonce =$seq->at(0)->asSequence()->at(7)->asTagged()->asExplicit()->asSequence()->at(4)->asSequence()->at(1)->asOctetString();
        $nonce_in_attest =substr($seq->at(0)->asSequence()->at(7)->asTagged()->asExplicit()->asSequence()->at(4)->asSequence()->at(1)->asOctetString()->string(), -32);

        /**
         * Step 4: Verify that decode the sequence and extract the single octet string equals nonce
         */

        if( $nonce != $nonce_in_attest ) {
            $this->messages[] = __('attestation::frontend.step_4_fails');
            return false;
        }

        /**
         * Step 5: Create the SHA256 hash of the public key in credCert, and verify that it matches the key identifier from your app.
         */

        //Skipped

        /**
         * Step 6: Compute the SHA256 hash of your app’s App ID, and verify that it’s the same as the authenticator data’s RP ID hash.
         */
        $app_id = config("attestation.{$this->deviceOs}.DEVELOPER_ID").".".config("attestation.{$this->deviceOs}.PACKGE_NAME");
        $auth_data = $norm['authData'];
        $rp_id_hash = substr($auth_data,0, 32);
        $app_id_hash = hash('sha256', $app_id, true);
        if ($app_id_hash != $rp_id_hash) {
            $this->messages[] = __('attestation::frontend.step_6_fails');
            return false;
        }

        /**
         * Step 7: Verify that the authenticator data’s counter field equals 0.
         */
        $auth_data = substr($auth_data, 32);
        $auth_data = substr($auth_data,1);
        $counter_bytes = substr($auth_data, 0, 4);

        if(unpack("n",$counter_bytes)[1] != 0){
            $this->messages[] = __('attestation::frontend.step_7_fails');
            return false;
        }

        /**
         * Step 8: Verify that the authenticator data’s aaguid field is either appattestdevelop if operating in the development environment, or appattest followed by seven 0x00 bytes if operating in the production environment.
         */
        $auth_data = substr($auth_data,4);
        $aaguid = substr($auth_data, 0, 16);
        if( $aaguid != 'appattestdevelop' && $aaguid != 'appattest0000000' ){
            $this->messages[] = __('attestation::frontend.step_8_fails');
            return false;
        }

        /**
         * Step 9: Verify that the authenticator data’s credentialId field is the same as the key identifier.
         */
        $auth_data = substr($auth_data, 16);
        $key_indentifier = $this->keyIndentifier; //from apple
        $cred_id_len_bytes = substr($auth_data, 0, 2);
        $auth_data = substr($auth_data, 2);
        $cred_id_len = unpack("n",$cred_id_len_bytes)[1];
        $cred_id_bytes = substr($auth_data, 0, $cred_id_len);
        // dd(base64_encode($cred_id_bytes));

        if( $key_indentifier != base64_encode($cred_id_bytes)){
            $this->messages[] = __('attestation::frontend.step_9_fails');
            return false;
        }

        return true;
    }
    private function _verifyAndroid() {
        $client = new Client();
        $client->setAuthConfig(config('attestation.Android.AUTH_CONFIG_URL'));
        $client->addScope(PlayIntegrity::PLAYINTEGRITY);
        $service = new PlayIntegrity($client);
        $tokenRequest = new DecodeIntegrityTokenRequest();
        $tokenRequest->setIntegrityToken($this->integrityToken);
        $result = $service->v1->decodeIntegrityToken(config('attestation.Android.PACKGE_NAME'), $tokenRequest);

        //check result logic here
        $requestDetails = $result->tokenPayloadExternal->requestDetails;
        $requestPackageName = $requestDetails->requestPackageName;
        $nonce = $requestDetails->nonce;
        $timestampMillis = $requestDetails->timestampMillis;

        // Ensure the token is from your app.
        // Ensure the token is for this specific request. See “Generate nonce” section of the doc on how to store/compute the expected nonce.
        // Ensure the freshness of the token.
        if ($requestPackageName != config('attestation.Android.PACKGE_NAME') || $nonce != $this->challenge || time() - $timestampMillis > config('attestation.Android.ALLOWED_WINDOW_MILLIS')) {
            // The token is invalid! See below for further checks.
            $this->messages[] = __('attestation::frontend.the-token-is-invalid-see-below-for-further-checks');
            return false;
        }

        $appIntegrity = $result->tokenPayloadExternal->appIntegrity;
        $appRecognitionVerdict = $appIntegrity->appRecognitionVerdict;

        if($appRecognitionVerdict == 'UNEVALUATED') {
            $this->messages[] = __('Application integrity was not evaluated.');
            return false;
        } elseif( $appRecognitionVerdict != 'PLAY_RECOGNIZED'){
            $this->messages[] = __('attestation::frontend.the-app-and-certificate-not-match-the-versions-distributed-by-google-play');
            return false;
        }

        $deviceIntegrity = $result->tokenPayloadExternal->deviceIntegrity;
        $deviceRecognitionVerdict = $deviceIntegrity->deviceRecognitionVerdict;
        if (!in_array("MEETS_DEVICE_INTEGRITY", $deviceRecognitionVerdict)) {
            // echo "The app isn't running on an Android device powered by Google Play services.";
            $this->messages[] = __("attestation::frontend.the-app-isnt-running-on-an-android-device-powered-by-google-play-services");
            return false;
        }

        $accountDetails = $result->tokenPayloadExternal->accountDetails;
        $licensingVerdict = $accountDetails->appLicensingVerdict;

        if ($licensingVerdict !== "LICENSED") {
            // Looks good!
            $this->messages[] = __("attestation::frontend.unlicensed");
            return false;
        }
        return true;
    }


}