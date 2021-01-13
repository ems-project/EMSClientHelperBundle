<?php

declare(strict_types=1);

namespace EMS\ClientHelperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class TwigListController extends AbstractController
{
    private KernelInterface $kernel;
    /** @var array<int, array{path: string, namespace: string}> */
    private array $locations;

    /**
     * @param array<int, array{path: string, namespace: string}> $templates
     */
    public function __construct(KernelInterface $kernel, array $templates = [])
    {
        $this->kernel = $kernel;
        $this->locations = $templates;
    }

    public function list(): Response
    {
        $list = [];

        foreach ($this->locations as $location) {
            $path = $location['path'];

            if ('@' === $path[0]) {
                $in = $this->kernel->locateResource($path);
            } else {
                $in = $this->kernel->getRootDir().'/../'.$path;
            }

            $files = Finder::create()->files()->name('*.twig')->in($in);

            foreach ($files as $file) {
                /** @var SplFileInfo $file */
                $relativePathname = $file->getRelativePathname();

                if ($location['namespace']) {
                    $relativePathname = $location['namespace'].DIRECTORY_SEPARATOR.$relativePathname;
                }

                $list[$path][] = $relativePathname;
            }
        }

        return $this->render('@EMSClientHelper/TwigList/Default/templates.html.twig', [
            'templates' => $list,
        ]);
    }

    public function template(Request $request): Response
    {
        $view = $request->get('view');

        return $this->render($view, [
            'templates' => [],
            'template' => $view,
            'path' => $view,
        ]);
    }
}
