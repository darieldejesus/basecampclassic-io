<?php
require '../autoload.php';

use \BasecampIO\Settings;
use \Illuminate\Database\Capsule\Manager;
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\App;
use \Slim\Container;
use \Slim\Views\Twig;

/**
 * Creating app
 */ 
$app = new App( new Container( $appSettings ) );

/**
 * Continer with required tools.
 */
$container = $app->getContainer();
$container['local_settings'] = new SettingsHandler;
$container['api'] = new BasecampAPI( $container['local_settings']->getSettings() );
$container['view'] = new Twig( dirname( __FILE__ ) . '/../views/' );
$container['db'] = function() {
    global $appSettings;
    $capsule = new Manager;
    $capsule->addConnection( $appSettings['settings']['db'] );
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule;
};
$container['auth'] =  new AuthHandler();

/**
 * Middleware to check Auth.
 */
$app->add( function( $request, $response, $next ) {
    $this->auth->startSession();
    $this->auth->regenerateSessionId();
    $isLoginPage = false;
    if ( strcmp( $request->getUri()->getPath(), '/login' ) === 0 ) {
        $isLoginPage = true;
    }
    if ( !$this->auth->isAuthorized() ) {
        if ( !$isLoginPage ) {
            return $response->withRedirect( '/login' );
        }
    } else {
        if ( $isLoginPage ) {
            return $response->withRedirect( '/' );
        }
    }
    return $next( $request, $response );
} );

/**
 * Middleware to prevent any cache.
 */
$app->add( function( $request, $response, $next ) {
    $response = $response->withHeader( 'Cache-Control', 'no-cache, must-revalidate' );
    $response = $response->withHeader( 'Expires', '0' );
    return $next( $request, $response );
} );

/**
 * Defining app routes.
 */
$app->get( '/', function( $request, $response ) {
    $projects = $this->api->getProjects( ['filter_by' => 'active'] );
    return $this->view->render( $response, 'index.html', [
        'projects'     => $projects,
        'status_label' => 'Active',
        'settings'     => $this->local_settings->getSettings()
    ] );
} );

$app->get( '/onhold', function( $request, $response ) {
    $projects = $this->api->getProjects( ['filter_by' => 'on_hold'] );
    checkFilesHaveDownloaded( $projects, $this->db );
    return $this->view->render( $response, 'hold.html', [
        'projects'     => $projects,
        'status_label' => 'On hold',
        'settings'     => $this->local_settings->getSettings()
    ] );
} );

$app->get( '/archived', function( $request, $response ) {
    $projects = $this->api->getProjects( ['filter_by' => 'archived'] );
    return $this->view->render( $response, 'index.html', [
        'projects'     => $projects,
        'status_label' => 'Archived',
        'settings'     => $this->local_settings->getSettings()
    ] );
} );

$app->group( '/project', function() {
    $this->get( '/files/{projectId}[/{page}]', function( $request, $response, $args ) {
        $files = $this->api->getProjectFiles( $args['projectId'], $args['page'] );
        $projectName = 'Basecamp panel';
        $nextPage = "/project/files/{$args['projectId']}/" . ( $args['page'] + 1 );
        $prevPage = "/project/files/{$args['projectId']}/" . ( $args['page'] - 1 );
        return $this->view->render( $response, 'project-files.html', [
            'files' => $files,
            'project_name' => $projectName,
            'next_page'    => $nextPage,
            'prev_page'    => $prevPage,
            'current_page' => $args['page']
        ] );
    } );
} );

/**
 * Route group: Settings
 * Accepts:
 * GET /settings
 * GET /settings/
 * POST /settings
 * POST /settings/
 */
$app->group( '/settings', function() {
    $this->get( '[/]', function( $request, $response ) {
        return $this->view->render( $response, "settings.html", [
            'settings' => $this->local_settings->getSettings()
        ] );
    } );

    $this->post( '[/]', function( $request, $response ) {
        $this->local_settings->setSetting( 'token', $request->getParam( 'basecamp_token' ) );
        $this->local_settings->setSetting( 'home_id', $request->getParam( 'basecamp_url' ) );
        return $response->withRedirect( '/settings' );
    } );
});


/**
 * Route group: Users
 * Accepts:
 * GET /users
 * GET /users/
 * GET /users/edit/#{userId}
 * POST /users/edit/#{userId}
 * GET /users/register
 * POST /users/register
 */
$app->group( '/users', function() {
    $this->get( '[/]', function( $request, $response ) {
        return $this->view->render( $response, "users.html", [
            'users' => $this->db->table( 'bc_users' )->get()
        ] );
    } );

    $this->get( '/edit/{id}', function( $request, $response, $args ) {
        $error = isset( $request->getQueryParams()['error'] ) ? $request->getQueryParams()['error'] : '';
        $user = $this->db->table( 'bc_users' )->where( 'id', $args['id'] )->first();
        return $this->view->render( $response, "user-register.html", [
            'user'  => $user,
            'error' => $error
        ] );
    } );

    $this->post( '/edit/{id}', function( $request, $response, $args ) {
        $userDetails = $request->getParsedBody();
        $userDetails['id'] = $args['id'];
        $redirectTo = "/users/edit/{$args['id']}";
        $success = insertUpdateUser( $userDetails, $this->db );
        if ( $success['error'] ) {
            $redirectTo .= '?' . http_build_query( [ 'error' => $success['message'] ] );
        }
        return $response->withRedirect( $redirectTo );
    } );

    $this->get( '/register', function( $request, $response ) {
        return $this->view->render( $response, "user-register.html" );
    } );

    $this->post( '/register', function( $request, $response ) {
        insertUpdateUser( $request->getParsedBody(), $this->db );
        return $response->withRedirect( '/users' );
    } );
});

/**
 * Route group: Login
 * Accepts:
 * GET /login
 * GET /login/
 * POST /login
 * POST /login/
 */
$app->group( '/login', function() {
    $this->get( '[/]', function( $request, $response ) {
        return $this->view->render( $response, "user-login.html" );
    } );

    $this->post( '[/]', function( $request, $response ) {
        $loginDetails = $request->getParsedBody();
        $user = $this->db->table( 'bc_users' )->where( 'email', $loginDetails['email'] )->first();
        if ( !$user ) {
            return $response->withRedirect( '/users?error' );
        }
        if ( !password_verify( $loginDetails['password'], $user->hash ) ) {
            return $response->withRedirect( '/users?error' );
        }

        $this->auth->authorize( $user );
        return $response->withRedirect( '/' );
    } );
});

$app->get( '/logout', function( $request, $response ) {
    $this->auth->logout();
    return $response->withRedirect( '/login' );
} );

/**
 * Ajax routers
 */
$app->post( '/update', function( $request, $response ) {
    $status = $this->api->putProject( [
        'id'     => $request->getParam( 'id' ),
        'status' => $request->getParam( 'status' )
    ] );
    return $response->getBody()->write( $status['http_code'] );
} );

$app->post( '/update-checked-files', function( $request, $response ) {
    $todoLists = $this->api->getTodoLists( $request->getParam( 'id' ) );
    $todoListId = NULL;
    $todoItemId = NULL;
    $checked = strcmp( $request->getParam( 'status' ), 'true' ) === 0;
    $resultCode = FALSE;
    if ( $todoLists instanceof SimpleXMLElement && $todoLists->{'todo-list'} ) {
        foreach ( $todoLists->{'todo-list'} as $todoList ) {
            if ( strcmp( strtolower( $todoList->name ), 'downloaded' ) === 0 ) {
                $todoListId = $todoList->id;
                break;
            }
        }
        $todoItems = $this->api->getTodoListItems( $todoListId );
        if ( $todoItems && $todoItems->{'todo-item'} ) {
            foreach ( $todoItems->{'todo-item'} as $todoItem ) {
                if ( strcmp( strtolower( $todoItem->content ), 'files downloaded' ) === 0 ) {
                    $todoItemId = $todoItem->id;
                    break;
                }
            }
            if ( $todoItemId ) {
                $resultCode = $this->api->completeTodoListItem( $todoItemId, $checked );
            }
        }
    }
    if ( $resultCode ) {
        updateFilesHaveDownloaded( $request->getParam( 'id' ), $request->getParam( 'status' ), $this->db );
    }
    return $response->getBody()->write( $resultCode );
} );

// Run app
$app->run();