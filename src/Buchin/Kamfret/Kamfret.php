<?php namespace Buchin\Kamfret;

use Config;
use User;
use Log;

class Kamfret {

    public function registerUrl($user_id)
    {
        return  $user_id . ';SecurityKey;15;'.url('fingerprints/register/' . $user_id).';' . url('fingerprints/ac');

    }

    public function register($id, $serialized_data)
    {
        $result = array(
            'verified' => false,
            'user' => null,
            'message' => '',
        );


        $result['user'] = User::find($id);

        if($result['user'] == null){
            $result['message'] = 'User not found';
            return $result;
        }

        try{
            $data = Kamfret::decodeRegistrationData($serialized_data);
            if(empty($data)){
                $result['message'] = 'Error decoding fingerprint data';
                return $result;
            }
            
            if(Kamfret::isValidRegistration($result['user'], $data)){
                $result['user']->fingerprints = $data['regTemp'];
                if($result['user']->save()){
                    $result['verified'] = true;
                    $result['message'] = 'Fingerprints template successfully registered';

                    return $result;
                }
                else{
                    $result['message'] = 'Error saving fingerprint';
                    return $result;
                }

            }
            else{
                $result['message'] = 'Data is not valid';
                return $result;
            }
        }
        catch(Exception $e){
            $result['message'] = $e->getMessage();
            return $result;
        }

        return $result;
    }

    public function verificationUrl($user, $extra = array())
    {
        $query_string = http_build_query($extra);
        return $user->id . ";". $user->fingerprints.";SecurityKey;". '15' .";". url('fingerprints/verify/' . $user->id . '?' . $query_string) .";". url('fingerprints/ac');
    }

    public function getDeviceByVc($vc)
    {
        $devices = Config::get('kamfret::devices');
        foreach ($devices as $device) {
            if($device['vc'] === $vc){
                return $device;
            }
        }

        return false;
    }

    public function getDeviceBySn($sn)
    {
        $devices = Config::get('kamfret::devices');
        foreach ($devices as $device) {
            if($device['sn'] === $sn){
                return $device;
            }
        }

        return false;
    }

    public function decodeRegistrationData($serialized_data)
    {   
        @list($vStamp, $sn, $user_id, $regTemp) = explode(";", $serialized_data);
        if( !isset($vStamp) || !isset($sn) || !isset($user_id) || !isset($regTemp)){
            return array();
        }
        
        return array(
            'vStamp'     =>  $vStamp,
            'sn'         =>  $sn,
            'user_id'    =>  $user_id,
            'regTemp'    =>  $regTemp,
        );
        
    }

    public function isValidRegistration($user, $data)
    {
        
        if(!empty($user->fingerprints) || $user->id !== $data['user_id']){
            return false;
        }
        
        $device = Kamfret::getDeviceBySn($data['sn']);
        
        $salt = md5($device['ac'].$device['vkey'].$data['regTemp'].$data['sn'].$data['user_id']);
        return (strtoupper($data['vStamp']) == strtoupper($salt)) ? true : false;
    }

    public function getDevices()
    {
        return Config::get('kamfret::devices');
    }

    public function verify($id, $serialized_data)
    {
        $verified = false;
        $message = '';

        try{
            $user = User::findOrFail($id);
        }
        catch(Exception $e){
            $message = 'User not found';

            $result = array(
                'verified' => $verified,
                'user' => null,
                'message' => $message,
            );

            return $result;
        }

        @list($user_id, $vStamp, $time, $sn) = explode(";", $serialized_data);
        if( !isset($user_id) || !isset($vStamp) || !isset($time) || !isset($sn)){
            $message = 'Incorrect fingerprint data';

            $result = array(
                'verified' => $verified,
                'user' => $user,
                'message' => $message,
            );

            return $result;
        }
        

        if($user->id != $user_id){
            $message =  'User mismatch';

            $result = array(
                'verified' => $verified,
                'user' => $user,
                'message' => $message,
            );

            return $result;
        }

        if(empty($user->fingerprints)){
            $message =  'Fingerprints unregistered';

            $result = array(
                'verified' => $verified,
                'user' => $user,
                'message' => $message,
            );

            return $result;
        }
        
        $fingerData = $user->fingerprints;
        $device     = Kamfret::getDeviceBySn($sn);
            
        $salt = md5($sn.$fingerData.$device['vc'].$time.$user_id.$device['vkey']);
        
        if(strtoupper($vStamp) == strtoupper($salt)){
            $result = array(
                'verified' => true,
                'user' => $user,
                'message' => 'Verication success',
            );

            return $result;
        }

        $result = array(

            'user' => $user,
            'message' => 'Fingerprint mismatch',
        );

        return $result;
    }



    // Helpers
    public function getRegistrationLink($id)
    {
        return 'finspot:FingerspotReg;' . base64_encode(url('fingerprints/register/' . $id));
    }

    public function getVerificationLink($id, $extras = array('action' => 'login'))
    {
        $url = url('fingerprints/verify/' . $id . '?' . http_build_query($extras));
        return 'finspot:FingerspotVer;' . base64_encode($url);
    }

}