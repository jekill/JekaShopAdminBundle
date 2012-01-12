<?php
namespace Jeka\ShopAdminBundle\Controller;


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
     * @Route("/products/new", name="admin_product_new")
     */
    public function newAction()
    {
        $product = new Product();
        return $this->editAction($product);
    }

    /**
     * @param $id
     * @Route("/products/{id}/edit", name="admin_product_edit")
     * @Template
     */
    public function editAction($id)
    {
        $product = null;
        if (is_object($id)){
            $product = $id;
        }
        else{
            $product = $this->get('vespolina.product_manager')->findProductById($id);
        }

        $form = $this->createForm(new ProductFormExtendedType(), $product);


        return array(
            'form'=>$form->createView(),
            'product'=>$product
        );
    }
}
