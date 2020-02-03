<?php

declare(strict_types=1);

namespace Z\HyperfThinkphp\Concerns;

trait RequestUtils
{

   /**
     * 前端代理服务器IP
     * @var array
     */
    protected $proxyServerIp = [];

    /**
     * 前端代理服务器真实IP头
     * @var array
     */
    protected $proxyServerIpHeader = ['HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'HTTP_X_CLIENT_IP', 'HTTP_X_CLUSTER_CLIENT_IP'];

    /**
     * 当前请求的IP地址
     * @var string
     */
    protected $realIP;

    /**
     * Retrieve the input data from request, include query parameters, parsed body and json body,
     * if $key is null, will return all the parameters.
     *
     * @param mixed $default
     */
    public function param(string $key, $default = null)
    {
        return $this->getRequestProperty($key) ?: $this->input($key, $default);
    }

    /**
     * 获取请求
     * @param array $keys
     * @return array
     */
    public function only(array $keys)
    {
        $data = $this->getInputData();

        foreach ($keys as $key => $default) {
            if (\is_numeric($key)) {
                $key     = $default;
                $default = null;
            }
            $result[$key] = $this->getRequestProperty($key) ?: data_get($data, $key, $default);
        }

        return $result;
    }

    /**
     * 获取客户端IP地址
     * @access public
     * @return string
     */
    public function ip(): string
    {
        if (!empty($this->realIP)) {
            return $this->realIP;
        }

        $this->realIP = $this->server('REMOTE_ADDR', '');

        // 如果指定了前端代理服务器IP以及其会发送的IP头
        // 则尝试获取前端代理服务器发送过来的真实IP
        $proxyIp       = $this->proxyServerIp;
        $proxyIpHeader = $this->proxyServerIpHeader;

        if (count($proxyIp) > 0 && count($proxyIpHeader) > 0) {
            // 从指定的HTTP头中依次尝试获取IP地址
            // 直到获取到一个合法的IP地址
            foreach ($proxyIpHeader as $header) {
                $tempIP = $this->server($header);

                if (empty($tempIP)) {
                    continue;
                }

                $tempIP = trim(explode(',', $tempIP)[0]);

                if (!$this->isValidIP($tempIP)) {
                    $tempIP = null;
                } else {
                    break;
                }
            }

            // tempIP不为空，说明获取到了一个IP地址
            // 这时我们检查 REMOTE_ADDR 是不是指定的前端代理服务器之一
            // 如果是的话说明该 IP头 是由前端代理服务器设置的
            // 否则则是伪装的
            if (!empty($tempIP)) {
                $realIPBin = $this->ip2bin($this->realIP);

                foreach ($proxyIp as $ip) {
                    $serverIPElements = explode('/', $ip);
                    $serverIP         = $serverIPElements[0];
                    $serverIPPrefix   = $serverIPElements[1] ?? 128;
                    $serverIPBin      = $this->ip2bin($serverIP);

                    // IP类型不符
                    if (strlen($realIPBin) !== strlen($serverIPBin)) {
                        continue;
                    }

                    if (strncmp($realIPBin, $serverIPBin, (int) $serverIPPrefix) === 0) {
                        $this->realIP = $tempIP;
                        break;
                    }
                }
            }
        }

        if (!$this->isValidIP($this->realIP)) {
            $this->realIP = '0.0.0.0';
        }

        return $this->realIP;
    }

    /**
     * 检测是否是合法的IP地址
     *
     * @param string $ip   IP地址
     * @param string $type IP地址类型 (ipv4, ipv6)
     *
     * @return boolean
     */
    public function isValidIP(string $ip, string $type = ''): bool
    {
        switch (\strtolower($type)) {
            case 'ipv4':
                $flag = FILTER_FLAG_IPV4;
                break;
            case 'ipv6':
                $flag = FILTER_FLAG_IPV6;
                break;
            default:
                $flag = null;
                break;
        }

        return \boolval(\filter_var($ip, FILTER_VALIDATE_IP, $flag));
    }

    /**
     * 将IP地址转换为二进制字符串
     *
     * @param string $ip
     *
     * @return string
     */
    public function ip2bin(string $ip): string
    {
        if ($this->isValidIP($ip, 'ipv6')) {
            $IPHex = \str_split(\bin2hex(\inet_pton($ip)), 4);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = \intval($value, 16);
            }
            $IPBin = \vsprintf('%016b%016b%016b%016b%016b%016b%016b%016b', $IPHex);
        } else {
            $IPHex = \str_split(\bin2hex(\inet_pton($ip)), 2);
            foreach ($IPHex as $key => $value) {
                $IPHex[$key] = \intval($value, 16);
            }
            $IPBin = \vsprintf('%08b%08b%08b%08b', $IPHex);
        }

        return $IPBin;
    }

    /**
     * 检测是否使用手机访问
     * @access public
     * @return bool
     */
    public function isMobile(): bool
    {
        if ($this->server('HTTP_VIA') && \stristr($this->server('HTTP_VIA'), "wap")) {
            return true;
        } elseif ($this->server('HTTP_ACCEPT') && \strpos(\strtoupper($this->server('HTTP_ACCEPT')), "VND.WAP.WML")) {
            return true;
        } elseif ($this->server('HTTP_X_WAP_PROFILE') || $this->server('HTTP_PROFILE')) {
            return true;
        } elseif ($this->server('HTTP_USER_AGENT') && \preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $this->server('HTTP_USER_AGENT'))) {
            return true;
        }

        return false;
    }

    /**
     * 当前是否ssl
     * @access public
     * @return bool
     */
    public function isSsl(): bool
    {
        if ($this->server('HTTPS') && ('1' == $this->server('HTTPS') || 'on' == \strtolower($this->server('HTTPS')))) {
            return true;
        } elseif ('https' == $this->server('REQUEST_SCHEME')) {
            return true;
        } elseif ('443' == $this->server('SERVER_PORT')) {
            return true;
        } elseif ('https' == $this->server('HTTP_X_FORWARDED_PROTO')) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve a server variable from the request.
     *
     * @param null|mixed $default
     * @return null|array|string
     */
    public function server(string $key, $default = null)
    {
        return parent::server(strtolower($key), $default);
    }
}