<?php defined('SYSPATH') or die('No direct script access.');
/**
 * OAuth authentication basic method
 *
 * @author      sumh <oalite@gmail.com>
 * @package     Oauthz
 * @copyright   (c) 2011 OALite
 * @license     ISC License (ISCL)
 * @link        http://oalite.com
 * @see         Oauthz_Authentication
 * *
 */
class Oauthz_Token_Basic extends Oauthz_Authentication {

    public function verify($token)
    {
        if($data = static::parse())
        {
            $data = $data['client_id'] === $token['client_id'] AND $data['client_secret'] === $token['client_secret'];
        }

        return $data;
    }

    public static function parse($digest = NULL)
    {
        // mod_php
        if(isset($_SERVER['PHP_AUTH_USER']) AND isset($_SERVER['PHP_AUTH_PW']))
        {
            $data = array('client_id' => $_SERVER['PHP_AUTH_USER'], 'client_secret' => $_SERVER['PHP_AUTH_PW']);
        }
        // most other servers
        elseif (isset($_SERVER['HTTP_AUTHORIZATION'])
            AND strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic') === 0)
        {
            $params = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)), 2);

            $data = array('client_id' => $params[0], 'client_secret' => $params[1]);
        }

        return empty($data) ? FALSE : $data;
    }

} // END Oauthz_Token_Basic
