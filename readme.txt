=== Bamboo Backups ===
Contributors: Bamboo Solutions
Donate link: http://www.bamboosolutions.co.uk
Tags: backups, database
Requires at least: 3.0.1
Tested up to: 4.0
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily create daily backups of your Wordpress database.

== Description ==

Automatically create backups of your WordPress database. This plugin will create a daily sql file backup of your Wordpress database in the /wp-content/backups directory, so that you can easily backup your database with the rest of your files. To save space, all the backup files are zipped, and they are only retained if the database has changed since the last backup. Backup files are retained for whatever period of time you specify (the default is 30 days).

You can also perform a manual backup at any time using this plugin.

Usage

Select 'Bamboo Backups' from the 'Tools' menu. Select what time you would like backups to run and how many you would like to keep. The backups will occur the next time your website is accesses after the selected time. You can also perform a manual backup by clicking 'Backup Now'.

== Changelog ==
1.1 Fixed a bug causing some older backups to be deleted prematurely