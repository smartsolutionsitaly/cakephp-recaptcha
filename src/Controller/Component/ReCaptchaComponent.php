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
 * @package SmartSolutionsItaly\CakePHP\ReCaptcha\Controller\Component
 * @author Lucio Benini <dev@smartsolutions.it>
 * @since 1.0.0
 */
class ReCaptchaComponent extends Component
{
    /**
     * Nested components.
     * @var array
     * @since 1.0.0
     */
    public $components = [
        'Security',
        'Flash'
    ];

    /**
     * Validation result.
     * @var bool
     * @since 1.0.0
     */
    protected $_lastResult = true;

    /**
     * Default configuration.
     * @var array
     * @since 1.0.0
     */
    protected $_defaultConfig = [
        'field' => 'g-recaptcha-response',
        'flash' => true,
        'methods' => 'post',
        'actions' => []
    ];

    /**
     * {@inheritdoc}
     * @see \Cake\Controller\Controller::beforeFilter()
     * @since 1.0.0
     */
    public function beforeFilter(Event $event)
    {
        $request = $event->getSubject()->getRequest();

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
     * Determines whether the component is enabled.
     * @return bool A value indicating whether the component is enabled.
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
     * Processes the response.
     * @param Response $response The reCAPTCHA response.
     * @return bool The value of the last result.
     * @since 1.0.0
     */
    private function processResponse(Response $response): bool
    {
        $code = $response->getStatusCode();

        if ($code >= 200 && $code < 300) {
            $json = json_decode($response->getBody());

            if ($json->success) {
                $this->_lastResult = true;
            } else {
                $this->error(__('Invalid Captcha.'));
            }
        } else {
            $this->error(__('Captcha Error.'));
        }

        return $this->_lastResult;
    }

    /**
     * Sets last result as "False" and display a flash message, if enabled.
     * @param string $message The flash message.
     * @return bool The value of the last result. Always returns "False".
     */
    protected function error(string $message = ''): bool
    {
        if ($this->getConfig('flash') && $message) {
            $this->Flash->error($message);
        }

        return $this->_lastResult = false;
    }

    /**
     * Gets a value indicating whether the last result has been validated.
     * @return bool A value indicating whether the last result has been validated.
     * @since 1.0.0
     */
    public function getLastResult(): bool
    {
        return (bool)$this->_lastResult;
    }
}
