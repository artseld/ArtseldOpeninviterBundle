<?php

/*
 * This file is part of the Artseld\OpeninviterBundle package.
 *
 * (c) Dmitry Kozlovich <artseld@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * ====================================================
 * Based on original openinviter class code
 * ====================================================
 */

namespace Artseld\OpeninviterBundle\ArtseldOpeninviter;

use Symfony\Component\DependencyInjection\Container;

// Require library classes
require_once(__DIR__ . '/../Openinviter/openinviter.php');
require_once(__DIR__ . '/../Openinviter/plugins/_base.php');

class ArtseldOpeninviter extends \openinviter
{
    protected $container;
    protected $settings;

    protected $version          = '1.9.6';
    protected $availablePlugins = array();
    protected $currentPlugin    = array();
    protected $internalParams   = array();

    protected $customPlugins    = array('gmail', 'yandex');

    /**
     * Construct Openinviter instance
     * @param \Symfony\Component\DependencyInjection\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->basePath = __DIR__ . '/../Openinviter/';
        $this->settings = $container->getParameter('artseld_openinviter.settings');
        // Settings checked by Symfony native mechanism
    }

    /**
     * Get plugins list
     * @param bool $update
     * @param bool $required_details
     * @return array
     */
    public function getPlugins( $update = false, $requiredDetails = false )
    {
        $plugins = array();
        if ($requiredDetails) {
            $valid_rcache = false;
            $cache_rpath = $this->settings['cookie_path'] . '/' . "int_{$requiredDetails}.php";
            if (file_exists($cache_rpath)) {
                include($cache_rpath);
                $cache_rts = filemtime($cache_rpath);
                if (time() - $cache_rts <= $this->settings['plugins_cache_time']) {
                    $valid_rcache = true;
                }
            }
            if ($valid_rcache) {
                return $returnPlugins;
            }
        }
        $cache_path = $this->settings['cookie_path'] . '/' . $this->settings['plugins_cache_file'];
        $valid_cache = false;
        $cache_ts = 0;
        if (!$update) {
            if (file_exists($cache_path)) {
                include($cache_path);
                $cache_ts = filemtime($cache_path);
                if (time() - $cache_ts <= $this->settings['plugins_cache_time']) {
                    $valid_cache = true;
                }
            }
        }
        if (!$valid_cache) {
            $array_file = array();
            $temp = glob($this->basePath . "/plugins/*.plg.php");
            foreach ($temp as $file) {
                $array_file[basename($file, '.plg.php')] = in_array( basename($file, '.plg.php'), $this->customPlugins ) ?
                    $this->basePath . '/../ArtseldOpeninviter/plugins/' . basename($file) : $file;
            }
            if (!$update) {
                if ($this->settings['hosted']) {
                    if ($this->startPlugin('_hosted', true) !== false) {
                        $plugins = array();
                        $plugins['hosted'] = $this->servicesLink->getHostedServices();
                    } else {
                        return array();
                    }
                }
                if (isset($array_file['_hosted'])) {
                    unset($array_file['_hosted']);
                }
            }
            if ($update == true || $this->settings['hosted'] == false)
            {
                $reWriteAll = false;
                if (count($array_file) > 0) {
                    ksort($array_file);
                    $modified_files = array();
                    if (!empty($plugins['hosted'])) {
                        $reWriteAll = true;
                        $plugins = array();
                    } else {
                        foreach ($plugins as $key => $vals) {
                            foreach ($vals as $key2 => $val2) {
                                if (!isset($array_file[$key2])) {
                                    unset($vals[$key2]);
                                }
                            }
                            if (empty($vals)) {
                                unset($plugins[$key]);
                            } else {
                                $plugins[$key] = $vals;
                            }
                        }
                    }
                    foreach ($array_file as $plugin_key => $file) {
                        if (filemtime($file) > $cache_ts || $reWriteAll) {
                            $modified_files[$plugin_key] = $file;
                        }
                    }
                    foreach($modified_files as $plugin_key => $file) {
                        if (file_exists($this->basePath . '/conf/' . $plugin_key . '.conf')) {
                            include_once($this->basePath . '/conf/' . $plugin_key . '.conf');
                            if ($enable && $update == false) {
                                include($file);
                                if ($this->checkVersion($_pluginInfo['base_version'])) {
                                    $plugins[$_pluginInfo['type']][$plugin_key] = $_pluginInfo;
                                }
                            } elseif ($update == true) {
                                include($file);
                                if ($this->checkVersion($_pluginInfo['base_version'])) {
                                    $plugins[$_pluginInfo['type']][$plugin_key] = array_merge(array('autoupdate' => $autoUpdate), $_pluginInfo);
                                }
                            }
                        } else {
                            include($file);
                            if ($this->checkVersion($_pluginInfo['base_version'])) {
                                $plugins[$_pluginInfo['type']][$plugin_key] = $_pluginInfo;
                            }
                            $this->writePlConf($plugin_key, $_pluginInfo['type']);
                        }
                    }
                }
                foreach ($plugins as $key => $val) {
                    if (empty($val)) {
                        unset($plugins[$key]);
                    }
                }
            }
            if (!$update) {
                if ((!$valid_cache) && (empty($modified_files)) && (!$this->settings['hosted'])) {
                    touch($this->settings['cookie_path'] . '/' . $this->settings['plugins_cache_file']);
                } else {
                    $cache_contents="<?php\n";
                    $cache_contents.="\$plugins=array(\n".$this->arrayToText($plugins)."\n);\n";
                    $cache_contents.="?>";
                    file_put_contents($cache_path, $cache_contents);
                }
            }
        }
        if (!$this->settings['hosted']) {
            $returnPlugins = $plugins;
        } else {
            $returnPlugins = (!empty($plugins['hosted']) ? $plugins['hosted'] : array());
        }
        if ($requiredDetails) {
            if (!$valid_rcache) {
                foreach ($returnPlugins as $types => $plugins) {
                    foreach($plugins as $plugKey => $plugin) {
                        if (!empty($plugin['imported_details'])) {
                            if (!in_array($requiredDetails, $plugin['imported_details'])) {
                                unset($returnPlugins[$types][$plugKey]);
                            }
                        } else {
                            unset($returnPlugins[$types][$plugKey]);
                        }
                    }
                }
                if (!empty($returnPlugins)) {
                    $cache_contents="<?php\n";
                    $cache_contents.="\$returnPlugins=array(\n".$this->arrayToText($returnPlugins)."\n);\n";
                    $cache_contents.="?>";
                    file_put_contents($cache_rpath, $cache_contents);
                }
            }
            return $returnPlugins;
        }

        $temp = array();
        if (!empty($returnPlugins)) {
            foreach ($returnPlugins as $type => $typePlugins) {
                $temp = array_merge($temp, $typePlugins);
            }
        }
        $this->availablePlugins = $temp;

        return $returnPlugins;
    }

    /**
     * Start plugin work
     * @param $pluginName
     * @param bool $getPlugins
     * @return bool
     */
    public function startPlugin( $pluginName, $getPlugins = false )
    {
        if ($this->settings['hosted']) {
            if (!file_exists($this->basePath . '/plugins/_hosted.plg.php')) {
                $this->internalError = 'artseld_openinviter.notification.error.invalid_service_provider';
            } else {
                if (!class_exists('_hosted')) {
                    require_once($this->basePath . '/plugins/_hosted.plg.php');
                }
                if ($getPlugins) {
                    $this->servicesLink = new _hosted( $pluginName );
                    $this->servicesLink->settings = $this->settings;
                    $this->servicesLink->base_version = $this->version;
                    $this->servicesLink->base_path = $this->basePath;
                } else {
                    $this->plugin = new _hosted( $pluginName );
                    $this->plugin->settings = $this->settings;
                    $this->plugin->base_version = $this->version;
                    $this->plugin->base_path = $this->basePath;
                    $this->plugin->hostedServices = $this->getPlugins();
                }
            }
        } elseif (file_exists($this->basePath . '/../ArtseldOpeninviter/plugins/' . $pluginName . '.plg.php') ||
            file_exists($this->basePath . '/plugins/' . $pluginName . '.plg.php')) {
            if (!class_exists( $pluginName )) {
                if (in_array($pluginName, $this->customPlugins)) {
                    require_once($this->basePath . '/../ArtseldOpeninviter/plugins/' . $pluginName . '.plg.php');
                } else {
                    require_once($this->basePath . '/plugins/' . $pluginName . '.plg.php');
                }
            }
            $this->plugin = new $pluginName();
            $this->plugin->settings = $this->settings;
            $this->plugin->base_version = $this->version;
            $this->plugin->base_path = $this->basePath;
            $this->currentPlugin = $this->availablePlugins[$pluginName];
            if (file_exists($this->basePath . '/conf/' . $pluginName . '.conf')) {
                include_once($this->basePath . '/conf/' . $pluginName . '.conf');
                if (empty($enable)) {
                    $this->internalError = 'artseld_openinviter.notification.error.invalid_service_provider';
                }
                if (!empty($messageDelay)) {
                    $this->plugin->messageDelay = $messageDelay;
                } else {
                    $this->plugin->messageDelay = 1;
                }
                if (!empty($maxMessages)) {
                    $this->plugin->maxMessages = $maxMessages;
                } else {
                    $this->plugin->maxMessages = 10;
                }
            }
        } else {
            $this->internalError = 'artseld_openinviter.notification.error.invalid_service_provider';
            return false;
        }

		return true;
    }

    /**
     * Get plugin by email domain part
     * @param $user
     * @return bool|int|string
     */
    public function getPluginByDomain($user)
    {
        $user_domain = explode('@', $user);
        if (!isset($user_domain[1])) {
            return false;
        }
        $user_domain = $user_domain[1];
        foreach ($this->availablePlugins as $plugin => $details)
        {
            $patterns = array();
            if ($details['allowed_domains']) {
                $patterns = $details['allowed_domains'];
            } elseif (isset($details['detected_domains'])) {
                $patterns = $details['detected_domains'];
            }
            foreach ($patterns as $domain_pattern) {
                if (preg_match($domain_pattern, $user_domain)) {
                    return $plugin;
                }
            }
        }

        return false;
    }

    /**
     * Login action
     * @param $user
     * @param $pass
     * @return bool
     */
    public function login( $user, $pass )
    {
        if (!$this->checkLoginCredentials( $user )) {
            return false;
        }

        return $this->plugin->login( $user, $pass );
    }

    /**
     * Check login credentials
     * @param $user
     * @return bool
     */
    protected function checkLoginCredentials( $user )
    {
        $isEmail = $this->plugin->isEmail( $user );
        if ($this->currentPlugin['requirement']) {
            if ($this->currentPlugin['requirement']=='email' && !$isEmail) {
                $this->internalError = 'artseld_openinviter.notification.error.username_instead_email';
                return false;
            } elseif ($this->currentPlugin['requirement']=='user' && $isEmail) {
                $this->internalError = 'artseld_openinviter.notification.error.email_instead_username';
                return false;
            }
        }
        if ($this->currentPlugin['allowed_domains'] && $isEmail) {
            $temp = explode('@', $user);
            $userDomain = $temp[1];
            $temp = false;
            foreach ($this->currentPlugin['allowed_domains'] as $domain) {
                if (preg_match($domain, $userDomain)) {
                    $temp = true;
                    break;
                }
            }
            if (!$temp) {
                $this->internalError = $this->container->get('translator')
                    ->trans('artseld_openinviter.notification.error.invalid_userdomain',
                        array('userdomain' => $userDomain));
                return false;
            }
        }

        return true;
    }

    /**
     * Convert array to text string
     * @param $array
     * @return string
     */
    protected function arrayToText($array)
    {
        $text = '';
        $flag = false;
        foreach ($array as $key => $val) {
            if ($flag) $text .= ",\n";
            $flag = true;
            $text .= "'{$key}'=>";
            if (is_array($val)) {
                $text .= 'array(' . $this->arrayToText($val) . ')';
            } elseif (is_bool($val)) {
                $text .= ($val ? 'true' : 'false');
            } else {
                $text .= "\"{$val}\"";
            }
        }

        return $text;
    }

}
