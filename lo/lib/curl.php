<?php

class cURL
{
    public $headers;
    public $user_agent;
    public $compression;
    public $cookie_file;
    public $proxy;

    public function __construct($cookies=false, $cookie='cookies.txt', $compression='gzip', $proxy='')
    {
        $lo = new LO1();
        $this->headers[] 	= 'Accept: text/html,application/xhtml+xml,application/json,application/xml;q=0.9,*/*;q=0.8';
        //$this->headers[] 	= 'Connection: Keep-Alive';
        $this->headers[] 	= 'Content-type: application/x-www-form-urlencoded;charset=UTF-8';
        $this->user_agent 	= 'Mozilla/4.0 (LivrariOnline.ro '.$lo->version.')';
        $this->compression 	= $compression;
        $this->proxy 		= $proxy;
        $this->cookies 		= $cookies;
        if ($this->cookies == true) {
            $this->cookie($cookie);
        }
    }

    public function cookie($cookie_file)
    {
        if (file_exists($cookie_file)) {
            $this->cookie_file=$cookie_file;
        } else {
            fopen($cookie_file, 'w') or $this->error('The cookie file could not be opened. Make sure this directory has the correct permissions');
            $this->cookie_file=$cookie_file;
            fclose($this->cookie_file);
        }
    }

    public function get($url)
    {
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
        if ($this->cookies == true) {
            curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
        }
        if ($this->cookies == true) {
            curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        curl_setopt($process, CURLOPT_ENCODING, $this->compression);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        if ($this->proxy) {
            curl_setopt($process, CURLOPT_PROXY, $this->proxy);
        }
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
        $return = curl_exec($process);
        $error = curl_error($process);
        curl_close($process);
        
        if (!empty($error)) {
            return false;
        } else {
            return $return;
        }
    }

    public function post($url, $data)
    {
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($process, CURLOPT_HEADER, 0);
        curl_setopt($process, CURLOPT_USERAGENT, $this->user_agent);
        if ($this->cookies == true) {
            curl_setopt($process, CURLOPT_COOKIEFILE, $this->cookie_file);
        }
        if ($this->cookies == true) {
            curl_setopt($process, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        curl_setopt($process, CURLOPT_ENCODING, $this->compression);
        curl_setopt($process, CURLOPT_TIMEOUT, 30);
        if ($this->proxy) {
            curl_setopt($process, CURLOPT_PROXY, $this->proxy);
        }
        curl_setopt($process, CURLOPT_POSTFIELDS, $data);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($process, CURLOPT_POST, 1);
        $return = curl_exec($process);
        $error = curl_error($process);
        curl_close($process);
        
        if (!empty($error)) {
            return false;
        } else {
            return $return;
        }
    }

    public function error($error)
    {
        echo "<center><div style='width:500px;border: 3px solid #FFEEFF; padding: 3px; background-color: #FFDDFF;font-family: verdana; font-size: 10px'><b>cURL Error</b><br>$error</div></center>";
        die;
    }
}
