<?php
namespace Jeka\ShopAdminBundle\Controller;


use \Symfony\Component\HttpFoundation\Response;
use \Application\Vespolina\ProductBundle\Form\Type\ProductFormExtendedType;
use \Application\Vespolina\ProductBundle\Document\Product;
use \Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class ProductsController extends Controller
{

    /**
     * List of products
     * @Route("/products/", name="shop_admin_products")
     * @Template
     */
    public function indexAction()
    {
        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->get('vespolina.product_manager');

        /** @var $cm \Jeka\CategoryBundle\Document\CategoryManager */
        $cm = $this->get('jeka.category_manager');
        $categories_tree = $cm->getTreeList();

        $queryBuilder = $pm->createFindAllQuery();

        $session = $this->getRequest()->getSession();

        $filter_data = $session->get('products_filter', array());


        //$form = $this->createForm('q',null,array());

        $filter_form = $this->_createFilterForm($filter_data);

        if ($this->getRequest()->get('filter')) {
            $filter_form->bindRequest($this->getRequest());
            if ($filter_form->isValid()) {
                $filter_data = $filter_form->getData();
                $session->set('products_filter', $filter_data);
            }

        }

        if (isset($filter_data['category']) && trim($filter_data['category']) != '') {
            $queryBuilder->field('categories.id')->equals($filter_data['category']);
        }

        if (!isset($filter_data['disabled']) || $filter_data['disabled'] == false) {
            $queryBuilder->field('disabled')->equals(false);
        }


        if (isset($filter_data['name'])) {

            $queryBuilder->field('name')->equals(array('$regex' => "{$filter_data['name']}"));
        }

        $req = $this->getRequest();
        $curr_page = $req->get('page', 1);
        if ($curr_page < 1) $curr_page = 1;

        $adapter = new \Pagerfanta\Adapter\DoctrineODMMongoDBAdapter($queryBuilder);
        $pager = new \Pagerfanta\Pagerfanta($adapter);
        $pager->setMaxPerPage(30);
        $pager->setCurrentPage($curr_page);


        return array(
            'pager' => $pager,
            'categories_tree' => $categories_tree,
            'filter_form' => $filter_form->createView()
        );


    }

    /**
     * @param \Application\Vespolina\ProductBundle\Document\Product $product
     * @Template
     */
    public function showImagesAction(Product $product)
    {
        return array(
            'product' => $product
        );
    }

    /**
     * @Route("/images-sort", name="shop_admin_images_sort")
     */
    public function imagesSortAction()
    {
        $images = $this->getRequest()->get('image');
        $images = array_reverse($images);
        /** @var $image_manager \Jeka\ImageBundle\Document\ImageManager */
        $image_manager = $this->get('jeka.image_manager');
        /** @var $d_manager \Doctrine\ODM\MongoDB\DocumentManager */
        $d_manager = $this->get('doctrine.odm.mongodb.document_manager');
        foreach ($images as $index => $id)
        {
            $image = $image_manager->findImageById($id);
            $image->setPos($index);
            $d_manager->persist($image);
        }
        $d_manager->flush();
        return new Response('OK', 200, array('Content-type' => 'text/plain'));
    }

    /**
     * @Route("/products/new", name="shop_admin_product_new")
     */
    public function newAction()
    {
        $product = $this->get('vespolina.product_manager')->createProduct();
        return $this->editAction($product);
    }

    /**
     * @param $id
     * @Route("/products/{id}/edit", name="shop_admin_product_edit")
     */
    public function editAction($id)
    {
        /** @var $product Product */
        $product = null;
        /** @var $product_manager \Jeka\ShopBundle\Document\ProductManager */
        $product_manager = $this->get('vespolina.product_manager');


        if (is_object($id)) {
            $product = $id;
            $id = $product->getId();
        }
        else {
            /** @var $product Product */
            $product = $product_manager->findProductById($id);
            if (!$product) {
                throw $this->createNotFoundException();
            }
        }

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->createForm(new ProductFormExtendedType(), $product);

        /** @var $req \Symfony\Component\HttpFoundation\Request */
        $req = $this->getRequest();

        if ($req->getMethod() == 'POST') {
            $form->bindRequest($req);
            if ($form->isValid()) {
                $product_manager->updateProduct($product);

                /** @var $uploaded_image \Symfony\Component\HttpFoundation\File\UploadedFile */
                if ($uploaded_image = $product->getUploadedImage()) {
                    /** @var $im \Jeka\ImageBundle\Document\ImageManager */
                    $im = $this->get('jeka.image_manager');
                    $image = $im->createImageFromFile($uploaded_image->getPathname());
                    $image->setPos(-1);
                    $product->addImages($image);
                    $product_manager->updateProduct($product);
                }

                /** @var $session \Symfony\Component\HttpFoundation\Session */
                $session = $this->get('session');
                $session->setFlash('success', 'The product is saved successfully');
                return $this->redirect(
                    $this->generateUrl('shop_admin_product_edit', array('id' => $product->getId()))
                );
            }
        }

        return $this->render('JekaShopAdminBundle:Products:edit.html.twig', array(
            'form' => $form->createView(),
            'product' => $product,
            'filter_form' => $this->_createFilterForm()->createView()
        ));
    }

    /**
     * @Route("/products/{product_id}/imageRemove", name="shop_admin_product_image_remove")
     */
    function imageRemoveAction($product_id)
    {
        /** @var $im \Jeka\ImageBundle\Document\ImageManager */
        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->get('vespolina.product_manager');
        $product = $pm->findProductById($product_id);
        if (!$product) {
            throw $this->createNotFoundException();
        }
        $im = $this->get('jeka.image_manager');
        $image = $im->findImageById($this->getRequest()->get('image_id'));
        if (!$image) {
            throw $this->createNotFoundException();
        }

        $product->removeImage($image);
        $im->remove($image);
        if ($this->getRequest()->isXmlHttpRequest()) {
            return new Response('success');
        }

        return $this->redirect($this->generateUrl('shop_admin_product_edit', array(
            'id' => $product->id
        )) . "#images");

    }

    /**
     * @Route("/products/{id}/remove", name="shop_admin_remove")
     */
    function removeAction($id)
    {
        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->get('vespolina.product_manager');
        $product = $pm->findProductById($id);

        if (!$product) {
            throw $this->createNotFoundException();
        }

        $pm->removeProduct($product);
        $this->getRequest()->getSession()->setFlash('success', 'The product was removed');
        return $this->redirect($this->generateUrl('shop_admin_products'));
    }

    /**
     * @Route("/products/feature/{id}/remove", name="shop_admin_remove_feature")
     */
    function removeFeatureAction($id)
    {
        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->get('vespolina.product_manager');

        $product = $pm->findProductByFeatureId($id);
        if (!$product) {
            throw $this->createNotFoundException();
        }
        $pm->removeFeature($id);
        if ($this->getRequest()->isXmlHttpRequest()) {
            return new Response('success', 'text/plain');
        }
        //throw new \Exception();
        return $this->redirect($this->generateUrl('shop_admin_product_edit', array('id' => $product->getId())) . '#options');

    }

    /**
     * @Route("/products/feature/{product_id}/{type}/removeAll", name="shop_admin_remove_feature_all")
     */
    function removeFeatureAllAction($product_id, $type)
    {
        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->get('vespolina.product_manager');
        $pm->removeFeatureAll($type);

        $product = $pm->findProductById($product_id);
        if (!$product) {
            throw $this->createNotFoundException();
        }

        $pm->removeFeatureAll($type);

        if ($this->getRequest()->isXmlHttpRequest()) {
            return new Response('success', 'text/plain');
        }
        //throw new \Exception();
        return $this->redirect($this->generateUrl('shop_admin_product_edit', array('id' => $product->getId())) . '#options');
    }


    /**
     * @Route("/products/disableToggle",name="shop_admin_product_toggle")
     * @param $id
     * @Template
     */
    function disableToggleAction()
    {
        $pm = $this->_getProductManager();
        $product = $pm->findProductById($this->getRequest()->get('id'));

        if (!$product) throw $this->createNotFoundException();

        //$pm->disableProduct($product,true);
        $pm->disableToggleProduct($product);
        if ($this->getRequest()->isXmlHttpRequest()) {
            //return new Response('success');
            return array(
                'product' => $product
            );
        }

        $this->getRequest()->getSession()->setFlash('success', sprintf('The product "%s" was "%s"',
            $product->getName(),
            $product->getDisabled() ? 'disabled' : 'enabled'));

        return $this->redirect($this->generateUrl('shop_admin_products'));

    }


    /**
     * @return \Jeka\ShopBundle\Document\ProductManager
     */
    private function _getProductManager()
    {
        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->get('vespolina.product_manager');
        return $pm;
    }


    private function _createFilterForm($filter_data = array())
    {
        $cm = $this->get('jeka.category_manager');
        $categories_tree = $cm->getTreeList();
        $category_choices = array();
        foreach ($categories_tree as $c)
        {
            //$category_choices= array($c->getId()=>$c->getName());
            $id = $c->getSlug() == 'root' ? '' : $c->getId();
            $category_choices[$id] = $c->getName();
        }
        unset($c);
        return $this->get('form.factory')->createNamedBuilder('form', 'filter', $filter_data, array('csrf_protection' => false))
            ->add('category', 'choice', array('choices' => $category_choices))
            ->add('disabled', 'checkbox', array('label' => 'Show disabled', 'required' => false))
            ->add('name', 'text', array('required' => false))
            ->getForm();
    }
}
