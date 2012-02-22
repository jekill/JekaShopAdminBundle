<?php

namespace Jeka\ShopAdminBundle\Command;

use \Application\Vespolina\ProductBundle\Document\Product;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ShuffleProductsCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this->setName("shop:products:shuffle");
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var $pm \Jeka\ShopBundle\Document\ProductManager */
        $pm = $this->getContainer()->get('vespolina.product_manager');
        $products = $pm->createFindAllQuery()
            ->field('type')->equals(Product::PHYSICAL)
            ->getQuery()
            ->execute();

        $count = $products->count();
        foreach ($products as $product) {
            $product->setRandom(rand(0,$count));
            $pm->updateProduct($product,false);
        }
        unset($product);

        $pm->flush();



    }
}