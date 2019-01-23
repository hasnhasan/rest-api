<?php
namespace RestApi;
use RestApi\ApplicationController;
use RestApi\Request;
use RestApi\MySQL;

class Query
{
    function __construct($mysql_hostname,$mysql_username,$mysql_password,$mysql_database)
    {
        @session_start();
        $dbh     = new MySQL($mysql_hostname,$mysql_username,$mysql_password,$mysql_database);
    }

    public function run()
    {
        $request = new Request(array('restful' => true, 'url_prefix' => 'api/'));
        if (is_null($request->controller)) {
            http_response_code(404);
            exit;
        }

        $controller_file = "${plugin_path}/controllers/" . $request->controller . '.php';
        $model_file      = "${plugin_path}/models/" . $request->controller . '.php';
        if (file_exists($model_file)) {
            require_once $model_file;
        }
        if (file_exists($controller_file)) {
            require_once $controller_file;
            $controller_name = ucfirst($request->controller . 'Controller');
            $controller      = new $controller_name($request->controller);
        } else {
            $controller = new ApplicationController($request->controller);
        }

        echo $controller->dispatch($request);
    }
}