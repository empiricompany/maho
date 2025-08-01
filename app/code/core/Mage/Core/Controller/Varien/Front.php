<?php

/**
 * Maho
 *
 * @package    Mage_Core
 * @copyright  Copyright (c) 2006-2020 Magento, Inc. (https://magento.com)
 * @copyright  Copyright (c) 2019-2025 The OpenMage Contributors (https://openmage.org)
 * @copyright  Copyright (c) 2024-2025 Maho (https://mahocommerce.com)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * @method ?Mage_Core_Controller_Varien_Action getAction()
 * @method $this setAction(Mage_Core_Controller_Varien_Action $value)
 * @method bool getNoRender()
 */
class Mage_Core_Controller_Varien_Front extends Varien_Object
{
    protected $_defaults = [];

    /**
     * Available routers array
     *
     * @var array
     */
    protected $_routers = [];

    protected $_urlCache = [];

    public const XML_STORE_ROUTERS_PATH = 'web/routers';

    /**
     * @param array|string $key
     * @param string|null $value
     * @return $this
     */
    public function setDefault($key, $value = null)
    {
        if (is_array($key)) {
            $this->_defaults = $key;
        } else {
            $this->_defaults[$key] = $value;
        }
        return $this;
    }

    /**
     * @param string|null $key
     * @return array|false
     */
    public function getDefault($key = null)
    {
        if (is_null($key)) {
            return $this->_defaults;
        } elseif (isset($this->_defaults[$key])) {
            return $this->_defaults[$key];
        }
        return false;
    }

    /**
     * Retrieve request object
     *
     * @return Mage_Core_Controller_Request_Http
     */
    public function getRequest()
    {
        return Mage::app()->getRequest();
    }

    /**
     * Retrieve response object
     *
     * @return Mage_Core_Controller_Response_Http
     */
    public function getResponse()
    {
        return Mage::app()->getResponse();
    }

    /**
     * Adding new router
     *
     * @param   string $name
     * @return  Mage_Core_Controller_Varien_Front
     */
    public function addRouter($name, Mage_Core_Controller_Varien_Router_Abstract $router)
    {
        $router->setFront($this);
        $this->_routers[$name] = $router;
        return $this;
    }

    /**
     * Retrieve router by name
     *
     * @param   string $name
     * @return  Mage_Core_Controller_Varien_Router_Abstract|false
     */
    public function getRouter($name)
    {
        return $this->_routers[$name] ?? false;
    }

    /**
     * Retrieve routers collection
     *
     * @return array
     */
    public function getRouters()
    {
        return $this->_routers;
    }

    /**
     * Init Front Controller
     *
     * @return $this
     */
    public function init()
    {
        Mage::dispatchEvent('controller_front_init_before', ['front' => $this]);

        $routersInfo = Mage::app()->getStore()->getConfig(self::XML_STORE_ROUTERS_PATH);

        Varien_Profiler::start('mage::app::init_front_controller::collect_routers');
        foreach ($routersInfo as $routerCode => $routerInfo) {
            if (isset($routerInfo['disabled']) && $routerInfo['disabled']) {
                continue;
            }
            if (isset($routerInfo['class'])) {
                /** @var Mage_Core_Controller_Varien_Router_Standard $router */
                $router = new $routerInfo['class']();
                if (isset($routerInfo['area'])) {
                    $router->collectRoutes($routerInfo['area'], $routerCode);
                }
                $this->addRouter($routerCode, $router);
            }
        }
        Varien_Profiler::stop('mage::app::init_front_controller::collect_routers');

        Mage::dispatchEvent('controller_front_init_routers', ['front' => $this]);

        // Add default router at the last
        $default = new Mage_Core_Controller_Varien_Router_Default();
        $this->addRouter('default', $default);

        return $this;
    }

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function dispatch()
    {
        $request = $this->getRequest();

        // If pre-configured, check equality of base URL and requested URL
        $this->_checkBaseUrl($request);

        $this->checkTrailingSlash($request);

        $request->setPathInfo()->setDispatched(false);

        if (!Mage::app()->getStore()->isAdmin()) {
            $this->_getRequestRewriteController()->rewrite();
        }

        Varien_Profiler::start('mage::dispatch::routers_match');
        $i = 0;
        while (!$request->isDispatched() && $i++ < 100) {
            foreach ($this->_routers as $router) {
                /** @var Mage_Core_Controller_Varien_Router_Abstract $router */
                if ($router->match($request)) {
                    break;
                }
            }
        }
        Varien_Profiler::stop('mage::dispatch::routers_match');
        if ($i > 100) {
            Mage::throwException('Front controller reached 100 router match iterations');
        }
        // This event gives possibility to launch something before sending output (allow cookie setting)
        Mage::dispatchEvent('controller_front_send_response_before', ['front' => $this]);
        Varien_Profiler::start('mage::app::dispatch::send_response');
        $this->getResponse()->sendResponse();
        Varien_Profiler::stop('mage::app::dispatch::send_response');
        Mage::dispatchEvent('controller_front_send_response_after', ['front' => $this]);
        return $this;
    }

    /**
     * Returns request rewrite instance.
     * Class name alias is declared in the configuration
     *
     * @return Mage_Core_Model_Url_Rewrite_Request
     */
    protected function _getRequestRewriteController()
    {
        $className = (string) Mage::getConfig()->getNode('global/request_rewrite/model');
        /** @var Mage_Core_Model_Url_Rewrite_Request $model */
        $model = Mage::getSingleton('core/factory')->getModel($className, [
            'routers' => $this->getRouters(),
        ]);
        return $model;
    }

    /**
     * Returns router instance by route name
     *
     * @param string $routeName
     * @return Mage_Core_Controller_Varien_Router_Abstract
     */
    public function getRouterByRoute($routeName)
    {
        // empty route supplied - return base url
        if (empty($routeName)) {
            $router = $this->getRouter('standard');
        } elseif ($this->getRouter('admin')->getFrontNameByRoute($routeName)) {
            // try standard router url assembly
            $router = $this->getRouter('admin');
        } elseif ($this->getRouter('standard')->getFrontNameByRoute($routeName)) {
            // try standard router url assembly
            $router = $this->getRouter('standard');
        } elseif ($router = $this->getRouter($routeName)) {
            // try custom router url assembly
        } else {
            // get default router url
            $router = $this->getRouter('default');
        }

        return $router;
    }

    /**
     * @param string $frontName
     * @return false|Mage_Core_Controller_Varien_Router_Standard
     */
    public function getRouterByFrontName($frontName)
    {
        // empty route supplied - return base url
        if (empty($frontName)) {
            $router = $this->getRouter('standard');
        } elseif ($this->getRouter('admin')->getRouteByFrontName($frontName)) {
            // try standard router url assembly
            $router = $this->getRouter('admin');
        } elseif ($this->getRouter('standard')->getRouteByFrontName($frontName)) {
            // try standard router url assembly
            $router = $this->getRouter('standard');
        } elseif ($router = $this->getRouter($frontName)) {
            // try custom router url assembly
        } else {
            // get default router url
            $router = $this->getRouter('default');
        }

        return $router;
    }

    /**
     * Apply configuration rewrites to current url
     *
     * @deprecated since 1.7.0.2. Refactored and moved to Mage_Core_Controller_Request_Rewrite
     */
    public function rewrite()
    {
        $request = $this->getRequest();
        $config = Mage::getConfig()->getNode('global/rewrite');
        if (!$config) {
            return;
        }
        foreach ($config->children() as $rewrite) {
            $from = (string) $rewrite->from;
            $to = (string) $rewrite->to;
            if (empty($from) || empty($to)) {
                continue;
            }
            $from = $this->_processRewriteUrl($from);
            $to   = $this->_processRewriteUrl($to);

            $pathInfo = preg_replace($from, $to, $request->getPathInfo());

            if (isset($rewrite->complete)) {
                $request->setPathInfo($pathInfo);
            } else {
                $request->rewritePathInfo($pathInfo);
            }
        }
    }

    /**
     * Replace route name placeholders in url to front name
     *
     * @param   string $url
     * @return  string
     * @deprecated since 1.7.0.2. Refactored and moved to Mage_Core_Controller_Request_Rewrite
     */
    protected function _processRewriteUrl($url)
    {
        $startPos = strpos($url, '{');
        if ($startPos !== false) {
            $endPos = strpos($url, '}');
            $routeName = substr($url, $startPos + 1, $endPos - $startPos - 1);
            $router = $this->getRouterByRoute($routeName);
            if ($router) {
                $fronName = $router->getFrontNameByRoute($routeName);
                $url = str_replace('{' . $routeName . '}', $fronName, $url);
            }
        }
        return $url;
    }

    /**
     * Auto-redirect to base url (without SID) if the requested url doesn't match it.
     * By default this feature is enabled in configuration.
     *
     * @param Mage_Core_Controller_Request_Http $request
     */
    protected function _checkBaseUrl($request)
    {
        if (!Mage::isInstalled() || $request->getPost() || strtolower($request->getMethod()) == 'post') {
            return;
        }

        $redirectCode = Mage::getStoreConfigAsInt('web/url/redirect_to_base');
        if (!$redirectCode) {
            return;
        } elseif ($redirectCode != 301) {
            $redirectCode = 302;
        }

        if ($this->_isAdminFrontNameMatched($request)) {
            return;
        }

        $baseUrl = Mage::getBaseUrl(
            Mage_Core_Model_Store::URL_TYPE_WEB,
            Mage::app()->getStore()->isCurrentlySecure(),
        );
        if (!$baseUrl) {
            return;
        }

        $uri = @parse_url($baseUrl);
        $requestUri = $request->getRequestUri() ?: '/';
        if (isset($uri['scheme']) && $uri['scheme'] != $request->getScheme()
            || isset($uri['host']) && $uri['host'] != $request->getHttpHost()
            || isset($uri['path']) && !str_contains($requestUri, $uri['path'])
        ) {
            Mage::app()->getFrontController()->getResponse()
                ->setRedirect($baseUrl, $redirectCode)
                ->sendResponse();
            exit;
        }
    }

    /**
     * Redirect to canonical URL if trailing slash doesn't match configured behavior
     */
    protected function checkTrailingSlash(Mage_Core_Controller_Request_Http $request): void
    {
        if (!Mage::isInstalled() || $request->getPost() || strtolower($request->getMethod()) === 'post') {
            return;
        }
        if ($this->_isAdminFrontNameMatched($request)) {
            return;
        }

        $requestUri = $request->getRequestUri();

        $canonicalUri = Mage::helper('core/url')->addOrRemoveTrailingSlash($requestUri);

        if ($canonicalUri !== $requestUri) {
            Mage::app()->getFrontController()->getResponse()
                ->setRedirect($canonicalUri, 301)
                ->sendResponse();
            exit;
        }
    }

    /**
     * Check if requested path starts with one of the admin front names
     *
     * @param Zend_Controller_Request_Http $request
     * @return bool
     */
    protected function _isAdminFrontNameMatched($request)
    {
        return Mage::helper('adminhtml')->isAdminFrontNameMatched($request->getPathInfo());
    }
}
