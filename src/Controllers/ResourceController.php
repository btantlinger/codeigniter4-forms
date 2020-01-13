<?php namespace Tatter\Forms\Controllers;


use Tatter\Forms\Traits\ResourceTrait;
use CodeIgniter\Entity;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class ResourceController extends \CodeIgniter\RESTful\ResourceController
{
    use ResourceTrait;

    /************* CRUD METHODS *************/


    public function new() {
        return $this->respond([]);
    }

	public function create()
	{
		$id = $this->_create($this->request->getRawInput());
		if (!$id) {
		    return $this->actionFailed('create', 422);
		}
		return $this->respondCreated(['id' => $id], lang('Forms.created', [$this->name]));
	}

	public function index()
	{
		$data = [];
        $items = $this->_find($this->request->getGet());
        if($items) {
            foreach ($items as $item) {
                $data[] = ($item instanceof Entity) ? $item->toArray() : $item;
            }
        }
	    return $this->respond($data);
	}
	
	public function show($id = null)
	{
	    $obj = $this->_read($id);
	    if(!empty($obj)) {
	        if($obj instanceof Entity) {
	            $obj = $obj->toArray();
            } else if (is_object($obj)) {
	            $obj = (array)$obj;
            }
            return $this->respond($obj);
        }
        return $this->entityNotFound();
    }

    public function edit($id = null)
    {
	    return $this->show($id);
    }
	
	public function update($id = null)
	{
	    if($this->entityExists($id)) {
            if($this->_update($id, $this->request->getRawInput())) {
                return $this->respond(['id' => $id], 200, lang('Forms.updated', [$this->name]));
            }
            return $this->actionFailed('update', 422);
        }
        return $this->failNotFound('Not Found', null, lang('Forms.notFound', [$this->name]));
	}

	public function delete($id = null)
	{
        if($this->entityExists($id)) {
            if($this->_delete($id)) {
                return $this->respondDeleted(['id' => $id], lang('Forms.deleted', [$this->name]));
            }
            return $this->actionFailed('delete');
        }
        return $this->entityNotFound();
	}


	/************* SUPPORT METHODS *************/
    protected function entityExists($id) {
        return !empty($this->_read($id));
    }

	protected function actionFailed(string $action, int $status = 400)
	{
		$message = lang("Forms.{$action}Failed", [$this->name]);
	    //$errors = $this->model->errors() ?? [lang("Forms.{$action}Failed", [$this->name])];

		$response = [
			'status'   => $status,
			'error'    => "{$action} Failed",
			'messages' => $this->model->errors(),
		];
		
		return $this->respond($response, $status, $message);
	}

	protected function entityNotFound() {
        return $this->failNotFound('Not Found', null, lang('Forms.notFound', [$this->name]));
    }
}
