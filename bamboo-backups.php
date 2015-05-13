<?php
/**********************************************************************************************************************/
/*
	Plugin Name: Bamboo Backups
	Plugin URI:  http://www.bamboosolutions.co.uk/wordpress/bamboo-backups
	Author:      Bamboo Solutions
	Author URI:  http://www.bamboosolutions.co.uk
	Version:     1.0
	Description: Automatically create backups of your WordPress database
*/
/**********************************************************************************************************************/

	define('BAMBOO_BACKUPS_DIR_NAME', '/backups/');

/**********************************************************************************************************************/

	// Set the time of day that backups should run
	$bamboo_backups_time = get_option( "bamboo_backups_time" );
	if( false===$bamboo_backups_time ) {
		$bamboo_backups_time = "23:00:00";
		update_option( "bamboo_backups_time", $bamboo_backups_time );
	}

	// Set how many backups to keep
	$bamboo_backups_history = get_option( "bamboo_backups_history" );
	if( false==$bamboo_backups_history ) {
		$bamboo_backups_history = 30;
		update_option( "bamboo_backups_history", $bamboo_backups_history );
	}

/**********************************************************************************************************************/

	// Hook into the shutdown event
	function bamboo_backups_shutdown() {

		// THIS FUNCTION IS CALLED ON EVERY PAGE VIEW SO KEEP IT LIGHT
		// ===========================================================

		// Establish the time of day that backups should run
		global $bamboo_backups_time;

		// Establish when the last backup was run
		$last_run = get_option( "bamboo_backups_last_run" );
		if( false == $last_run ) {
			$last_run = strtotime( "2000/01/01 00:00:00" );
		}

		// Establish when the last backup event occurred
		$hour = intval( date( 'H', time() ) );
		$date_of_last_event = strtotime( date( "M d Y ",  time() - 60 * 60 * 24 ) );
		if ( 23 < $hour ) $date_of_last_event = strtotime( date( "M d Y " ) );
		$last_event = strtotime( date( "M d Y", $date_of_last_event ) . " " . $bamboo_backups_time );

		// If the last backup was before the last backup event
		if ($last_run < $last_event) {

			// Execute a backup
			bamboo_backups_exec();

			// Update when the last backup was run
			update_option( "bamboo_backups_last_run", time() );

		}

	}
	add_action( 'shutdown',   'bamboo_backups_shutdown' );

/**********************************************************************************************************************/

	// Hook into the admin menu event
	function bamboo_backups_admin_menu() {

		// Add the management page to the tools admin menu
		add_management_page( 'Bamboo Backups', 'Bamboo Backups', 'manage_options', 'bamboo-backups', 'bamboo_backups_page' );

	}
	add_action( 'admin_menu', 'bamboo_backups_admin_menu' );

/**********************************************************************************************************************/

	function bamboo_backups_page() {

		global $bamboo_backups_time;
		global $bamboo_backups_history;

		// If the current user lacks the required permissions to view options abort
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Ouput the start of the management page
		echo '<div class="wrap">';
		echo '<div id="icon-tools" class="icon32"></div>';
		echo '<h2>Bamboo Backups</h2><br/>';

		// If the Backup Now button was clicked execute a backup
		if( isset( $_POST["bamboo_backup_now"] ) ){
			bamboo_backups_exec();
		}

		// If the backup settings form has been posted back...
		if( isset( $_POST["bamboo_backup_settings"] ) ){

			//... Process the time setting
			if( isset( $_POST["bamboo_backup_time"] ) ){
				if( strtotime(  $_POST["bamboo_backup_time"] ) ) {
					$bamboo_backups_time = date( "H:i:s", strtotime( $_POST["bamboo_backup_time"] ) );
					update_option( "bamboo_backups_time", $bamboo_backups_time );
				}
			}

			//... Process the history setting
			if( isset( $_POST["bamboo_backups_history"] ) ){
				if( is_numeric( $_POST["bamboo_backups_history"] ) ) {
					$bamboo_backups_history = intval( $_POST["bamboo_backups_history"] );
					update_option( "bamboo_backups_history", $bamboo_backups_history );
				}
			}

		}

		// Generate the backup settings
		echo '<form name="frmSettings" method="post">';
		echo '<table class="form-table">';
		echo '<tr>';
		echo '<th scope="row">Backup Time</th>';
		echo '<td><input type="text" name="bamboo_backup_time" size="10" value="'. $bamboo_backups_time . '"/><p class="description">A backup will be performed each day at (or after) this time. (default is "23:00:00")</p></td>';
		echo '<tr></tr>';
		echo '<th scope="row">Backup History Count</label></th>';
		echo '<td><input type="text" name="bamboo_backups_history" size="4" value="'. $bamboo_backups_history . '"/><p class="description">This is the number of backups that will be retained. (default is "30")</p></td>';
		echo '</tr>';
		echo '</table>';
		echo '<input type="submit" value="Save Changes" class="button button-primary" id="submit" name="bamboo_backup_settings">';
		echo '</form>';
		echo '<br/><hr />';

		// Output a list of existing backups
		bamboo_backups_list();

		// Output the rest of the management page
		echo '<br/>';
		echo '<form name="frmBackup" method="post">';
		echo '<input name="bamboo_backup_now" type="submit" value="Backup Now" class="button button-primary"/>';
		echo '</form>';
		echo '</div>';

	}

/**********************************************************************************************************************/

	function bamboo_backups_list() {

		// Get base path
		$path = WP_CONTENT_DIR . BAMBOO_BACKUPS_DIR_NAME;

		// Get an array of existing backups
		$backups = array();
		if( file_exists( $path ) ) {
			$dir = opendir( $path );
		    while ( $file = readdir( $dir ) ) {
				if ( substr( $file, -4, 4) == '.zip' ) {
					$backups[] =  $file;
				}
		    }
		}

	    // Sort the array of existing backups into reverse order (latest first)
	    rsort( $backups );

	    // List the exiting backups
	    echo '<h3>Existing Backups:</h3>';
?>
	<table cellspacing="0" class="wp-list-table widefat">
		<thead>
			<tr>
				<th class="manage-column column-name" scope="col"><strong>Date</strong>&nbsp;&nbsp;&nbsp; <em>(Note: The server time may be different to you local time)</em></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th class="manage-column column-cb" scope="col">&nbsp;</th>
			</tr>
		</tfoot>
		<tbody id="the-list">
<?php
		foreach ( $backups as $backup ) {
			$parts = explode( ".", $backup );
			$name = $parts[0];
			$date = substr( $name, 11, 2 ) . ':' . substr( $name, 14, 2 ) . ':' . substr( $name, 17, 2 ) . ' ' . substr( $name, 5, 2 ) . '/' . substr( $name, 8, 2 ) . '/'. substr($name, 0, 4 );
			echo '<tr>';
			echo '<th class="check-column" scope="row">';
			echo '<label>&nbsp;'.date( "j F Y, g:i a", strtotime( $date )),'</label>';
			echo '</th>';
			echo '</tr>';
	    }
?>
		</tbody>
	</table>
<?php
}

/**********************************************************************************************************************/

	function bamboo_backups_exec() {

		// Establish how many backups to keep
		global $bamboo_backups_history;

		// Initialise the result
		$result = '';

		// Establish the path to the backups
		$path = WP_CONTENT_DIR . BAMBOO_BACKUPS_DIR_NAME;

		// Check that the path exists
		if( !file_exists( $path ) ) {
			if( !mkdir( $path ) ) {
				$result = 'Failed to create folder: ' . $path;
				if( $_POST["bamboo_backup_now"] == "Backup Now" ) {
					echo '<div class="updated settings-error" id="setting-error-settings_updated">';
					echo '<p><strong>' .$result .'</strong></p></div>';
				}
				return;
			}
		}

		// Before we go any further check that the backup folder contains the .htaccess file
		// to prevent access to the backups via the web for security reasons
		$access_file = $path.".htaccess";
		if( !file_exists( $access_file ) ) {
			file_put_contents( $access_file, "deny from all\n" );
		}

		// Get the database settings
		$db_host 	 = DB_HOST;
		$db_name 	 = DB_NAME;
		$db_user 	 = DB_USER;
		$db_password = DB_PASSWORD;

		// Establish the filename for the backup
		$filename = $path . date( 'Y-m-d-H-i-s' );

		// Get an array of all existing backups
		$backups = array();
		$dir = opendir( $path );
	    while ( $file = readdir( $dir ) ) {
			if ( substr( $file, -4, 4 ) == '.zip' ) {
				$file = substr( $file, 0, sizeof( $file )-5 );
				$backups[] =  $file;
			}
	    }

	    // Sort the array of existing backups into reverse order (latest first)
	     rsort($backups);

	    // Get the filename of the last backup
	    $last_filename = '';
	    if( sizeof( $backups )>0 ) {
	    	$last_filename = $path.$backups[0];
	    }

		// Construct the SQL dump command
		if( file_exists("/usr/local/mysql/bin/") ) {
			$command = "/usr/local/mysql/bin/";
		} else {
			$command = "/usr/bin/";
		}
		$command.= "mysqldump --opt --skip-comments --skip-dump-date --host=$db_host --user=$db_user --password=$db_password $db_name > $filename.sql";

		// Execute the SQL dump command
		exec( $command );

		// Establish if this backup is the same as the last one:
		$backups_is_the_same = false;

		// If there is a previous backup
		if( '' != $last_filename ) {

			// Unzip the previous backup
			$command = "unzip $last_filename.zip -d $path";
			exec ( $command );

			// Compare the current backup to the previous one
			if( sha1_file( $last_filename.".sql" ) == sha1_file( $filename.".sql" )) {
				$backups_is_the_same = true;
			}

			// Delete the unzipped previous backup
			unlink( $last_filename.".sql" );

		}

		// If this backup is the same as the last one delete it, otherwise zip it then delete it
		if( $backups_is_the_same ) {
			unlink( $filename.".sql" );
			$result = 'No backup necessary - no changes since last backup.';
		} else {
			$command = "zip -j $filename.zip $filename.sql";
			exec( $command );
			unlink( $filename.".sql" );
			$result = 'Backup completed.';
		}

		// If there are more than the required number of backups, delete the excess
		$count = sizeof( $backups );
	    if( $count > $bamboo_backups_history ) {
			sort( $backups );
			for( $i = 1; $i <= $count-$history; $i++ ) {
				$file = $path.'/'.$backups[$i-1];
				unlink( $file.".zip" );
			}
	    }

	    // If we ran this backup from the admin page write out the result - otherwise stay silent
		if( $_POST["bamboo_backup_now"] == "Backup Now" ) {
			echo '<div class="updated settings-error" id="setting-error-settings_updated">';
			echo '<p><strong>' .$result .'</strong></p></div>';
		}

	}

/**********************************************************************************************************************/
?>