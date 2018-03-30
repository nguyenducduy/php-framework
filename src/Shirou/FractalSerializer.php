<?php
namespace Shirou;

use League\Fractal\Serializer\ArraySerializer;

class FractalSerializer extends ArraySerializer
{
    public function collection($resourceKey, array $data)
    {
        if ($resourceKey == 'parent') {
            return $data;
        }

        return array($resourceKey ?: 'data' => $data);
    }

    public function item($resourceKey, array $data)
    {
        if ($resourceKey == 'parent') {
            return $data;
        }
        return array($resourceKey ?: 'data' => $data);
    }
}
