<?php

namespace Jeka\ShopAdminBundle\Controller;

use \Jeka\CategoryBundle\Form\CategoryForm;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class CategoriesController extends Controller{

    /**
     * @Route("/categories", name="shop_admin_categories")
     * @Template
     */
    public function indexAction()
    {
        /** @var $cm \Jeka\CategoryBundle\Document\CategoryManager */
        $cm = $this->get('jeka.category_manager');
        $root=$cm->getRoot();
        return array(
            'root'=>$root,
            'category_manager'=>$cm,
            'tree'=>$cm->getTreeList()
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
        $category=null;
        /** @var $cm \Jeka\CategoryBundle\Document\CategoryManager */
        $cm = $this->get('jeka.category_manager');
        if (is_object($id)){
            $category = $id;
            $id = $category->getId();

        }
        else{
            $category = $cm->findCategoryById($id);
        }

        if (!$category)
        {
            throw $this->createNotFoundException();
        }

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->createForm(new CategoryForm(),$category);

        /** @var $req \Symfony\Component\HttpFoundation\Request */
        $req = $this->getRequest();
        if ($req->getMethod()=='POST')
        {
            $form->bindRequest($req);
            if ($form->isValid())
            {
                $cm->updateCategory($category);
                $this->get('session')->setFlash('success','The object was saved successfully');
                return $this->redirect($this->generateUrl('shop_admin_categories_edit',array('id'=>$category->getId())));
            }
        }

        return $this->render('JekaShopAdminBundle:Categories:edit.html.twig',array(
            'category'=>$category,
            'form'=>$form->createView()
        ));
    }


}