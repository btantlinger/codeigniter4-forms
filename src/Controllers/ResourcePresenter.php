<?php namespace Tatter\Forms\Controllers;

use Tatter\Forms\Traits\ResourceTrait;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ResourcePresenter extends \CodeIgniter\RESTful\ResourcePresenter
{
	use ResourceTrait;

	protected $helpers = ['alerts'];

	protected $viewData = [];

	protected $viewOptions = [];

	protected $viewBase = '';


	/************* CRUD METHODS *************/
	
	public function new()
	{
		helper('form');
		return $this->request->isAJAX() ? $this->crudView('form') : $this->crudView('new');
	}
	
	public function create()
	{
        $id = $this->_create($this->request->getPost());
        if($id) {
            if($this->request->isAJAX()) {
                $resp = $this->ajaxSuccess($this->msg("create"), ['id' => $id]);
            } else {
                $this->alert('success', $this->msg("create"));
                //$resp = redirect()->to($this->names);
                $resp = $this->redirectTo("index");
            }
        } else {
            $resp = $this->actionFailed('create');
        }
        return $resp;
	}

	public function index()
	{
	    $items = $this->_find($this->request->getGet());
	    $this->viewData[$this->names] = $items;
		return $this->crudView('index');
	}
	
	public function show($id = null)
	{
	    $obj = $this->getEntity($id);
        return $this->entityView($obj, 'show');
	}
	
	public function edit($id = null)
	{
	    $obj = $this->getEntity($id);
	    $view = $this->request->isAJAX() ? 'form' : 'edit';
	    return $this->entityView($obj, $view);
	}

	public function update($id = null)
	{
        $object = $this->getEntity($id);
        if($object) {
            if($this->_update($id, $this->request->getPost())) {
                if($this->request->isAJAX()) {
                    $resp = $this->ajaxSuccess($this->msg('update'), ['id' => $id]);
                } else {
                    $this->alert('success', $this->msg("update"));
                    //$resp = redirect()->to("{$this->names}/{$id}");
                    $resp = $this->redirectTo("edit", $id);
                }
            } else {
                $resp = $this->actionFailed('update');
            }
            return $resp;
        }
        return $this->entityNotFound();
	}
	
	public function remove($id = null)
	{
        $obj = $this->getEntity($id);
        $view = $this->request->isAJAX() ? 'confirm' : 'remove';
        return $this->entityView($obj, $view);
	}
	
	public function delete($id = null)
	{
        $object = $this->getEntity($id);
        if($object) {
            if($this->_delete($id)) {
                if($this->request->isAJAX()) {
                    $resp = $this->ajaxSuccess($this->msg('delete'), ['id' => $id]);
                } else {
                    $this->alert('success', $this->msg("delete"));
                    //$resp = redirect()->to("/{$this->names}/");
                    $resp = $this->redirectTo("index");
                }
            } else {
                $resp = $this->actionFailed('delete');
            }
            return $resp;
        }
        return $this->entityNotFound();
	}
	
	/************* SUPPORT METHODS *************/

    protected function msg($action)
    {
        return lang("Forms.{$action}", [$this->name]);
    }

    protected function errMsg($action)
    {
        return lang("Forms.{$action}Failed", [$this->name]);
    }

    protected function ajaxSuccess(string $message, array $data)
    {
        return $this->ajaxResponse(true, $message, $data);
    }

    protected function ajaxFail(string $message, array $errors=[])
    {
        return $this->ajaxResponse(false, $message, $errors);
    }

    protected function ajaxResponse($success, string $message, array $data = null)
    {
        $response = [
            'success' => $success ? true : false,
            'message' => $message
        ];
        if(!empty($data)) {
            $key = $success ? 'data' : 'errors';
            $response[$key] = $data;
        }
        return $this->response->setJSON($response);
    }

    protected function crudView(string $action)
    {
        $base = rtrim($this->viewBase, DIRECTORY_SEPARATOR);
        if(!empty($base)) {
            $base .= DIRECTORY_SEPARATOR;
        }
        $template = $base . $this->names . DIRECTORY_SEPARATOR . $action;
        helper('form');
        return view($template, $this->viewData, $this->viewOptions);
    }

    protected function entityView($obj, $view)
    {
        if(!$obj) {
            return $this->entityNotFound();
        }
        $this->viewData[$this->name] = $obj;
        return $this->crudView($view);
    }

    protected function getEntity($id)
    {
        if($id) {
            $object = $this->_read($id);
            if (!empty($object)) {
                return $object;
            }
        }
        return false;
    }

	protected function entityNotFound()
    {
        $error = $this->msg('notFound');
        if($this->request->isAJAX()) {
            return $this->response->setStatusCode(404, $error);
        }
        $this->alert('danger', $error);
        return redirect()->back()->withInput()->with('errors', [$error]);
    }

	protected function actionFailed(string $action)
	{
	    $modelErrors = $this->model->errors();
	    if($this->request->isAJAX()) {
	        return $this->ajaxFail($this->errMsg($action), $modelErrors);
        }
        $errors = $modelErrors ?? [$this->errMsg($action)];
		foreach ($errors as $error)
		{
			$this->alert('warning', $error);
		}
		return redirect()->back()->withInput()->with('errors', $errors);
	}
	
	protected function alert($status, $message)
	{
		if ($alerts = service('alerts'))
		{
			$alerts->add($status, $message);
		}
	}

	private function redirectTo($crudAction, ...$params) {
        $method = get_class($this) .  "::" . $crudAction;
        $route = route_to($method, ...$params);
        return redirect()->to($route);
    }
}
