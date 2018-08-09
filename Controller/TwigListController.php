<?php

namespace EMS\ClientHelperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

class TwigListController extends AbstractController
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var array
     */
    private $locations;

    /**
     * @param KernelInterface $kernel
     * @param array           $templates
     */
    public function __construct(KernelInterface $kernel, array $templates = [])
    {
        $this->kernel = $kernel;
        $this->locations = $templates;
    }

    /**
     * @return Response
     */
    public function listAction()
    {
        $list = [];

        foreach ($this->locations as $location) {
            $path = $location['path'];

           if ('@' === $path[0]) {
                $in = $this->kernel->locateResource($path);
            } else {
                $in = $this->kernel->getRootDir() . '/../' . $path;
            }

            $files = Finder::create()->files()->name('*.twig')->in($in);

            foreach ($files as $file) {
                /** @var SplFileInfo $file */

                $relativePathname = $file->getRelativePathname();

                if ($location['namespace']) {
                    $relativePathname = $location['namespace'] . DIRECTORY_SEPARATOR  . $relativePathname;
                }

                $list[$path][] = $relativePathname;
            }
        }

        return $this->render('@EMSBackendBridge/TwigList/Default/templates.html.twig', [
            'templates' => $list
        ]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function templateAction(Request $request)
    {
        $view = $request->get('view');

        return $this->render($view, [
            'templates' => [],
            'template'  => $view,
            'path'      => $view
        ]);
    }
}
