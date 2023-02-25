<?php

declare(strict_types=1);

namespace Conia\Core\View;

use Conia\Chuck\Exception\HttpBadRequest;
use Conia\Chuck\Exception\HttpNotFound;
use Conia\Chuck\Factory;
use Conia\Chuck\Registry;
use Conia\Chuck\Response;
use Conia\Core\Context;
use Conia\Core\Finder;
use Conia\Core\Node;

class Page
{
    public function __construct(
        protected readonly Factory $factory,
        protected readonly Registry $registry,
    ) {
    }

    public function catchall(Context $context, Finder $find): Response
    {
        $path = $context->request->uri()->getPath();
        $data = $find->page->byPath($path);

        if (!$data) {
            $this->redirectIfExists($context, $path);

            throw new HttpNotFound();
        }

        $class = $data['classname'];

        if (is_subclass_of($class, Node::class)) {
            $page = new $class($context, $find, $data);

            return $page->response();
        }

        throw new HttpBadRequest();
    }

    protected function redirectIfExists(Context $context, string $path): void
    {
        $db = $context->db;
        $path = $db->paths->byPath(['path' => $path])->one();

        if ($path && !is_null($path['inactive'])) {
            $paths = $db->paths->activeByNode(['node' => $path['node']])->all();

            $pathsByLocale = array_combine(
                array_map(fn ($p) => $p['locale'], $paths),
                array_map(fn ($p) => $p['path'], $paths),
            );

            $locale = $context->request->get('locale');

            while ($locale) {
                $path = $pathsByLocale[$locale->id] ?? null;

                if ($path) {
                    header('Location: ' . $path, true, 301);
                    exit;
                }

                $locale = $locale->fallback();
            }
        }
    }
}
