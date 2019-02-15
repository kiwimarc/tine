<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */

use \Psr\Http\Message\RequestInterface;
use \Zend\Diactoros\Response;
use \Zend\Diactoros\Response\EmitterInterface;
use \Zend\Diactoros\Response\SapiEmitter;
use \Zend\Stratigility\MiddlewarePipe;

/**
 * Expressive Server class with handle() function
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Server_Expressive extends Tinebase_Server_Abstract implements Tinebase_Server_Interface
{
    const QUERY_PARAM_DO_EXPRESSIVE = 'doRouting';

    /**
     * the request
     *
     * @var \Zend\Diactoros\Request
     */
    protected $_request = NULL;

    /**
     * the request method
     *
     * @var string
     */
    protected $_method = NULL;

    /**
     *
     * @var boolean
     */
    protected $_supportsSessions = true;

    /**
     * @var EmitterInterface
     */
    protected $_emitter = null;

    /**
     * Tinebase_Server_Expressive constructor.
     *
     * @param EmitterInterface|null $emitter
     * @param bool $requestFromGlobals
     */
    public function __construct(EmitterInterface $emitter = null)
    {
        $this->_emitter = $emitter;
        parent::__construct();
    }

    /**
     * (non-PHPdoc)
     * @see Tinebase_Server_Interface::handle()
     * @param  \Zend\Http\Request  $request
     * @param  resource|string     $body
     * @throws Tinebase_Exception_NotImplemented
     * @return boolean
     */
    public function handle(\Zend\Http\Request $request = null, $body = null)
    {
        // TODO session handling in middle ware? this is a question!
        try {
            if (Tinebase_Session::sessionExists()) {
                try {
                    Tinebase_Core::startCoreSession();
                } catch (Zend_Session_Exception $zse) {
                    // expire session cookie for client
                    Tinebase_Session::expireSessionCookie();
                }
            }

            Tinebase_Core::initFramework();

            $this->_request = Tinebase_Core::getContainer()->get(RequestInterface::class);

            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                .' Is Routing request. uri: ' . $this->_request->getUri()->getPath() . '?'
                . $this->_request->getUri()->getQuery() . ' method: ' . $this->_request->getMethod());
            if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::'
                . __LINE__ .' REQUEST: ' . print_r($this->_request, true));

            $responsePrototype = new Response();

            $middleWarePipe = new MiddlewarePipe();
            $middleWarePipe->setResponsePrototype($responsePrototype);
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_ResponseEnvelop());
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_FastRoute());
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_CheckRouteAuth());
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_RoutePipeInject());
            $middleWarePipe->pipe(new Tinebase_Expressive_Middleware_Dispatch());


            $response = $middleWarePipe($this->_request, $responsePrototype, function() {
                throw new Tinebase_Exception('reached end of pipe stack, should never happen');
            });

            if (null === $this->_emitter) {
                $emitter = new SapiEmitter();
                $emitter->emit($response);
            } else {
                // unittesting mostly
                $this->_emitter->emit($response);
            }

        } catch (Exception $exception) {
            Tinebase_Exception::log($exception, false);
            header('HTTP/1.0 500 Service Unavailable');
            return false;
        }

        return true;
    }

    /**
     * returns request method
     *
     * @return string|NULL
     */
    public function getRequestMethod()
    {
        return null;
    }

    /**
     * @param null|bool $bool
     * @return bool
     */
    public function doRequestFromGlobals($bool = null)
    {
        $oldValue = $this->_requestFromGlobals;
        if (null !== $bool) {
            $this->_requestFromGlobals = (bool) $bool;
        }
        return $oldValue;
    }
    /**
     * @param EmitterInterface|null $emitter
     * @return null|EmitterInterface
     */
    public function setEmitter(EmitterInterface $emitter = null)
    {
        $oldEmitter = $this->_emitter;
        $this->_emitter = $emitter;
        return $oldEmitter;
    }
}
