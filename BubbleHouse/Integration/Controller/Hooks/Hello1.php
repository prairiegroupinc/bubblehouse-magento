<?php

declare(strict_types=1);

namespace BubbleHouse\Integration\Controller\Hooks;

use BubbleHouse\Integration\Controller\Hooks\Abstract\Index;
use Magento\Framework\Controller\ResultInterface;

class Hello1 extends Index
{
    private const MAGIC = 'magic';

    /**
     * @inherit
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();
        $this->logger->critical($this->serializer->unserialize($this->request->getContent()));
        $this->logger->critical($this->serializer->unserialize($this->request->getHeaders()));

//        if ($this->validateToken()) {
            $content = $this->serializer->unserialize($this->request->getContent());
            $magic = $content[self::MAGIC] ?? null;

            if ($magic) {
                $hooks = $this->getHookNames(__DIR__);

                return $result->setData([
                    'magic' => $magic,
                    'hooks' => $hooks
                ]);
            }
//        }

        $result->setHttpResponseCode(404);
        $result->setData(['error' => 'Not found']);

        return $result;
    }

    /**
     * @param $directory
     * @return string[]
     */
    private function getHookNames($directory): array
    {
        $hooks = [];
        foreach (glob($directory . '/*.php') as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            if ($className !== 'Hello1') { // Exclude itself if needed
                $hooks[] = $className;
            }
        }
        return $hooks;
    }
}
