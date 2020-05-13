<?
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

/* email_domain: You domain (ex: foo.com => alias@foo.com).
 */
$config['email_domain'] = 'foo.com';

/* alias_file: Path+filename of the alias file to manage.
 *             (Note that it must be writable by the webserver)
 */
$config['alias_file'] = '/var/lib/roundcube/identities-aliases';

/* alias_exec: Command to execute after something changed in the
 *             alias file.
 */
$config['alias_exec'] = '/usr/bin/sudo /usr/bin/newaliases';
?>
