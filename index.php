<?php

// use \Psr\Http\Message\ServerRequestInterface as Request;
// use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\UploadedFile;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/json.class.php';
require_once __DIR__ . '/classes/user.class.php';
require_once __DIR__ . '/classes/directory.class.php';
require_once __DIR__ . '/classes/file.class.php';
require_once __DIR__ . '/classes/helpers.class.php';

// FUNCTIONS
// Get user informations or debug informations according $debug parameter
function getUser($debug)
{
    $user = new MdEditApi\User();
    if ($debug) {
        return $user->getUserInfoDebug()->getInfo();
    }
    return $user->getUserInfo()->getInfo();
}

// Send response accordind data and format
function sendResponse($response, $format, $data, $status)
{
    switch ($format) {
        case 'json':
            $data = json_encode($data);
            $response->withHeader('Content-Type', 'application/json; charset=utf-8');
            break;
        case 'text':
            $response->withHeader('Content-Type', 'plain/text; charset=utf-8');
            break;
        case 'xml':
            $response->withHeader('Content-Type', 'plain/text; charset=utf-8');
            break;
    }
    $response->withStatus($status);
    $response->getBody()->write($data);
    return $response;
}

// Check if file is in path of user and exists (if $is_file = true)
function fileAccess($file, $user, $is_file = false)
{
    if (($is_file and is_file($file)) or !$is_file) {
        foreach ($user['directories'] as $directory) {
            if (strpos($file, $user['root_directory'] . $directory) === 0) {
                return true;
            }
        }
    }
    return false;
}

// Check if directory is in path of user and exists (if $is_directory = true)
function directoryAccess($directory, $user, $is_directory = false)
{
    if (($is_directory and is_dir($directory)) or !$is_directory) {
        foreach ($user['directories'] as $dir) {
            if (strpos($directory, $user['root_directory'] . $dir) === 0) {
                return true;
            }
        }
    }
    return false;
}

// API
$config = [
    'settings' => [
        'displayErrorDetails' => true
    ],
];
$app = new \Slim\App($config);

// $container = $app->getContainer();
// $container['upload_directory'] = __DIR__ . '/uploads';

// Enable CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

// Enable CORS
$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->get('/', function (Request $request, Response $response, $args) {
    $response->getBody()->write("Welcome on mdEdit 2 API server");
    return $response;
});

$app->get('/me', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();
    $user = getUser($params['debug']);
    return sendResponse($response, 'json', $user, 200);
});

$app->get('/editor', function (Request $request, Response $response, $args) {
    $params = $request->getQueryParams();
    $user = getUser($params['debug']);
    $data = [
        'editor' => $user['editor'],
        'username' => $user['username']
    ];
    return sendResponse($response, 'json', $data, 200);
});

/**
 * URL pour lister les fichiers
 * - Si le paramètre file est renseigné (chemin vers un fichier spécifique), elle renvoie le contenu du fichier au format XML
 * - Si le paramètre files est reneseigné (liste de fichiers séparés par un ";") elle renvoie la liste de ce fichiers et leur contenu au format JSON
 * - Sinon, renvoie la liste des fichiers de l'utilisateur authentifié et leur contenu
 */
$app->get('/files', function (Request $request, Response $response, $args) {
    $config = MdEditApi\Json::get(__DIR__ . '/config/config.json');
    $params = $request->getQueryParams();
    $user = getUser($params['debug']);
    $data = [
        'user' => $user
    ];
    if ($params['file']) {
        // Return file content if exist else error message
        $text = 'File does\'nt exist.';
        if (is_file($config['md_relative_path'] . $params['file'])) {
            $text = file_get_contents($config['md_relative_path'] . $params['file']);
        }
        return sendResponse($response, 'xml', $text, 200);
    } elseif ($user['editor']) {
        if ($params['files']) {
            $data['files'] = [];
            $files = array_filter(explode(';', $params['files']));
            foreach ($files as $file) {
                if (fileAccess($config['md_relative_path'] . $file, $user, true)) {
                    $f = new MdEditApi\File();
                    $data['files'][] = $f->getFile($file, $config['md_relative_path']);
                }
            }
        } else {
            $recursive = $params['recursive'] == NULL or $params['recursive'] == 1 ? true : false;
            if ($params['directory']) {
                if (directoryAccess($config['md_relative_path'] . $params['directory'], $user, false)) {
                    $dir = new MdEditApi\Directory();
                    $data['files'] = $dir->getFiles($params['directory'], $recursive, [], $config['md_relative_path'])['files'];
                }
            } else {
                // Return files list of user
                $dir = new MdEditApi\Directory();
                foreach ($user['directories'] as $directory_value) {
                    if (directoryAccess($config['md_relative_path'] . $directory_value, $user, true)) {
                        $data['directories'][$directory_value] = $dir->getFiles($directory_value, $recursive, ['xml'], $config['md_relative_path']);
                    }
                }
            }
        }
    }
    return sendResponse($response, 'json', $data, 200);
});

// Create / upload files
$app->post('/files', function (Request $request, Response $response, $args) {
    $config = MdEditApi\Json::get(__DIR__ . '/config/config.json');
    $params = $request->getQueryParams();
    $user = getUser($params['debug']);
    $data = [
        'files' => [],
        'created' => 0
    ];
    if ($user['editor']) {
        if ($params['upload']) {
            $file_path = $config['md_relative_path'] . $params['file'];
            $uploadedFile = $request->getUploadedFiles()['file'];
            // Check if file is in path of user
            if (fileAccess($file_path, $user, false)) {
                if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
                    $uploadedFile->moveTo($file_path);
                    $f = new MdEditApi\File();
                    $data['files'][] = $f->getFile($params['file'], $config['md_relative_path']);
                    $data['created']++;
                }
            }
        } else {
            $body = $request->getBody()->getContents();
            $files = json_decode($body, true);
            foreach ($files as $file_key => $file) {
                // Check if file is in path of user
                if (fileAccess($config['md_relative_path'] . $file['file'], $user, false)) {
                    $f = new MdEditApi\File();
                    $content = $file['content'];
                    $data['files'][] = $f->saveFile($content, $file['file'], $config['md_relative_path']);
                    $data['created']++;
                }
            }
        }
    }
    return sendResponse($response, 'json', $data, 200);
});

// Update files - NOT USED
$app->put('/files', function (Request $request, Response $response, $args) {
    $config = MdEditApi\Json::get(__DIR__ . '/config/config.json');
    $params = $request->getQueryParams();
    $body = $request->getBody()->getContents();
    $user = getUser($params['debug']);
    $data = [
        'file' => [],
        'updated' => 0
    ];
    if ($user['editor']) {
        $json_body = json_decode($body, true);
        $files = array_filter(explode(';', $params['files']));
        if (count($files) == 0) {
            $files = array_map(
                function ($f) {
                    return $f['path'] . '/' . $f['filename'];
                },
                $json_body
            );
        }
        foreach ($files as $file_key => $file) {
            // Check if file exists and if file is in path of user
            if (fileAccess($file, $user, true)) {
                $f = new MdEditApi\File();
                $data['files'][] = $f->saveFile($json_body[$file_key]['content'], $file);
                $data['updated']++;
            }
        }
    }
    return sendResponse($response, 'json', $data, 200);
});

// Delete files - NOT USED
$app->delete('/files', function (Request $request, Response $response, $args) {
    $config = MdEditApi\Json::get(__DIR__ . '/config/config.json');
    $params = $request->getQueryParams();
    $body = $request->getBody()->getContents();
    $user = getUser($params['debug']);
    $data = [
        'files' => [],
        'deleted' => 0
    ];
    if ($user['editor']) {
        $json_body = json_decode($body, true);
        $files = array_filter(explode(';', $params['files']));
        if (count($files) == 0 and is_array($json_body)) {
            $files = array_map(
                function ($f) {
                    return $f['path'] . '/' . $f['filename'];
                },
                $json_body
            );
        }
        foreach ($files as $file) {
            // Check if file exists and is in path of user
            if (fileAccess($config['md_relative_path'] . $file, $user, true)) {
                $f = new MdEditApi\File();
                $data['files'][] = $f->deleteFile($config['md_relative_path'] . $file);
                $data['deleted']++;
            }
        }
    }
    return sendResponse($response, 'json', $data, 200);
});

// Create directories - NOT USED
$app->post('/directries', function (Request $request, Response $response, $args) {
    $config = MdEditApi\Json::get(__DIR__ . '/config/config.json');
    $params = $request->getQueryParams();
    $body = $request->getBody()->getContents();
    $user = getUser($params['debug']);
    $data = [
        'directories' => [],
        'created' => 0
    ];
    if ($user['editor']) {
        $json_body = json_decode($body, true);
        $directories = array_filter(explode(';', $params['directories']));
        if (count($directories) == 0) {
            $directories = $json_body;
        }
        foreach ($directories as $directory) {
            // Check if file is in path of user
            if (directoryAccess($config['md_relative_path'] . $directory, $user, false)) {
                $d = new MdEditApi\Directory();
                $data['directories'][] = $d->createDirectory($config['md_relative_path'] . $directory);
                $data['created']++;
            }
        }
    }
    return sendResponse($response, 'json', $data, 200);
});

// Delete directories
$app->delete('/directories', function (Request $request, Response $response, $args) {
    $config = MdEditApi\Json::get(__DIR__ . '/config/config.json');
    $params = $request->getQueryParams();
    $body = $request->getBody()->getContents();
    $user = getUser($params['debug']);
    $data = [
        'directories' => [],
        'deleted' => 0
    ];
    if ($user['editor']) {
        $json_body = json_decode($body, true);
        $directories = array_filter(explode(';', $params['directories']));
        if (count($directories) == 0) {
            $directories = $json_body;
        }
        foreach ($directories as $directory) {
            // Check if file exists and is in path of user
            if (directoryAccess($config['md_relative_path'] . $directory, $user, true)) {
                $d = new MdEditApi\Directory();
                $data['directories'][] = $d->removeDirectory($config['md_relative_path'] . $directory);
                $data['deleted']++;
            }
        }
    }
    return sendResponse($response, 'json', $data, 200);
});

// Enable CORS
// Catch-all route to serve a 404 Not Found page if none of the routes match
// NOTE: make sure this route is defined last
$app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($req, $res) {
    $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
    return $handler($req, $res);
});

$app->run();
