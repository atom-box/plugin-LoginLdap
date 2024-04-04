<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\LoginLdap\tests\System;

use Piwik\Plugins\LoginLdap\Commands\SynchronizeUsers;
use Piwik\Plugins\LoginLdap\LdapInterop\UserMapper;
use Piwik\Plugins\LoginLdap\tests\System\Output\TestOutput;
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class SynchronizeUsersCommandTest extends SystemTestCase
{

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass(); // TODO: Change the autogenerated stub
        $userMapper = UserMapper::makeConfigured();
        UsersManagerAPI::getInstance()->addUser($user1='user1', 'random12345', 'user1@starkindustries.com');
        $userMapper->markUserAsLdapUser($user1);
        UsersManagerAPI::getInstance()->addUser($user2='user2', 'random123456', 'user2@starkindustries.com');
        $userMapper->markUserAsLdapUser($user2);
        UsersManagerAPI::getInstance()->addUser($user3='user3', 'random123457', 'user3@starkindustries.com');
        $userMapper->markUserAsLdapUser($user3);
    }

    public function test_sync_command_will_not_purge_old_users()
    {
        $console = new \Piwik\Console(self::$fixture->piwikEnvironment);
        $synchronizeUsers = new SynchronizeUsers();
        $console->addCommands([$synchronizeUsers]);
        $command = $console->find('loginldap:synchronize-users');
        $arguments = array(
            'command'    => 'loginldap:synchronize-users',
            '--login'     => ['user1','user2']
        );
        $inputObject = new ArrayInput($arguments);
        $output = new TestOutput();
        $command->run($inputObject, $output);
        $outputAsString = json_encode($output->output);
        $this->assertStringNotContainsString('Purging user', $outputAsString);
    }

    public function test_sync_command_will_purge_old_users()
    {
        $console = new \Piwik\Console(self::$fixture->piwikEnvironment);
        $synchronizeUsers = new SynchronizeUsers();
        $console->addCommands([$synchronizeUsers]);
        $command = $console->find('loginldap:synchronize-users');
        $arguments = array(
            'command'    => 'loginldap:synchronize-users',
            '--login'     => ['user1','user2'],
            '--purge-non-existent-users' => null
        );
        $inputObject = new ArrayInput($arguments);
        $output = new TestOutput();
        $command->run($inputObject, $output);
        $outputAsString = json_encode($output->output);
        $this->assertStringContainsString('Purging user user3', $outputAsString);
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}
