<?php defined('SYSPATH') or die('No direct script access.');
/**
 * OAuth protect layout for all API controller, which can be accessed through access_token
 *
 * @author      sumh <oalite@gmail.com>
 * @package     Oauthz
 * @copyright   (c) 2010 OALite
 * @license     ISC License (ISCL)
 * @link        http://oalite.com
 * @see         Kohana_Controller
 * *
 */
abstract class Oauthz_Api extends Kohana_Controller {

    /**
     * Config group name
     *
     * @access	protected
     * @var		string	$_type
     */
    protected $_type = 'default';

    /**
     * methods exclude from OAuth protection
     *
     * @access  protected
     * @var     array    $_exclude
     */
    protected $_exclude = array('xrds');

    /**
     * Verify the request to protected resource.
     * if unauthorized, redirect action to invalid_request
     *
     * @access  public
     * @return  void
     */
    public function __construct(Request $request)
    {
        // Exclude actions do NOT need to protect
        if( ! in_array($request->action, $this->_exclude))
        {
            $config = Kohana::config('oauth-api')->get($this->_type);

            switch(Request::$method)
            {
                case 'POST':
                    $target = $_POST;
                    break;
                case 'GET':
                    $target = $_GET;
                    break;
            }

            try
            {
                // Verify the request method supported in the config settings
                if( ! isset($target) OR empty($config['methods'][Request::$method]))
                {
                    throw new Oauthz_Exception_Access('invalid_request', Oauthz_Authentication::state($target));
                }

                $token_type = Arr::get($target, 'token_type', 'bearer');

                if( ! isset($config[$token_type]))
                {
                    throw new Oauthz_Exception_Access('invalid_grant', Oauthz_Authentication::state($target));
                }

                // Process the access token from the request header or body
                $oauth = Oauthz_Authentication::factory($token_type, $config[$token_type], $target);

                // Load the token information from database
                if( ! $token = Model_Oauthz::factory('Token')
                    ->access_token($oauth->token()))
                {
                    throw new Oauthz_Exception_Access('unauthorized_client', Oauthz_Authentication::state($target));
                }

                if($token['expires_in'] < $_SERVER['REQUEST_TIME'])
                {
                    throw new Oauthz_Exception_Access('invalid_grant', Oauthz_Authentication::state($target));
                }

                // Verify the access token
                $oauth->verify($token);
            }
            catch (Oauthz_Exception $e)
            {
                $this->exception = $e;

                // Redirect the action to unauthenticated
                $request->action = 'unauthenticated';
            }
        }

        parent::__construct($request);
    }

    /**
     * Unauthorized response, only be called from internal
     *
     * @access	public
     * @param	string	$error
     * @return	void
     * @todo    Add list of error codes
     */
    public function action_unauthenticated()
    {
        if($this->exception->error === 'invalid_client')
        {
            // HTTP/1.1 401 Unauthorized
            $this->request->status = 401;
            // TODO: If the client attempted to authenticate via the "Authorization" request header field
            // the "WWW-Authenticate" response header field matching the authentication scheme used by the client.
            // $this->request->headers['WWW-Authenticate'] = 'OAuth2 realm=\'Service\','.http_build_query($error, '', ',');
        }
        else
        {
            // HTTP/1.1 400 Bad Request
            $this->request->status = 400;
        }

        $this->request->headers['Content-Type']     = 'application/json';
        $this->request->headers['Expires']          = 'Sat, 26 Jul 1997 05:00:00 GMT';
        $this->request->headers['Cache-Control']    = 'no-store, must-revalidate';
        $this->request->response                    = $this->exception->as_json();
    }

    /**
     * OAuth server auto-discovery for user
     *
     * @access	public
     * @return	void
     * @todo    Add list of error codes
     */
    public function action_xrds()
    {
        $this->request->headers['Content-Type'] = 'application/xrds+xml';
        $this->request->response = View::factory('oauth-server-xrds')->render();
    }

} // END Oauthz_Api
