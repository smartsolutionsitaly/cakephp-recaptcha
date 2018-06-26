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
 * @category  cakephp-plugin
 * @package   cakephp-recaptcha
 * @author    Lucio Benini <dev@smartsolutions.it>
 * @copyright 2018 Smart Solutions S.r.l. (https://smartsolutions.it)
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 * @link      https://smartsolutions.it Smart Solutions
 * @since     1.0.0
 */
namespace SmartSolutionsItaly\CakePHP\ReCaptcha\Controller\Component;

use Cake\Controller\Component;
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
class ReCaptchaComponent extends Component
{

    /**
     * Nested components.
     *
     * @var array
     * @since 1.0.0
     */
    public $components = [
        'Security',
        'Flash'
    ];

    /**
     * Validation result.
     *
     * @var bool "True" if the last result was validated; otherwise "False".
     * @since 1.0.0
     */
    protected $_lastResult = true;

    /**
     * Default configuration.
     *
     * @var array
     * @since 1.0.0
     */
    protected $_defaultConfig = [
        'field' => 'g-recaptcha-response',
        'flash' => true,
        'methods' => 'post'
    ];

    /**
     * Initialize config, data and properties.
     *
     * @param array $config
     *            The config data.
     * @return void
     * @since 1.0.5
     */
    public function initialize(array $config)
    {
        $config = $config + $this->_defaultConfig;
        
        $this->setConfig('field', $config['field']);
        $this->setConfig('flash', (bool) $config['flash']);
        $this->setConfig('methods', $config['methods']);
    }

    /**
     *
     * {@inheritdoc}
     *
     * @see \Cake\Controller\Controller::beforeFilter()
     * @since 1.0.0
     */
    public function beforeFilter(Event $event)
    {
        $request = $this->getController()->getRequest();
        
        if ($request->is($this->getConfig('methods')) && $this->isEnabled()) {
            $this->Security->setConfig('unlockedFields', $this->getConfig('field'));
            
            if ($data = $request->getData($this->getConfig('field'))) {
                $client = new Client([
                    'host' => 'www.google.com/recaptcha/api',
                    'scheme' => 'https'
                ]);
                
                $res = $client->post('/siteverify', [
                    'secret' => Configure::read('Google.recaptcha.secretKey'),
                    'response' => $data
                ]);
                
                $this->processResponse($res);
            } else {
                $this->error(__('Empty Captcha.'));
            }
        } else {
            $this->_lastResult = true;
        }
        
        return true;
    }

    /**
     * Gets the last result of the validation.
     *
     * @return bool "True" if the last result was validated; otherwise "False".
     * @since 1.0.0
     */
    public function getLastResult(): bool
    {
        return (bool) $this->_lastResult;
    }

    /**
     * Determines wheter the component is enabled.
     *
     * @return bool "True" if the captcha is enable; otherwise "False".
     * @since 1.0.0
     */
    protected function isEnabled(): bool
    {
        $request = $this->getController()->getRequest();
        
        if (is_array($this->getConfig('action'))) {
            return in_array($request->getParam('action'), $this->getConfig('action'));
        } else {
            return $request->getParam('action') == $this->getConfig('action');
        }
    }

    /**
     * Sets lat result as "False" and display a flash message, if enabled.
     * 
     * @param string $message The flash message.
     */
    protected function error(string $message = '')
    {
        if ($this->getConfig('flash')) {
            $this->Flash->error($message);
        }
        
        $this->_lastResult = false;
    }

    /**
     * Processes the response.
     *
     * @param Response $response
     *            The reCAPTCHA response.
     * @since 1.0.0
     */
    private function processResponse(Response $response)
    {
        $code = $response->getStatusCode();
        
        if ($code >= 200 && $code < 300) {
            $json = json_decode($response->getBody());
            
            if (! $json->success) {
                $this->error(__('Invalid Captcha.'));
            }
        } else {
            $this->error(__('Captcha Error.'));
        }
    }
}
