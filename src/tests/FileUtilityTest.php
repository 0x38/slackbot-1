<?php

use Slackbot\utility\FileUtility;

class FileUtilityTest extends PHPUnit_Framework_TestCase
{
    /**
     * @throws Exception
     */
    public function testJsonFileToArray()
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Slackbot' . DIRECTORY_SEPARATOR . 'dictionary' . DIRECTORY_SEPARATOR . 'test.json';

        $array = (new FileUtility())->jsonFileToArray($dir);

        $expected = [
            'test1',
            'test2'
        ];

        $this->assertEquals($expected, $array);
    }

    public function testJsonFileToArrayEmptyPath()
    {
        try {
            (new FileUtility())->jsonFileToArray('');
        } catch (Exception $e) {
            $this->assertEquals('File path is empty', $e->getMessage());
        }

    }

    public function testJsonFileToArrayMissingFile()
    {
        try {
            (new FileUtility())->jsonFileToArray('/path/to/dummy.json');
        } catch (\Exception $e) {
            $this->assertEquals('File: \'/path/to/dummy.json\' does not exist or is not a file', $e->getMessage());
        }
    }

    public function testJsonFileToArrayInvalidFile()
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Slackbot' . DIRECTORY_SEPARATOR . 'Config.php';

        try {
            (new FileUtility())->jsonFileToArray($dir);
        } catch (\Exception $e) {
            $this->assertEquals('File: \'/Applications/MAMP/htdocs/slackbot/src/Slackbot/Config.php\' is not a json file', $e->getMessage());
        }
    }
}