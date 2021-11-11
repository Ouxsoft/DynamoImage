# DynamoImage

A PHP library for resizing and caching images based on request.

Example showing Symfony integration:
```
<?php

namespace App\Controller;

use Ouxsoft\DynamoImage\DynamoImage;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImageController
{
    /**
     * @Route("/assets/images/{url}", priority=10, name="imageRoute", requirements={"url"=".+"})
     * @param $url
     * @return Response
     * @throws Exception
     */
    public function indexAction($url) : Response
    {

        $image = new DynamoImage();
        $image->setCacheDir(__DIR__ . '/../../var/cache/images/');
        $image->setAssetDir(__DIR__ . '/../../public/assets/images/');
        $image->setURL($url);

        // send cache file if exists
        if ($image->isCached()) {
            $response = new BinaryFileResponse($image->getCacheFilepath());
            $response->headers->set('Content-Length', $image->getFileSize());
            $response->headers->set('Content-Type', $image->getContentType());
            return $response;
        }

        // try to generate cache and send
        try {
            $image->resize();
            $image->saveCache();

            $response = new Response();
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-Type', $image->getContentType());
            $response->sendHeaders();
            $response->setContent($image->getContent());
            return $response;

        } catch (Exception $e) {
            return new Response('Resizing image failed');
        }
    }
}
```
