<?php

namespace Uecode\DaemonBundle\Service;

/**
 * Daemon is a php5 wrapper class for the PEAR library System_Daemon
 *
 * PHP version 5
 *
 * @category  Uecode
 * @package   UecodeDaemonBundle
 * @author    Aaron Scherer <aequasi@gmail.com>
 * @license   MIT
 * @link      https://github.com/uecode/daemon-bundle
 */

use Uecode\DaemonBundle\System\Daemon as System_Daemon;
use Uecode\DaemonBundle\System\Daemon\Exception as UecodeDaemonBundleException;

class DaemonService
{

	private $_config = array();
	private $_pid;
	private $_interval = 2;

	public function __construct()
	{
	}

	public function initialize( $options )
	{
		if ( !empty( $options ) ) {
			$options = $this->validateOptions( $options );
			$this->setConfig( $options );
		} else {
			throw new UecodeDaemonBundleException( 'Daemon instantiated without a config' );
		}
		$this->_pid = $this->getPid();
	}

	private function validateOptions( $options )
	{
		if ( null === ( $options[ 'appRunAsUID' ] ) ) {
			throw new UecodeDaemonBundleException( 'Daemon instantiated without user or group' );
		}

		if ( !isset( $options[ 'appRunAsGID' ] ) ) {
			try {
				$user                     = posix_getpwuid( $options[ 'appRunAsUID' ] );
				$options[ 'appRunAsGID' ] = $user[ 'gid' ];
			} catch( UecodeDaemonBundleException $e ) {
				echo 'Exception caught: ', $e->getMessage(), "\n";
			}
		}

		return $options;
	}

	public function setConfig( $config )
	{
		$this->_config = $config;
	}

	public function getPid()
	{
		if ( file_exists( $this->_config[ 'appPidLocation' ] ) ) {
			return trim( file_get_contents( $this->_config[ 'appPidLocation' ] ) );
		} else {
			return null;
		}

	}

	public function setPid( $pid )
	{
		$this->_pid = $pid;
	}

	public function setInterval( $interval )
	{
		$this->_interval = $interval;
	}

	public function getInterval()
	{
		return $this->_interval;
	}

	public function getConfig()
	{
		return $this->_config;
	}

	public function start()
	{
		$this->setConfigs();

		System_Daemon::setSigHandler( SIGTERM,
			function() {
				System_Daemon::warning( "Received SIGTERM. " );
				System_Daemon::stop();
			}
		);

		System_Daemon::start();
		System_Daemon::info(
			'{appName} System Daemon Started at %s',
			date( "F j, Y, g:i a" )
		);
		$this->setPid( $this->getPid() );
	}

	public function restart()
	{
		$this->setConfigs();
		System_Daemon::restart();
	}

	public function iterate( $sec )
	{
		$this->setConfigs();
		System_Daemon::iterate( $sec );
	}

	public function isRunning()
	{
		$this->setConfigs();
		$running = System_Daemon::isRunning();
		var_dump( $running );

		return $running;
	}

	public function stop()
	{
		$this->setConfigs();
		System_Daemon::stop();
	}

	private function setConfigs()
	{
		if ( empty( $this->_config ) ) {
			throw new UecodeDaemonBundleException( 'Daemon instantiated without a config' );
		}
		System_Daemon::setOptions( $this->getConfig() );
	}
}
