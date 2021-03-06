<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace SMBBundle\SMB;

use SMBBundle\SMB\Exception\AuthenticationException;
use SMBBundle\SMB\Exception\ConnectException;
use SMBBundle\SMB\Exception\ConnectionException;
use SMBBundle\SMB\Exception\InvalidHostException;
use SMBBundle\SMB\Exception\NoLoginServerException;

class Connection extends RawConnection {
	const DELIMITER = 'smb:';

	/**
	 * send input to smbclient
	 *
	 * @param string $input
	 */
	public function write($input) {
		parent::write($input . PHP_EOL);
	}

	/**
	 * get all unprocessed output from smbclient until the next prompt
	 *
	 * @return string
	 * @throws AuthenticationException
	 * @throws ConnectException
	 * @throws ConnectionException
	 * @throws InvalidHostException
	 * @throws NoLoginServerException
	 */
	public function read() {
		if (!$this->isValid()) {
			throw new ConnectionException('Connection not valid');
		}
		$line = $this->readLine(); //first line is prompt
		$this->checkConnectionError($line);

		$output = array();
		$line = $this->readLine();
		if ($line === false) {
			throw new ConnectException('Unknown error');
		}
		$length = mb_strlen(self::DELIMITER);
		while (mb_substr($line, 0, $length) !== self::DELIMITER) { //next prompt functions as delimiter
			$output[] .= $line;
			$line = $this->readLine();
		}
		return $output;
	}

	/**
	 * check if the first line holds a connection failure
	 *
	 * @param $line
	 * @throws AuthenticationException
	 * @throws InvalidHostException
	 * @throws NoLoginServerException
	 */
	private function checkConnectionError($line) {
		$line = rtrim($line, ')');
		if (substr($line, -23) === ErrorCodes::LogonFailure) {
			throw new AuthenticationException('Invalid login');
		}
		if (substr($line, -26) === ErrorCodes::BadHostName) {
			throw new InvalidHostException('Invalid hostname');
		}
		if (substr($line, -22) === ErrorCodes::Unsuccessful) {
			throw new InvalidHostException('Connection unsuccessful');
		}
		if (substr($line, -28) === ErrorCodes::ConnectionRefused) {
			throw new InvalidHostException('Connection refused');
		}
		if (substr($line, -26) === ErrorCodes::NoLogonServers) {
			throw new NoLoginServerException('No login server');
		}
	}

	public function close($terminate = true) {
		if (is_resource($this->getInputStream())) {
			$this->write('close' . PHP_EOL);
		}
		parent::close($terminate);
	}
}
