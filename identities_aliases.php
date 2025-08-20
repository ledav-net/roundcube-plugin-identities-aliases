<?php
/*
 * Copyright (C) 2018 David De Grave <david@ledav.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class identities_aliases extends rcube_plugin
{
	private $user;
	private $config;
	private $alias_file;
	private $alias_handle;
	private $email_domain;
	private $aliases = array();

	public function init() {
		$this->config = rcmail::get_instance()->config;
		$this->load_config();

		$this->alias_handle = NULL;
		$this->alias_file   = $this->config->get('alias_file');
		$this->email_domain = $this->config->get('email_domain');
		$this->user = $_SESSION['username'];

		$this->add_hook('identity_form',   array($this, 'identity_form'));
		$this->add_hook('identity_create', array($this, 'identity_create'));
		$this->add_hook('identity_update', array($this, 'identity_update'));
		$this->add_hook('identity_delete', array($this, 'identity_delete'));
	}

	private function arrayToString($_a) {
		$b = "";
		foreach ( $_a as $u => $v ) {
			$b .= "\n### $u\n";
			foreach ( $v as $a )
				$b .= sprintf("%-25s %s\n", "$a:", $u);
		}
		return $b;
	}
	private function splitEmail($email) {
		return preg_split("/@/", $email);
	}
	private function loadAliases() {
		$this->alias_handle = fopen($this->alias_file, "r+");
		flock($this->alias_handle, LOCK_EX);
		$this->aliases = array();
		while ( $l = @fgets($this->alias_handle) ) {
			$l = trim($l);
			if ( !strlen($l) || $l[0] == '#' ) continue;
			$a = preg_split("/[\s:]+/", $l);
			if ( strlen($a[0]) ) $this->aliases[$a[1]][] = $a[0];
		}
		return $this->aliases;
	}
	private function saveAliases() {
		if ( ! $this->alias_handle ) return -1;
		rewind($this->alias_handle);
		ftruncate($this->alias_handle, 0);
		fwrite($this->alias_handle, $this->arrayToString($this->aliases));
		flock($this->alias_handle, LOCK_UN);
		fclose($this->alias_handle);
		$this->alias_handle = NULL;
		return 0;
	}
	private function sanityCheck($alias) {
		foreach ( $this->aliases as $uname => $u ) {
			if ( $alias == $uname && $uname != $this->user )
				return array('abort' => true, 'result' => false, 'message' => "This is the main account of <i>$uname</i>, you cannot use this one.<br/><center>Nice try anyway ;-)</center>");
			foreach ( $u as $a )
				if ( $a == $alias && $this->user != $uname )
					return array('abort' => true, 'result' => false, 'message' => "email alias already in use by <i>$uname</i> !");
		}
		return array();
	}

	public function identity_form($_d) {
		if ( empty($_d['record']['email']) ) {
			$_d['record']['name']  = $this->user;
			$_d['record']['email'] = md5($this->user.time()).'@'.$this->email_domain;
		}
		return $_d;
	}
	public function identity_create($_d) {
		$this->loadAliases();
		$email = $this->splitEmail($_d['record']['email']);
		$alias = $email[0];
		if ( $this->user == $alias ) return; // No alias record needed in this case
		if ( ($r = $this->sanityCheck($alias)) ) return $r;
		$this->aliases[$this->user][] = $alias;
		$this->saveAliases();
		system($this->config->get('alias_exec'));
	}
	public function identity_update($_d) {
		global $RCMAIL;
		$this->loadAliases();
		$email = $this->splitEmail($_d['record']['email']);
		$alias = $email[0];
		if ( ($r = $this->sanityCheck($alias)) ) return $r;
		$record = $RCMAIL->user->get_identity($_d['id']);
		$email = $this->splitEmail($record['email']);
		$oldalias = $email[0];
		foreach ( $this->aliases[$this->user] as $id => $a )
			if ( $a == $oldalias )
				$this->aliases[$this->user][$id] = $alias;
		$this->saveAliases();
		system($this->config->get('alias_exec'));
	}
	public function identity_delete($_d) {
		global $RCMAIL;
		$this->loadAliases();
		$record = $RCMAIL->user->get_identity($_d['id']);
		$email = $this->splitEmail($record['email']);
		$alias = $email[0];
		foreach ( $this->aliases[$this->user] as $id => $a )
			if ( $a == $alias )
				unset($this->aliases[$this->user][$id]);
		$this->saveAliases();
		system($this->config->get('alias_exec'));
	}
}
