<?php
namespace Shirou\Behavior\Model;

use Phalcon\Mvc\Model\Behavior;
use Phalcon\Mvc\Model\BehaviorInterface;
use Shirou\UserException;
use Shirou\Constants\ErrorCode;
use Core\Helper\Utils as Helper;

class Fileable extends Behavior implements BehaviorInterface
{
    protected $uploadPath = '';
    protected $fileField = '';
    protected $oldFile = '';
    protected $fileSystem = null;
    protected $allowedFormat = ['image/jpeg', 'image/png', 'image/gif'];
    protected $allowedMaximumSize = 0;
    protected $allowedMinimumSize = 0;
    protected $isOverwrite = false;
    protected $errorCode;

    public function notify($eventType, \Phalcon\Mvc\ModelInterface $model)
    {
        if (!is_string($eventType)) {
            throw new Exception("Invalid parameter type.");
        }

        if (!$this->mustTakeAction($eventType)) {
            return;
        }

        $options = $this->getOptions($eventType);
        $this->fileSystem = $model->getDI()->get('file');

        $this->setFileField($options, $model)
            ->setAllowedFormats($options)
            ->setUploadPath($options)
            ->setOverwrite($options)
            ->setAllowMaximumSize($options)
            ->setAllowMinimumSize($options);

        switch ($eventType) {
            case 'beforeCreate':
                $this->processUpload($model);
                break;
            case 'beforeDelete':
                $this->processDelete($options, $model);
                break;
            case 'beforeUpdate':
                $this->processUpdate($options, $model);
                break;
        }
    }

    protected function setFileField(array $options, \Phalcon\Mvc\ModelInterface $model)
    {
        if (!isset($options['field']) || !is_string($options['field'])) {
            throw new Exception("The option 'field' is required and it must be string.");
        }

        $this->fileField = $options['field'];
        $this->oldFile = $model->{$this->fileField};

        return $this;
    }

    protected function setUploadPath(array $options)
    {
        if (!isset($options['uploadPath']) || !is_string($options['uploadPath'])) {
            throw new Exception("The option 'uploadPath' is required and it must be string.");
        }

        $path = $options['uploadPath'] . Helper::getCurrentDateDirName();
        $this->uploadPath = $path;

        return $this;
    }

    protected function setAllowedFormats(array $options)
    {
        if (isset($options['allowedFormats']) && is_array($options['allowedFormats'])) {
            $this->allowedFormats = $options['allowedFormats'];
        }

        return $this;
    }

    protected function setAllowMaximumSize(array $options)
    {
        if (isset($options['allowedMaximumSize']) && is_numeric($options['allowedMaximumSize'])) {
            $this->allowedMaximumSize = $options['allowedMaximumSize'];
        }

        return $this;
    }

    protected function setAllowMinimumSize(array $options)
    {
        if (isset($options['allowedMinimumSize']) && is_numeric($options['allowedMinimumSize'])) {
            $this->allowedMinimumSize = $options['allowedMinimumSize'];
        }

        return $this;
    }

    protected function setOverwrite(array $options)
    {
        if (isset($options['isOverwrite'])) {
            $this->isOverwrite = $options['isOverwrite'];
        }

        return $this;
    }

    protected function processUpload($model = null)
    {
        $request = $model->getDI()->getRequest();

        if (true == $request->hasFiles(true)) {
            foreach ($request->getUploadedFiles() as $key => $file) {
                $key = $file->getKey();
                $type = $file->getType();

                if (!in_array($type, $this->allowedFormats)) {
                    throw new UserException(ErrorCode::FILE_UPLOAD_ERR_ALLOWED_FORMAT);
                }

                if (!$this->checkMaxsize($file, $this->allowedMaximumSize)) {
                    throw new UserException(ErrorCode::FILE_UPLOAD_ERR_MAX_SIZE);
                }

                if (!$this->checkMinsize($file, $this->allowedMinimumSize)) {
                    throw new UserException(ErrorCode::FILE_UPLOAD_ERR_MIN_SIZE);
                }

                // Find namepart and extension part
                $pos = strrpos($file->getName(), '.');
                if ($pos === false) {
                    $pos = strlen($file->getName());
                }

                $namePart = Helper::slug(substr($file->getName(), 0, $pos));

                // Check overwrite
                if (isset($this->isOverwrite) && $this->isOverwrite === true) {
                    $fileName = $namePart . '.' . $file->getExtension();
                } else {
                    $fileName = $namePart . '-' . time() . '.' . $file->getExtension();
                }

                $targetUploadPath = $this->uploadPath . $fileName;

                $result = $this->fileSystem->put($targetUploadPath, file_get_contents($file->getTempName()));

                if (!$result) {
                    throw new UserException(ErrorCode::FILE_UPLOAD_ERR);
                }

                // Set field path in model
                preg_match('/(\d{4})\/(.*)\/(\d{1,2})(.*)/', $targetUploadPath, $modelFilePath);
                $model->writeAttribute($this->fileField, $modelFilePath[0]);
            }
        }

        return $this;
    }

    protected function processDelete(array $options, $model = null)
    {
        if ($model->{$options['field']}) {
            $sourcePath = $options['uploadPath'] . $model->{$options['field']};
            $result = $this->fileSystem->delete(DS . $sourcePath);

            if (!$result) {
                throw new UserException(ErrorCode::FILE_DELETE_ERR);
            }

            return $this;
        }
    }
    protected function processUpdate(array $options, $model = null)
    {
        $request = $model->getDI()->getRequest();

        if (true == $request->hasFiles(true)) {
            $this->processDelete($options, $model);
            $this->processUpload($model);
        }

        return $this;
    }

    public function checkMinsize(\Phalcon\Http\Request\File $file, $value)
    {
        $pass = true;

        if ($value !== null && is_numeric($value)) {
            if ($file->getSize() < (int)$value) {
                $pass = false;
            }
        }

        return $pass;
    }

    public function checkMaxsize(\Phalcon\Http\Request\File $file, $value)
    {
        $pass = true;

        if ($value !== null && is_numeric($value)) {
            if ($file->getSize() > (int)$value) {
                $pass = false;
            }
        }

        return $pass;
    }
}
