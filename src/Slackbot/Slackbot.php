<?php

namespace Slackbot;

use Slackbot\plugin\AbstractPlugin;
use Slackbot\utility\MessageUtility;

/**
 * Class Slackbot
 * @package Slackbot
 */
class Slackbot
{
    private $request;

    /**
     * Slackbot constructor.
     *
     * @param $request
     */
    public function __construct($request)
    {
        $this->setRequest($request);
    }

    /**
     * @param $request
     */
    public function setRequest($request)
    {
        $this->request = $request;

        if ($this->verifyRequest() !== true) {
            //throw new \Exception('Request is not valid');
            echo 'Request is not coming from Slack';
            exit;
        }
    }

    /**
     * @param null $key
     * @return mixed
     * @throws \Exception
     */
    public function getRequest($key = null)
    {
        if ($key === null) {
            // return the entire request since key is null
            return $this->request;
        } else {
            if (array_key_exists($key, $this->request)) {
                return $this->request[$key];
            }
        }

        return null;
    }

    /**
     * Listen to incoming requests from Slack
     */
    public function listenToSlack()
    {
        $this->logChat($this->getRequest('text'), __METHOD__);

        try {
            $response = $this->respond($this->getRequest('text'));
        } catch (\Exception $e) {
            // TODO this can be re-thrown and be handled in public/listener.php
            echo $e->getMessage();
            exit;
        }

        $this->sendToSlack($response);
    }

    public function respond($message = null)
    {
        // If message is not set, get it from the current request
        if ($message === null) {
            $message = $this->getRequest('text');
        }

        /**
         * Process the message
         */
        $command = (new MessageUtility())->extractCommandName($message);

        $config = new Config();

        // check command name
        if (empty($command)) {
            return $config->get('noCommandMessage');
        }

        $commandDetails = (new Command())->get($command);

        // check command details
        if (empty($commandDetails)) {
            //return "Sorry. I do not know anything about your command: '/{$command}'. I List the available commands using /help";
            return $config->get('unknownCommandMessage', ['command' => $command]);
        }

        // check the module
        if (!isset($commandDetails['module'])) {
            throw new \Exception("Module is not set for this command");
        }

        // check the action
        if (!isset($commandDetails['action'])) {
            throw new \Exception("Action is not set for this command");
        }

        // create the class
        $module = $commandDetails['module'];
        $moduleClassFile = __NAMESPACE__ . "\\plugin\\{$module}";
        $moduleClass = new $moduleClassFile();

        // check class is valid
        if (!$moduleClass instanceof AbstractPlugin) {
            throw new \Exception("Couldn't create class: '{$moduleClassFile}'");
        }

        // check action exists
        $action = $commandDetails['action'];
        if (!method_exists($moduleClass, $action)) {
            throw new \Exception("Action / function: '{$action}' does not exist in '{$moduleClassFile}'");
        }

        return $moduleClass->$action();
    }

    /**
     * @param $message
     *
     * @return bool|mixed
     */
    public function sendToSlack($message)
    {
        if ($this->isThisBot()) {
            return false;
        }

        $config = $this->getConfig();

        $ch = curl_init($config->get('endPoint'));
        $data = http_build_query([
            'token' => $config->get('apiToken'),
            'channel' => $config->get('channelName'),
            'text' => $message,
            'username' => $config->get('botUsername'),
            'as_user' => false,
            'icon_url' => $config->get('iconURL')
        ]);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        $this->logChat($message, __METHOD__);

        return $result;
    }

    /**
     * @return bool
     */
    private function isThisBot()
    {
        $userId = $this->getRequest('user_id');
        $username = $this->getRequest('user_name');

        if ((isset($userId) && $userId == 'USLACKBOT')
            || (isset($username) && $username == 'slackbot')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    private function verifyRequest()
    {
        $token = $this->getRequest('token');
        if (isset($token)
            && $token === $this->getConfig()->get('outgoingWebhookToken')
            && $this->isThisBot() == false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param        $function
     * @param string $message
     *
     * @return bool
     */
    private function logChat($function, $message = '')
    {
        $config = $this->getConfig();
        
        if ($config->get('chatLogging') !== true) {
            return false;
        }

        $currentTime = date('Y-m-d H:i:s');

        file_put_contents(
            $config->get('chatLoggingFileName'),
            "{$currentTime}|{$function}|{$message}\r\n",
            FILE_APPEND
        );
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return (new Config());
    }
}