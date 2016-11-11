<?php

/**
 * Function to verify if on hold project have already processed (downloaded files).
 * @param array $projects List of the projects to be processed.
 * @param \Illuminate\Database\Capsule\Manager $db Db where projects details are located.
 */

function checkFilesHaveDownloaded( $projects, $db ) {
	if ( empty( $projects ) ) {
		return FALSE;
	}
	foreach ( $projects as $project ) {
		$found = $db->table( 'bc_project_meta' )->where( 'project_id', (string) $project->id )
					   ->where( 'meta_key', 'files_archived' )
					   ->first();
		$filesArchived = 0;
		if ( $found ) {
			$filesArchived = $found->meta_value;
		}
		$project->addChild( 'files_archived', $filesArchived );
	}
}

/**
 * Function to update Project meta if files_archived checkbox changes.
 * @param array $projectId Project to be processed.
 * @param bool $status New status.
 * @param \Illuminate\Database\Capsule\Manager $db Db where projects details are located.
 * @return bool If the entity has been inserted/updated.
 */
function updateFilesHaveDownloaded( $projectId, $status, $db ) {
	$found = $db->table( 'bc_project_meta' )->where( 'project_id', $projectId)
									   ->where( 'meta_key', 'files_archived' )
					   				   ->first();
	if ( $found ) {
		return $db->table( 'bc_project_meta' )
					->where( 'project_id', $projectId)
					->where( 'meta_key', 'files_archived' )
					->update( ['meta_value' => $status]);
	} else {
		return $db->table( 'bc_project_meta' )
					->insert([
						'project_id' => $projectId,
						'meta_key'   => 'files_archived',
						'meta_value' => $status
					]);
	}
}

/**
 * Function to insert/update user.
 * @param array $userDetails Array with the user details 
 * @param \Illuminate\Database\Capsule\Manager $db DB connection
 * @return array|bool Array with a message if error ocurrs. Bool if user have been inserted/updated.
 */
function insertUpdateUser( $userDetails, $db ) {

	// Confirm required fields exist.
	if ( empty( $userDetails['fullname'] ) || empty( $userDetails['email'] ) || ( empty( $userDetails['password'] ) && !isset( $userDetails['id'] ) ) ) {
		return [ 'error' => TRUE, 'message' => 'Required fields were not provided. Please, verify Full name, Email and Password.' ];
	}

	if ( !empty( $userDetails['password'] ) ) {
		$hash = password_hash( $userDetails['password'], PASSWORD_BCRYPT );
		// Remove password field.
		$userDetails['hash'] = $hash;
	}
	unset( $userDetails['password'] );

	// If 'id' does not exists, then insert the user.
	if ( !isset( $userDetails['id'] ) ) {
		// Confirm email does not exists.
		if ( emailExists( $userDetails['email'], $db ) ) {
			return [ 'error' => TRUE, 'message' => 'This email already exists.' ];
		}
		$result = $db->table( 'bc_users' )->insert( $userDetails );
		return $result;
	}
	// Updating user.
	$user = $db->table( 'bc_users' )->where( 'id', $userDetails['id'] )->first();
	if ( strcmp( $user->email, $userDetails['email'] ) !== 0 ) {
		if ( emailExists( $userDetails['email'], $db ) ) {
			return [ 'error' => TRUE, 'message' => 'This email already exists.' ];
		}
	}

	$result = $db->table( 'bc_users' )
				->where( 'id', $userDetails['id'] )
				->update( $userDetails );
	return $result;
}

/**
 * Function to verify if a email already exists
 * @param string $email Email address to be verified.
 * @param  \Illuminate\Database\Capsule\Manager $db Database connection to execute the query.
 * @return bool True or False if the email exists
 */
function emailExists( $email, $db ) {
	if ( empty( $email ) ) {
		return FALSE;
	}
	$user = $db->table( 'bc_users' )->where( 'email', $email )->first();
	return ( !empty( $user ) );
}

/**
 * Function to handle logs using an standard pattern.
 * In the future, this function would log all messages in a .log file.
 * Right now, it would return an array with a defined structure.
 * @param boolean $status Identify if the result is success or error.
 * @param string $message Message which provide extra details about the result. 
 */
function resultLog( $status, $message, $level = 1 ) {
	return [
		'status' => $status,
		'body' => $message
	];
}
