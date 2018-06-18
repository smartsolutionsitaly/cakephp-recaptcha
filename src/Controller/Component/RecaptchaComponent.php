<?php
/**
 * cakephp-recaptcha (https://github.com/smartsolutionsitaly/cakephp-recaptcha)
 * Copyright (c) 2018 Smart Solutions S.r.l. (https://smartsolutions.it)
 *
 * reCAPTCHA component for CakePHP
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP version 7.1
 *
 * @category  cakephp-plugin
 * @package   cakephp-recaptcha
 * @author    Lucio Benini <dev@smartsolutions.it>
 * @copyright 2018 Smart Solutions S.r.l. (https://smartsolutions.it)
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      https://smartsolutions.it Smart Solutions
 * @since     1.0.0
 */
namespace SmartSolutionsItaly\ReCaptcha\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Http\Client;
use Cake\Http\Client\Response;

/**
 * reCAPTCHA component.
 *
 * @author Lucio Benini <dev@smartsolutions.it>
 * @since 1.0.0
 */
class RecaptchaComponent extends Component
{

    /**
     * Nested components.
     *
     * @var array
     */
    public $components = [
        'Security',
        'Flash'
    ];

    /**
     * Validation result.
     *
     * @var bool "True" if the last result was validated; otherwise "False".
     */
    protected $_lastResult = true;

    /**
     * Default configuration.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'field' => 'g-recaptcha-response',
        'flash' => 'g-recaptcha-response'
    ];

    /**
     * Contructor.
     *
     * @param ComponentRegistry $collection            
     * @param array $config            
     */
    public function __construct(ComponentRegistry $collection, $config = [])
    {
        parent::__construct($collection, $config + $this->_defaultConfig);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Cake\Controller\Controller::beforeFilter()
     */
    public function beforeFilter(Event $event)
    {
        $request = $this->getController()->getRequest();
        
        if ($request->is('post') && $this->isEnabled()) {
            $this->Security->setConfig('unlockedFields', $this->_config['field']);
            
            $data = $event->getSubject()->request->getData();
            
            if (! empty($data[$this->_config['field']])) {
                $client = new Client([
                    'host' => 'www.google.com/recaptcha/api/',
                    'scheme' => 'https'
                ]);
                
                $res = $client->post('/siteverify', [
                    'body' => [
                        'secret' => Configure::read('Google.recaptcha.secretKey'),
                        'response' => $data[$this->_config['field']]
                    ]
                ]);
                
                $this->processResponse($res);
            } else {
                if ($this->_config['flash']) {
                    $this->Flash->error(__('Empty Captcha.'));
                }
                
                $this->_lastResult = false;
            }
            
            return true;
        }
        
        $this->_lastResult = true;
        
        return true;
    }

    /**
     * Gets the last result of the validation.
     *
     * @return bool "True" if the last result was validated; otherwise "False".
     */
    public function getLastResult(): bool
    {
        return (bool) $this->_lastResult;
    }

    /**
     * Determines wheter the component is enabled.
     *
     * @return bool "True" if the captcha is enable; otherwise "False".
     */
    protected function isEnabled(): bool
    {
        $request = $this->getController()->getRequest();
        
        if (is_array($this->_config['action'])) {
            return in_array($request->getParam('action'), $this->_config['action']);
        } else {
            return $request->getParam('action') == $this->_config['action'];
        }
    }

    /**
     * Processes the response.
     *
     * @param Response $response The reCAPTCHA response.
     */
    private function processResponse(Response $response)
    {
        $code = $response->getStatusCode();

        if ($code >= 200 && $code < 300) {
            $json = json_decode($response->getBody());
            
            if (! $json->success) {
                if ($this->_config['flash']) {
                    $this->Flash->error(__('Invalid Captcha.'));
                }
                
                $this->_lastResult = false;
            }
        } else {
            if ($this->_config['flash']) {
                $this->Flash->error(__('Captcha Error.'));
            }
            
            $this->_lastResult = false;
        }
    }
}
