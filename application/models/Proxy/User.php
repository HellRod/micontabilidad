<?php
class Proxy_User extends Contabilidad_Proxy
{
    
    protected static $_instance = null;

    /**
     * @return Public Proxy_Primitives
     */
    public static function getInstance ()
    {
        if (null === self::$_instance) {
            self::$_instance = new self('usuario', 'VO_User');
        }
        return (self::$_instance);
    }
}