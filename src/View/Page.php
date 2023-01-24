<?php

declare(strict_types=1);

namespace Conia\Core\View;

use Conia\Chuck\Exception\HttpBadRequest;
use Conia\Chuck\Exception\HttpNotFound;
use Conia\Chuck\Factory;
use Conia\Chuck\Registry;
use Conia\Chuck\Renderer\Render;
use Conia\Chuck\Request;
use Conia\Chuck\Response;
use Conia\Core\Config;
use Conia\Core\Pages;
use Conia\Core\Type;
use Conia\Quma\Database;
use Throwable;

class Page
{
    public function __construct(
        protected readonly Factory $factory,
        protected readonly Registry $registry,
        protected readonly Pages $pages,
    ) {
    }

    public function catchall(Request $request, Config $config): Response
    {
        $data = $this->pages->byUrl($request->uri()->getPath());

        if (!$data) {
            throw new HttpNotFound();
        }


        $classname = $data['classname'];

        if (is_subclass_of($classname, Type::class)) {
            $pages = new Pages($this->registry->get(Database::class));
            $page = new $classname($request, $config, $pages, $data);

            // Create a JSON response if the URL ends with .json
            if (strtolower($extension ?? '') === 'json') {
                return Response::fromFactory($this->factory)->json($page->json());
            }

            // try {
            // Render the template
            $render = new Render('template', $page::template());

            return $render->response($this->registry, [
                'page' => $page,
                'pages' => $pages,
            ]);
            // } catch (Throwable) {
            //     throw new HttpBadRequest();
            // }
        }

        throw new HttpBadRequest();
    }
}
