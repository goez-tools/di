<?php
namespace Stub;

class App
{
    protected $auth = null;
    protected $session = null;
    protected $appName;

    public function __construct(Auth $auth, Session $session, $appName = 'ThisApp')
    {
        $this->auth = $auth;
        $this->session = $session;
        $this->appName = $appName;
    }

    /**
     * @return null|Auth
     */
    public function getAuth()
    {
        return $this->auth;
    }

    /**
     * @return null|Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return string
     */
    public function getAppName()
    {
        return $this->appName;
    }
}