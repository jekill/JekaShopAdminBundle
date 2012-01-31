<?php

namespace Jeka\ShopAdminBundle\Controller;

use \Symfony\Component\BrowserKit\Response;
use \Jeka\CategoryBundle\Document\Category;
use \Jeka\CategoryBundle\Form\CategoryForm;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class CategoriesController extends Controller
{

    /**
     * @Route("/categories", name="shop_admin_categories")
     * @Template
     */
    public function indexAction()
    {
        /** @var $cm \Jeka\CategoryBundle\Document\CategoryManager */
        $cm = $this->get('jeka.category_manager');
        $root = $cm->getRoot();
        return array(
            'root' => $root,
            'category_manager' => $cm,
            'tree' => $cm->getTreeList()
        );
    }

    /**
     * @Route("/categories/new", name="shop_admin_categories_new")
     */
    public function newAction()
    {
        $category = $this->get('jeka.category_manager')->createCategory();

        return $this->editAction($category);
    }

    /**
     * @Route("/categories/{id}/edit", name="shop_admin_categories_edit")
     * @param $category
     */
    public function editAction($id)
    {
        $category = null;
        /** @var $cm \Jeka\CategoryBundle\Document\CategoryManager */
        $cm = $this->get('jeka.category_manager');
        if (is_object($id)) {
            $category = $id;
            $id = $category->getId();

        }
        else {
            $category = $cm->findCategoryById($id);
        }

        if (!$category) {
            throw $this->createNotFoundException();
        }

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->createForm(new CategoryForm(), $category);

        /** @var $req \Symfony\Component\HttpFoundation\Request */
        $req = $this->getRequest();
        if ($req->getMethod() == 'POST') {
            $form->bindRequest($req);
            if ($form->isValid()) {
                $cm->updateCategory($category);
                $this->get('session')->setFlash('success', 'The object was saved successfully');
                return $this->redirect($this->generateUrl('shop_admin_categories_edit', array('id' => $category->getId())));
            }
        }

        return $this->render('JekaShopAdminBundle:Categories:edit.html.twig', array(
            'category' => $category,
            'form' => $form->createView()
        ));
    }


    /**
     * @Route("/categories/{id}/position/{direction}", name="shop_admin_categories_position")
     */
    public function positionAction($id, $direction)
    {
        /** @var $req \Symfony\Component\HttpFoundation\Request */
        $req = $this->getRequest();

        $direction = $direction == 'up' ? $direction : 'down';
        /** @var $cm \Jeka\CategoryBundle\Document\CategoryManager */
        $cm = $this->get('jeka.category_manager');
        $category = $cm->findCategoryById($id);
        //$curr_cat = $cm->findCategoryById($id);
        if (!$category->getParent()) {
            throw new \Exception("Operation Not Available");
        }

        /** @var $neighbours \Doctrine\ODM\MongoDB\LoggableCursor */

        $neighbours_cursor = $cm->findChildren($category->getParent());

        //print_r(array_keys($neighbours_cursor->toArray()));exit;
        $neighbours=array();

        $curr_pos = 0;

        $index = 0;
        foreach ($neighbours_cursor as $neighbour)
        {
            $neighbour->setPosition($index);
            $neighbours[$index]=$neighbour;
            $cm->updateCategory($neighbour, false);

            if ($neighbour->getId() == $category->getId()) {
                $curr_pos = $index;
                //print $index;exit;
            }
            $index++;
        }

        if ((!$curr_pos&&$direction=='up') || ($curr_pos >= (count($neighbours) - 1))&&$direction=='down') {
            //return new Response('');
            throw new \Exception("Operation Not Available $curr_pos/".count($neighbours).'/'.$direction);
        }

        $next_pos = ($direction == 'up') ? $curr_pos - 1 : $curr_pos + 1;

        $category->setPosition($next_pos);
        $neighbours[$next_pos]->setPosition($curr_pos);

        $cm->flushManager();

        if (!$req->isXmlHttpRequest())
        {
            return $this->redirect($this->generateUrl( 'shop_admin_categories').'#cat_'.$category->getId());
        }
        return new Response('ok');

    }


    /**
     * @Route("/categories/{id}/remove", name="shop_admin_categories_remove")
     */
    public function removeAction($id)
    {
        /** @var $cm \Jeka\CategoryBundle\Document\CategoryManager */
        $cm = $this->get('jeka.category_manager');
        $cat = $cm->findCategoryById($id);
        if (!$cat){
            throw $this->createNotFoundException();
        }

        $cm->remove($cat);
        $this->get('session')->setFlash('success',sprintf('Category "%s" was removed', $cat->getName()));
        return $this->redirect($this->generateUrl('shop_admin_categories'));
    }


}