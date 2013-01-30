<?php
class Proxy_User extends Contabilidad_Proxy
{
    
    protected static $_instance = null;

    public static function getInstance ()
    {
        if (null === self::$_instance) {
            self::$_instance = new self('user', 'VO_User');
        }
        return (self::$_instance);
    }
    
    public function createNew($params){
        $is = $this->checkEmail($params['email']);
        if ($is){
            $row = $this->createRow();
            $row->full_name = $params['full_name'];
            $row->email = $params['email'];
            $row->password = Contabilidad_Auth::encryptPassword($params['email'], $params['password']);
            $row->id_currency = 1;
            $row->nickname = $this->createNickname($params['full_name']);
            $row->creation_date = time();
            $row->save();
        }
        else
            Contabilidad_Exceptions::showException ('Este email ya existe');
    }
    
    public function createGoogleUser($params){
        $row = $this->createRow();
        $row->full_name = $params['name'];
        $row->email = $params['email'];
        $row->id_currency = 1;
        $row->nickname = $this->createNickname($params['name']);
        $row->creation_date = time();
        $row->google_id = $params['id'];
        if(isset($params['picture'])){
            $row->google_picture_url = $params['picture'];
        }
        if (isset($params ['gender'])){
            $row->gender = $params['gender'];
        }
        if (isset($params ['locale'])){
            $row->locale = $params['locale'];
        }
        $password = $this->createPassword();
        $encryptedPass = Contabilidad_Auth::encryptPassword($row->email, $password);
        $row->password = $encryptedPass;
        $row->save();
        //send email to user
//        Contabilidad_Utils_EmailTemplate::getInstance()->sendWelcomeEmailAndPassword($row, $password);
        return $row;
    }
    
    
    public function edit($user, $params){
        foreach($params as $prp => $value){
            if($prp == "email") continue;
            $user->__set($prp, $value);
        }
        $user->save();
        return $user;
    }
    
    public function addGoogleData($user, $params){
        $user->google_id = $params ['id'];
        if (isset($params ['picture'])){
                $user->google_picture_url = $params ['picture'];
        }
        if (isset($params ['gender'])){
                $user->gender = $params ['gender'];
        }
        if (isset($params ['locale'])){
                $user->locale = $params ['locale'];
        }
        $user->save();
        return $user;
    }

    public function editPassword($user, $password){
        $newPass = Contabilidad_Auth::encryptPassword($user->email, $password);
        $user->password = $newPass;
        $user->save();
        return $user;
    }

    public function checkEmail($email){
        $mailCorrect = false;
        if ((strlen($email) >= 6) && (substr_count($email,"@") == 1) && (substr($email,0,1) != "@") && (substr($email,strlen($email)-1,1) != "@")){
          if ((!strstr($email,"'")) && (!strstr($email,"\"")) && (!strstr($email,"\\")) && (!strstr($email,"\$")) && (!strstr($email," "))) {
            if (substr_count($email,".")>= 1){
                $termDom = substr(strrchr ($email, '.'),1);
              if (strlen($termDom)>1 && strlen($termDom)<5 && (!strstr($termDom,"@")) ){
                  $beforeDom = substr($email,0,strlen($email) - strlen($termDom) - 1);
                  $characterUlt = substr($beforeDom,strlen($beforeDom)-1,1);
                if ($characterUlt != "@" && $characterUlt != "."){
                    $mailCorrect = true;
                   }
                 }
              }
            }
          }


        if ($mailCorrect){
            $isEmail= $this->findByEmail($email);
            if (!$isEmail){
                return true;
            } 
               else{
                    return false;
               } 
        }    
        else{
            return false;
        }   
        }               
    
    public function findById ($id){
        return $this->getTable()->fetchRow("id = '$id'");
    }
    
    public function findByGoogleId ($id){
       // return null;
        return $this->getTable()->fetchRow("google_id = '$id'");
    }

    public function findByEmail($email){
        return $this->getTable()->fetchRow("email = '$email'");
    }
    
    public function findByNickname ($nickname){
        return $this->getTable()->fetchRow("nickname = '$nickname'");
    }

    private function createNickname ($nickname){
        $newNickname = Contabilidad_Utils_String::cleanString($nickname);
        $nickname = $newNickname;
        $suf =1;
        $ban=false;
        do{
            $is = $this->findByNickname($nickname);
            if (!$is){
                $ban = true;
            }else{
                $nickname = $newNickname . $suf;
                $suf++;
            }
        }while ($ban==false);
        return $nickname;
    }
    
    private function createPassword(){
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $string = '';
        $length = 8;
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $string;
    }

    public function serialize($user){
        $array = array();
        $array["full_name"] = $user->full_name;
        $array["email"] = $user->email;
        $array["nickname"] = $user->nickname;
        $array["id"] = $user->id;
        $array["country"] = $user->country;
        $array["city"] = $user->city;
        $array["id_currency"] = $user->id_currency;
        $array["gender"] = $user->gender;
        $array["locale"] = $user->locale;
        $array["avatarUrl"] = $user->getPictureUrl();
        return $array;
    }
    
    
    public function addAvatarUrl($user, $url){
        //checkout it is an url
        $user->picture_url = $url;
        $user->save();
    }
}