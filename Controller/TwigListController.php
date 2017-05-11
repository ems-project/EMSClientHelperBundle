<?php

namespace EMS\ClientHelperBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

class TwigListController extends Controller
{   

    /**
     * @Route("/templates", name="templates")
     */
    public function pageAction(Request $request) {
    	
    	$templates = $this->container->getParameter('ems_client_helper.twig_list.templates');
    	
    	$twigs = array();

    	foreach ($templates as $key =>$template) {
    		$finder = new Finder();
    		$finder->files()->name('*.twig')->in($this->get('kernel')->locateResource('@' . $template['resource']) . $template['base_path']);
    		
    		foreach ($finder as $file) {
    			/** @var File $file */
    			$search  = array('\\', '.html.twig');
    			$replace = array('/', '');
    			$subject = $file->getRelativePathname();
    			$twigs[$template['resource']][$template['base_path']][$file->getBasename()] = str_replace($search, $replace, $subject);
    		}
    	}
    	
    	if($this->container->getParameter('ems_client_helper.twig_list.app_enabled')){
    		$base_paths = $this->container->getParameter('ems_client_helper.twig_list.app_base_path');
    		
    		foreach ($base_paths as $key =>$base_path) {
    			$finder = new Finder();
    			$finder->files()->name('*.twig')->in($this->get('kernel')->getRootDir() .'/' . $base_path);
    		
    			foreach ($finder as $file) {
    				/** @var File $file */
    				$search  = array('\\', '.html.twig');
    				$replace = array('/', '');
    				$subject = $file->getRelativePathname();
    				$twigs['app'][$base_path][$file->getBasename()] = str_replace($search, $replace, $subject);
    			}
    		}
    		 
    	}
    	
    	return $this->render('EMSClientHelperBundle:Default:templates.html.twig',[
    			'templates' => $twigs
    	]);
    }
    
    /**
     * @Route("/templates/{template_path}", name="template", requirements={"template_path"=".+"})
     */
    public function templateAction($template_path, Request $request) {
    
    	$resource = $request->query->get('resource');
   		$base_path = $request->query->get('base_path');
   		
   		$pos = strpos($base_path, '/views');
   		
   		$folder = '';
   		if(strlen($base_path) > $pos + 7) {
   			$folder = substr($base_path, $pos + 7);
   		}
   		
   		if ($resource === 'app') {
   			$path = $resource . '/' . $base_path;
   			$render = $folder . '/' .  $template_path . '.html.twig';
   		} else {
   			$path= $this->get('kernel')->locateResource('@' . $resource) . $base_path;
   			$render = $resource . ':' . $folder . ':' . $template_path . '.html.twig';
   		}
   		
   		return $this->render($render ,[
   				'template' => $template_path,
   				'path' => $path . '/' . $template_path . '.html.twig',
   		]);
    }
}
