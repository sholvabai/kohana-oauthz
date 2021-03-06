<?php defined('SYSPATH') or die('No direct script access.');
/**
 * OAuth Exception
 *
 * @author      sumh <oalite@gmail.com>
 * @package     Oauthz
 * @copyright   (c) 2011 OALite
 * @license     ISC License (ISCL)
 * @link        http://oalite.com
 * *
 */
class Oauthz_Exception_Authorize extends Oauthz_Exception {

	public function __construct($message, array $state = NULL, $code = 0)
	{
        $error_description = I18n::get('Authorization Errors Response');

        $this->error_description = $error_description[$message];

		// Pass the message to the parent
		parent::__construct($message, $state, $code);
	}

} // END Oauthz_Exception_Authorize
