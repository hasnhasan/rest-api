<?php
namespace RestApi;

use RestApi\Response;
use RestApi\Model;

class ApplicationController
{
    public $request, $id, $params, $table;

    public function __construct($controller_name)
    {
        global $table_routes;
        $this->table = $table_routes[$controller_name] ?: $controller_name;
    }

    public function dispatch($request)
    {
        $this->request = $request;
        $this->id      = $request->id;
        $this->params  = $request->params;

        if ($request->isRestful()) {
            return $this->dispatchRestful();
        }
        if ($request->action) {
            return $this->{$request->action}();
        }
    }

    public function view()
    {
        $res          = new Response();
        $res->success = true;
        $res->message = "success";
        if ($this->request->id) {
            $all = Model::all("*", $this->table, array("id" => $this->request->id));
        } else {
            $all = Model::all("*", $this->table, $this->params);
        }
        $res->total = $all["total"];
        $res->data  = $all["data"];
        return $res->to_json();
    }

    public function create()
    {
        $res = new Response();
        $rec = Model::create($this->table, $this->request->params);
        if ($rec) {
            $res->success = true;
            $res->message = "Yeni Eklendi {$this->request->controller} {$rec->id}";
            $res->data    = $rec->to_hash();
        } else {
            $res->message = "Yeni {$this->request->controller} oluşturulamadı.";
        }
        return $res->to_json();
    }

    public function update()
    {
        $res = new Response();
        $rec = Model::update($this->table, $this->request->id, $this->request->params);
        if ($rec) {
            $res->data    = $rec->to_hash();
            $res->success = true;
            $res->message = "Güncellendi {$this->request->controller} {$this->request->id}";
        } else {
            $res->message = "$this->request->controller $this->id güncellenmedi.";
        }
        return $res->to_json();
    }

    public function destroy()
    {
        $res = new Response();
        if ($rec = Model::destroy($this->table, $this->id)) {
            $res->data    = $rec->to_hash();
            $res->success = true;
            $res->message = "Silindi. {$this->request->controller} " . $this->id;
        } else {
            $res->message = "$this->request->controller $this->id silinemedi.";
        }
        return $res->to_json();
    }

    protected function dispatchRestful()
    {
        switch ($this->request->method) {
            case 'GET':
                return $this->view();
                break;
            case 'POST':
                return $this->create();
                break;
            case 'PUT':
                return $this->update();
                break;
            case 'DELETE':
                return $this->destroy();
                break;
        }
    }
}
