<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace SMBBundle\SMB;

use SMBBundle\SMB\Exception\AuthenticationException;
use SMBBundle\SMB\Exception\InvalidHostException;

class Server {
	const CLIENT = 'smbclient';
	const LOCALE = 'en_US.UTF-8';

	/**
	 * @var string $host
	 */
	protected $host;

	/**
	 * @var string $user
	 */
	protected $user;

	/**
	 * @var string $password
	 */
	protected $password;

	/**
	 * @var string $workgroup
	 */
	protected $workgroup;

	/**
	 * Check if the smbclient php extension is available
	 *
	 * @return bool
	 */
	public static function NativeAvailable() {
		return function_exists('smbclient_state_new');
	}

	/**
	 * 
	 * @param \Symfony\Component\DependencyInjection\Container $container
	 */
	public function __construct(\Symfony\Component\DependencyInjection\Container $container) {
		$this->container = $container;
	    
	    $this->host = $this->container->getParameter('smb.host');
		list($workgroup, $user) = $this->splitUser($this->container->getParameter('smb.user'));
		$this->user = $this->container->getParameter('smb.user');
		$this->workgroup = $workgroup;
		$this->password = $this->container->getParameter('smb.password');
	}
	
	/**
	 * 
	 * @param string $host
	 * @param string $user
	 * @param string $password
	 */
	public function setAuthParams($host, $user, $password)
	{
	    $this->host = $host;
	    list($workgroup, $user) = $this->splitUser($user);
	    $this->user = $this->container->getParameter('smb.user');
	    $this->workgroup = $workgroup;
	    $this->password = $password;
	}

	/**
	 * Split workgroup from username
	 *
	 * @param $user
	 * @return string[] [$workgroup, $user]
	 */
	public function splitUser($user) {
		if (strpos($user, '/')) {
			return explode('/', $user, 2);
		} elseif (strpos($user, '\\')) {
			return explode('\\', $user);
		} else {
			return array(null, $user);
		}
	}

	/**
	 * @return string
	 */
	public function getAuthString() {
		return $this->user . '%' . $this->password;
	}

	/**
	 * @return string
	 */
	public function getUser() {
		return $this->user;
	}

	/**
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * return string
	 */
	public function getHost() {
		return $this->host;
	}

	/**
	 * @return string
	 */
	public function getWorkgroup() {
		return $this->workgroup;
	}

	/**
	 * @return \SMBBundle\SMB\IShare[]
	 *
	 * @throws \SMBBundle\SMB\Exception\AuthenticationException
	 * @throws \SMBBundle\SMB\Exception\InvalidHostException
	 */
	public function listShares() {
		$workgroupArgument = ($this->workgroup) ? ' -W ' . escapeshellarg($this->workgroup) : '';
		$command = Server::CLIENT . $workgroupArgument . ' --authentication-file=/proc/self/fd/3' .
			' -gL ' . escapeshellarg($this->getHost());
		$connection = new RawConnection($command);
		$connection->writeAuthentication($this->getUser(), $this->getPassword());
		$output = $connection->readAll();

		$line = $output[0];

		$line = rtrim($line, ')');
		if (substr($line, -23) === ErrorCodes::LogonFailure) {
			throw new AuthenticationException();
		}
		if (substr($line, -26) === ErrorCodes::BadHostName) {
			throw new InvalidHostException();
		}
		if (substr($line, -22) === ErrorCodes::Unsuccessful) {
			throw new InvalidHostException();
		}
		if (substr($line, -28) === ErrorCodes::ConnectionRefused) {
			throw new InvalidHostException();
		}

		$shareNames = array();
		foreach ($output as $line) {
			if (strpos($line, '|')) {
				list($type, $name, $description) = explode('|', $line);
				if (strtolower($type) === 'disk') {
					$shareNames[$name] = $description;
				}
			}
		}

		$shares = array();
		foreach ($shareNames as $name => $description) {
			$shares[] = $this->getShare($name);
		}
		return $shares;
	}

	/**
	 * @param string $name
	 * @return \SMBBundle\SMB\IShare
	 */
	public function getShare($name) {
		return new Share($this, $name);
	}

	/**
	 * @return string
	 */
	public function getTimeZone() {
		$command = 'net time zone -S ' . escapeshellarg($this->getHost());
		return exec($command);
	}
}
