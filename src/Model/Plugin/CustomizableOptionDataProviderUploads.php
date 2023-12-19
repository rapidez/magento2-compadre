<?php

declare(strict_types=1);

namespace Rapidez\Compadre\Model\Plugin;

use Magento\Framework\Api\ImageContent;
use Magento\Catalog\Model\Webapi\Product\Option\Type\File\Processor as FileProcessor;

class CustomizableOptionDataProviderUploads {

    public function __construct(protected FileProcessor $fileProcessor)
    {
    }

    public function afterExecute($subject, $result)
    {
        $result['options'] = array_map($this->processFileUpload(...), $result['options']);

        return $result;
    }

    public function processFileUpload($value) {
        if (!($decodedValue = @json_decode($value, true))) {
            return $value;
        }

        if (array_key_exists('file_info', $decodedValue)) {
            $decodedValue = $decodedValue['file_info'];
        }

        if(!array_key_exists('base64_encoded_data', $decodedValue)) {
            return $value;
        }

        $response = $this->fileProcessor->processFileContent(new ImageContent($decodedValue));
        return $response;
    }
}
