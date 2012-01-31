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
        $queryBuilder = $pm->createFindAllQuery();
        $req = $this->getRequest();
        $curr_page = $req->get('page', 1);
        if ($curr_page < 1) $curr_page = 1;

        $adapter = new \Pagerfanta\Adapter\DoctrineODMMongoDBAdapter($queryBuilder);
        $pager = new \Pagerfanta\Pagerfanta($adapter);
        $pager->setMaxPerPage(30);
        $pager->setCurrentPage($curr_page);

        return array(
            'pager' => $pager
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
        $product = new Product();
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

        }

        /** @var $form \Symfony\Component\Form\Form */
        $form = $this->createForm(new ProductFormExtendedType(), $product);

        /** @var $req \Symfony\Component\HttpFoundation\Request */
        $req = $this->getRequest();

        if ($req->getMethod() == 'POST') {
            $form->bindRequest($req);
            if ($form->isValid()) {
                $product_manager->updateProduct($product);

                //print_r($req->files);exit;
                /** @var $uploaded_image \Symfony\Component\HttpFoundation\File\UploadedFile */
                if ($uploaded_image = $product->getUploadedImage())
                {
                    //var_dump($uploaded_image->getPathname());exit;
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

        return $this->render('JekaShopAdminBundle:Products:edit.html.twig',array(
            'form' => $form->createView(),
            'product' => $product
        ));
    }
}
