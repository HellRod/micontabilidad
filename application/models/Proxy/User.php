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
            $row->password = $params['password'];
            $row->email = $params['email'];
            $row->id_currency = 1;
            $row->nickname = $this->createNickname($params['full_name']);
            $row->creation_date = time();
            $row->save();
        }
        else
            Contabilidad_Exceptions::showException ('Este email ya existe');
        }
    
    public function checkEmail($email){
            $mail_correcto = 0;
            if ((strlen($email) >= 6) && (substr_count($email,"@") == 1) && (substr($email,0,1) != "@") && (substr($email,strlen($email)-1,1) != "@")){
                if ((!strstr($email,"'")) && (!strstr($email,"\"")) && (!strstr($email,"\\")) && (!strstr($email,"\$")) && (!strstr($email," "))) {
                    if (substr_count($email,".")>= 1){
                    $term_dom = substr(strrchr ($email, '.'),1);
                        if (strlen($term_dom)>1 && strlen($term_dom)<5 && (!strstr($term_dom,"@")) ){
                        $antes_dom = substr($email,0,strlen($email) - strlen($term_dom) - 1);
                        $caracter_ult = substr($antes_dom,strlen($antes_dom)-1,1);
                            if ($caracter_ult != "@" && $caracter_ult != "."){
                                $mail_correcto = 1;
                            }
                        }
                     }
                }
            }
            if ($mail_correcto ){
                $is= $this->findByEmail($email);
                if ($is==NULL)
                return TRUE;
                else
                return FALSE;
            }    
            else
            return FALSE;
            }
    
    public function findById ($id){
        return $this->getTable()->fetchRow("id = '$id'");
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
}