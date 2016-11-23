<?php

/**
 * This class contains the functions to make calls to basecamp API.
 * @author Dariel de Jesus darieldejesus@gmail.com
 * @version 1.0
 */

class BasecampAPI {
	private $domain;
	private $supportedStatus;
	private $apiURL;
	private $token;
	private $homeID;

	function __construct( $options ) {
		if ( !array_key_exists( 'token', $options ) || empty( $options['token'] ) ) {
			// throw new Exception( "Authentication credentials were not provided.", 1 );
			return;
		}
		if ( !array_key_exists( 'home_id', $options ) || empty( $options['home_id'] ) ) {
			// throw new Exception( "Home id was not provided.", 1 );
			return;
		}
		$this->token = $options['token'];
		$this->homeID = $options['home_id'];
		$this->domain = $options['domain'];
		$this->supportedStatus = ['active', 'on_hold', 'archived'];
	}

	/**
	 * Generates the API URL based on token and homeID.
	 * @return string Generated API URL
	 */
	private function generateApiURL() {
		if ( empty( $this->apiURL ) ) {
			$this->apiURL = "https://{$this->token}@{$this->homeID}.{$this->domain}";
		}
		return $this->apiURL;
	}

	public function getProjects( $options ) {
		$xml = $this->execute( 'projects.xml' );
		if ( $xml['error'] !== false ) {
			return NULL;
		}
		$projects = $this->parseResponse( $xml['body'] );
		$this->filterProjectsByStatus( $projects, $options['filter_by'] );
		return $projects;
	}

	public function putProject( $options ) {
		if ( !array_key_exists( 'id', $options ) ) {
			return [ 'error' => true, 'body' => 'Required "id" field was not provided.' ];
		}

		if ( !array_key_exists( 'status', $options ) ) {
			return [ 'error' => true, 'body' => 'Required "status" field was not provided.' ];
		}

		if ( !in_array( $options['status'], $this->supportedStatus ) ) {
			return [ 'error' => true, 'body' => "Provided status ({$options['status']}) is not supported." ];
		}

		$data = "<project><status>{$options['status']}</status></project>";
		$command = "projects/{$options['id']}.xml";
		$result = $this->execute( $command, $data, 'PUT' );
		return $result;
	}

	/**
	 * Function to get todo lists within a given project ID.
	 * @param int $projectId Project ID
	 * @return SimpleXMLElement Parsed XML.	
	 */
	public function getTodoLists( $projectId ) {
		if ( empty( $projectId ) ) {
			return resultLog( FALSE, 'Project ID cannot be empty.' );
		}
		$command = "projects/{$projectId}/todo_lists.xml";
		$result = $this->execute( $command );
		if ( $result['error'] !== FALSE  ) {
			return resultLog( FALSE, "Error executing $command." );
		}
		$todoLists = $this->parseResponse( $result['body'] );
		return $todoLists;
	}

	/**
	 * Function to get todo lists items within a given todo list ID.
	 * @param int $todoListId To Do List ID
	 * @return SimpleXMLElement Parsed XML.	
	 */
	public function getTodoListItems( $todoListId ) {
		if ( empty( $todoListId ) ) {
			return FALSE;
		}
		$command = "todo_lists/{$todoListId}/todo_items.xml";
		$result = $this->execute( $command );
		if ( $result['error'] !== FALSE  ) {
			return NULL;
		}
		$todoItems = $this->parseResponse( $result['body'] );
		return $todoItems;
	}

	/**
	 * Function to get files within a given project.
	 * @param int $projectId Project ID
	 * @return SimpleXMLElement XML object with all files.
	 */
	public function getProjectFiles( $projectId, $page = 0 ) {
		if ( empty( $projectId ) ) {
			return resultLog( FALSE, 'Project ID is required.' );
		}
		$command = "projects/{$projectId}/attachments.xml?n=" . ( $page * 100 );
		$result = $this->execute( $command );
		if ( $result['error'] !== FALSE ) {
			return resultLog( FALSE, 'Could not process project attachments.' );
		}
		$result = $this->parseResponse( $result['body'] );
		return $result;
	}

	/**
	 * Function to complete or uncomplete an item from a Todo List.
	 * @param int $todoListItemId To Do List ID
	 * @return boolean True if the item was updated. False if isn't.
	 */
	public function completeTodoListItem( $todoListItemId, $complete = TRUE ) {
		if ( empty( $todoListItemId ) ) {
			return FALSE;
		}
		$command = "todo_items/{$todoListItemId}/" . ( $complete ? 'complete' : 'uncomplete' ) . ".xml";
		$result = $this->execute( $command, '', 'PUT' );
		if ( $result['error'] !== FALSE  ) {
			return NULL;
		}
		return ( $result['http_code'] == 200 );
	}

	private function filterProjectsByStatus( $projects, $status ) {
		for ($index=0; $index < count( $projects->project ); $index++) { 
			if ( $projects->project[ $index ]->status != $status ) {
				unset( $projects->project[ $index ] );
				$index--;
			}
		}
		return $projects;
	}

	private function execute( $command, $body = '', $action = 'GET' ) {
		$this->generateApiURL();
		
		$fullCommand = $this->apiURL . '/' . $command;
		
		$ch = curl_init();
		
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)');
		curl_setopt( $ch, CURLOPT_URL, $fullCommand );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

		if ( strtoupper($action) == 'GET' ) {
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
		} elseif ( strtoupper($action) == 'PUT' ) {
			curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, ['Content-Type: application/xml']);
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $body );
		}
		
		$xmlResponse = curl_exec( $ch );
		$errorMessage = curl_error( $ch );
		$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		
		if ( $errorMessage ) {
			return [ 'error' => true, 'body' => $errorMessage, 'http_code' => $httpCode ];
		}


		curl_close( $ch );
		unset( $ch );

		return [ 'error' => false, 'body' => $xmlResponse, 'http_code' => $httpCode ];
	}

	private function parseResponse( $xml ) {
		$xml = simplexml_load_string( $xml );
		return $xml;
	}
}