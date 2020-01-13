<?php
namespace Tatter\Forms\Traits;

use Tatter\Forms\Exceptions\FormsException;

trait ResourceTrait
{
    // Convenience singular & plural names for entity variables
    protected $name;
    protected $names;


    // Extend the framework setModel to require a valid model and set names
    public function setModel($which = null)
    {
        parent::setModel($which);

        // Ensure we received a valid model
        if (!$this->model instanceof \CodeIgniter\Model)
        {
            throw FormsException::forMissingModel(get_class($this));
        }

        // Set singular and plural names
        helper('inflector');

        // Check for overriding model property
        if (! empty($this->model->name))
        {
            $name = $this->model->name;
        }

        // Use the model class name
        // e.g. \App\Models\PhotoModel = photo(s)
        else
        {
            $name = strtolower($this->modelName);

            // Remove namespaces
            if ($offset = strrpos($name, '\\'))
            {
                $name = substr($name, $offset + 1);
            }

            // Remove the word "model"
            $name = str_replace('model', '', $name);
        }

        $this->name  = singular($name);
        $this->names = plural($name);
    }


    protected function _create(array $data): int
    {
        return $this->model->insert($data);
    }

    protected function _read($id): ?object
    {
        return $this->model->find($id);
    }

    protected function _update($id, array $data): bool
    {
        return $this->model->update($id, $data);
    }

    protected function _delete($id): bool
    {
        $res = $this->model->delete($id);
        if(is_bool($res)) {
            return $res;
        }
        $res = $this->_read($id);
        return empty($res);
    }

    protected function _find(array $data): array
    {
        return $this->model->findAll();
    }
}